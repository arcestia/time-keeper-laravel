<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];

    // Simple key-value accessors
    public static function get(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();
        if (!$row) return $default;
        $val = $row->value;
        // Try JSON decode; fallback to raw string
        $decoded = json_decode($val, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
    }

    public static function set(string $key, $value): void
    {
        $payload = is_string($value) ? $value : json_encode($value);
        static::updateOrCreate(['key' => $key], ['value' => $payload]);
    }
}
