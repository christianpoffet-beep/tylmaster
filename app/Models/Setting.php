<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get credit roles (from DB or fallback to Track constant).
     */
    public static function creditRoles(): array
    {
        return static::get('credit_roles', \App\Models\Track::CREDIT_ROLES);
    }

    /**
     * Get instruments (from DB or fallback to Track constant).
     */
    public static function instruments(): array
    {
        return static::get('instruments', \App\Models\Track::INSTRUMENTS);
    }

    public static function productTypes(): array
    {
        return static::get('product_types', \App\Models\Release::PRODUCT_TYPES_DEFAULT);
    }

    /**
     * Get flat list of all product type values (for validation).
     */
    public static function productTypeValues(): array
    {
        $types = static::productTypes();
        $values = [];
        foreach ($types as $items) {
            foreach ($items as $item) {
                $values[] = $item;
            }
        }
        return $values;
    }
}
