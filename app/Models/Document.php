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
            Contact::class => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300',
            Contract::class => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
            Task::class => 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300',
            Track::class => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
            Project::class => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300',
            Organization::class => 'bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300',
            Artwork::class => 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300',
            ArtworkLogo::class => 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300',
            Photo::class => 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
            Booking::class => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
            Invoice::class => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        };
    }

    public function getFileExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }
}
