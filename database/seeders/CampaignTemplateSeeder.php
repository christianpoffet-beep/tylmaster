<?php

namespace Database\Seeders;

use App\Models\CampaignTemplate;
use Illuminate\Database\Seeder;

class CampaignTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // --- DEUTSCH ---
            [
                'name' => 'Neue Veröffentlichung (DE)',
                'type' => 'email',
                'language' => 'de',
                'sort_order' => 1,
                'subject' => 'Neue Veröffentlichung: {name} – Jetzt reinhören',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 5px;">NEUE VERÖFFENTLICHUNG</p>
        <h2 style="color:#fff;margin:0 0 20px;font-size:22px;">[Titel / Künstler hier einfügen]</h2>
        <p style="line-height:1.6;color:#ccc;">Hallo {name},</p>
        <p style="line-height:1.6;color:#ccc;">Wir freuen uns, eine neue Veröffentlichung aus unserem Katalog anzukündigen. Ab sofort auf allen Plattformen verfügbar.</p>
        <div style="margin:25px 0;text-align:center;">
            <a href="#" style="display:inline-block;background-color:#c0392b;color:#fff;text-decoration:none;padding:12px 30px;font-size:14px;font-weight:bold;letter-spacing:1px;">JETZT REINHÖREN</a>
        </div>
        <p style="line-height:1.6;color:#ccc;">Wir würden uns über Unterstützung freuen — ob als Playlist-Aufnahme, Rezension oder einfach durchs Teilen mit eurem Netzwerk.</p>
        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Beste Grüsse,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zürich, Schweiz</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],
            [
                'name' => 'Konzert-Einladung (DE)',
                'type' => 'email',
                'language' => 'de',
                'sort_order' => 2,
                'subject' => 'Live: {name} – Konzertankündigung',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 5px;">LIVE ON STAGE</p>
        <h2 style="color:#fff;margin:0 0 20px;font-size:22px;">[Bandname] · [Venue] · [Datum]</h2>
        <p style="line-height:1.6;color:#ccc;">Hallo {name},</p>
        <p style="line-height:1.6;color:#ccc;">Wir laden euch herzlich zu folgendem Konzert ein:</p>
        <div style="background-color:#222;border-left:3px solid #c0392b;padding:15px 20px;margin:20px 0;">
            <p style="color:#fff;margin:0 0 5px;font-weight:bold;font-size:16px;">[Bandname]</p>
            <p style="color:#ccc;margin:0 0 3px;">Support: [Support-Band]</p>
            <p style="color:#999;margin:0 0 3px;">[Datum] · Türöffnung [Zeit]</p>
            <p style="color:#999;margin:0;">[Venue], [Stadt]</p>
        </div>
        <p style="line-height:1.6;color:#ccc;">Gästeliste und Presseakkreditierungen können direkt über uns angefragt werden.</p>
        <div style="margin:25px 0;text-align:center;">
            <a href="#" style="display:inline-block;background-color:#c0392b;color:#fff;text-decoration:none;padding:12px 30px;font-size:14px;font-weight:bold;letter-spacing:1px;">TICKETS</a>
        </div>
        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Beste Grüsse,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zürich, Schweiz</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],
            [
                'name' => 'Newsletter / Update (DE)',
                'type' => 'email',
                'language' => 'de',
                'sort_order' => 3,
                'subject' => 'News von The Yelling Light',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 15px;">NEWSLETTER</p>
        <p style="line-height:1.6;color:#ccc;">Hallo {name},</p>
        <p style="line-height:1.6;color:#ccc;">Hier die neusten Updates aus dem Hause The Yelling Light:</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">🎵 Neue Releases</h3>
        <p style="line-height:1.6;color:#ccc;">[Release-Infos hier einfügen]</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">🎤 Konzerte & Festivals</h3>
        <p style="line-height:1.6;color:#ccc;">[Konzert-Infos hier einfügen]</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">📰 Weiteres</h3>
        <p style="line-height:1.6;color:#ccc;">[Weitere News hier einfügen]</p>

        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Beste Grüsse,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zürich, Schweiz</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],

            // --- ENGLISH ---
            [
                'name' => 'New Release (EN)',
                'type' => 'email',
                'language' => 'en',
                'sort_order' => 4,
                'subject' => 'New Release: {name} – Listen Now',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 5px;">NEW RELEASE</p>
        <h2 style="color:#fff;margin:0 0 20px;font-size:22px;">[Title / Artist here]</h2>
        <p style="line-height:1.6;color:#ccc;">Hi {name},</p>
        <p style="line-height:1.6;color:#ccc;">We are excited to announce a new release from our catalogue. Available now on all platforms.</p>
        <div style="margin:25px 0;text-align:center;">
            <a href="#" style="display:inline-block;background-color:#c0392b;color:#fff;text-decoration:none;padding:12px 30px;font-size:14px;font-weight:bold;letter-spacing:1px;">LISTEN NOW</a>
        </div>
        <p style="line-height:1.6;color:#ccc;">We'd appreciate any support — whether it's a playlist add, a review, or simply sharing with your network.</p>
        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Best regards,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zurich, Switzerland</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],
            [
                'name' => 'Show Announcement (EN)',
                'type' => 'email',
                'language' => 'en',
                'sort_order' => 5,
                'subject' => 'Live: {name} – Show Announcement',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 5px;">LIVE ON STAGE</p>
        <h2 style="color:#fff;margin:0 0 20px;font-size:22px;">[Band] · [Venue] · [Date]</h2>
        <p style="line-height:1.6;color:#ccc;">Hi {name},</p>
        <p style="line-height:1.6;color:#ccc;">We'd like to invite you to the following show:</p>
        <div style="background-color:#222;border-left:3px solid #c0392b;padding:15px 20px;margin:20px 0;">
            <p style="color:#fff;margin:0 0 5px;font-weight:bold;font-size:16px;">[Band]</p>
            <p style="color:#ccc;margin:0 0 3px;">Support: [Support Band]</p>
            <p style="color:#999;margin:0 0 3px;">[Date] · Doors [Time]</p>
            <p style="color:#999;margin:0;">[Venue], [City]</p>
        </div>
        <p style="line-height:1.6;color:#ccc;">Guest list and press accreditation can be requested directly through us.</p>
        <div style="margin:25px 0;text-align:center;">
            <a href="#" style="display:inline-block;background-color:#c0392b;color:#fff;text-decoration:none;padding:12px 30px;font-size:14px;font-weight:bold;letter-spacing:1px;">TICKETS</a>
        </div>
        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Best regards,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zurich, Switzerland</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],
            [
                'name' => 'Newsletter / Update (EN)',
                'type' => 'email',
                'language' => 'en',
                'sort_order' => 6,
                'subject' => 'News from The Yelling Light',
                'body' => <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#111;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:600px;margin:0 auto;background-color:#1a1a1a;color:#e0e0e0;">
    <div style="background-color:#000;padding:30px 20px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:2px;">THE YELLING LIGHT</h1>
    </div>
    <div style="padding:30px 25px;">
        <p style="color:#999;font-size:13px;margin:0 0 15px;">NEWSLETTER</p>
        <p style="line-height:1.6;color:#ccc;">Hi {name},</p>
        <p style="line-height:1.6;color:#ccc;">Here are the latest updates from The Yelling Light:</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">🎵 New Releases</h3>
        <p style="line-height:1.6;color:#ccc;">[Release info here]</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">🎤 Shows & Festivals</h3>
        <p style="line-height:1.6;color:#ccc;">[Show info here]</p>

        <h3 style="color:#fff;font-size:16px;margin:25px 0 10px;border-bottom:1px solid #333;padding-bottom:8px;">📰 Other News</h3>
        <p style="line-height:1.6;color:#ccc;">[Other news here]</p>

        <p style="line-height:1.6;color:#ccc;margin-top:25px;">Best regards,<br><strong>The Yelling Light</strong></p>
    </div>
    <div style="background-color:#111;padding:20px;text-align:center;font-size:11px;color:#666;">
        <p style="margin:0;">The Yelling Light · Zurich, Switzerland</p>
        <p style="margin:5px 0 0;">info@theyellinglight.ch</p>
    </div>
</div>
</body>
</html>
HTML,
            ],
        ];

        foreach ($templates as $template) {
            CampaignTemplate::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($template['name'])],
                $template
            );
        }
    }
}
