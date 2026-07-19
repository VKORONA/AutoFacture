@php
    $photoGroups = \Crater\Models\EstimateLinePhoto::query()
        ->where('estimate_id', $estimate->id)
        ->orderBy('sort_order')
        ->get()
        ->groupBy('line_uuid');

    $annexAttachments = \Crater\Models\EstimateAttachment::query()
        ->where('estimate_id', $estimate->id)
        ->orderBy('sort_order')
        ->get();

    $hasPhotoAnnex = $estimate->include_photo_annex && $photoGroups->flatten()->isNotEmpty();
    $hasAnnex = $hasPhotoAnnex
        || filled($estimate->annex_title)
        || filled($estimate->annex_notes)
        || $annexAttachments->isNotEmpty();
@endphp

<table width="100%" class="items-table" cellspacing="0" border="0">
    <tr class="item-table-heading-row">
        <th width="2%" class="pr-20 text-right item-table-heading">#</th>
        <th width="40%" class="pl-0 text-left item-table-heading">@lang('pdf_items_label')</th>
        @foreach($customFields as $field)
            <th class="text-right item-table-heading">{{ $field->label }}</th>
        @endforeach
        <th class="pr-20 text-right item-table-heading">@lang('pdf_quantity_label')</th>
        <th class="pr-20 text-right item-table-heading">@lang('pdf_price_label')</th>
        @if($estimate->discount_per_item === 'YES')
            <th class="pl-10 text-right item-table-heading">@lang('pdf_discount_label')</th>
        @endif
        <th class="text-right item-table-heading">@lang('pdf_amount_label')</th>
    </tr>

    @foreach ($estimate->items as $itemIndex => $item)
        @php
            $linePhotos = $photoGroups->get($item->line_uuid, collect());
            $thumbnail = $linePhotos->first()?->dataUri('thumbnail');
        @endphp
        <tr class="item-row">
            <td class="pr-20 text-right item-cell" style="vertical-align: top;">
                {{ $itemIndex + 1 }}
            </td>
            <td class="pl-0 text-left item-cell" style="vertical-align: top;">
                <table width="100%" cellspacing="0" border="0">
                    <tr>
                        @if ($thumbnail)
                            <td width="58" style="vertical-align: top; padding-right: 8px;">
                                <img
                                    src="{{ $thumbnail }}"
                                    width="52"
                                    height="39"
                                    alt="Photo de la ligne {{ $itemIndex + 1 }}"
                                    style="border: 1px solid #dbeafe; border-radius: 4px;"
                                >
                            </td>
                        @endif
                        <td style="vertical-align: top;">
                            <span>{{ $item->name }}</span><br>
                            <span class="item-description">
                                {!! nl2br(htmlspecialchars($item->description)) !!}
                            </span>
                            @if ($linePhotos->count() > 1 && $estimate->include_photo_annex)
                                <br>
                                <span style="font-size: 8px; color: #2563eb;">
                                    {{ $linePhotos->count() }} photos détaillées en annexe
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            @foreach($customFields as $field)
                <td class="text-right item-cell" style="vertical-align: top;">
                    {{ $item->getCustomFieldValueBySlug($field->slug) }}
                </td>
            @endforeach
            <td class="pr-20 text-right item-cell" style="vertical-align: top;">
                {{ $item->quantity }} @if($item->unit_name) {{ $item->unit_name }} @endif
            </td>
            <td class="pr-20 text-right item-cell" style="vertical-align: top;">
                {!! format_money_pdf($item->price, $estimate->customer->currency) !!}
            </td>
            @if($estimate->discount_per_item === 'YES')
                <td class="pl-10 text-right item-cell" style="vertical-align: top;">
                    @if($item->discount_type === 'fixed')
                        {!! format_money_pdf($item->discount_val, $estimate->customer->currency) !!}
                    @endif
                    @if($item->discount_type === 'percentage')
                        {{ $item->discount }}%
                    @endif
                </td>
            @endif
            <td class="text-right item-cell" style="vertical-align: top;">
                {!! format_money_pdf($item->total, $estimate->customer->currency) !!}
            </td>
        </tr>
    @endforeach
</table>

<hr class="item-cell-table-hr">

