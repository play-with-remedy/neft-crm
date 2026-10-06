<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_TWO_FOR_ONE = 'two_for_one';

    protected $fillable = [
        'purchased_at',
        'type',
        'player_id',
        'purchaser_nickname',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'used_at' => 'date',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CERTIFICATE => 'Сертификат',
            self::TYPE_TWO_FOR_ONE => '2 по цене одного',
        ];
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Certificate $certificate): void {
            if ($certificate->player_id !== null) {
                $certificate->purchaser_nickname = Player::query()
                    ->whereKey($certificate->player_id)
                    ->value('nickname');
            }
        });
    }
}
