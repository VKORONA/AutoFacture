@php
    $autofactureTheme = 'premium';
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

    $company_address = $formatPremiumAddress($estimate->company->address);
    $billing_address = $formatPremiumAddress($estimate->customer->billingAddress);
    $shipping_address = $formatPremiumAddress($estimate->customer->shippingAddress);

    $presentationValues = collect([
        'project_name' => $estimate->project_name,
        'project_address' => $estimate->project_address,
        'purchase_order_number' => $estimate->purchase_order_number,
        'project_contact' => $estimate->project_contact,
        'payment_terms' => $estimate->payment_terms_label,
    ])->filter(static fn ($value) => filled($value));

    if ($presentationValues->isNotEmpty()) {
        $syntheticFields = $presentationValues->map(
            static fn ($value, $slug) => (object) [
                'customField' => (object) ['slug' => $slug],
                'defaultAnswer' => $value,
            ]
        )->values();

        $estimate->setRelation('fields', $estimate->fields->concat($syntheticFields));
    }
@endphp
@include('app.pdf.shared.autofacture-premium-estimate')
