<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'title', 'category', 'file_path', 'file_size', 'mime_type', 'notes',
        'documentable_id', 'documentable_type', 'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->documentable_type) {
            Contact::class => 'Kontakt',
            Contract::class => 'Vertrag',
            Task::class => 'Aufgabe',
            Track::class => 'Track',
            Project::class => 'Projekt',
            Organization::class => 'Organisation',
            Artwork::class => 'Artwork',
            ArtworkLogo::class => 'Logo',
            Photo::class => 'Foto',
            Booking::class => 'Buchung',
            Invoice::class => 'Rechnung',
            default => 'Allgemein',
        };
    }

    public function getSourceColorAttribute(): string
    {
        return match ($this->documentable_type) {
            Contact::class => 'bg-purple-100 text-purple-700',
            Contract::class => 'bg-blue-100 text-blue-700',
            Task::class => 'bg-orange-100 text-orange-700',
            Track::class => 'bg-green-100 text-green-700',
            Project::class => 'bg-indigo-100 text-indigo-700',
            Organization::class => 'bg-teal-100 text-teal-700',
            Artwork::class => 'bg-rose-100 text-rose-700',
            ArtworkLogo::class => 'bg-rose-100 text-rose-700',
            Photo::class => 'bg-amber-100 text-amber-700',
            Booking::class => 'bg-emerald-100 text-emerald-700',
            Invoice::class => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getFileExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }
}
