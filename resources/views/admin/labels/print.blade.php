{{--
    Printable labels, sized in mm. Sheets fill a grid per A4 page; roll
    templates print one label per page (thermal printers feed per label).
--}}
@php
    $t = $template;
    $isRoll = $t['rows'] === null;
    $perPage = $isRoll ? 1 : $t['cols'] * $t['rows'];
    $cells = array_merge(array_fill(0, $isRoll ? 0 : $skip, null), $labels);
    $pages = array_chunk($cells, $perPage);
    $money = fn ($v) => number_format((float) $v, 2);
    $small = $t['h'] < 24;
    $compact = $t['h'] <= 26; // short labels: one-line name, smaller price
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ count($labels) }} labels &middot; {{ config('app.name') }}</title>
    <style>
        @page { size: {{ $isRoll ? $t['w'].'mm '.$t['h'].'mm' : 'A4' }}; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Instrument Sans', Arial, Helvetica, sans-serif; color: #000; background: #e7e2e2; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 12px; align-items: center; padding: 12px 16px; background: #6B0A14; color: #F3ECEC; font-size: 14px; z-index: 1; }
        .toolbar button { font: inherit; font-weight: 600; background: #F3ECEC; color: #56070F; border: 0; border-radius: 8px; padding: 8px 16px; cursor: pointer; }
        .page {
            width: {{ $isRoll ? $t['w'].'mm' : '210mm' }}; height: {{ $isRoll ? $t['h'].'mm' : '297mm' }};
            margin: 16px auto; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.15);
            padding: {{ $t['top'] }}mm 0 0 {{ $t['left'] }}mm;
            display: grid; grid-template-columns: repeat({{ $t['cols'] }}, {{ $t['w'] }}mm);
            grid-auto-rows: {{ $t['h'] }}mm; column-gap: {{ $t['gapX'] }}mm; row-gap: {{ $t['gapY'] }}mm;
            break-after: page; overflow: hidden;
        }
        .label { width: {{ $t['w'] }}mm; height: {{ $t['h'] }}mm; padding: {{ $small ? '1mm 1mm' : ($compact ? '1.2mm 1mm' : '2mm 2.5mm') }}; display: flex; flex-direction: column; justify-content: center; line-height: 1.15; text-align: center; overflow: hidden; outline: 1px dashed #ddd; }
        .shop { font-size: {{ $small ? '5.5pt' : '6.5pt' }}; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .name { font-size: {{ $small ? '6.5pt' : '8pt' }}; font-weight: 600; line-height: 1.15; overflow: hidden; {{ $compact ? 'white-space: nowrap; text-overflow: ellipsis;' : 'max-height: 2.3em;' }} flex: none; }
        .shop, .code, .price { flex: none; }
        .bars { flex: 1 1 0; min-height: 4mm; max-height: {{ $small ? '9mm' : '14mm' }}; margin: .6mm 0 .3mm; }
        .bars svg { display: block; width: 100%; height: 100%; }
        .code { font-family: 'Courier New', monospace; font-size: {{ $small ? '6pt' : '7pt' }}; letter-spacing: .08em; }
        .price { font-size: {{ $small ? '8pt' : ($compact ? '9pt' : '10.5pt') }}; font-weight: 700; line-height: 1.1; }
        .price s { font-weight: 400; font-size: .75em; margin-left: 1mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; }
            .label { outline: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print {{ count($labels) }} labels</button>
        <span>{{ count($pages) }} {{ $isRoll ? 'labels on the roll' : \Illuminate\Support\Str::plural('page', count($pages)) }} &middot; Set margins to "None" and scale to 100% in the print dialog.</span>
    </div>

    @foreach ($pages as $page)
        <div class="page">
            @foreach ($page as $product)
                <div class="label">
                    @if ($product)
                        @if ($show['shop'])<div class="shop">{{ config('app.name') }}</div>@endif
                        @if ($show['name'])<div class="name">{{ $product->name }}</div>@endif
                        <div class="bars">{!! \App\Support\Code128::svg($product->sku, 40) !!}</div>
                        <div class="code">{{ $product->sku }}</div>
                        @if ($show['price'])
                            <div class="price">{{ $currency ? $currency.' ' : '' }}{{ $money($product->base_price) }}@if ($product->regular_price && $product->regular_price > $product->base_price)<s>{{ $money($product->regular_price) }}</s>@endif</div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
