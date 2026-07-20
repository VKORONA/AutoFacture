@php
    $autofactureTheme = 'premium';
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
