<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'version', 'isrc', 'genre', 'duration_seconds', 'status',
        'composers', 'producers', 'language', 'description',
        'bpm', 'musical_key',
        'audio_file_path', 'bitrate', 'sample_rate', 'audio_format', 'channels',
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
        return $this->belongsToMany(Contact::class, 'track_contact')->withPivot('role', 'instrument');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
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

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) return '--:--';
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getIsrcFormattedAttribute(): ?string
    {
        if (!$this->isrc) return null;
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $this->isrc));
        if (strlen($clean) !== 12) return $this->isrc;
        return substr($clean, 0, 2) . '-' . substr($clean, 2, 3) . '-' . substr($clean, 5, 2) . '-' . substr($clean, 7, 5);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->version ? "{$this->title} ({$this->version})" : $this->title;
    }
}
