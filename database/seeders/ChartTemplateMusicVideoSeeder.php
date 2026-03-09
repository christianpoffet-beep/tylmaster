<?php

namespace Database\Seeders;

use App\Models\ChartTemplate;
use App\Models\ChartTemplateAccount;
use Illuminate\Database\Seeder;

class ChartTemplateMusicVideoSeeder extends Seeder
{
    public function run(): void
    {
        $template = ChartTemplate::create([
            'name' => 'Musikvideo-Produktion',
            'slug' => 'musikvideo-produktion',
            'description' => 'Kontenplan für Musikvideo-Produktionen. Deckt Pre-Production, Dreh, Post-Production, Crew, Equipment und Lizenzierung ab.',
            'organization_type_slug' => null,
        ]);

        $accounts = [
            // ──── 1xxx AKTIVEN (Assets) ────
            ['number' => '1000', 'name' => 'Umlaufvermögen',              'type' => 'asset', 'is_header' => true,  'parent_number' => null],
            ['number' => '1001', 'name' => 'Kasse',                       'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1002', 'name' => 'Post',                        'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1003', 'name' => 'Bank',                        'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1004', 'name' => 'PayPal',                      'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1100', 'name' => 'Debitoren',                   'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],

            ['number' => '1500', 'name' => 'Anlagevermögen',              'type' => 'asset', 'is_header' => true,  'parent_number' => null],
            ['number' => '1501', 'name' => 'Kameraequipment',             'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1502', 'name' => 'Lichtequipment',              'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1503', 'name' => 'Tonequipment',                'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1504', 'name' => 'Computer & Software',         'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],

            // ──── 2xxx PASSIVEN (Liabilities / Equity) ────
            ['number' => '2000', 'name' => 'Fremdkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2001', 'name' => 'Kreditoren',                  'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],
            ['number' => '2002', 'name' => 'Vorauszahlungen Kunden',      'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],

            ['number' => '2800', 'name' => 'Eigenkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2801', 'name' => 'Eigenkapital',                'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],
            ['number' => '2810', 'name' => 'Rückstellungen',              'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],

            // ──── 3xxx ERTRAG (Income) ────
            ['number' => '3000', 'name' => 'Produktions-Ertrag',                              'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3001', 'name' => 'Musikvideo-Produktion',                            'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3002', 'name' => 'Lyric Videos',                                     'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3003', 'name' => 'Live Session Videos',                              'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3004', 'name' => 'Dokumentation / EPK',                              'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3005', 'name' => 'Social Media Content',                             'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],

            ['number' => '3100', 'name' => 'Post-Production Ertrag',                          'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3101', 'name' => 'Schnitt / Editing',                                'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3102', 'name' => 'Color Grading',                                    'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3103', 'name' => 'VFX / Animation',                                  'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3104', 'name' => 'Sound Design / Mix',                               'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],

            ['number' => '3200', 'name' => 'Lizenz-Ertrag',                                    'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3201', 'name' => 'Nutzungsrechte / Lizenzen',                        'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],
            ['number' => '3202', 'name' => 'Zweitverwertung',                                  'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],

            ['number' => '3300', 'name' => 'Weiterer Ertrag',                                  'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3301', 'name' => 'Sponsoring / Product Placement',                   'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3302', 'name' => 'Fördergelder / Subventionen',                      'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3303', 'name' => 'Equipment-Vermietung',                             'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3304', 'name' => 'Sonstiger Ertrag',                                 'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],

            // ──── 4xxx ADMINISTRATIVER AUFWAND (Expense) ────
            ['number' => '4000', 'name' => 'Administrativer Aufwand',                          'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '4001', 'name' => 'Büromaterial',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4002', 'name' => 'Porto / Versand',                                  'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4003', 'name' => 'Gebühren Kontoführung Bank',                       'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4004', 'name' => 'Webhosting / Domain',                              'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4005', 'name' => 'Buchhaltungssoftware',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4006', 'name' => 'Versicherungen',                                   'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4007', 'name' => 'Sonstiger Aufwand',                                'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],

            // ──── 5xxx PRE-PRODUCTION AUFWAND (Expense) ────
            ['number' => '5000', 'name' => 'Pre-Production Aufwand',                           'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '5001', 'name' => 'Konzept / Treatment',                              'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5002', 'name' => 'Storyboard',                                       'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5003', 'name' => 'Location Scouting',                                'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5004', 'name' => 'Casting',                                          'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5005', 'name' => 'Produktionsplanung',                               'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],

            // ──── 5500 CREW & DARSTELLER (Expense) ────
            ['number' => '5500', 'name' => 'Crew & Darsteller',                                'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '5501', 'name' => 'Regie',                                            'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5502', 'name' => 'Kamera / DoP',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5503', 'name' => 'Licht / Gaffer',                                   'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5504', 'name' => 'Ton / Tonmeister',                                 'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5505', 'name' => 'Produktionsassistenz',                              'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5506', 'name' => 'Darsteller / Statisten',                           'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5507', 'name' => 'Styling / Make-up / Garderobe',                    'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5508', 'name' => 'Choreografie',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],
            ['number' => '5509', 'name' => 'Art Direction / Szenenbild',                       'type' => 'expense', 'is_header' => false, 'parent_number' => '5500'],

            // ──── 6xxx DREH / PRODUKTION (Expense) ────
            ['number' => '6000', 'name' => 'Dreh / Produktion Aufwand',                        'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6001', 'name' => 'Location-Miete',                                   'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6002', 'name' => 'Studio-Miete',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6003', 'name' => 'Equipment-Miete Kamera',                           'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6004', 'name' => 'Equipment-Miete Licht',                            'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6005', 'name' => 'Equipment-Miete Grip / Zubehör',                   'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6006', 'name' => 'Requisiten / Dekoration',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6007', 'name' => 'Catering / Verpflegung',                           'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6008', 'name' => 'Transport / Fahrzeuge',                            'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6009', 'name' => 'Genehmigungen / Bewilligungen',                   'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],

            // ──── 6500 REISEKOSTEN (Expense) ────
            ['number' => '6500', 'name' => 'Reisekosten',                                      'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6501', 'name' => 'Reisekosten Crew',                                 'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6502', 'name' => 'Übernachtung',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6503', 'name' => 'Spesen Crew',                                      'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],

            // ──── 7xxx POST-PRODUCTION AUFWAND (Expense) ────
            ['number' => '7000', 'name' => 'Post-Production Aufwand',                          'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '7001', 'name' => 'Schnitt / Editing',                                'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7002', 'name' => 'Color Grading',                                    'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7003', 'name' => 'VFX / Animation / Motion Graphics',               'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7004', 'name' => 'Sound Design / Foley',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7005', 'name' => 'Mastering / Encoding',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],

            // ──── 7500 SOFTWARE & LIZENZEN (Expense) ────
            ['number' => '7500', 'name' => 'Software & Lizenzen',                              'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '7501', 'name' => 'Adobe Creative Cloud',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7502', 'name' => 'DaVinci Resolve / Schnittsoftware',                'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7503', 'name' => 'Musik-Lizenzen / Sync',                            'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7504', 'name' => 'Stock Footage / Grafiken',                         'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7505', 'name' => 'Cloud-Speicher / Backup',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],

            // ──── 8xxx MARKETING & DISTRIBUTION (Expense) ────
            ['number' => '8000', 'name' => 'Marketing & Distribution',                         'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '8001', 'name' => 'Social Media Werbung',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '8000'],
            ['number' => '8002', 'name' => 'YouTube / VEVO Distribution',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '8000'],
            ['number' => '8003', 'name' => 'PR / Pressearbeit',                                'type' => 'expense', 'is_header' => false, 'parent_number' => '8000'],
            ['number' => '8004', 'name' => 'Behind-the-Scenes Content',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '8000'],
            ['number' => '8005', 'name' => 'Festival-Einreichungen',                           'type' => 'expense', 'is_header' => false, 'parent_number' => '8000'],
        ];

        foreach ($accounts as $i => $account) {
            ChartTemplateAccount::create([
                'chart_template_id' => $template->id,
                'number' => $account['number'],
                'name' => $account['name'],
                'type' => $account['type'],
                'is_header' => $account['is_header'],
                'parent_number' => $account['parent_number'],
                'sort_order' => $i,
            ]);
        }
    }
}
