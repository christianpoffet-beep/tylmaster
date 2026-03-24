<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Send password reset notification via Brevo API (bypasses SMTP).
     */
    public function sendPasswordResetNotification($token): void
    {
        $brevo = app(\App\Services\BrevoService::class);

        if ($brevo->isConfigured()) {
            $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $this->email], false));

            $brevo->sendEmail(
                $this->email,
                $this->name ?? $this->email,
                'Passwort zurücksetzen – TYL Admin',
                '<p>Hallo,</p>'
                . '<p>Du erhältst diese E-Mail, weil eine Passwort-Zurücksetzung für dein Konto angefordert wurde.</p>'
                . '<p><a href="' . $resetUrl . '" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">Passwort zurücksetzen</a></p>'
                . '<p>Dieser Link ist 60 Minuten gültig.</p>'
                . '<p>Falls du keine Zurücksetzung angefordert hast, kannst du diese E-Mail ignorieren.</p>'
                . '<p>– The Yelling Light</p>'
            );
        } else {
            // Fallback to default Laravel notification (SMTP)
            parent::sendPasswordResetNotification($token);
        }
    }
}
