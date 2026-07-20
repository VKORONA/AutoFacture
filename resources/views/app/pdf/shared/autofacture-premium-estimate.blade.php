@php
    $company = $estimate->company;
    $customer = $estimate->customer;
    $companyAddress = $company->address;
    $customerIsIndividual = data_get($customer, 'customer_type') === 'individual';
    $legalMentions = app(\Crater\Domain\FrenchInvoicing\FrenchLegalMentionBuilder::class)->forCompany($company);
    $sepaQr = app(\Crater\Domain\Payments\SepaQrCodeService::class)->forEstimate($estimate);
    $isVatFranchise = (bool) data_get($company, 'vat_exempt')
        || data_get($company, 'vat_regime') === 'franchise_base'
        || blank(data_get($company, 'vat_number'));
    $validityDate = $estimate->expiry_date ? $estimate->formattedExpiryDate : 'Sans limite définie';

    $statusLabels = [
        'DRAFT' => 'Brouillon',
        'SENT' => 'Envoyé',
        'VIEWED' => 'Consulté',
        'ACCEPTED' => 'Accepté',
        'REJECTED' => 'Refusé',
        'EXPIRED' => 'Expiré',
    ];
    $statusLabel = $statusLabels[$estimate->status] ?? 'En cours';

    $customValue = static function ($model, array $slugs) {
        foreach ($model->fields ?? [] as $field) {
            $slug = data_get($field, 'customField.slug');
            if (in_array($slug, $slugs, true) && filled($field->defaultAnswer)) {
                return $field->defaultAnswer;
            }
        }

        return null;
    };

    $projectName = $customValue($estimate, ['project_name', 'chantier_name', 'nom_projet'])
        ?: $estimate->reference_number
        ?: 'Mission / prestation';
    $projectAddress = $customValue($estimate, ['project_address', 'chantier_address', 'adresse_chantier']);
    $purchaseOrder = $customValue($estimate, ['purchase_order_number', 'bon_commande', 'numero_commande']);
    $projectContact = $customValue($estimate, ['project_contact', 'interlocuteur', 'contact_projet'])
        ?: $customer->contact_name;
    $paymentTerms = $customValue($estimate, ['payment_terms', 'conditions_paiement'])
        ?: 'Selon conditions du devis';

    $svgData = static fn (string $svg): string => 'data:image/svg+xml;base64,'.base64_encode($svg);
    $icon = static function (string $name, string $color = '#0b63f6') use ($svgData): string {
        $paths = [
            'building' => '<path d="M5 20h14M7 20V6l5-3 5 3v14M9 9h1m4 0h1M9 13h1m4 0h1M9 17h1m4 0h1"/>',
            'user' => '<circle cx="12" cy="8" r="3"/><path d="M5 21c.7-4 3.1-6 7-6s6.3 2 7 6"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>',
            'pin' => '<path d="M12 22s7-6.2 7-13a7 7 0 1 0-14 0c0 6.8 7 13 7 13Z"/><circle cx="12" cy="9" r="2"/>',
            'folder' => '<path d="M3 7h7l2 2h9v10H3z"/>',
            'bank' => '<path d="m3 9 9-5 9 5M5 10v8m4-8v8m6-8v8m4-8v8M3 20h18"/>',
            'shield' => '<path d="M12 3 5 6v5c0 4.8 2.6 8 7 10 4.4-2 7-5.2 7-10V6z"/><path d="m9 12 2 2 4-5"/>',
            'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
            'document' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6m-6 4h6"/>',
            'headset' => '<path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H4zm13 0h3v6h-3zM17 19c-1 2-3 2-5 2"/>',
            'award' => '<circle cx="12" cy="9" r="5"/><path d="m9 14-2 7 5-3 5 3-2-7"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
            'phone' => '<path d="M7 3 4 5c0 8 7 15 15 15l2-3-5-3-2 2c-3-1-5-3-6-6l2-2z"/>',
            'signature' => '<path d="M3 18c4-5 5-9 7-9 3 0-1 9 2 9 2 0 3-5 5-5 1 0 1 3 4 3"/><path d="M4 21h16"/>',
        ];
        $path = $paths[$name] ?? $paths['check'];

        return $svgData('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="'.$color.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$path.'</svg>');
    };

    $autoFactureLogo = $svgData('<svg xmlns="http://www.w3.org/2000/svg" width="420" height="86" viewBox="0 0 420 86"><defs><linearGradient id="g" x1="0" y1="86" x2="82" y2="0"><stop stop-color="#0ea5e9"/><stop offset=".55" stop-color="#2563eb"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs><path d="M43 6c3-5 10-5 13 0l29 50c3 5-1 11-7 11H20c-6 0-10-6-7-11L43 6Z" fill="url(#g)"/><path d="m49 23 18 32H52L44 41l-8 14H21l18-32c2-4 8-4 10 0Z" fill="#fff"/><path d="m57 29 22 39H62L48 43l9-14Z" fill="#6d28d9" opacity=".95"/><text x="100" y="53" font-family="Arial,sans-serif" font-size="43" font-weight="700" fill="#071a3f">Auto</text><text x="196" y="53" font-family="Arial,sans-serif" font-size="43" font-weight="700" fill="#0b63f6">Facture</text><text x="102" y="75" font-family="Arial,sans-serif" font-size="12" fill="#64748b">Automatisez. Facturez. Pilotez.</text></svg>');

    $blueprint = $svgData('<svg xmlns="http://www.w3.org/2000/svg" width="560" height="260" viewBox="0 0 560 260" fill="none" stroke="#7aa7df" stroke-width="1"><path d="M40 220V95l95-45 90 43v127M135 50v170M225 93l85-43 96 48v122M310 50v170M406 98l94-44v166M40 95l95 43 90-45 85 43 96-38 94 42M72 80v140m32-155v155m63-156v156m31-139v139m58-146v146m28-159v159m58-154v154m32-136v136m64-150v150" opacity=".32"/></svg>');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $estimate->estimate_number }}</title>
    <style>
        @page { margin: 18px 22px 20px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #071a3f; font-family: DejaVu Sans, sans-serif; font-size: 8.6px; line-height: 1.42; }
        table { width: 100%; border-collapse: collapse; }
        .page { position: relative; }
        .top { position: relative; min-height: 86px; }
        .af-logo { width: 205px; height: 44px; object-fit: contain; }
        .blueprint { position: absolute; z-index: -1; top: 18px; left: 210px; width: 270px; opacity: .42; }
        .title-box { position: absolute; top: 0; right: 0; width: 250px; }
        .title { margin: 0; color: #071a3f; font-size: 30px; font-weight: 800; letter-spacing: .8px; }
        .number { margin-top: 5px; color: #0b63f6; font-size: 13px; font-weight: 700; }
        .status { position: absolute; top: 2px; right: 0; padding: 5px 10px; border: 1px solid #93c5fd; border-radius: 14px; color: #1d4ed8; background: #eff6ff; font-size: 8px; font-weight: 700; }
        .title-meta { margin-top: 13px; width: 100%; }
        .title-meta td { padding: 3px 0; vertical-align: middle; }
        .title-meta .meta-icon { width: 18px; }
        .title-meta img { width: 12px; height: 12px; }
        .title-meta .meta-label { width: 95px; color: #64748b; font-size: 7.5px; }
        .title-meta .meta-value { color: #071a3f; font-weight: 600; text-align: right; }
        .parties { margin-top: 10px; table-layout: fixed; }
        .parties td { vertical-align: top; }
        .issuer-card { width: 42%; padding: 14px 15px 13px; border-radius: 13px; color: #fff; background: linear-gradient(145deg, #061a42, #082f78); }
        .card-gap { width: 3%; }
        .client-card { width: 55%; padding: 14px 16px 13px; border: 1px solid #dbeafe; border-radius: 13px; background: #fff; }
        .card-label { color: #67e8f9; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
        .client-card .card-label { color: #0b63f6; }
        .card-title { margin-top: 4px; font-size: 14px; font-weight: 800; }
        .company-logo { float: left; width: 38px; height: 38px; margin-right: 9px; object-fit: contain; border-radius: 8px; background: #fff; }
        .issuer-rule { clear: both; margin: 10px 0 8px; border-top: 1px solid #38bdf8; }
        .detail-row { margin-top: 5px; color: #dbeafe; }
        .detail-row img { width: 11px; height: 11px; margin-right: 5px; vertical-align: middle; }
        .client-detail { margin-top: 4px; color: #64748b; }
        .identifier { margin-top: 6px; color: #64748b; font-size: 7.2px; }
        .address-html h3, .address-html p { margin: 0; padding: 0; font-size: inherit; font-weight: normal; }
        .project-strip { margin-top: 10px; border: 1px solid #dbeafe; border-radius: 10px; background: #f8fbff; }
        .project-strip td { padding: 8px 10px; vertical-align: middle; border-right: 1px solid #dbeafe; }
        .project-strip td:last-child { border-right: 0; }
        .project-strip .project-icon { width: 28px; }
        .project-strip img { width: 18px; height: 18px; }
        .mini-label { color: #0b63f6; font-size: 6.8px; font-weight: 700; text-transform: uppercase; }
        .mini-value { margin-top: 2px; color: #071a3f; font-size: 7.7px; font-weight: 700; }
        .items { margin-top: 11px; table-layout: fixed; }
        .items thead { display: table-header-group; }
        .items th { padding: 7px 6px; color: #fff; background: #071a3f; font-size: 7px; text-align: right; text-transform: uppercase; }
        .items th:first-child { border-radius: 7px 0 0 7px; text-align: left; }
        .items th:last-child { border-radius: 0 7px 7px 0; }
        .items td { padding: 8px 6px; border-bottom: 1px solid #dbeafe; vertical-align: top; text-align: right; page-break-inside: avoid; }
        .items td:first-child { text-align: left; }
        .item-name { color: #071a3f; font-weight: 800; }
        .item-description { margin-top: 2px; color: #64748b; font-size: 7px; line-height: 1.35; }
        .item-symbol { float: left; width: 24px; height: 24px; margin-right: 7px; padding: 5px; border-radius: 7px; background: #eff6ff; }
        .summary { margin-top: 11px; table-layout: fixed; page-break-inside: avoid; }
        .summary td { vertical-align: top; }
        .payment-card { width: 38%; padding: 9px 10px; border: 1px solid #dbeafe; border-radius: 9px; background: #fff; }
        .payment-card .heading, .qr-card .heading { color: #0b63f6; font-size: 7px; font-weight: 800; text-transform: uppercase; }
        .bank-table { margin-top: 5px; }
        .bank-table td { padding: 2px 0; font-size: 7px; }
        .bank-table td:first-child { width: 38%; color: #64748b; }
        .qr-gap { width: 2%; }
        .qr-card { width: 22%; padding: 8px; border: 1px solid #dbeafe; border-radius: 9px; text-align: center; background: #f8fbff; }
        .qr-card .qr { width: 86px; height: 86px; margin: 4px auto 1px; object-fit: contain; }
        .qr-note { color: #64748b; font-size: 6.2px; line-height: 1.25; }
        .total-gap { width: 2%; }
        .totals-card { width: 36%; border: 1px solid #dbeafe; border-radius: 9px; overflow: hidden; }
        .totals-card td { padding: 4px 8px; }
        .totals-card td:first-child { color: #64748b; }
        .totals-card td:last-child { color: #071a3f; font-weight: 700; text-align: right; }
        .totals-card .total-row td { border-top: 1px solid #dbeafe; color: #0b63f6; font-weight: 800; }
        .totals-card .net-row td { padding: 9px 8px; color: #fff; background: linear-gradient(120deg, #071a3f, #0b63f6); }
        .totals-card .net-row td:first-child { font-size: 7px; font-weight: 700; text-transform: uppercase; }
        .totals-card .net-row td:last-child { font-size: 18px; }
        .approval { margin-top: 9px; table-layout: fixed; page-break-inside: avoid; }
        .approval td { width: 50%; min-height: 70px; padding: 9px 10px; border: 1px solid #dbeafe; vertical-align: top; }
        .approval td:first-child { border-radius: 8px 0 0 8px; }
        .approval td:last-child { border-radius: 0 8px 8px 0; }
        .approval img { float: left; width: 18px; height: 18px; margin-right: 7px; }
        .approval-title { color: #0b63f6; font-size: 7px; font-weight: 800; text-transform: uppercase; }
        .approval-text { clear: both; margin-top: 8px; color: #64748b; font-size: 7px; }
        .vat-franchise { margin-top: 8px; padding: 7px 10px; border: 1px dashed #93c5fd; border-radius: 8px; color: #64748b; text-align: center; font-size: 8px; font-style: italic; }
        .notes { margin-top: 8px; padding: 8px 10px; border: 1px solid #dbeafe; border-radius: 8px; background: #f8fbff; page-break-inside: avoid; }
        .notes-title { color: #0b63f6; font-size: 7px; font-weight: 800; text-transform: uppercase; }
        .service-band { margin-top: 9px; table-layout: fixed; color: #fff; background: #071a3f; }
        .service-band td { padding: 8px 10px; border-right: 1px solid #234a7b; vertical-align: middle; }
        .service-band td:last-child { border-right: 0; }
        .service-band img { float: left; width: 18px; height: 18px; margin-right: 7px; }
        .service-band strong { display: block; font-size: 7px; }
        .service-band span { color: #bfdbfe; font-size: 6px; }
        .legal-footer { margin-top: 7px; table-layout: fixed; page-break-inside: avoid; }
        .legal-footer td { padding: 5px 7px; border-right: 1px solid #dbeafe; color: #475569; font-size: 6.1px; vertical-align: top; }
        .legal-footer td:last-child { border-right: 0; }
        .legal-title { margin-bottom: 2px; color: #0b63f6; font-size: 6.4px; font-weight: 800; text-transform: uppercase; }
        .locked-brand { margin-top: 5px; color: #94a3b8; font-size: 5.8px; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="top">
        <img class="af-logo" src="{{ $autoFactureLogo }}" alt="AutoFacture">
        <img class="blueprint" src="{{ $blueprint }}" alt="">
        <div class="title-box">
            <h1 class="title">DEVIS</h1>
            <div class="number">N° {{ $estimate->estimate_number }}</div>
            <div class="status">✓ {{ $statusLabel }}</div>
            <table class="title-meta">
                <tr><td class="meta-icon"><img src="{{ $icon('calendar') }}"></td><td class="meta-label">Date d’émission</td><td class="meta-value">{{ $estimate->formattedEstimateDate }}</td></tr>
                <tr><td class="meta-icon"><img src="{{ $icon('calendar') }}"></td><td class="meta-label">Date de validité</td><td class="meta-value">{{ $validityDate }}</td></tr>
                <tr><td class="meta-icon"><img src="{{ $icon('clock') }}"></td><td class="meta-label">Conditions</td><td class="meta-value">{{ $paymentTerms }}</td></tr>
            </table>
        </div>
    </div>

    <table class="parties">
        <tr>
            <td class="issuer-card">
                @if ($logo)<img class="company-logo" src="{{ $logo }}" alt="Logo entreprise">@else<img class="company-logo" src="{{ $icon('building', '#0b63f6') }}" alt="">@endif
                <div class="card-label">Émetteur</div>
                <div class="card-title">{{ $company->name }}</div>
                <div class="issuer-rule"></div>
                <div class="address-html">{!! $company_address !!}</div>
                @if ($company->siret)<div class="detail-row">SIRET : {{ $company->siret }}</div>@endif
                @if ($company->vat_number)<div class="detail-row">TVA intracom. : {{ $company->vat_number }}</div>@endif
                @if (data_get($companyAddress, 'phone'))<div class="detail-row"><img src="{{ $icon('phone', '#dbeafe') }}">{{ data_get($companyAddress, 'phone') }}</div>@endif
                @if (data_get($company, 'owner.email'))<div class="detail-row"><img src="{{ $icon('mail', '#dbeafe') }}">{{ data_get($company, 'owner.email') }}</div>@endif
            </td>
            <td class="card-gap"></td>
            <td class="client-card">
                <img class="company-logo" src="{{ $icon($customerIsIndividual ? 'user' : 'building') }}" alt="">
                <div class="card-label">Client {{ $customerIsIndividual ? 'particulier' : 'professionnel' }}</div>
                <div class="card-title">{{ $customer->company_name ?: $customer->name }}</div>
                <div style="clear:both"></div>
                <div class="address-html client-detail">{!! $billing_address !!}</div>
                @unless ($customerIsIndividual)
                    <div class="identifier">
                        @if ($customer->siret) SIRET : {{ $customer->siret }}<br>@endif
                        @if ($customer->vat_number) TVA intracom. : {{ $customer->vat_number }}<br>@endif
                    </div>
                @endunless
                @if ($customer->email)<div class="client-detail">{{ $customer->email }}</div>@endif
                @if ($customer->phone)<div class="client-detail">{{ $customer->phone }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="project-strip">
        <tr>
            <td class="project-icon"><img src="{{ $icon('folder') }}"></td>
            <td style="width:36%"><div class="mini-label">Référence / projet</div><div class="mini-value">{{ $projectName }}</div></td>
            <td class="project-icon"><img src="{{ $icon('pin') }}"></td>
            <td style="width:32%"><div class="mini-label">Chantier / lieu d’intervention</div><div class="mini-value">{{ $projectAddress ?: strip_tags($shipping_address ?: 'Non renseigné') }}</div></td>
            <td style="width:25%"><div class="mini-label">Bon de commande / interlocuteur</div><div class="mini-value">{{ $purchaseOrder ?: '—' }} @if($projectContact)<br>{{ $projectContact }}@endif</div></td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th style="width:50%">Désignation</th>
            <th style="width:10%">Qté</th>
            <th style="width:14%">PU HT</th>
            <th style="width:10%">TVA</th>
            <th style="width:16%">Total HT</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($estimate->items as $item)
            @php
                $taxPercent = collect($item->taxes ?? [])
                    ->pluck('percent')
                    ->filter(static fn ($value) => $value !== null)
                    ->map(static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ','))
                    ->implode(' + ');
            @endphp
            <tr>
                <td>
                    <img class="item-symbol" src="{{ $icon('document') }}" alt="">
                    <div class="item-name">{{ $item->name }}</div>
                    @if ($item->description)<div class="item-description">{!! nl2br(e($item->description)) !!}</div>@endif
                </td>
                <td>{{ $item->quantity }} @if($item->unit_name){{ $item->unit_name }}@endif</td>
                <td>{!! format_money_pdf($item->price, $customer->currency) !!}</td>
                <td>{{ $taxPercent !== '' ? $taxPercent.' %' : ($isVatFranchise ? '0 %' : '—') }}</td>
                <td>{!! format_money_pdf($item->total, $customer->currency) !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="payment-card">
                <div class="heading">Informations bancaires</div>
                <table class="bank-table">
                    <tr><td>Titulaire</td><td><strong>{{ $company->name }}</strong></td></tr>
                    @if ($company->iban)<tr><td>IBAN</td><td>{{ $company->iban }}</td></tr>@endif
                    @if ($company->bic)<tr><td>BIC</td><td>{{ $company->bic }}</td></tr>@endif
                    <tr><td>Référence</td><td>{{ $estimate->estimate_number }}</td></tr>
                </table>
            </td>
            <td class="qr-gap"></td>
            <td class="qr-card">
                <div class="heading">QR coordonnées bancaires</div>
                @if ($sepaQr)
                    <img class="qr" src="{{ $sepaQr }}" alt="QR code SEPA">
                    <div class="qr-note">Scannez pour récupérer automatiquement le bénéficiaire et l’IBAN.</div>
                @else
                    <img class="qr" src="{{ $icon('bank', '#94a3b8') }}" alt="">
                    <div class="qr-note">Renseignez l’IBAN dans « Mon entreprise » pour activer le QR SEPA.</div>
                @endif
            </td>
            <td class="total-gap"></td>
            <td class="totals-card">
                <table>
                    <tr><td>Sous-total HT</td><td>{!! format_money_pdf($estimate->sub_total, $customer->currency) !!}</td></tr>
                    @if ($estimate->discount_val > 0)<tr><td>Remise</td><td>- {!! format_money_pdf($estimate->discount_val, $customer->currency) !!}</td></tr>@endif
                    <tr><td>TVA</td><td>{!! format_money_pdf($estimate->tax, $customer->currency) !!}</td></tr>
                    <tr class="total-row"><td>Total TTC</td><td>{!! format_money_pdf($estimate->total, $customer->currency) !!}</td></tr>
                    <tr class="net-row"><td>Montant proposé</td><td>{!! format_money_pdf($estimate->total, $customer->currency) !!}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="approval">
        <tr>
            <td><img src="{{ $icon('signature') }}"><div class="approval-title">Acceptation du client</div><div class="approval-text">Bon pour accord, date et signature précédées de la mention « Lu et approuvé ».</div></td>
            <td><img src="{{ $icon('check') }}"><div class="approval-title">Validation</div><div class="approval-text">Nom, qualité du signataire, cachet et signature. Le devis accepté constitue l’engagement contractuel.</div></td>
        </tr>
    </table>

    @if ($isVatFranchise)
        <div class="vat-franchise">ENTREPRISE NON ASSUJETTIE À LA TVA — TVA non applicable, art. 293 B du CGI</div>
    @endif

    @if ($notes)
        <div class="notes"><div class="notes-title">Description, conditions et précisions contractuelles</div>{!! $notes !!}</div>
    @endif

    <table class="service-band">
        <tr>
            <td><img src="{{ $icon('user', '#67e8f9') }}"><strong>À vos côtés</strong><span>Une proposition claire et structurée</span></td>
            <td><img src="{{ $icon('headset', '#67e8f9') }}"><strong>Réactivité et expertise</strong><span>Des prestations détaillées sans ambiguïté</span></td>
            <td><img src="{{ $icon('award', '#67e8f9') }}"><strong>Qualité et indépendance</strong><span>Une présentation professionnelle et traçable</span></td>
        </tr>
    </table>

    <table class="legal-footer">
        <tr>
            <td style="width:38%"><div class="legal-title">Informations légales</div>{{ implode(' · ', $legalMentions) }}@if(data_get($companyAddress, 'address_street_1'))<br>{{ data_get($companyAddress, 'address_street_1') }} {{ data_get($companyAddress, 'zip') }} {{ data_get($companyAddress, 'city') }}@endif</td>
            <td style="width:32%"><div class="legal-title">Validité et règlement</div>Offre valable jusqu’au {{ $validityDate }}. Conditions de règlement : {{ $paymentTerms }}. Toute modification de périmètre pourra faire l’objet d’un avenant.</td>
            <td style="width:30%"><div class="legal-title">Litiges et droit applicable</div>En cas de litige, compétence du tribunal du siège social, sous réserve des règles impératives applicables. Droit français.</td>
        </tr>
    </table>
    <div class="locked-brand">Matrice Premium AutoFacture verrouillée — identité visuelle AutoFacture conservée</div>
</div>
</body>
</html>
