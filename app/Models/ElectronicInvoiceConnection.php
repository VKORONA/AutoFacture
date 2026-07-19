<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicInvoiceConnection extends Model
{
    use HasFactory;

    public const PROVIDER_SUPERPDP = 'superpdp';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIGURED = 'configured';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_ERROR = 'error';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_created_at' => 'datetime',
            'company_verified_at' => 'datetime',
            'credentials_configured_at' => 'datetime',
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_tested_at' => 'datetime',
            'last_connected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function hasCredentials(): bool
    {
        return filled($this->client_id) && filled($this->client_secret);
    }

    public function maskedClientId(): ?string
    {
        if (! filled($this->client_id)) {
            return null;
        }

        $value = (string) $this->client_id;
        $visible = mb_substr($value, -4);

        return str_repeat('•', max(4, mb_strlen($value) - 4)).$visible;
    }
}
