<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ data_get($document, 'credit_note.number', 'Avoir') }}</title>
    <style>
        @page { margin: 32px 38px; }
        * { box-sizing: border-box; }
        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }
        h1 { margin: 0; font-size: 25px; letter-spacing: .5px; }
        h2 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; }
        .muted { color: #6b7280; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .document-meta { text-align: right; }
        .parties { width: 100%; margin-bottom: 24px; border-collapse: collapse; }
        .parties td {
            width: 50%;
            padding: 12px;
            vertical-align: top;
            border: 1px solid #d1d5db;
        }
        .reference {
            margin-bottom: 20px;
            padding: 10px 12px;
            background: #f3f4f6;
            border-left: 4px solid #374151;
        }
        .lines { width: 100%; border-collapse: collapse; }
        .lines th {
            padding: 8px;
            color: #ffffff;
            background: #374151;
            text-align: left;
        }
        .lines td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; }
        .lines .number { text-align: right; white-space: nowrap; }
        .totals { width: 43%; margin: 18px 0 24px auto; border-collapse: collapse; }
        .totals td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .totals .number { text-align: right; white-space: nowrap; }
        .totals .grand-total { font-size: 13px; font-weight: bold; background: #f3f4f6; }
        .reason { margin: 18px 0; padding: 10px 12px; border: 1px solid #d1d5db; }
        .settlement { margin: 18px 0; padding: 10px 12px; background: #eff6ff; }
        .legal { margin-top: 28px; color: #4b5563; font-size: 8px; }
        .integrity { margin-top: 14px; color: #9ca3af; font-size: 7px; word-break: break-all; }
    </style>
</head>
<body>
@php
    $currencyCode = data_get($document, 'currency.code', 'EUR');
    $money = static fn ($amount) => number_format(((int) $amount) / 100, 2, ',', ' ').' '.$currencyCode;
    $sellerAddress = array_filter((array) data_get($document, 'seller.address', []));
    $buyerAddress = array_filter((array) data_get($document, 'buyer.address', []));
@endphp

<table class="header">
    <tr>
        <td>
            <h1>AVOIR</h1>
            <div class="muted">Document rectificatif</div>
        </td>
        <td class="document-meta">
            <strong>{{ data_get($document, 'credit_note.number') }}</strong><br>
            Date d’émission : {{ data_get($document, 'credit_note.issue_date') }}
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td>
            <h2>Émetteur</h2>
            <strong>{{ data_get($document, 'seller.name') }}</strong><br>
            @foreach ($sellerAddress as $value)
                {{ $value }}<br>
            @endforeach
            @if (data_get($document, 'seller.siren')) SIREN : {{ data_get($document, 'seller.siren') }}<br> @endif
            @if (data_get($document, 'seller.siret')) SIRET : {{ data_get($document, 'seller.siret') }}<br> @endif
            @if (data_get($document, 'seller.vat_number')) TVA : {{ data_get($document, 'seller.vat_number') }} @endif
        </td>
        <td>
            <h2>Client</h2>
            <strong>{{ data_get($document, 'buyer.company_name') ?: data_get($document, 'buyer.name') }}</strong><br>
            @foreach ($buyerAddress as $value)
                {{ $value }}<br>
            @endforeach
            @if (data_get($document, 'buyer.siren')) SIREN : {{ data_get($document, 'buyer.siren') }}<br> @endif
            @if (data_get($document, 'buyer.siret')) SIRET : {{ data_get($document, 'buyer.siret') }}<br> @endif
            @if (data_get($document, 'buyer.vat_number')) TVA : {{ data_get($document, 'buyer.vat_number') }} @endif
        </td>
    </tr>
</table>

<div class="reference">
    Cet avoir rectifie explicitement la facture
    <strong>{{ data_get($document, 'original_invoice.number') }}</strong>
    émise le {{ data_get($document, 'original_invoice.issue_date') }}.
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
            <td>
                <strong>{{ data_get($line, 'name') }}</strong><br>
                <span class="muted">{{ data_get($line, 'description') }}</span>
            </td>
            <td class="number">{{ $money(data_get($line, 'sub_total', 0)) }}</td>
            <td class="number">{{ $money(data_get($line, 'tax', 0)) }}</td>
            <td class="number">{{ $money(data_get($line, 'total', 0)) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Total HT</td>
        <td class="number">{{ $money(data_get($document, 'credit_note.sub_total', 0)) }}</td>
    </tr>
    <tr>
        <td>TVA correspondante</td>
        <td class="number">{{ $money(data_get($document, 'credit_note.tax', 0)) }}</td>
    </tr>
    <tr class="grand-total">
        <td>Total de l’avoir</td>
        <td class="number">{{ $money(data_get($document, 'credit_note.total', 0)) }}</td>
    </tr>
</table>

<div class="reason">
    <strong>Motif :</strong><br>
    {{ data_get($document, 'credit_note.reason') }}
</div>

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
    @foreach ((array) data_get($document, 'seller.legal_mentions', []) as $mention)
        {{ $mention }}<br>
    @endforeach
</div>

<div class="integrity">
    Document émis et scellé par AutoFacture. Toute correction ultérieure doit faire l’objet d’un nouveau document comptable.
</div>
</body>
</html>
