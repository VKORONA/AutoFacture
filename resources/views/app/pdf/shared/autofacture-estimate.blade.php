@php
    $themes = [
        'standard' => [
            'page' => '#ffffff',
            'surface' => '#f8fafc',
            'surface_alt' => '#eef4fb',
            'text' => '#1e293b',
            'muted' => '#64748b',
            'accent' => '#1d4ed8',
            'border' => '#dbe4ef',
            'total' => '#123b72',
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
    $theme = $themes[$autofactureTheme ?? 'standard'] ?? $themes['standard'];
    $isFranchise = ($autofactureTheme ?? null) === 'franchise';
    $customerIsIndividual = data_get($estimate, 'customer.customer_type') === 'individual';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis - {{ $estimate->estimate_number }}</title>
    <style>
        @page { margin: 26px 32px 30px; background: {{ $theme['page'] }}; }
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
        .document-header { width: 100%; margin-bottom: 15px; border-bottom: 2px solid {{ $theme['accent'] }}; }
        .document-header td { padding-bottom: 12px; vertical-align: top; }
        .brand { width: 58%; }
        .brand img { max-width: 180px; max-height: 54px; }
        .brand-name { font-size: 20px; font-weight: bold; color: {{ $theme['accent'] }}; }
        .document-title { text-align: right; }
        .document-title h1 { margin: 0; font-size: 27px; letter-spacing: .8px; }
        .document-number { margin-top: 4px; color: {{ $theme['accent'] }}; font-size: 12px; font-weight: bold; }
        .document-status { margin-top: 5px; color: {{ $theme['muted'] }}; font-size: 8px; }
        .facts { width: 100%; margin-bottom: 14px; background: {{ $theme['surface'] }}; }
        .facts td { padding: 8px 10px; border: 1px solid {{ $theme['border'] }}; vertical-align: top; }
        .fact-label { display: block; color: {{ $theme['muted'] }}; font-size: 7px; text-transform: uppercase; }
        .fact-value { display: block; margin-top: 2px; font-weight: bold; }
        .parties { width: 100%; margin-bottom: 15px; }
        .parties td { width: 50%; padding: 12px; vertical-align: top; border: 1px solid {{ $theme['border'] }}; }
        .parties td + td { border-left: 6px solid {{ $theme['page'] }}; }
        .section-label { margin-bottom: 5px; color: {{ $theme['accent'] }}; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .7px; }
        .identifier { margin-top: 4px; color: {{ $theme['muted'] }}; font-size: 8px; }
        .reference { margin: 0 0 14px; padding: 8px 11px; border-left: 4px solid {{ $theme['accent'] }}; background: {{ $theme['surface_alt'] }}; }
        .items-table { width: 100%; }
        .item-table-heading-row { background: {{ $theme['surface_alt'] }}; }
        .item-table-heading { padding: 8px 6px; color: {{ $theme['text'] }}; border-bottom: 2px solid {{ $theme['accent'] }}; font-size: 8px; text-transform: uppercase; }
        .item-row td { border-bottom: 1px solid {{ $theme['border'] }}; }
        .item-cell { padding: 9px 6px; color: {{ $theme['text'] }}; font-size: 9px; }
        .item-description { color: {{ $theme['muted'] }}; font-size: 8px; }
        .item-cell-table-hr { display: none; }
        .total-display-container { width: 100%; }
        .total-display-table { width: 43%; margin: 15px 0 12px auto; page-break-inside: avoid; }
        .total-display-table td { padding: 5px 8px; }
        .total-table-attribute-label { color: {{ $theme['muted'] }}; }
        .total-table-attribute-value { text-align: right; color: {{ $theme['text'] }}; font-weight: bold; }
        .total-border-left, .total-border-right { padding: 9px 8px !important; background: {{ $theme['total'] }}; color: #ffffff !important; font-size: 12px; }
        .notes { margin-top: 12px; padding: 10px 12px; border: 1px solid {{ $theme['border'] }}; page-break-inside: avoid; }
        .notes-label { margin-bottom: 5px; color: {{ $theme['accent'] }}; font-weight: bold; text-transform: uppercase; }
        .vat-franchise { margin-top: 12px; padding: 9px 11px; border: 1px dashed {{ $theme['accent'] }}; color: {{ $theme['accent'] }}; font-size: 9px; font-weight: bold; text-align: center; }
        .approval { width: 100%; margin-top: 15px; page-break-inside: avoid; }
        .approval td { width: 50%; height: 76px; padding: 10px 12px; vertical-align: top; border: 1px solid {{ $theme['border'] }}; }
        .approval td + td { border-left: 6px solid {{ $theme['page'] }}; }
        .approval-title { color: {{ $theme['accent'] }}; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid {{ $theme['border'] }}; color: {{ $theme['muted'] }}; font-size: 7px; text-align: center; }
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
                <div class="brand-name">{{ $estimate->company->name }}</div>
            @endif
        </td>
        <td class="document-title">
            <h1>DEVIS</h1>
            <div class="document-number">N° {{ $estimate->estimate_number }}</div>
            <div class="document-status">Proposition commerciale standard</div>
        </td>
    </tr>
</table>

<table class="facts">
    <tr>
        <td><span class="fact-label">Date d’émission</span><span class="fact-value">{{ $estimate->formattedEstimateDate }}</span></td>
        <td><span class="fact-label">Validité</span><span class="fact-value">Jusqu’au {{ $estimate->formattedExpiryDate }}</span></td>
        <td><span class="fact-label">Référence</span><span class="fact-value">{{ $estimate->reference_number ?: '—' }}</span></td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <div class="section-label">Émetteur</div>
            <strong>{{ $estimate->company->name }}</strong><br>
            {!! $company_address !!}
            <div class="identifier">
                @if (data_get($estimate, 'company.siren')) SIREN {{ data_get($estimate, 'company.siren') }}<br> @endif
                @if (data_get($estimate, 'company.siret')) SIRET {{ data_get($estimate, 'company.siret') }}<br> @endif
                @if (data_get($estimate, 'company.vat_number')) TVA {{ data_get($estimate, 'company.vat_number') }} @endif
            </div>
        </td>
        <td>
            <div class="section-label">Client {{ $customerIsIndividual ? 'particulier' : 'professionnel' }}</div>
            <strong>{{ $estimate->customer->company_name ?: $estimate->customer->name }}</strong><br>
            {!! $billing_address !!}
            @unless ($customerIsIndividual)
                <div class="identifier">
                    @if ($estimate->customer->siren) SIREN {{ $estimate->customer->siren }}<br> @endif
                    @if ($estimate->customer->siret) SIRET {{ $estimate->customer->siret }}<br> @endif
                    @if ($estimate->customer->vat_number) TVA {{ $estimate->customer->vat_number }} @endif
                </div>
            @endunless
        </td>
    </tr>
</table>

@if ($estimate->reference_number)
    <div class="reference"><strong>Objet / référence :</strong> {{ $estimate->reference_number }}</div>
@endif

@include('app.pdf.estimate.partials.table')

@if ($isFranchise || blank($estimate->company->vat_number))
    <div class="vat-franchise">TVA non applicable, art. 293 B du CGI</div>
@endif

@if ($notes)
    <div class="notes">
        <div class="notes-label">Description, conditions et précisions contractuelles</div>
        {!! $notes !!}
    </div>
@endif

<table class="approval">
    <tr>
        <td>
            <div class="approval-title">Acceptation du client</div>
            Bon pour accord, date et signature précédées de la mention « Lu et approuvé ».
        </td>
        <td>
            <div class="approval-title">Cachet et signature</div>
        </td>
    </tr>
</table>

<div class="footer">
    {{ $estimate->company->name }}
    @if (data_get($estimate, 'company.siret')) — SIRET {{ data_get($estimate, 'company.siret') }} @endif
    @if (data_get($estimate, 'company.vat_number')) — TVA {{ data_get($estimate, 'company.vat_number') }} @endif
    — Document généré par AutoFacture
</div>
</body>
</html>
