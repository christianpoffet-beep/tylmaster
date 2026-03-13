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
            AddressCircle::class => 'Adresskreis',
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

    /**
     * Get the human-readable display name for a field.
     */
    public function getFieldLabelAttribute(): ?string
    {
        if (!$this->field) {
            return null;
        }

        return static::FIELD_LABELS[$this->field] ?? Str::headline(str_replace('_', ' ', $this->field));
    }

    /**
     * Mapping of database field names to German display names.
     */
    const FIELD_LABELS = [
        // Allgemein
        'name' => 'Name',
        'title' => 'Titel',
        'description' => 'Beschreibung',
        'notes' => 'Notizen',
        'status' => 'Status',
        'type' => 'Typ',
        'type_id' => 'Typ',
        'color' => 'Farbe',
        'slug' => 'Slug',
        'sort_order' => 'Sortierung',
        'is_active' => 'Aktiv',
        'is_archived' => 'Archiviert',

        // Kontakte
        'ref_nr' => 'Referenz-Nr.',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'mobile' => 'Mobil',
        'website' => 'Website',
        'gender' => 'Geschlecht',
        'salutation' => 'Anrede',
        'date_of_birth' => 'Geburtsdatum',
        'company' => 'Firma',
        'position' => 'Position',
        'department' => 'Abteilung',

        // Adressen
        'street' => 'Strasse',
        'street_number' => 'Hausnummer',
        'address' => 'Adresse',
        'address_line_2' => 'Adresszusatz',
        'zip' => 'PLZ',
        'city' => 'Ort',
        'state' => 'Kanton/Bundesland',
        'country' => 'Land',
        'country_code' => 'Ländercode',

        // Musik / ISRC
        'isrc' => 'ISRC',
        'upc' => 'UPC',
        'iswc' => 'ISWC',
        'bpm' => 'BPM',
        'key' => 'Tonart',
        'duration' => 'Dauer',
        'duration_seconds' => 'Dauer (Sek.)',
        'release_date' => 'Veröffentlichungsdatum',
        'genre' => 'Genre',
        'genre_id' => 'Genre',
        'artist' => 'Künstler',
        'artist_name' => 'Künstlername',
        'album' => 'Album',
        'label' => 'Label',
        'publisher' => 'Verlag',
        'audio_file' => 'Audiodatei',
        'cover_image' => 'Cover',

        // IPI / Verwertung
        'ipi_name_number' => 'IPI Name Nr.',
        'ipi_base_number' => 'IPI Basis Nr.',

        // Verträge
        'contract_number' => 'Vertragsnummer',
        'contract_type_id' => 'Vertragstyp',
        'contract_template_id' => 'Vertragsvorlage',
        'start_date' => 'Startdatum',
        'end_date' => 'Enddatum',
        'signed_date' => 'Unterschriftsdatum',
        'termination_date' => 'Kündigungsdatum',
        'auto_renew' => 'Auto-Verlängerung',
        'renewal_period' => 'Verlängerungszeitraum',
        'notice_period' => 'Kündigungsfrist',
        'territory' => 'Territorium',
        'zession' => 'Zession',
        'rights_json' => 'Rechte',
        'remuneration_json' => 'Vergütung',

        // Rechnungen / Finanzen
        'invoice_number' => 'Rechnungsnummer',
        'invoice_date' => 'Rechnungsdatum',
        'due_date' => 'Fälligkeitsdatum',
        'amount' => 'Betrag',
        'total' => 'Total',
        'subtotal' => 'Zwischensumme',
        'tax' => 'MwSt.',
        'tax_rate' => 'MwSt.-Satz',
        'vat_rate' => 'MwSt.-Satz',
        'vat_number' => 'MwSt.-Nr.',
        'currency' => 'Währung',
        'payment_terms' => 'Zahlungsbedingungen',
        'paid_at' => 'Bezahlt am',
        'paid_date' => 'Bezahlt am',
        'price' => 'Preis',
        'unit_price' => 'Einzelpreis',
        'quantity' => 'Menge',
        'discount' => 'Rabatt',
        'category' => 'Kategorie',

        // Ausgaben
        'expense_date' => 'Ausgabedatum',
        'vendor' => 'Lieferant',
        'receipt_file' => 'Beleg',

        // Projekte
        'project_type_id' => 'Projekttyp',
        'priority' => 'Priorität',
        'deadline' => 'Deadline',
        'completed_at' => 'Abgeschlossen am',
        'assigned_to' => 'Zugewiesen an',

        // Aufgaben
        'is_completed' => 'Erledigt',
        'due_at' => 'Fällig am',
        'project_id' => 'Projekt',

        // Dokumente
        'file_path' => 'Dateipfad',
        'file_name' => 'Dateiname',
        'file_size' => 'Dateigrösse',
        'mime_type' => 'Dateityp',
        'documentable_type' => 'Verknüpft mit (Typ)',
        'documentable_id' => 'Verknüpft mit (ID)',

        // Vorlagen
        'template_id' => 'Vorlage',
        'body' => 'Inhalt',
        'header' => 'Kopfzeile',
        'footer' => 'Fusszeile',
        'logo_path' => 'Logo',
        'use_avatar_as_logo' => 'Avatar als Logo',
        'sender_name' => 'Absendername',
        'sender_address' => 'Absenderadresse',
        'recipient_name' => 'Empfängername',
        'recipient_address' => 'Empfängeradresse',

        // Bank
        'bank_name' => 'Bank',
        'bank_iban' => 'IBAN',
        'bank_bic' => 'BIC',
        'bank_account_holder' => 'Kontoinhaber',

        // Buchhaltung
        'account_id' => 'Konto',
        'debit_account_id' => 'Soll-Konto',
        'credit_account_id' => 'Haben-Konto',
        'booking_date' => 'Buchungsdatum',
        'accounting_id' => 'Buchhaltung',
        'invoice_id' => 'Rechnung',
        'number' => 'Nummer',
        'code' => 'Code',
        'fiscal_year' => 'Geschäftsjahr',

        // Kontakt/Organisation-Beziehungen
        'contact_id' => 'Kontakt',
        'organization_id' => 'Organisation',
        'role' => 'Rolle',

        // Fotos
        'folder_id' => 'Ordner',
        'caption' => 'Beschriftung',
        'photographer' => 'Fotograf',
        'taken_at' => 'Aufgenommen am',

        // Adresskreis
        'email_override' => 'E-Mail-Überschreibung',

        // Submissions
        'artist_email' => 'Künstler-E-Mail',
        'track_title' => 'Tracktitel',
        'submission_date' => 'Einreichungsdatum',
        'reviewed_at' => 'Geprüft am',
        'reviewer_notes' => 'Prüfnotizen',
    ];

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
