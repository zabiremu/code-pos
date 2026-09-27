<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Product spreadsheet import/export. Import is all-or-nothing: every row is
 * checked first, and if any row has a problem nothing is saved.
 */
class ProductCsvController extends Controller
{
    public const COLUMNS = [
        'sku', 'name', 'category', 'brand', 'unit', 'purchase_price', 'sale_price', 'regular_price',
        'tax_rate', 'track_stock', 'opening_stock', 'low_stock_alert', 'available', 'description',
    ];

    private const ALIASES = ['price' => 'sale_price', 'cost' => 'purchase_price', 'barcode' => 'sku', 'mrp' => 'regular_price', 'stock' => 'opening_stock', 'product' => 'name'];

    public function export(): StreamedResponse
    {
        $filename = 'products-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads Bangla / UTF-8 correctly
            fputcsv($out, self::COLUMNS);
            Product::with(['category:id,name', 'brand:id,name', 'unit:id,short_name'])->orderBy('name')->chunk(500, function ($products) use ($out) {
                foreach ($products as $p) {
                    fputcsv($out, [
                        $p->sku, $p->name, $p->category?->name, $p->brand?->name, $p->unit?->short_name,
                        $p->purchase_price, $p->base_price, $p->regular_price, $p->tax_rate,
                        $p->track_stock ? 'yes' : 'no', rtrim(rtrim((string) $p->stock_quantity, '0'), '.') ?: '0',
                        rtrim(rtrim((string) $p->low_stock_threshold, '0'), '.') ?: '0', $p->is_available ? 'yes' : 'no', $p->description,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            fputcsv($out, ['MJ-1L', 'Mango juice 1L', 'Beverages', 'Pran', 'pcs', '80', '100', '120', '', 'yes', '24', '5', 'yes', '']);
            fputcsv($out, ['', 'Basmati rice (loose)', 'Groceries', '', 'kg', '95', '120', '', '', 'yes', '50.5', '10', 'yes', 'Sold by weight']);
            fclose($out);
        }, 'products-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        return view('admin.products.import', ['columns' => self::COLUMNS]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']], [
            'file.mimes' => 'Upload a .csv file. In Excel use File > Save As > "CSV UTF-8".',
            'file.max' => 'The file must be 5 MB or smaller.',
        ]);

        [$rows, $errors] = $this->parse($request->file('file')->getRealPath());

        if ($errors) {
            return back()->withErrors(['file' => 'Nothing was imported. Fix these rows and upload again:'])->with('importErrors', array_slice($errors, 0, 50));
        }

        $result = DB::transaction(function () use ($rows, $stock) {
            $created = $updated = 0;
            $warehouseId = Warehouse::default()->id;
            $cache = ['category' => [], 'brand' => [], 'unit' => []];

            foreach ($rows as $row) {
                $attrs = [
                    'name' => $row['name'],
                    'category_id' => $this->lookup('category', $row['category'], $cache),
                    'brand_id' => $this->lookup('brand', $row['brand'], $cache),
                    'unit_id' => $this->lookup('unit', $row['unit'], $cache),
                    'purchase_price' => $row['purchase_price'],
                    'base_price' => $row['sale_price'],
                    'regular_price' => $row['regular_price'],
                    'tax_rate' => $row['tax_rate'],
                    'track_stock' => $row['track_stock'],
                    'low_stock_threshold' => $row['low_stock_alert'],
                    'is_available' => $row['available'],
                    'description' => $row['description'],
                ];

                $existing = $row['sku'] ? Product::where('sku', $row['sku'])->first() : null;
                if ($existing) {
                    // Blank cells leave existing values alone on update.
                    $existing->update(array_filter($attrs, fn ($v) => $v !== null));
                    $updated++;
                } else {
                    $product = Product::create(array_merge($attrs, [
                        'sku' => $row['sku'],
                        'stock_quantity' => 0,
                        'purchase_price' => $attrs['purchase_price'] ?? 0,
                        'track_stock' => $attrs['track_stock'] ?? true,
                        'low_stock_threshold' => $attrs['low_stock_threshold'] ?? 0,
                        'is_available' => $attrs['is_available'] ?? true,
                    ]));
                    if (($row['opening_stock'] ?? 0) > 0) {
                        $stock->adjust($product, $warehouseId, (float) $row['opening_stock'], 'opening', 'Imported from CSV');
                    }
                    $created++;
                }
            }

            return compact('created', 'updated');
        });

        return redirect()->route('admin.products.index')->with('status', "Import done: {$result['created']} added, {$result['updated']} updated.");
    }

    /** @return array{0: array<int, array>, 1: string[]} clean rows, and "Row N: problem" messages */
    private function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $headers = array_map(function ($h) {
            $h = Str::snake(trim(mb_strtolower((string) $h)));
            $h = str_replace([' ', '-'], '_', $h);

            return self::ALIASES[$h] ?? $h;
        }, str_getcsv($first, $delimiter));

        $errors = [];
        foreach (['name', 'sale_price'] as $required) {
            if (! in_array($required, $headers, true)) {
                $errors[] = "The file needs a \"{$required}\" column. Download the template to see the layout.";
            }
        }
        if ($errors) {
            fclose($handle);

            return [[], $errors];
        }

        $rows = [];
        $skus = [];
        $line = 1;
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }
            if (count($rows) >= 5000) {
                $errors[] = 'Import up to 5,000 products at a time.';
                break;
            }

