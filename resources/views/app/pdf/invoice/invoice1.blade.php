@php
    $autofactureTheme = 'premium';

    // Laravel 12 no longer converts legacy $dates entries reliably. Keep the
    // premium renderer compatible with existing Invoice models and test data.
    $rawAttributes = $invoice->getAttributes();
    foreach (['invoice_date', 'due_date'] as $dateAttribute) {
        if (filled($rawAttributes[$dateAttribute] ?? null)) {
            $rawAttributes[$dateAttribute] = \Carbon\Carbon::parse($rawAttributes[$dateAttribute]);
        }
    }
    $invoice->setRawAttributes($rawAttributes, true);

    $formatPremiumAddress = static function ($address): string {
        if (! $address) {
            return '';
        }

        $postalCity = trim(implode(' ', array_filter([
            data_get($address, 'zip'),
            data_get($address, 'city'),
        ])));

        return collect([
            data_get($address, 'address_street_1'),
            data_get($address, 'address_street_2'),
            $postalCity,
            data_get($address, 'state'),
            data_get($address, 'country.name'),
        ])->filter(static fn ($value) => filled($value))
            ->map(static fn ($value) => e($value))
            ->implode('<br>');
    };

    $company_address = $formatPremiumAddress($invoice->company->address);
    $billing_address = $formatPremiumAddress($invoice->customer->billingAddress);
    $shipping_address = $formatPremiumAddress($invoice->customer->shippingAddress);

    $presentationValues = collect([
        'project_name' => $invoice->project_name,
        'project_address' => $invoice->project_address,
        'purchase_order_number' => $invoice->purchase_order_number,
        'project_contact' => $invoice->project_contact,
        'payment_terms' => $invoice->payment_terms_label,
    ])->filter(static fn ($value) => filled($value));

    if ($presentationValues->isNotEmpty()) {
        $syntheticFields = $presentationValues->map(
            static fn ($value, $slug) => (object) [
                'customField' => (object) ['slug' => $slug],
                'defaultAnswer' => $value,
            ]
        )->values();

        $invoice->setRelation('fields', $invoice->fields->concat($syntheticFields));
    }
@endphp
@include('app.pdf.shared.autofacture-premium-invoice')
