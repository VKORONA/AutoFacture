<?php

use Crater\Models\Company;
use Crater\Models\EmailLog;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Crater\Models\Transaction;

return [
    'default' => 'main',

    'connections' => [
        Invoice::class => [
            'salt' => Invoice::class.config('app.key'),
            'length' => 20,
            'alphabet' => 'XKyIAR7mgt8jD2vbqPrOSVenNGpiYLx4M61T',
        ],
        Estimate::class => [
            'salt' => Estimate::class.config('app.key'),
            'length' => 20,
            'alphabet' => 'yLJWP79M8rYVqbn1NXjulO6IUDdvekRQGo40',
        ],
        Payment::class => [
            'salt' => Payment::class.config('app.key'),
            'length' => 20,
            'alphabet' => 'asqtW3eDRIxB65GYl7UVLS1dybn9XrKTZ4zO',
        ],
        Company::class => [
            'salt' => Company::class.config('app.key'),
            'length' => 20,
            'alphabet' => 's0DxOFtEYEnuKPmP08Ch6A1iHlLmBTBVWms5',
        ],
        EmailLog::class => [
            'salt' => EmailLog::class.config('app.key'),
            'length' => 20,
            'alphabet' => 'BRAMEz5str5UVe9oCqzoYY2oKgUi8wQQSmrR',
        ],
        Transaction::class => [
            'salt' => Transaction::class.config('app.key'),
            'length' => 20,
            'alphabet' => 'ADyQWE8mgt7jF2vbnPrKLJenHVpiUIq4M12T',
        ],
    ],
];
