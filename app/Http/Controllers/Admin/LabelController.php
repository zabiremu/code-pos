<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Barcode stickers for products. The barcode is the product's SKU, so scanning it at the register adds the product. */
class LabelController extends Controller
{
    /**
     * Label stock the page can print on. Sizes in mm. Sheets (rows set) print
     * several per A4 page; rolls (rows null) print one label per page, which
     * is how thermal label printers expect it.
     */
    public const TEMPLATES = [
        'a4_21' => ['label' => 'A4 sheet, 21 per page (63.5 × 38.1 mm)', 'w' => 63.5, 'h' => 38.1, 'cols' => 3, 'rows' => 7, 'top' => 15.1, 'left' => 7.2, 'gapX' => 2.5, 'gapY' => 0],
        'a4_40' => ['label' => 'A4 sheet, 40 per page (52.5 × 29.7 mm)', 'w' => 52.5, 'h' => 29.7, 'cols' => 4, 'rows' => 10, 'top' => 0, 'left' => 0, 'gapX' => 0, 'gapY' => 0],
        'a4_65' => ['label' => 'A4 sheet, 65 per page (38.1 × 21.2 mm)', 'w' => 38.1, 'h' => 21.2, 'cols' => 5, 'rows' => 13, 'top' => 10.7, 'left' => 4.7, 'gapX' => 2.5, 'gapY' => 0],
        'roll_50x25' => ['label' => 'Thermal roll, 50 × 25 mm', 'w' => 50, 'h' => 25, 'cols' => 1, 'rows' => null, 'top' => 0, 'left' => 0, 'gapX' => 0, 'gapY' => 0],
        'roll_38x25' => ['label' => 'Thermal roll, 38 × 25 mm', 'w' => 38, 'h' => 25, 'cols' => 1, 'rows' => null, 'top' => 0, 'left' => 0, 'gapX' => 0, 'gapY' => 0],
    ];

    public function index(Request $request): View
    {
        $preselected = collect(explode(',', (string) $request->query('products')))->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id);

        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'base_price', 'regular_price', 'stock_quantity', 'track_stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'price' => (float) $p->base_price,
                'stock' => $p->track_stock ? max((int) floor((float) $p->stock_quantity), 0) : null,
            ]);

        return view('admin.labels.index', [
            'products' => $products,
            'preselected' => $preselected->values(),
            'templates' => collect(self::TEMPLATES)->map(fn ($t) => $t['label']),
        ]);
    }

    public function print(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', Rule::in(array_keys(self::TEMPLATES))],
            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'skip' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], ['items.required' => 'Add at least one product.']);

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

        $missing = $products->filter(fn ($p) => blank($p->sku));
        if ($missing->isNotEmpty()) {
            return back()->withInput()->withErrors(['items' => $missing->count().' of these products have no SKU/barcode yet: '.$missing->pluck('name')->take(5)->implode(', ').($missing->count() > 5 ? '...' : '').'. Use "Give barcodes to products without one" first.']);
        }

        $labels = [];
        foreach ($data['items'] as $row) {
            for ($i = 0; $i < (int) $row['qty']; $i++) {
                $labels[] = $products[$row['product_id']];
            }
        }
        if (count($labels) > 3000) {
            return back()->withInput()->withErrors(['items' => 'That is '.count($labels).' labels. Print up to 3,000 at a time.']);
        }

        return view('admin.labels.print', [
            'labels' => $labels,
            'template' => self::TEMPLATES[$data['template']],
            'skip' => (int) ($data['skip'] ?? 0), // blank spots at the start of a part-used sheet
            'show' => [
                'name' => $request->boolean('show_name'),
                'price' => $request->boolean('show_price'),
                'shop' => $request->boolean('show_shop'),
            ],
            'currency' => Branch::first()?->currency,
        ]);
    }

    /** Gives every product without a SKU a unique numeric one: 2 + id padded to 8 digits. */
    public function generateSkus(): RedirectResponse
    {
        $count = 0;
        Product::where(fn ($q) => $q->whereNull('sku')->orWhere('sku', ''))->orderBy('id')->each(function (Product $p) use (&$count) {
            $sku = '2'.str_pad((string) $p->id, 8, '0', STR_PAD_LEFT);
            while (Product::where('sku', $sku)->exists()) {
                $sku = '2'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
            }
            $p->update(['sku' => $sku]);
            $count++;
        });

        return back()->with('status', $count ? "Gave barcodes to {$count} ".str('product')->plural($count).'.' : 'Every product already has a barcode.');
    }
}
