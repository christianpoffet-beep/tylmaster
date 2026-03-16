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
     * Auto-fill label_name from linked tracks' label organizations.
     */
    public function syncLabelFromTracks(): void
    {
        $labels = collect();
        foreach ($this->tracks as $track) {
            $trackLabels = $track->organizations->where('type', 'label');
            $labels = $labels->merge($trackLabels);
        }
        $labelNames = $labels->unique('id')->pluck('primary_name')->filter()->implode(', ');
        if ($labelNames) {
            $this->update(['label_name' => $labelNames]);
        }
    }
}
