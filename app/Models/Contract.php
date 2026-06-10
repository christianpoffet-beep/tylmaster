<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_number', 'title', 'type', 'status', 'language', 'start_date', 'end_date', 'terms',
        'subject', 'relations_note',
        'has_zession', 'zession_amount', 'zession_currency', 'zession_notes',
        'territory', 'rights', 'rights_label_a', 'rights_label_b',
        'logo_path', 'logo_in_header', 'logo_as_watermark',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'has_zession' => 'boolean',
        'zession_amount' => 'decimal:2',
        'territory' => 'array',
        'rights' => 'array',
        'logo_in_header' => 'boolean',
        'logo_as_watermark' => 'boolean',
    ];

    /**
     * Translation strings for the contract PDF, keyed by language.
     * Falls back to German for unknown languages.
     */
    public const PDF_STRINGS = [
        'de' => [
            'subtitle' => 'Vertrag',
            'meta_type' => 'Typ',
            'meta_status' => 'Status',
            'meta_start' => 'Startdatum',
            'meta_end' => 'Enddatum',
            'status_draft' => 'Entwurf',
            'status_active' => 'Aktiv',
            'status_expired' => 'Ausgelaufen',
            'status_terminated' => 'Gekündigt',
            'parties_title' => 'Vertragsparteien',
            'parties_col_party' => 'Partei',
            'parties_col_contact' => 'Ansprechperson',
            'parties_col_share' => 'Anteil',
            'subject_title' => 'Vertragsgegenstand',
            'zession_title' => 'Zession (Vorschusszahlung)',
            'zession_note' => 'Dieser Betrag wird mit künftigen Einnahmen verrechnet.',
            'territory_title' => 'Geltungsbereich / Territory',
            'territory_worldwide' => 'Weltweit',
            'rights_title' => 'Vergütung',
            'rights_intro' => 'Die Einnahmen werden zwischen :a und :b wie folgt aufgeteilt:',
            'rights_col_type' => 'Rechtetyp',
            'rights_col_split' => 'Aufteilung',
            'relations_title' => 'Verknüpfungen',
            'relations_projects' => 'Projekte',
            'relations_tracks' => 'Tracks',
            'relations_releases' => 'Produkte',
            'relations_intro_default' => 'Folgende Songs sind Bestandteil dieses Vertrages.',
            'relations_credits' => 'Credits',
            'terms_title' => 'Bedingungen',
            'signature_line' => 'Ort, Datum, Unterschrift',
            'party_default_a' => 'Partei 1',
            'party_default_b' => 'Partei 2',
            'generated_on' => 'Generiert am',
            'phone_short' => 'Tel.',
        ],
        'en' => [
            'subtitle' => 'Contract',
            'meta_type' => 'Type',
            'meta_status' => 'Status',
            'meta_start' => 'Start date',
            'meta_end' => 'End date',
            'status_draft' => 'Draft',
            'status_active' => 'Active',
            'status_expired' => 'Expired',
            'status_terminated' => 'Terminated',
            'parties_title' => 'Contracting parties',
            'parties_col_party' => 'Party',
            'parties_col_contact' => 'Contact person',
            'parties_col_share' => 'Share',
            'subject_title' => 'Subject of contract',
            'zession_title' => 'Advance payment',
            'zession_note' => 'This amount will be offset against future revenue.',
            'territory_title' => 'Territory',
            'territory_worldwide' => 'Worldwide',
            'rights_title' => 'Remuneration',
            'rights_intro' => 'Revenue is split between :a and :b as follows:',
            'rights_col_type' => 'Type of right',
            'rights_col_split' => 'Split',
            'relations_title' => 'Linked items',
            'relations_projects' => 'Projects',
            'relations_tracks' => 'Tracks',
            'relations_releases' => 'Products',
            'relations_intro_default' => 'The following songs are part of this contract.',
            'relations_credits' => 'Credits',
            'terms_title' => 'Terms',
            'signature_line' => 'Place, date, signature',
            'party_default_a' => 'Party 1',
            'party_default_b' => 'Party 2',
            'generated_on' => 'Generated on',
            'phone_short' => 'Phone',
        ],
        'es' => [
            'subtitle' => 'Contrato',
            'meta_type' => 'Tipo',
            'meta_status' => 'Estado',
            'meta_start' => 'Fecha de inicio',
            'meta_end' => 'Fecha de finalización',
            'status_draft' => 'Borrador',
            'status_active' => 'Activo',
            'status_expired' => 'Expirado',
            'status_terminated' => 'Rescindido',
            'parties_title' => 'Partes contratantes',
            'parties_col_party' => 'Parte',
            'parties_col_contact' => 'Persona de contacto',
            'parties_col_share' => 'Participación',
            'subject_title' => 'Objeto del contrato',
            'zession_title' => 'Anticipo (pago anticipado)',
            'zession_note' => 'Este importe se compensará con los ingresos futuros.',
            'territory_title' => 'Territorio',
            'territory_worldwide' => 'Mundial',
            'rights_title' => 'Remuneración',
            'rights_intro' => 'Los ingresos se reparten entre :a y :b de la siguiente manera:',
            'rights_col_type' => 'Tipo de derecho',
            'rights_col_split' => 'Reparto',
            'relations_title' => 'Vínculos',
            'relations_projects' => 'Proyectos',
            'relations_tracks' => 'Pistas',
            'relations_releases' => 'Productos',
            'relations_intro_default' => 'Las siguientes canciones forman parte de este contrato.',
            'relations_credits' => 'Créditos',
            'terms_title' => 'Condiciones',
            'signature_line' => 'Lugar, fecha, firma',
            'party_default_a' => 'Parte 1',
            'party_default_b' => 'Parte 2',
            'generated_on' => 'Generado el',
            'phone_short' => 'Tel.',
        ],
    ];

    /**
     * Get the translation strings for this contract's language.
     */
    public static function pdfStrings(?string $language): array
    {
        return self::PDF_STRINGS[$language] ?? self::PDF_STRINGS['de'];
    }

    /**
     * Contact details of contracting party A (the first party) for the PDF header.
     * Returns null when no usable entity is available.
     */
    public function getHeaderPartyAttribute(): ?array
    {
        $party = $this->parties->first();
        if (!$party) {
            return null;
        }

        $org = $party->organization;
        $contact = $party->contact;
        $entity = $org ?? $contact;
        if (!$entity) {
            return null;
        }

        // Name + optional contact person line
        if ($org) {
            $name = $org->primary_name;
            $contactName = $contact?->full_name;
            $website = collect($org->websites ?? [])->first();
        } else {
            $name = $contact->full_name;
            $contactName = null;
            $website = null;
        }

        $addressLines = [];
        if ($entity->street) {
            $addressLines[] = $entity->street;
        }
        if ($entity->zip || $entity->city) {
            $addressLines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }

        return [
            'name' => $name,
            'contact_name' => $contactName,
            'address_lines' => $addressLines,
            'website' => $website ?: null,
            'phone' => $entity->phone ?: null,
            'email' => $entity->email ?: null,
        ];
    }

    /**
     * Territory presets for quick selection.
     */
    public const TERRITORY_PRESETS = [
        'world' => ['label' => 'Weltweit', 'countries' => ['ALL']],
        'europe' => ['label' => 'Europa', 'countries' => ['AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','XK','LV','LI','LT','LU','MT','MD','MC','ME','NL','MK','NO','PL','PT','RO','RU','SM','RS','SK','SI','ES','SE','CH','UA','GB','VA']],
        'usa' => ['label' => 'USA', 'countries' => ['US']],
        'gsa' => ['label' => 'GSA (DACH)', 'countries' => ['DE','AT','CH']],
        'uk' => ['label' => 'UK', 'countries' => ['GB']],
        'nordics' => ['label' => 'Nordics', 'countries' => ['DK','FI','IS','NO','SE']],
        'benelux' => ['label' => 'Benelux', 'countries' => ['BE','NL','LU']],
    ];

    /**
     * Get human-readable territory display.
     */
    public function getTerritoryDisplayAttribute(): string
    {
        if (empty($this->territory)) {
            return '';
        }

        if (in_array('ALL', $this->territory)) {
            return 'Weltweit';
        }

        // Check if it matches a preset
        foreach (self::TERRITORY_PRESETS as $key => $preset) {
            if ($preset['countries'] === ['ALL']) continue;
            $presetCountries = $preset['countries'];
            sort($presetCountries);
            $territory = $this->territory;
            sort($territory);
            if ($presetCountries === $territory) {
                return $preset['label'];
            }
        }

        return count($this->territory) . ' Länder';
    }

    public function parties()
    {
        return $this->hasMany(ContractParty::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contract')->withPivot('role');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "VT-{$year}-";

        $last = static::where('contract_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTR(contract_number, " . (strlen($prefix) + 1) . ") AS INTEGER) DESC")
            ->first();

        if ($last) {
            $nextSeq = (int) substr($last->contract_number, strlen($prefix)) + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_contract');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }
}
