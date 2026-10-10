<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Model;

/**
 * The Kashier account every event's payments go to. There is only ever one row.
 */
class PaymentSetting extends Model
{
    protected $fillable = ['mode', 'merchant_id', 'api_key', 'secret_key'];

    /**
     * The keys never leave the server: kept out of arrays and JSON, and encrypted at rest.
     *
     * @var list<string>
     */
    protected $hidden = ['api_key', 'secret_key'];

    protected function casts(): array
    {
        return [
            'mode' => PaymentMode::class,
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
        ];
    }

    /**
     * The saved settings, or an unsaved blank set in test mode when nobody has filled them in.
     */
    public static function current(): self
    {
        return self::query()->first() ?? new self(['mode' => PaymentMode::Test]);
    }

    public function isComplete(): bool
    {
        return filled($this->merchant_id) && filled($this->api_key) && filled($this->secret_key);
    }
}
