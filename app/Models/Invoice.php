<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_number', 'title', 'type', 'contact_id', 'organization_id',
        'sender_contact_id', 'sender_organization_id',
        'amount', 'vat_rate', 'currency',
        'invoice_date', 'due_date', 'status', 'notes',
        'invoice_template_id', 'accounting_id', 'debit_account_id', 'credit_account_id', 'project_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function template()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function senderContact()
    {
        return $this->belongsTo(Contact::class, 'sender_contact_id');
    }

    public function senderOrganization()
    {
        return $this->belongsTo(Organization::class, 'sender_organization_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function hasSender(): bool
    {
        return $this->sender_contact_id || $this->sender_organization_id;
    }

    public function getSenderDisplayNameAttribute(): string
    {
        if ($this->senderOrganization) {
            $name = $this->senderOrganization->primary_name;
            if ($this->senderContact) {
                $name .= ' — ' . $this->senderContact->full_name;
            }
            return $name;
        }
        if ($this->senderContact) {
            return $this->senderContact->full_name;
        }
        return '—';
    }

    public function getSenderNameAttribute(): string
    {
        if ($this->senderOrganization) {
            return $this->senderOrganization->primary_name;
        }
        if ($this->senderContact) {
            return $this->senderContact->company ?: $this->senderContact->full_name;
        }
        return '';
    }

    public function getSenderAddressAttribute(): string
    {
        $entity = $this->senderOrganization ?? $this->senderContact;
        if (!$entity) return '';
        $lines = [];
        if ($entity->street) $lines[] = $entity->street;
        if ($entity->zip || $entity->city) {
            $lines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }
        return implode("\n", $lines);
    }

    public function getSenderEmailAttribute(): ?string
    {
        return $this->senderOrganization?->email ?? $this->senderContact?->email;
    }

    public function getSenderPhoneAttribute(): ?string
    {
        return $this->senderOrganization?->phone ?? $this->senderContact?->phone;
    }

    public function getSenderCountryAttribute(): string
    {
        return $this->senderOrganization?->country ?? $this->senderContact?->country ?? 'CH';
    }

    public function getSenderIbanAttribute(): ?string
    {
        return $this->senderOrganization?->iban ?? $this->senderContact?->iban;
    }

    public function getSenderFormattedIbanAttribute(): string
    {
        $iban = $this->sender_iban;
        if (!$iban) return '';
        return trim(chunk_split($iban, 4, ' '));
    }

    public function getSenderBillingNameAttribute(): string
    {
        if ($this->senderOrganization) {
            return $this->senderOrganization->primary_name;
        }
        if ($this->senderContact) {
            return $this->senderContact->company ?: $this->senderContact->full_name;
        }
        return '';
    }

    public function getSenderBillingAddressLineAttribute(): string
    {
        $entity = $this->senderOrganization ?? $this->senderContact;
        return $entity?->street ?? '';
    }

    public function getSenderBillingZipAttribute(): string
    {
        $entity = $this->senderOrganization ?? $this->senderContact;
        return $entity?->zip ?? '';
    }

    public function getSenderBillingCityAttribute(): string
    {
        $entity = $this->senderOrganization ?? $this->senderContact;
        return $entity?->city ?? '';
    }

    public function getSenderBillingCountryAttribute(): string
    {
        $entity = $this->senderOrganization ?? $this->senderContact;
        return $entity?->country ?? 'CH';
    }

    public function recalculateAmount(): void
    {
        $subtotal = $this->items->sum(fn ($item) => $item->total);
        $vat = ($this->vat_rate && $this->vat_rate > 0) ? round($subtotal * $this->vat_rate / 100, 2) : 0;
        $this->amount = round($subtotal + $vat, 2);
        $this->saveQuietly();
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->items->sum(fn ($item) => $item->total), 2);
    }

    public function getVatAmountAttribute(): float
    {
        if (!$this->vat_rate || $this->vat_rate <= 0) return 0;
        return round($this->subtotal * $this->vat_rate / 100, 2);
    }

    public function getTotalWithVatAttribute(): float
    {
        return round($this->subtotal + $this->vat_amount, 2);
    }

    public function getRecipientDisplayNameAttribute(): string
    {
        if ($this->contact) {
            return $this->contact->full_name;
        }
        if ($this->organization) {
            return $this->organization->primary_name;
        }
        return '—';
    }

    public function getRecipientFullAddressAttribute(): string
    {
        $entity = $this->contact ?? $this->organization;
        if (!$entity) return '—';

        if ($entity instanceof Contact) {
            $lines = [$entity->full_name];
        } else {
            $lines = [$entity->primary_name];
        }

        if ($entity->street) {
            $lines[] = $entity->street;
        }
        if ($entity->zip || $entity->city) {
            $lines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }
        return implode("\n", array_filter($lines));
    }

    /**
     * Generate an invoice number.
     * Format: RE-{YEAR}-{SEQUENTIAL}
     * e.g. RE-2026-0001
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "RE-{$year}-";

        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTR(invoice_number, " . (strlen($prefix) + 1) . ") AS INTEGER) DESC")
            ->first();

        if ($lastInvoice) {
            $lastSeq = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
