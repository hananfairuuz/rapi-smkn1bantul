<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Token API Mobile (Tabel 'api_tokens')
 *
 * Menyimpan token autentikasi mobile Flutter:
 * - user_id   : Pemilik sesi mobile.
 * - token     : Hash SHA-256 dari token sesi.
 * - expires_at: Masa berlaku token (30 hari).
 */
class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke User pemilik token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
