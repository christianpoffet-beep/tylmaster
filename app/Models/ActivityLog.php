<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'model_type', 'model_id',
        'model_label', 'action', 'field', 'old_value', 'new_value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getModelTypeLabelAttribute(): string
    {
        return match ($this->model_type) {
            Contact::class => 'Kontakt',
            Organization::class => 'Organisation',
            Contract::class => 'Vertrag',
            ContractParty::class => 'Vertragspartei',
            ContractTemplate::class => 'Vertragsvorlage',
            ContractType::class => 'Vertragstyp',
            Document::class => 'Dokument',
            Invoice::class => 'Rechnung',
            InvoiceItem::class => 'Rechnungsposition',
            InvoiceTemplate::class => 'Rechnungsvorlage',
            InvoiceTemplateItem::class => 'Vorlagenposition',
            Expense::class => 'Ausgabe',
            Task::class => 'Aufgabe',
            Track::class => 'Track',
            Release::class => 'Release',
            Project::class => 'Projekt',
            MusicSubmission::class => 'Submission',
            Artwork::class => 'Artwork',
            ArtworkLogo::class => 'Logo',
            ArtworkCredit::class => 'Credit',
            Photo::class => 'Foto',
            PhotoFolder::class => 'Fotoordner',
            Booking::class => 'Buchung',
            Account::class => 'Konto',
            Accounting::class => 'Buchhaltung',
            Genre::class => 'Genre',
            Tag::class => 'Tag',
            ContactType::class => 'Kontakttyp',
            OrganizationType::class => 'Org. Typ',
            ProjectType::class => 'Projekttyp',
            ChartTemplate::class => 'Kontoplan-Vorlage',
            ChartTemplateAccount::class => 'Vorlagenkonto',
            default => class_basename($this->model_type),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'bg-green-100 text-green-700',
            'updated' => 'bg-blue-100 text-blue-700',
            'deleted' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Erstellt',
            'updated' => 'Geändert',
            'deleted' => 'Gelöscht',
            default => $this->action,
        };
    }
}