            $raw = [];
            foreach ($headers as $i => $h) {
                $raw[$h] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
            }

            $row = ['sku' => $raw['sku'] ?? '' ?: null, 'name' => $raw['name'] ?? ''];
            $problems = [];

            if ($row['name'] === '') {
                $problems[] = 'name is empty';
            } elseif (mb_strlen($row['name']) > 150) {
                $problems[] = 'name is longer than 150 characters';
            }
            if ($row['sku'] !== null) {
                if (isset($skus[mb_strtolower($row['sku'])])) {
                    $problems[] = "SKU {$row['sku']} appears twice in the file";
                }
                $skus[mb_strtolower($row['sku'])] = true;
            }

            foreach (['sale_price' => true, 'purchase_price' => false, 'regular_price' => false, 'tax_rate' => false, 'opening_stock' => false, 'low_stock_alert' => false] as $col => $required) {
                $value = str_replace([',', ' '], '', $raw[$col] ?? '');
                if ($value === '') {
                    $row[$col] = null;
                    if ($required) {
                        $problems[] = str_replace('_', ' ', $col).' is empty';
                    }
                } elseif (! is_numeric($value) || (float) $value < 0) {
                    $problems[] = str_replace('_', ' ', $col)." \"{$raw[$col]}\" isn't a number";
                } else {
                    $row[$col] = (float) $value;
                }
            }
            if (($row['tax_rate'] ?? 0) > 100) {
                $problems[] = 'tax rate is over 100';
            }

            foreach (['track_stock', 'available'] as $col) {
                $v = mb_strtolower($raw[$col] ?? '');
                $row[$col] = match (true) {
                    $v === '' => null,
                    in_array($v, ['yes', 'y', '1', 'true', 'on'], true) => true,
                    in_array($v, ['no', 'n', '0', 'false', 'off'], true) => false,
                    default => 'bad',
                };
                if ($row[$col] === 'bad') {
                    $problems[] = str_replace('_', ' ', $col)." should be yes or no, not \"{$raw[$col]}\"";
                }
            }

            foreach (['category', 'brand', 'unit', 'description'] as $col) {
                $row[$col] = ($raw[$col] ?? '') !== '' ? $raw[$col] : null;
            }

            if ($problems) {
                $errors[] = "Row {$line}: ".implode('; ', $problems).'.';
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (! $rows && ! $errors) {
            $errors[] = 'The file has no product rows.';
        }

        return [$rows, $errors];
    }

    /** Finds a category/brand/unit by name, creating it if new. */
    private function lookup(string $type, ?string $name, array &$cache): ?int
    {
        if ($name === null) {
            return null;
        }
        $key = mb_strtolower($name);
        if (isset($cache[$type][$key])) {
            return $cache[$type][$key];
        }

        $id = match ($type) {
            'category' => (Category::whereRaw('lower(name) = ?', [$key])->first()
                ?? Category::create(['name' => $name, 'slug' => $this->uniqueSlug($name), 'is_active' => true]))->id,
            'brand' => (Brand::whereRaw('lower(name) = ?', [$key])->first() ?? Brand::create(['name' => $name, 'is_active' => true]))->id,
            'unit' => (Unit::whereRaw('lower(short_name) = ?', [$key])->orWhereRaw('lower(name) = ?', [$key])->first()
                ?? Unit::create(['name' => $name, 'short_name' => Str::limit($name, 20, ''), 'allow_decimal' => in_array($key, ['kg', 'g', 'l', 'ml', 'litre', 'liter', 'kilogram', 'gram', 'metre', 'meter', 'm'], true), 'is_active' => true]))->id,
        };

        return $cache[$type][$key] = $id;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $n = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
