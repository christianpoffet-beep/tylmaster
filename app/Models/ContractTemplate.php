<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContractTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'contract_type_slug', 'default_terms', 'default_status',
        'default_parties', 'sort_order', 'rights', 'rights_label_a', 'rights_label_b',
    ];

    protected $casts = [
        'default_parties' => 'array',
        'rights' => 'array',
    ];

    /**
     * Common rights presets for music contracts.
     */
    public const RIGHTS_PRESETS = [
        ['label' => 'Mechanische Rechte', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
        ['label' => 'Aufführungsrechte', 'mode' => 'custom', 'split_a' => null, 'split_b' => null, 'custom_text' => 'gemäss Verteilung der Verwertungsgesellschaft (SUISA)'],
        ['label' => 'Synchronisationsrechte', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
        ['label' => 'Digitale Rechte', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
        ['label' => 'Druckrechte (Print)', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
        ['label' => 'Nebenrechte', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
        ['label' => 'Sonstige Einnahmen', 'mode' => 'split', 'split_a' => 50, 'split_b' => 50, 'custom_text' => ''],
    ];

    protected static function booted(): void
    {
        static::creating(function (ContractTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        static::updating(function (ContractTemplate $template) {
            if ($template->isDirty('name') && !$template->isDirty('slug')) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function contractType()
    {
        return ContractType::where('slug', $this->contract_type_slug)->first();
    }

    public function getTypeLabelAttribute(): string
    {
        $type = ContractType::where('slug', $this->contract_type_slug)->first();
        return $type ? $type->name : ucfirst($this->contract_type_slug);
    }

    public function getTypeColorAttribute(): string
    {
        $type = ContractType::where('slug', $this->contract_type_slug)->first();
        return $type ? $type->color : 'bg-gray-100 text-gray-600';
    }
}
