<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    protected $fillable = ['number', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $t) => $t->token ??= self::newToken());
    }

    /** Token acak untuk URL QR; diganti bila QR lama perlu dimatikan. */
    public static function newToken(): string
    {
        do {
            $token = Str::random(16);
        } while (self::where('token', $token)->exists());
        return $token;
    }

    public function regenerateToken(): void
    {
        $this->token = self::newToken();
        $this->save();
    }

    public function orderUrl(): string
    {
        return route('qr.menu', $this->token);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
