@php
    $themes = [
        'night' => [
            'page' => '#08142d',
            'surface' => '#0f2348',
            'surface_alt' => '#122b57',
            'text' => '#edf4ff',
            'muted' => '#a9bfdf',
            'accent' => '#31a7ff',
            'border' => '#294775',
            'total' => '#087dff',
        ],
        'franchise' => [
            'page' => '#ffffff',
            'surface' => '#f0fdf4',
            'surface_alt' => '#ecfdf5',
            'text' => '#123524',
            'muted' => '#4b6b5b',
            'accent' => '#059669',
            'border' => '#bbf7d0',
            'total' => '#047857',
        ],
    ];
    $theme = $themes[$autofactureTheme ?? 'night'] ?? $themes['night'];
    $isFranchise = ($autofactureTheme ?? null) === 'franchise';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px 34px; background: {{ $theme['page'] }}; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: {{ $theme['text'] }};
            background: {{ $theme['page'] }};
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.45;
        }
        table { border-collapse: collapse; }
        .document-header { width: 100%; margin-bottom: 18px; }
        .document-header td { vertical-align: top; }
        .brand { width: 58%; }
        .brand img { max-width: 190px; max-height: 58px; }
        .brand-name { font-size: 21px; font-weight: bold; color: {{ $theme['accent'] }}; }
        .document-title { text-align: right; }
        .document-title h1 { margin: 0; font-size: 29px; letter-spacing: 1px; }
        .document-number { margin-top: 5px; color: {{ $theme['accent'] }}; font-size: 13px; font-weight: bold; }
        .meta { margin-top: 9px; color: {{ $theme['muted'] }}; line-height: 1.7; }
        .parties { width: 100%; margin-bottom: 16px; }
        .parties td { width: 50%; padding: 13px; vertical-align: top; border: 1px solid {{ $theme['border'] }}; background: {{ $theme['surface'] }}; }
        .parties td + td { border-left: 7px solid {{ $theme['page'] }}; }
        .section-label { margin-bottom: 5px; color: {{ $theme['accent'] }}; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; }
        .reference { margin: 0 0 15px; padding: 9px 12px; border-left: 4px solid {{ $theme['accent'] }}; background: {{ $theme['surface_alt'] }}; }
        .items-table { width: 100%; }
        .item-table-heading-row { background: {{ $theme['surface_alt'] }}; }
        .item-table-heading { padding: 8px 6px; color: {{ $theme['text'] }}; border-bottom: 2px solid {{ $theme['accent'] }}; font-size: 8px; text-transform: uppercase; }
        .item-row td { border-bottom: 1px solid {{ $theme['border'] }}; }
        .item-cell { padding: 9px 6px; color: {{ $theme['text'] }}; font-size: 9px; }
        .item-description { color: {{ $theme['muted'] }}; font-size: 8px; }
        .item-cell-table-hr { display: none; }
        .total-display-container { width: 100%; }
        .total-display-table { width: 43%; margin: 16px 0 15px auto; page-break-inside: avoid; }
        .total-display-table td { padding: 5px 8px; }
        .total-table-attribute-label { color: {{ $theme['muted'] }}; }
        .total-table-attribute-value { text-align: right; color: {{ $theme['text'] }}; font-weight: bold; }
        .total-border-left, .total-border-right { padding: 9px 8px !important; background: {{ $theme['total'] }}; color: #ffffff !important; font-size: 12px; }
        .notes { margin-top: 15px; padding: 11px 13px; border: 1px solid {{ $theme['border'] }}; background: {{ $theme['surface'] }}; page-break-inside: avoid; }
        .notes-label { margin-bottom: 5px; color: {{ $theme['accent'] }}; font-weight: bold; text-transform: uppercase; }
        .vat-franchise { margin-top: 14px; padding: 10px 12px; border: 1px dashed {{ $theme['accent'] }}; color: {{ $theme['accent'] }}; font-size: 10px; font-weight: bold; text-align: center; }
        .footer { margin-top: 20px; padding-top: 9px; border-top: 1px solid {{ $theme['border'] }}; color: {{ $theme['muted'] }}; font-size: 7px; text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .border-0 { border: 0; }
        .pr-20 { padding-right: 10px !important; }
        .pl-0 { padding-left: 6px !important; }
        .pl-10 { padding-left: 6px !important; }
        .py-2 { padding-top: 2px; padding-bottom: 2px; }
        .py-3 { padding-top: 3px; padding-bottom: 3px; }
        .py-8 { padding-top: 8px; padding-bottom: 8px; }
    </style>
</head>
<body>
<table class="document-header">
    <tr>
        <td class="brand">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo de l’entreprise">
            @else
                <div class="brand-name">{{ $invoice->company->name }}</div>
            @endif
        </td>
        <td class="document-title">
            <h1>FACTURE</h1>
            <div class="document-number">N° {{ $invoice->invoice_number }}</div>
            <div class="meta">
                Émise le {{ $invoice->formattedInvoiceDate }}<br>
                Échéance {{ $invoice->formattedDueDate }}
            </div>
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <div class="section-label">Émetteur</div>
            <strong>{{ $invoice->company->name }}</strong><br>
            {!! $company_address !!}
        </td>
        <td>
            <div class="section-label">Client</div>
            <strong>{{ $invoice->customer->company_name ?: $invoice->customer->name }}</strong><br>
            {!! $billing_address !!}
        </td>
    </tr>
</table>

@if ($invoice->reference_number)
    <div class="reference"><strong>Référence :</strong> {{ $invoice->reference_number }}</div>
@endif

@include('app.pdf.invoice.partials.table')

@if ($isFranchise || blank($invoice->company->vat_number))
    <div class="vat-franchise">TVA non applicable, art. 293 B du CGI</div>
@endif

@if ($notes)
    <div class="notes">
        <div class="notes-label">Notes</div>
        {!! $notes !!}
    </div>
@endif

<div class="footer">
    Document généré par AutoFacture — {{ $invoice->company->name }}
    @if ($invoice->company->siret) — SIRET {{ $invoice->company->siret }} @endif
    @if ($invoice->company->vat_number) — TVA {{ $invoice->company->vat_number }} @endif
</div>
</body>
</html>
