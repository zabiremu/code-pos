<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Floor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(): View
    {
        $tables = DiningTable::with('floor')->orderBy('floor_id')->get();
        $floors = Floor::orderBy('name')->get();

        return view('admin.tables.index', compact('tables', 'floors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'floor_id' => ['required', 'exists:floors,id'],
            'label' => ['required', 'string', 'max:20'],
            'seats' => ['required', 'integer', 'min:1'],
            'pos_x' => ['nullable', 'integer'],
            'pos_y' => ['nullable', 'integer'],
        ]);

        DiningTable::create($data + ['status' => 'free']);

        return back()->with('status', 'Table created.');
    }

    /** Print-friendly page: table's QR code (rendered client-side) plus the raw link, to tape to the table. */
    public function qr(DiningTable $table): View
    {
        return view('admin.tables.qr', [
            'table' => $table,
            'url' => route('order.menu', $table),
        ]);
    }

    /** Waiter/host use — moves a table between free/occupied/reserved/billed. */
    public function updateStatus(Request $request, DiningTable $table): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:free,occupied,reserved,billed'],
        ]);

        $table->update($data);

        return back()->with('status', "Table {$table->label} marked {$data['status']}.");
    }

    public function destroy(DiningTable $table): RedirectResponse
    {
        $table->delete();

        return back()->with('status', 'Table deleted.');
    }
}
