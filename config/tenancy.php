<?php

use Crater\Models\CreditNote;
use Crater\Models\CreditNoteItem;
use Crater\Models\Customer;
use Crater\Models\CustomField;
use Crater\Models\Estimate;
use Crater\Models\ExchangeRateLog;
use Crater\Models\ExchangeRateProvider;
use Crater\Models\Expense;
use Crater\Models\ExpenseCategory;
use Crater\Models\IntegrationSecret;
use Crater\Models\Invoice;
use Crater\Models\Item;
use Crater\Models\Note;
use Crater\Models\Payment;
use Crater\Models\PaymentMethod;
use Crater\Models\RecurringInvoice;
use Crater\Models\TaxType;
use Crater\Models\Unit;

return [
    'models' => [
        CreditNote::class,
        CreditNoteItem::class,
        Customer::class,
        CustomField::class,
        Estimate::class,
        ExchangeRateLog::class,
        ExchangeRateProvider::class,
        Expense::class,
        ExpenseCategory::class,
        IntegrationSecret::class,
        Invoice::class,
        Item::class,
        Note::class,
        Payment::class,
        PaymentMethod::class,
        RecurringInvoice::class,
        TaxType::class,
        Unit::class,
    ],
];
