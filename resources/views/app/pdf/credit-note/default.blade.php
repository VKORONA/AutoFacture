@php
    $themes = [
        'premium' => [
            'page' => '#ffffff', 'text' => '#0f172a', 'muted' => '#64748b',
            'accent' => '#2563eb', 'surface' => '#eff6ff', 'border' => '#bfdbfe',
            'heading' => '#0b2f6b', 'total' => '#2563eb',
        ],
        'classique' => [
            'page' => '#ffffff', 'text' => '#1f2937', 'muted' => '#6b7280',
            'accent' => '#374151', 'surface' => '#f3f4f6', 'border' => '#d1d5db',
            'heading' => '#111827', 'total' => '#374151',
        ],
        'minimal' => [
            'page' => '#ffffff', 'text' => '#334155', 'muted' => '#94a3b8',
            'accent' => '#64748b', 'surface' => '#f8fafc', 'border' => '#e2e8f0',
            'heading' => '#0f172a', 'total' => '#475569',
        ],
        'nuit' => [
            'page' => '#08142d', 'text' => '#edf4ff', 'muted' => '#a9bfdf',
            'accent' => '#31a7ff', 'surface' => '#0f2348', 'border' => '#294775',
            'heading' => '#ffffff', 'total' => '#087dff',
        ],
        'franchise-tva' => [
            'page' => '#ffffff', 'text' => '#123524', 'muted' => '#4b6b5b',
            'accent' => '#059669', 'surface' => '#f0fdf4', 'border' => '#bbf7d0',
            'heading' => '#064e3b', 'total' => '#047857',
        ],
    ];
    $theme = $themes[$templateName ?? 'premium'] ?? $themes['premium'];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ data_get($document, 'credit_note.number', 'Avoir') }}</title>
    <style>
        @page { margin: 32px 38px; background: {{ $theme['page'] }}; }
        * { box-sizing: border-box; }
        body {
            color: {{ $theme['text'] }};
            background: {{ $theme['page'] }};
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }
        h1 { margin: 0; color: {{ $theme['heading'] }}; font-size: 25px; letter-spacing: .5px; }
        h2 { margin: 0 0 8px; color: {{ $theme['accent'] }}; font-size: 12px; text-transform: uppercase; }
        .muted { color: {{ $theme['muted'] }}; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .document-meta { text-align: right; }
        .document-meta strong { color: {{ $theme['accent'] }}; }
        .parties { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .parties td {
            width: 50%; padding: 12px; vertical-align: top;
            border: 1px solid {{ $theme['border'] }};
            background: {{ $theme['surface'] }};
        }
        .reference {
            margin-bottom: 20px; padding: 10px 12px;
            background: {{ $theme['surface'] }};
            border-left: 4px solid {{ $theme['accent'] }};
        }
        .lines { width: 100%; border-collapse: collapse; }
        .lines th {
            padding: 8px; color: #ffffff; background: {{ $theme['accent'] }};
            text-align: left;
        }
        .lines td { padding: 9px 8px; border-bottom: 1px solid {{ $theme['border'] }}; }
        .lines .number { text-align: right; white-space: nowrap; }
        .totals { width: 43%; margin: 18px 0 24px auto; border-collapse: collapse; }
        .totals td { padding: 6px 8px; border-bottom: 1px solid {{ $theme['border'] }}; }
        .totals .number { text-align: right; white-space: nowrap; }
        .totals .grand-total { color: #ffffff; background: {{ $theme['total'] }}; font-size: 13px; font-weight: bold; }
        .reason { margin: 18px 0; padding: 10px 12px; border: 1px solid {{ $theme['border'] }}; }
        .settlement { margin: 18px 0; padding: 10px 12px; background: {{ $theme['surface'] }}; }
        .vat-franchise {
            margin: 18px 0; padding: 10px 12px; color: {{ $theme['accent'] }};
            border: 1px dashed {{ $theme['accent'] }}; font-weight: bold; text-align: center;
        }
        .legal { margin-top: 28px; color: {{ $theme['muted'] }}; font-size: 8px; }
        .integrity { margin-top: 14px; color: {{ $theme['muted'] }}; font-size: 7px; word-break: break-all; }
    </style>
</head>
<body>
@php
    $currencyCode = data_get($document, 'currency.code', 'EUR');
    $money = static fn ($amount) => number_format(((int) $amount) / 100, 2, ',', ' ').' '.$currencyCode;
    $formatDate = static function ($date): ?string {
        if (! $date) return null;
        try { return \Carbon\Carbon::parse((string) $date)->format('d/m/Y'); }
        catch (\Throwable) { return (string) $date; }
    };
    $addressLines = static function ($address): array {
        $address = (array) $address;
        $street1 = $address['street_1'] ?? $address['address_street_1'] ?? null;
        $street2 = $address['street_2'] ?? $address['address_street_2'] ?? null;
        $postalCode = $address['postal_code'] ?? $address['zip'] ?? null;
        $city = $address['city'] ?? null;
        $state = $address['state'] ?? null;
        $country = $address['country'] ?? null;
        $postalCity = trim(implode(' ', array_filter([$postalCode, $city])));
        return array_values(array_filter([$street1, $street2, $postalCity ?: null, $state, $country]));
    };
    $sellerAddressLines = $addressLines(data_get($document, 'seller.address', []));
    $buyerAddressLines = $addressLines(data_get($document, 'buyer.address', []));
    $creditNoteDate = $formatDate(data_get($document, 'credit_note.issue_date'));
    $originalInvoiceDate = $formatDate(data_get($document, 'original_invoice.issue_date'));
@endphp

<table class="header">
    <tr>
        <td>
            <h1>AVOIR</h1>
            <div class="muted">Document rectificatif</div>
        </td>
        <td class="document-meta">
            <strong>{{ data_get($document, 'credit_note.number') }}</strong><br>
            Date d’émission : {{ $creditNoteDate ?: 'Non renseignée' }}
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <h2>Émetteur</h2>
            <strong>{{ data_get($document, 'seller.name') }}</strong><br>
            @foreach ($sellerAddressLines as $value) {{ $value }}<br> @endforeach
            @if (data_get($document, 'seller.siren')) SIREN : {{ data_get($document, 'seller.siren') }}<br> @endif
            @if (data_get($document, 'seller.siret')) SIRET : {{ data_get($document, 'seller.siret') }}<br> @endif
            @if (data_get($document, 'seller.vat_number')) TVA : {{ data_get($document, 'seller.vat_number') }} @endif
        </td>
        <td>
            <h2>Client</h2>
            <strong>{{ data_get($document, 'buyer.company_name') ?: data_get($document, 'buyer.name') }}</strong><br>
            @foreach ($buyerAddressLines as $value) {{ $value }}<br> @endforeach
            @if (data_get($document, 'buyer.siren')) SIREN : {{ data_get($document, 'buyer.siren') }}<br> @endif
            @if (data_get($document, 'buyer.siret')) SIRET : {{ data_get($document, 'buyer.siret') }}<br> @endif
            @if (data_get($document, 'buyer.vat_number')) TVA : {{ data_get($document, 'buyer.vat_number') }} @endif
        </td>
    </tr>
</table>

<div class="reference">
    Cet avoir rectifie explicitement la facture
    <strong>{{ data_get($document, 'original_invoice.number') }}</strong>@if ($originalInvoiceDate), émise le {{ $originalInvoiceDate }}@endif.
</div>

<table class="lines">
    <thead>
    <tr>
        <th>Désignation</th>
        <th class="number">Montant HT</th>
        <th class="number">TVA</th>
        <th class="number">Total TTC</th>
    </tr>
    </thead>
    <tbody>
    @foreach ((array) data_get($document, 'lines', []) as $line)
        <tr>
            <td><strong>{{ data_get($line, 'name') }}</strong><br><span class="muted">{{ data_get($line, 'description') }}</span></td>
            <td class="number">{{ $money(data_get($line, 'sub_total', 0)) }}</td>
            <td class="number">{{ $money(data_get($line, 'tax', 0)) }}</td>
            <td class="number">{{ $money(data_get($line, 'total', 0)) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Total HT</td><td class="number">{{ $money(data_get($document, 'credit_note.sub_total', 0)) }}</td></tr>
    <tr><td>TVA correspondante</td><td class="number">{{ $money(data_get($document, 'credit_note.tax', 0)) }}</td></tr>
    <tr class="grand-total"><td>Total de l’avoir</td><td class="number">{{ $money(data_get($document, 'credit_note.total', 0)) }}</td></tr>
</table>

@if (($templateName ?? null) === 'franchise-tva' || blank(data_get($document, 'seller.vat_number')))
    <div class="vat-franchise">TVA non applicable, art. 293 B du CGI</div>
@endif

<div class="reason"><strong>Motif :</strong><br>{{ data_get($document, 'credit_note.reason') }}</div>

<div class="settlement">
    Montant imputé sur le solde de la facture :
    <strong>{{ $money(data_get($document, 'credit_note.applied_to_balance', 0)) }}</strong><br>
    @if ((int) data_get($document, 'credit_note.refundable_amount', 0) > 0)
        Montant restant à rembourser ou à porter au crédit du client :
        <strong>{{ $money(data_get($document, 'credit_note.refundable_amount', 0)) }}</strong>
    @else
        Aucun remboursement complémentaire n’est identifié lors de l’émission.
    @endif
</div>

<div class="legal">
    @foreach ((array) data_get($document, 'seller.legal_mentions', []) as $mention) {{ $mention }}<br> @endforeach
</div>
<div class="integrity">
    Document émis et scellé par AutoFacture. Toute correction ultérieure doit faire l’objet d’un nouveau document comptable.
</div>
</body>
</html>
