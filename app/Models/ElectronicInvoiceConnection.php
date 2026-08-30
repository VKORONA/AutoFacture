<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicInvoiceConnection extends Model
{
    use HasFactory;

    public const PROVIDER_SUPERPDP = 'superpdp';

    public const STATUS_NOT_CONFIGURED = 'not_configured';

    public const STATUS_CREDENTIALS_SAVED = 'credentials_saved';

    public const STATUS_AUTHORIZATION_PENDING = 'authorization_pending';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_TOKEN_REFRESH_REQUIRED = 'token_refresh_required';

    public const STATUS_CONNECTION_LOST = 'connection_lost';

    /** @deprecated Kept for extensions compiled against the beta constants. */
    public const STATUS_DRAFT = self::STATUS_NOT_CONFIGURED;

    /** @deprecated Kept for extensions compiled against the beta constants. */
    public const STATUS_CONFIGURED = self::STATUS_CREDENTIALS_SAVED;

    /** @deprecated Kept for extensions compiled against the beta constants. */
    public const STATUS_ERROR = self::STATUS_CONNECTION_LOST;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_step' => 'integer',
            'account_created_at' => 'datetime',
            'company_verified_at' => 'datetime',
            'credentials_configured_at' => 'datetime',
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'oauth_state_expires_at' => 'datetime',
            'last_tested_at' => 'datetime',
            'last_connected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
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
