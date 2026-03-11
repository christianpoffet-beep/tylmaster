<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvoiceTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'contact_id', 'organization_id',
        'recipient_contact_id', 'recipient_organization_id',
        'logo_path', 'use_avatar_as_logo', 'vat_rate',
        'footer_text', 'payment_terms_days',
    ];

    protected $casts = [
        'use_avatar_as_logo' => 'boolean',
        'vat_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function recipientContact()
    {
        return $this->belongsTo(Contact::class, 'recipient_contact_id');
    }

    public function recipientOrganization()
    {
        return $this->belongsTo(Organization::class, 'recipient_organization_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceTemplateItem::class)->orderBy('sort_order');
    }

    public function getUsageCountAttribute(): int
    {
        return $this->invoices()->count();
    }

    // --- Logo accessor ---

    public function getEffectiveLogoPathAttribute(): ?string
    {
        if ($this->use_avatar_as_logo) {
            return $this->organization?->avatar_path ?? $this->contact?->avatar_path;
        }
        return $this->logo_path;
    }

    // --- Sender accessors (for PDF header) ---

    public function getSenderNameAttribute(): string
    {
        if ($this->organization) {
            return $this->organization->primary_name;
        }
        if ($this->contact) {
            return $this->contact->company ?: $this->contact->full_name;
        }
        return $this->name;
    }

    public function getSenderAddressAttribute(): string
    {
        $entity = $this->organization ?? $this->contact;
        if (!$entity) return '';
        $lines = [];
        if ($entity->street) $lines[] = $entity->street;
        if ($entity->zip || $entity->city) $lines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        return implode("\n", $lines);
    }

    public function getSenderEmailAttribute(): ?string
    {
        return $this->organization?->email ?? $this->contact?->email;
    }

    public function getSenderPhoneAttribute(): ?string
    {
        return $this->organization?->phone ?? $this->contact?->phone;
    }

    public function getSenderCountryAttribute(): string
    {
        return $this->organization?->country ?? $this->contact?->country ?? 'CH';
    }

    // --- Bank details from linked org/contact ---

    public function getIbanAttribute(): ?string
    {
        return $this->organization?->iban ?? $this->contact?->iban;
    }

    public function getFormattedIbanAttribute(): string
    {
        $iban = $this->iban;
        if (!$iban) return '';
        return trim(chunk_split($iban, 4, ' '));
    }

    public function getMaskedIbanAttribute(): string
    {
        $iban = $this->iban;
        if (!$iban) return '—';
        if (strlen($iban) > 8) {
            return substr($iban, 0, 4) . ' **** ' . substr($iban, -4);
        }
        return $iban;
    }

    public function getBankNameAttribute(): ?string
    {
        return $this->organization?->bank_name ?? $this->contact?->bank_name;
    }

    public function getBicAttribute(): ?string
    {
        return $this->organization?->bic ?? $this->contact?->bic;
    }

    // --- VAT from organization ---

    public function getVatNumberDisplayAttribute(): ?string
    {
        return $this->organization?->vat_number;
    }

    // --- Billing accessors (for QR-Bill creditor) ---

    public function getBillingNameAttribute(): string
    {
        if ($this->organization) {
            return $this->organization->primary_name;
        }
        if ($this->contact) {
            return $this->contact->company ?: $this->contact->full_name;
        }
        return $this->name;
    }

    public function getBillingAddressLineAttribute(): string
    {
        $entity = $this->organization ?? $this->contact;
        return $entity?->street ?? '';
    }

    public function getBillingZipAttribute(): string
    {
        $entity = $this->organization ?? $this->contact;
        return $entity?->zip ?? '';
    }

    public function getBillingCityAttribute(): string
    {
        $entity = $this->organization ?? $this->contact;
        return $entity?->city ?? '';
    }

    public function getBillingCountryAttribute(): string
    {
        $entity = $this->organization ?? $this->contact;
        return $entity?->country ?? 'CH';
    }
}
