<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'title_language', 'product_type', 'ean_upc', 'upc',
        'release_date', 'label', 'label_name', 'catalog_number', 'main_artist',
        'territory', 'description', 'release_info', 'release_info_internal',
        'width', 'height', 'depth', 'weight', 'price', 'currency', 'stock',
        'cover_image_path',
    ];

    protected $casts = [
        'release_date' => 'date',
        'territory' => 'array',
        'product_type' => 'array',
    ];

    public const PRODUCT_TYPES_DEFAULT = [
        'Musik-Produkte' => [
            'Album (LP)', 'EP (Extended Play)', 'Single', 'Maxi-Single',
            'Compilation / Best Of', 'Live-Album', 'Remix-Album', 'Instrumental-Album',
            'Acoustic-Album', 'Deluxe Edition', 'Reissue / Re-Release', 'Anniversary Edition',
            'CD', 'Vinyl 7"', 'Vinyl 10"', 'Vinyl 12"', 'Kassette',
            'Digital Album', 'Digital Single', 'Digital EP', 'High-Resolution Audio',
        ],
        'Video-Produkte' => [
            'Musikvideo', 'Lyric Video', 'Visualizer', 'Live-Video',
            'Behind-the-Scenes', 'Studio Session Video', 'Performance Video',
            'Konzertfilm', 'Dokumentation', 'Tourfilm', 'Making-of',
        ],
        'Merchandise: Kleidung' => [
            'T-Shirt', 'Hoodie', 'Cap', 'Jacke', 'Tour-Merch',
        ],
        'Merchandise: Accessoires' => [
            'Poster', 'Sticker', 'Button', 'Schlüsselanhänger', 'Tasche',
        ],
        'Merchandise: Premium' => [
            'Signiertes Instrument', 'Kunstprint', 'Fotobuch', 'Songbook',
            'NFT', 'Fanclub-Mitgliedschaft', 'Exklusives digitales Release',
            'Early Access Release',
        ],
    ];

    /** Topped up into the quick picks of the type field while few products exist. */
    public const COMMON_PRODUCT_TYPES = [
        'Digital Single', 'Digital EP', 'Digital Album', 'CD', 'Vinyl 12"', 'Vinyl 7"', 'Single', 'Album (LP)',
    ];

    /** Quick picks for the type field: the types used most, then common formats - only types the settings still list. */
    public static function frequentProductTypes(int $limit = 8): array
    {
        $known = Setting::productTypeValues();
        $used = static::pluck('product_type')->flatten()->filter(fn ($type) => filled($type))->countBy()->sortDesc()->keys();

        return $used->merge(self::COMMON_PRODUCT_TYPES)
            ->unique()
            ->filter(fn ($type) => in_array($type, $known, true))
            ->take($limit)
            ->values()
            ->all();
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class, 'product_track')
            ->withPivot('track_number', 'disc_number', 'role')
            ->withTimestamps()
            ->orderByPivot('disc_number')
            ->orderByPivot('track_number');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'release_contact')->withPivot('role', 'instrument');
    }

    public function artworks()
    {
        return $this->belongsToMany(Artwork::class, 'artwork_release')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryArtwork()
    {
        return $this->artworks()->wherePivot('is_primary', true)->first();
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function getProductTypeDisplayAttribute(): string
    {
        if (empty($this->product_type)) return '';
        return implode(', ', $this->product_type);
    }

    public function getTerritoryDisplayAttribute(): string
    {
        if (empty($this->territory)) return '';
        if (in_array('ALL', $this->territory)) return 'Weltweit';

        foreach (Contract::TERRITORY_PRESETS as $preset) {
            if ($preset['countries'] === ['ALL']) continue;
            $presetCountries = $preset['countries'];
            sort($presetCountries);
            $territory = $this->territory;
            sort($territory);
            if ($presetCountries === $territory) return $preset['label'];
        }

        return count($this->territory) . ' Länder';
    }

    /**
     * Main artist and label are linked organizations (band, label). The text
     * columns main_artist and label_name only hold what was typed before that,
     * for names that have no organization yet - shown until one is linked.
     */
    public function getMainArtistDisplayAttribute(): string
    {
        return $this->organizationNames('band') ?: (string) $this->main_artist;
    }

    public function getLabelDisplayAttribute(): string
    {
        return $this->organizationNames('label') ?: (string) $this->label_name;
    }

    private function organizationNames(string $type): string
    {
        return $this->organizations->where('type', $type)->map(fn ($org) => $org->primary_name)->filter()->join(', ');
    }

    /**
     * A product without a label takes the labels of its tracks, as the label
     * name text used to.
     */
    public function syncLabelFromTracks(): void
    {
        if ($this->organizations()->where('type', 'label')->exists()) {
            return;
        }

        $labelIds = $this->tracks
            ->flatMap(fn ($track) => $track->organizations->where('type', 'label'))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($labelIds) {
            $this->organizations()->syncWithoutDetaching($labelIds);
        }
    }

    /** Old text gives way once an organization of its kind is linked. */
    public function clearSupersededNames(): void
    {
        $types = $this->organizations()->pluck('type');
        $changes = [];

        if (filled($this->main_artist) && $types->contains('band')) $changes['main_artist'] = null;
        if (filled($this->label_name) && $types->contains('label')) $changes['label_name'] = null;

        if ($changes) {
            $this->update($changes);
        }
    }
}
