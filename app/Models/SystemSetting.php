<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'key_name',
        'key_value',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key_name', $key)->first();
        return $setting ? $setting->key_value : $default;
    }

    public static function set(string $key, $value, string $group = 'general')
    {
        return self::updateOrCreate(
            ['key_name' => $key],
            ['key_value' => $value, 'group_name' => $group]
        );
    }
}
