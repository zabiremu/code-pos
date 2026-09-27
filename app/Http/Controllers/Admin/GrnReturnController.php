<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\GrnReturn;
use App\Services\PurchasingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Returning goods from a GRN back to the supplier. */
class GrnReturnController extends Controller
{
    public function __construct(private PurchasingService $purchasing) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $returns = GrnReturn::with(['grn:id,grn_no,supplier_id,warehouse_id', 'grn.supplier:id,name,company_name', 'grn.warehouse:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('return_no', 'like', "%{$search}%")
                ->orWhereHas('grn', fn ($g) => $g->where('grn_no', 'like', "%{$search}%"))))
            ->latest('return_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.grn-returns.index', compact('returns', 'search'));
    }

    /** Step 1 picks the GRN (?grn_id=…); step 2 enters quantities per GRN line. */
    public function create(Request $request): View
    {
        $grn = $request->filled('grn_id')
            ? Grn::with(['items.product.unit', 'supplier', 'warehouse'])->findOrFail($request->integer('grn_id'))
            : null;

        $recentGrns = $grn ? collect() : Grn::with('supplier:id,name,company_name')
            ->whereHas('items', fn ($q) => $q->whereColumn('returned_quantity', '<', 'quantity'))
            ->latest('received_date')->latest('id')->take(50)->get();

        return view('admin.grn-returns.form', [
            'return' => new GrnReturn(['return_date' => today()]),
            'grn' => $grn,
            'recentGrns' => $recentGrns,
            'current' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $grn = Grn::findOrFail($request->integer('grn_id'));
        $return = $this->purchasing->saveReturn($grn, $this->validated($request));

        return redirect()->route('admin.grn-returns.show', $return)->with('status', "{$return->return_no} saved. Stock taken out of {$grn->warehouse->name}.");
    }

    public function show(GrnReturn $grnReturn): View
    {
        $grnReturn->load(['grn.supplier', 'grn.warehouse', 'creator:id,name', 'items.product.unit']);

        return view('admin.grn-returns.show', ['return' => $grnReturn]);
    }

    public function edit(GrnReturn $grnReturn): View
    {
        $grnReturn->load('items');
        $grn = $grnReturn->grn()->with(['items.product.unit', 'supplier', 'warehouse'])->first();

        return view('admin.grn-returns.form', [
            'return' => $grnReturn,
            'grn' => $grn,
            'recentGrns' => collect(),
            // This return's own quantities, so "can return" adds them back in.
            'current' => $grnReturn->items->pluck('quantity', 'grn_item_id')->map(fn ($q) => (float) $q)->all(),
        ]);
    }

    public function update(Request $request, GrnReturn $grnReturn): RedirectResponse
    {
        $this->purchasing->saveReturn($grnReturn->grn, $this->validated($request), $grnReturn);

        return redirect()->route('admin.grn-returns.show', $grnReturn)->with('status', "{$grnReturn->return_no} updated.");
    }

    public function destroy(GrnReturn $grnReturn): RedirectResponse
    {
        $this->purchasing->deleteReturn($grnReturn);

        return redirect()->route('admin.grn-returns.index')->with('status', "{$grnReturn->return_no} deleted and the stock put back.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'grn_id' => ['required', 'exists:grns,id'],
            'return_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.grn_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ], [
            'return_date.before_or_equal' => 'The return date can\'t be in the future.',
        ]);
    }
}
