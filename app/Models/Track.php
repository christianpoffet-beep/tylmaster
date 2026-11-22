<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use LogsActivity;

    /*
     * The columns audio_file_path, bitrate, sample_rate, audio_format and
     * channels still exist on the table but are no longer used: the audio now
     * lives in track_audio_files, one row per version (see audioFiles()).
     */
    protected $fillable = [
        'title', 'version', 'alternative_titles', 'isrc', 'genre', 'duration_seconds', 'status',
        'composers', 'producers', 'language', 'description',
        'bpm', 'musical_key', 'recording_location', 'recording_years',
    ];

    protected $casts = [
        // json:unicode keeps Umlaute as themselves, so a LIKE over the raw
        // column still finds them.
        'alternative_titles' => 'json:unicode',
    ];

    public const MUSICAL_KEYS = [
        'C', 'Cm', 'C#', 'C#m', 'Db', 'Dbm',
        'D', 'Dm', 'D#', 'D#m', 'Eb', 'Ebm',
        'E', 'Em', 'F', 'Fm', 'F#', 'F#m',
        'Gb', 'Gbm', 'G', 'Gm', 'G#', 'G#m',
        'Ab', 'Abm', 'A', 'Am', 'A#', 'A#m',
        'Bb', 'Bbm', 'B', 'Bm',
    ];

    public const ISRC_REGEX = '/^[A-Z]{2}-?[A-Z0-9]{3}-?\d{2}-?\d{5}$/i';

    /** A single year (2026) or a span (2024 - 2026), several of them comma separated. */
    public const RECORDING_YEARS_REGEX = '/^\d{4}(\s*[-\x{2013}\x{2014}]\s*\d{4})?(\s*,\s*\d{4}(\s*[-\x{2013}\x{2014}]\s*\d{4})?)*$/u';

    public const CREDIT_ROLES = [
        'Songwriting & Komposition' => [
            'composer' => 'Komponist',
            'lyricist' => 'Texter / Lyricist',
            'arranger' => 'Arrangeur',
            'orchestrator' => 'Orchestrator',
        ],
        'Musikalische Beiträge' => [
            'lead_vocals' => 'Lead Vocals',
            'background_vocals' => 'Background Vocals',
            'instrumentalist' => 'Instrumentalist',
            'choir' => 'Chor / Ensemble',
            'conductor' => 'Dirigent',
        ],
        'Produktion' => [
            'producer' => 'Produzent',
            'co_producer' => 'Co-Produzent',
            'associate_producer' => 'Associate Producer',
            'vocal_producer' => 'Vocal Producer',
            'additional_producer' => 'Additional Producer',
            'beat_producer' => 'Beat Producer',
            'remix_producer' => 'Remix Producer',
        ],
        'Aufnahme' => [
            'recording_engineer' => 'Recording Engineer',
            'assistant_recording_engineer' => 'Assistant Recording Engineer',
            'vocal_engineer' => 'Vocal Engineer',
            'instrument_engineer' => 'Instrument Engineer',
            'studio_technician' => 'Studio Technician',
        ],
        'Editing' => [
            'audio_editor' => 'Audio Editor',
            'vocal_editor' => 'Vocal Editor',
            'drum_editor' => 'Drum Editor',
            'comping_engineer' => 'Comping Engineer',
            'tuning_engineer' => 'Tuning Engineer',
        ],
        'Mixing' => [
            'mixing_engineer' => 'Mixing Engineer',
            'assistant_mixing_engineer' => 'Assistant Mixing Engineer',
            'mix_consultant' => 'Mix Consultant',
            'mix_revision_engineer' => 'Mix Revision Engineer',
        ],
        'Mastering' => [
            'mastering_engineer' => 'Mastering Engineer',
            'assistant_mastering_engineer' => 'Assistant Mastering Engineer',
            'mastering_studio' => 'Mastering Studio',
        ],
        'Performance' => [
            'main_artist' => 'Hauptkünstler',
            'featured_artist' => 'Featured Artist',
            'guest_musician' => 'Gastmusiker',
            'narrator' => 'Sprecher / Narrator',
        ],
    ];

    public function releases()
    {
        return $this->belongsToMany(Release::class, 'product_track')
            ->withPivot('track_number', 'disc_number', 'role')
            ->withTimestamps();
    }

    /** All audio versions, main version first. */
    public function audioFiles()
    {
        return $this->hasMany(TrackAudioFile::class)
            ->chaperone()
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** The version that lists, products and the player use for this track. */
    public function mainAudioFile()
    {
        return $this->hasOne(TrackAudioFile::class)->chaperone()->where('is_main', true);
    }

    public const INSTRUMENTS = [
        'Saiteninstrumente' => [
            'Akustische Gitarre', 'E-Gitarre', 'Bassgitarre', 'Kontrabass', 'Ukulele',
            'Banjo', 'Mandoline', 'Sitar', 'Laute', 'Harfe', 'Zither',
            'Violine', 'Viola', 'Cello', 'Pedal Steel Guitar', '12-String Gitarre',
        ],
        'Tasteninstrumente' => [
            'Klavier', 'E-Piano', 'Keyboard', 'Synthesizer', 'Orgel',
            'Hammondorgel', 'Akkordeon', 'Cembalo', 'Melodica', 'Celesta',
        ],
        'Schlaginstrumente' => [
            'Drums', 'Percussion', 'Cajon', 'Congas', 'Bongos',
            'Timbales', 'Djembe', 'Marimba', 'Xylophon', 'Vibraphon',
            'Glockenspiel', 'Timpani', 'Tambourin', 'Shaker', 'Cowbell',
            'Triangle', 'Drum Machine',
        ],
        'Blasinstrumente' => [
            'Trompete', 'Posaune', 'Saxophon', 'Alt-Saxophon', 'Tenor-Saxophon',
            'Bariton-Saxophon', 'Klarinette', 'Oboe', 'Fagott', 'Flöte',
            'Querflöte', 'Piccolo', 'Tuba', 'Waldhorn', 'Euphonium',
            'Mundharmonika', 'Panflöte', 'Blockflöte', 'Dudelsack', 'Didgeridoo',
        ],
        'Elektronisch' => [
            'Synthesizer', 'Drum Machine', 'Sampler', 'Turntables',
            'Sequencer', 'Vocoder', 'Theremin', 'Modular Synth',
            'Groovebox', 'Loop Station',
        ],
        'Andere' => [
            'Gesang', 'Beatbox', 'Handclaps', 'Stimmgabel', 'Kalimba',
            'Steeldrum', 'Hang Drum', 'Drehleier', 'Erhu', 'Koto',
            'Gamelan', 'Tabla', 'Bouzouki', 'Charango',
        ],
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'track_contact')->withPivot('role', 'instrument', 'ipi_number');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    /** Usage reported by distributors (Rebeat), see the Rebeat-Matcher */
    public function royaltyLines()
    {
        return $this->hasMany(RoyaltyLine::class);
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

    /** What the haystack reads; load these up front when building it for many tracks. */
    public const SEARCH_RELATIONS = ['organizations', 'contacts', 'releases', 'projects', 'audioFiles'];

    /**
     * Everything a search matches against, lowercased once: titles and
     * alternative titles, the ISRCs with and without dashes (also those of
     * every audio version, with their titles and remarks), the linked bands,
     * labels and publishers under all their names, the credited people,
     * products and projects, and the descriptive fields.
     *
     * Built in PHP rather than matched in SQL: organization names sit in a JSON
     * column that escapes Umlaute, so a LIKE would miss "Züri".
     */
    public function getSearchHaystackAttribute(): string
    {
        $parts = array_merge(
            [
                $this->display_title, $this->title, $this->isrc, $this->isrc_formatted,
                $this->genre, $this->composers, $this->producers,
                $this->recording_location, $this->recording_years, $this->description,
            ],
            $this->alternative_titles ?? [],
            $this->organizations->flatMap(fn ($o) => $o->names ?? [])->all(),
            $this->contacts->map(fn ($c) => $c->full_name)->all(),
            $this->releases->pluck('title')->all(),
            $this->projects->pluck('name')->all(),
            $this->audioFiles
                ->flatMap(fn ($a) => [$a->title, $a->isrc, $a->isrc_formatted, $a->notes, $a->original_name])
                ->all(),
        );

        $parts = array_filter($parts, fn ($part) => $part !== null && $part !== '');

        // One field per line, so a quoted phrase does not match across two of them.
        return mb_strtolower(implode("\n", $parts));
    }

    /**
     * Splits a search into terms: words, or "several words" in quotes.
     * Every term has to turn up somewhere in the track for it to match.
     */
    public static function searchTerms(?string $search): array
    {
        preg_match_all('/"([^"]+)"|(\S+)/u', (string) $search, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn ($m) => ($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? ''))
            ->map(fn ($term) => mb_strtolower(trim($term, " \t\""))) // a stray quote is not part of the word
            ->filter(fn ($term) => $term !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** IDs of the tracks in which every term turns up somewhere. */
    public static function idsMatchingSearch(array $terms): array
    {
        return static::with(static::SEARCH_RELATIONS)->get()
            ->filter(function (Track $track) use ($terms) {
                $haystack = $track->search_haystack;

                foreach ($terms as $term) {
                    if (!str_contains($haystack, $term)) {
                        return false;
                    }
                }

                return true;
            })
            ->modelKeys();
    }

    /**
     * The years an entry covers: "2024 - 2026" becomes [2024, 2025, 2026]. That
     * way a filter for a single year also finds the spans that contain it.
     */
    public static function expandRecordingYears(?string $value): array
    {
        $years = [];

        foreach (explode(',', (string) $value) as $part) {
            if (!preg_match_all('/\d{4}/', $part, $matches)) {
                continue;
            }

            $from = (int) $matches[0][0];
            $to = (int) ($matches[0][1] ?? $from);
            if ($to < $from) {
                [$from, $to] = [$to, $from];
            }

            for ($year = $from; $year <= $to; $year++) {
                $years[] = $year;
            }
        }

        sort($years);

        return array_values(array_unique($years));
    }

    /** The alternative titles as one line, for lists and detail pages. */
    public function getAlternativeTitlesListAttribute(): string
    {
        return implode(', ', $this->alternative_titles ?? []);
    }

    /** Band names for display next to the track title. */
    public function getBandNamesAttribute(): string
    {
        return $this->organizations->where('type', 'band')->map(fn ($o) => $o->primary_name)->join(', ');
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) return '--:--';
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getIsrcFormattedAttribute(): ?string
    {
        return static::formatIsrc($this->isrc);
    }

    /** How an ISRC is stored: uppercase, without dashes. */
    public static function normalizeIsrc(?string $isrc): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $isrc));

        return $clean !== '' ? $clean : null;
    }

    /** CHTYL2600001 becomes CH-TYL-26-00001; anything that is not 12 characters stays as it is. */
    public static function formatIsrc(?string $isrc): ?string
    {
        if (!$isrc) return null;
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $isrc));
        if (strlen($clean) !== 12) return $isrc;
        return substr($clean, 0, 2) . '-' . substr($clean, 2, 3) . '-' . substr($clean, 5, 2) . '-' . substr($clean, 7, 5);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->version ? "{$this->title} ({$this->version})" : $this->title;
    }
}
