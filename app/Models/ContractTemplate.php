<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContractTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'contract_type_slug', 'language', 'default_terms', 'default_status',
        'default_subject', 'default_subject_heading', 'default_relations_note', 'default_relations_heading',
        'default_sections', 'default_closing_note',
        'default_parties', 'sort_order', 'rights', 'rights_label_a', 'rights_label_b', 'rights_labels',
        'default_has_zession', 'default_zession_amount', 'default_zession_currency', 'default_zession_notes',
        'default_territory',
        'default_preamble_mode', 'default_preamble_text', 'default_show_parties_table', 'default_auto_number_sections',
        'default_project_ids', 'default_track_ids', 'default_release_ids',
        'logo_path', 'logo_in_header', 'logo_as_watermark',
    ];

    protected $casts = [
        'default_parties' => 'array',
        'default_sections' => 'array',
        'rights' => 'array',
        'rights_labels' => 'array',
        'default_has_zession' => 'boolean',
        'default_zession_amount' => 'decimal:2',
        'default_territory' => 'array',
        'default_show_parties_table' => 'boolean',
        'default_auto_number_sections' => 'boolean',
        'default_project_ids' => 'array',
        'default_track_ids' => 'array',
        'default_release_ids' => 'array',
        'logo_in_header' => 'boolean',
        'logo_as_watermark' => 'boolean',
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

    /**
     * Documents handed on to every contract created from this template.
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * The works a new contract should start out linked to.
     */
    public function defaultProjects()
    {
        return Project::whereIn('id', $this->default_project_ids ?? [])->orderBy('name')->get();
    }

    public function defaultTracks()
    {
        return Track::whereIn('id', $this->default_track_ids ?? [])->orderBy('title')->get();
    }

    public function defaultReleases()
    {
        return Release::whereIn('id', $this->default_release_ids ?? [])->orderBy('title')->get();
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
        return $type ? $type->color : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
    }
}