<div class="total-display-container">
    <table width="100%" cellspacing="0px" border="0" class="total-display-table @if(count($estimate->items) > 12) page-break @endif">
        <tr>
            <td class="border-0 total-table-attribute-label">@lang('pdf_subtotal')</td>
            <td class="border-0 item-cell total-table-attribute-value">{!! format_money_pdf($estimate->sub_total, $estimate->customer->currency) !!}</td>
        </tr>

        @if($estimate->discount > 0 && $estimate->discount_per_item === 'NO')
            <tr>
                <td class="pl-10 border-0 total-table-attribute-label">
                    @lang('pdf_discount_label')
                    @if($estimate->discount_type === 'percentage') ({{ $estimate->discount }}%) @endif
                </td>
                <td class="text-right border-0 item-cell total-table-attribute-value">
                    {!! format_money_pdf($estimate->discount_val, $estimate->customer->currency) !!}
                </td>
            </tr>
        @endif

        @if ($estimate->tax_per_item === 'YES')
            @foreach ($taxes as $tax)
                <tr>
                    <td class="border-0 total-table-attribute-label">{{ $tax->name.' ('.$tax->percent.'%)' }}</td>
                    <td class="py-2 border-0 item-cell total-table-attribute-value">
                        {!! format_money_pdf($tax->amount, $estimate->customer->currency) !!}
                    </td>
                </tr>
            @endforeach
        @else
            @foreach ($estimate->taxes as $tax)
                <tr>
                    <td class="border-0 total-table-attribute-label">{{ $tax->name.' ('.$tax->percent.'%)' }}</td>
                    <td class="border-0 item-cell total-table-attribute-value">
                        {!! format_money_pdf($tax->amount, $estimate->customer->currency) !!}
                    </td>
                </tr>
            @endforeach
        @endif

        <tr><td class="py-3"></td><td class="py-3"></td></tr>
        <tr>
            <td class="border-0 total-border-left total-table-attribute-label">@lang('pdf_total')</td>
            <td class="py-8 border-0 total-border-right item-cell total-table-attribute-value" style="color: #5851D8">
                {!! format_money_pdf($estimate->total, $estimate->customer->currency) !!}
            </td>
        </tr>
    </table>
</div>

@if ($hasAnnex)
    <div style="clear: both; page-break-before: always; padding: 24px 30px; font-family: 'DejaVu Sans';">
        <div style="border-bottom: 3px solid #0b57d0; padding-bottom: 10px; margin-bottom: 20px;">
            <div style="font-size: 22px; font-weight: bold; color: #06245c;">ANNEXE AU DEVIS</div>
            <div style="font-size: 10px; color: #64748b; margin-top: 4px;">
                Devis {{ $estimate->estimate_number }} — {{ $estimate->formattedEstimateDate }}
            </div>
        </div>

        @if (filled($estimate->annex_title))
            <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 8px;">
                {{ $estimate->annex_title }}
            </div>
        @endif

        @if (filled($estimate->annex_notes))
            <div style="font-size: 10px; line-height: 15px; color: #334155; margin-bottom: 20px;">
                {!! nl2br(e($estimate->annex_notes)) !!}
            </div>
        @endif

        @if ($hasPhotoAnnex)
            @foreach ($estimate->items as $itemIndex => $item)
                @php($linePhotos = $photoGroups->get($item->line_uuid, collect()))
                @if ($linePhotos->isNotEmpty())
                    <div style="page-break-inside: avoid; margin-bottom: 20px;">
                        <div style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 8px 10px; margin-bottom: 10px;">
                            <div style="font-size: 11px; font-weight: bold; color: #0f172a;">
                                Ligne {{ $itemIndex + 1 }} — {{ $item->name }}
                            </div>
                            @if ($item->description)
                                <div style="font-size: 9px; color: #475569; margin-top: 3px;">
                                    {{ \Illuminate\Support\Str::limit($item->description, 180) }}
                                </div>
                            @endif
                        </div>

                        <table width="100%" cellspacing="8" border="0">
                            @foreach ($linePhotos->chunk(2) as $photoRow)
                                <tr>
                                    @foreach ($photoRow as $photo)
                                        <td width="50%" style="vertical-align: top; text-align: center; padding: 6px; border: 1px solid #e2e8f0;">
                                            @if ($photo->dataUri('preview'))
                                                <img src="{{ $photo->dataUri('preview') }}" width="240" height="180" alt="Photo annexe">
                                            @endif
                                            @if ($photo->caption)
                                                <div style="font-size: 8px; color: #475569; margin-top: 5px;">{{ $photo->caption }}</div>
                                            @endif
                                        </td>
                                    @endforeach
                                    @if ($photoRow->count() === 1)
                                        <td width="50%"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            @endforeach
        @endif

        @if ($annexAttachments->isNotEmpty())
            <div style="page-break-inside: avoid; margin-top: 18px;">
                <div style="font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 10px;">
                    Pièces annexes jointes
                </div>
                @foreach ($annexAttachments as $attachment)
                    <div style="border-bottom: 1px solid #e2e8f0; padding: 7px 0; font-size: 9px; color: #334155;">
                        <strong>{{ $attachment->title ?: $attachment->original_name }}</strong>
                        — {{ number_format($attachment->size_bytes / 1024 / 1024, 1, ',', ' ') }} Mo
                        @if ($attachment->description)
                            <br><span style="color: #64748b;">{{ $attachment->description }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
