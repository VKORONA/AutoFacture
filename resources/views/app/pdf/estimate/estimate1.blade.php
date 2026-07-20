@php
    $autofactureTheme = 'premium';
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
