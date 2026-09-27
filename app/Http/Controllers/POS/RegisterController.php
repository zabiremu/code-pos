<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The full-screen register. The cart lives in the browser; "Pay" sends the
 * whole cart here once and it's saved atomically: sale + items + stock
 * deduction + bill + payment, then the sale is closed.
 */
class RegisterController extends Controller
{
    public const METHODS = ['cash' => 'Cash', 'card' => 'Card', 'mobile_wallet' => 'Mobile pay', 'other' => 'Other'];

    public function __construct(private BillingService $billing, private StockService $stock) {}

    public function index(): View
    {
        $branch = Branch::first();
        $products = Product::with('unit:id,short_name')
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'category_id', 'unit_id', 'base_price', 'regular_price', 'tax_rate', 'track_stock', 'stock_quantity'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category_id' => $p->category_id,
                'price' => (float) $p->base_price,
                'regular' => $p->regular_price !== null && (float) $p->regular_price > (float) $p->base_price ? (float) $p->regular_price : null,
                'tax' => $p->tax_rate !== null ? (float) $p->tax_rate : null,
                'track' => (bool) $p->track_stock,
                'stock' => (float) $p->stock_quantity,
                'unit' => $p->unit?->short_name,
            ]);

        $categoryIds = $products->pluck('category_id')->filter()->unique();

        return view('pos.register.index', [
            'products' => $products->values(),
            'categories' => Category::whereIn('id', $categoryIds)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'defaultTax' => (float) ($branch?->tax_rate ?? 0),
            'currency' => $branch?->currency,
            'methods' => self::METHODS,
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_available', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(array_keys(self::METHODS))],
            'tendered' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'The cart is empty.',
            'items.*.product_id.exists' => 'One of the products is no longer available. Refresh the register.',
        ]);

        [$bill, $change] = DB::transaction(function () use ($data, $request) {
            $sale = Sale::create([
                'cashier_id' => $request->user()->id,
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            // Merge duplicate lines so one product is one line on the receipt.
            $lines = collect($data['items'])->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
            $products = Product::whereIn('id', $lines->keys())->get()->keyBy('id');

            foreach ($lines as $productId => $qty) {
                $product = $products[$productId];
                $item = $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->base_price, // server price, never the browser's
                ]);
                $this->stock->deductForSaleItem($item);
            }

            $bill = $this->billing->createBill($sale, manualDiscount: (float) ($data['discount'] ?? 0));
            $total = (float) $bill->grand_total;

            $change = 0.0;
            if ($data['method'] === 'cash') {
                $tendered = (float) ($data['tendered'] ?? $total);
                if ($tendered + 0.005 < $total) {
                    throw ValidationException::withMessages(['tendered' => 'Cash received ('.number_format($tendered, 2).') is less than the total ('.number_format($total, 2).').']);
                }
                $change = round($tendered - $total, 2);
            }

            if ($total > 0) {
                $this->billing->recordPayment($bill, $data['method'], $total, $data['reference'] ?? null, $request->user()->id);
            } else {
                $bill->update(['status' => 'paid']);
            }

            $sale->update(['status' => 'closed']);

            return [$bill, $change];
        });

        return redirect()->route('pos.register.receipt', $bill)->with('change', $change);
    }

    public function receipt(Bill $bill): View
    {
        $bill->load(['sale.items.product.unit', 'sale.cashier:id,name', 'payments']);

        return view('pos.register.receipt', [
            'bill' => $bill,
            'change' => (float) session('change', 0),
            'currency' => Branch::first()?->currency,
            'shop' => [
                'address' => Setting::get('shop_address'),
                'phone' => Setting::get('shop_phone'),
                'email' => Setting::get('shop_email'),
                'footer' => Setting::get('receipt_footer'),
            ],
            'methods' => self::METHODS,
        ]);
    }
}
