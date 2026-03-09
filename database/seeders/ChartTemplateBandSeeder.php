<?php

namespace Database\Seeders;

use App\Models\ChartTemplate;
use App\Models\ChartTemplateAccount;
use Illuminate\Database\Seeder;

class ChartTemplateBandSeeder extends Seeder
{
    public function run(): void
    {
        $template = ChartTemplate::create([
            'name' => 'Rockband / Musikgruppe',
            'slug' => 'rockband-musikgruppe',
            'description' => 'Kontenplan für Bands und Musikgruppen. Deckt Live-Auftritte, Studiokosten, Merchandise, Streaming und Tantiemen ab.',
            'organization_type_slug' => 'band',
        ]);

        $accounts = [
            // ──── 1xxx AKTIVEN ────
            ['number' => '1000', 'name' => 'Umlaufvermögen',              'type' => 'asset', 'is_header' => true,  'parent_number' => null],
            ['number' => '1001', 'name' => 'Kasse',                       'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1002', 'name' => 'Bank',                        'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1003', 'name' => 'PayPal',                      'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1100', 'name' => 'Debitoren',                   'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],
            ['number' => '1110', 'name' => 'Vorsteuer (MWST)',            'type' => 'asset', 'is_header' => false, 'parent_number' => '1000'],

            ['number' => '1500', 'name' => 'Anlagevermögen',              'type' => 'asset', 'is_header' => true,  'parent_number' => null],
            ['number' => '1501', 'name' => 'Instrumente & Equipment',     'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1502', 'name' => 'PA / Lichtanlage',           'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1503', 'name' => 'Merch-Lager (Bestand)',       'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],

            // ──── 2xxx PASSIVEN ────
            ['number' => '2000', 'name' => 'Fremdkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2001', 'name' => 'Kreditoren',                  'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],
            ['number' => '2010', 'name' => 'MWST-Schuld',                 'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],
            ['number' => '2100', 'name' => 'Verbindlichkeiten Bandmitglieder', 'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],

            ['number' => '2800', 'name' => 'Eigenkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2801', 'name' => 'Bandkasse / Eigenkapital',    'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],
            ['number' => '2810', 'name' => 'Rückstellungen',              'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],

            // ──── 3xxx ERTRAG ────
            ['number' => '3000', 'name' => 'Live-Einnahmen',                        'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3001', 'name' => 'Gagen (Konzerte & Festivals)',           'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3002', 'name' => 'Tür-Einnahmen (Door Split)',             'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3003', 'name' => 'Backline-Vermietung',                   'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],

            ['number' => '3100', 'name' => 'Merchandise-Verkauf',                   'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3101', 'name' => 'T-Shirts & Textilien',                  'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3102', 'name' => 'Vinyl / CD / Kassetten',                'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3103', 'name' => 'Sonstiges Merch (Patches, Poster, etc.)', 'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],

            ['number' => '3200', 'name' => 'Streaming & Digital',                   'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3201', 'name' => 'Streaming (Spotify, Apple, etc.)',       'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],
            ['number' => '3202', 'name' => 'Downloads (Bandcamp, iTunes, etc.)',     'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],
            ['number' => '3203', 'name' => 'Sync-Licensing (Film, TV, Werbung)',     'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],

            ['number' => '3300', 'name' => 'Tantiemen & Verwertung',                'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3301', 'name' => 'SUISA (Urheberrechte)',                  'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3302', 'name' => 'SWISSPERFORM (Leistungsschutz)',         'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3303', 'name' => 'Label-Abrechnungen',                    'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3304', 'name' => 'Verlagsabrechnungen',                   'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],

            ['number' => '3400', 'name' => 'Weiterer Ertrag',                       'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3401', 'name' => 'Sponsoring',                             'type' => 'income', 'is_header' => false, 'parent_number' => '3400'],
            ['number' => '3402', 'name' => 'Crowdfunding',                           'type' => 'income', 'is_header' => false, 'parent_number' => '3400'],
            ['number' => '3403', 'name' => 'Kulturförderung / Beiträge',             'type' => 'income', 'is_header' => false, 'parent_number' => '3400'],
            ['number' => '3404', 'name' => 'Zinsen',                                'type' => 'income', 'is_header' => false, 'parent_number' => '3400'],
            ['number' => '3405', 'name' => 'Sonstiger Ertrag',                      'type' => 'income', 'is_header' => false, 'parent_number' => '3400'],

            // ──── 4xxx PRODUKTIONSKOSTEN ────
            ['number' => '4000', 'name' => 'Studiokosten',                          'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '4001', 'name' => 'Recording / Aufnahme',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4002', 'name' => 'Mixing',                                'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4003', 'name' => 'Mastering',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4004', 'name' => 'Proberaum-Miete',                       'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4005', 'name' => 'Session-Musiker / Gastbeiträge',        'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],

            ['number' => '4100', 'name' => 'Herstellungskosten',                    'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '4101', 'name' => 'Vinyl-Pressung',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],
            ['number' => '4102', 'name' => 'CD-Produktion',                         'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],
            ['number' => '4103', 'name' => 'Kassetten-Produktion',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],
            ['number' => '4104', 'name' => 'Merch-Produktion (Textilien, etc.)',     'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],
            ['number' => '4105', 'name' => 'Artwork & Design',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],
            ['number' => '4106', 'name' => 'Drucksachen (Poster, Flyer)',           'type' => 'expense', 'is_header' => false, 'parent_number' => '4100'],

            // ──── 5xxx TOURING & LIVE ────
            ['number' => '5000', 'name' => 'Touring & Live',                        'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '5001', 'name' => 'Transport (Benzin, Maut, Fähre)',       'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5002', 'name' => 'Fahrzeug (Miete, Leasing, Unterhalt)',  'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5003', 'name' => 'Übernachtung',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5004', 'name' => 'Verpflegung Tour',                     'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5005', 'name' => 'Backline-Miete',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5006', 'name' => 'Tontechnik / Lichttechnik',            'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5007', 'name' => 'Crew / Roadie',                         'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],
            ['number' => '5008', 'name' => 'Booking-Agent Provision',               'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],

            // ──── 6xxx MARKETING & PR ────
            ['number' => '6000', 'name' => 'Marketing & PR',                        'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6001', 'name' => 'Social Media Ads',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6002', 'name' => 'PR / Presse-Promotion',                'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6003', 'name' => 'Radio-Promotion',                       'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6004', 'name' => 'Musikvideo-Produktion',                 'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6005', 'name' => 'Fotoshooting',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6006', 'name' => 'Webseite & Hosting',                    'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6007', 'name' => 'Distribution (digital)',                'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],

            // ──── 6500 EQUIPMENT ────
            ['number' => '6500', 'name' => 'Equipment & Instrumente',               'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6501', 'name' => 'Instrumente (Kauf)',                    'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6502', 'name' => 'Verstärker & Pedale',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6503', 'name' => 'Saiten, Sticks, Felle, Verbrauch',     'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6504', 'name' => 'Reparaturen & Wartung',                'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],

            // ──── 7xxx ADMINISTRATION ────
            ['number' => '7000', 'name' => 'Administration',                        'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '7001', 'name' => 'SUISA-Beiträge (Mitgliedschaft)',       'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7002', 'name' => 'Versicherungen',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7003', 'name' => 'Bankgebühren',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7004', 'name' => 'Buchhaltung / Treuhand',               'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7005', 'name' => 'Rechtsberatung',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7006', 'name' => 'Porto & Versand',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7007', 'name' => 'Software & Abos',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],
            ['number' => '7008', 'name' => 'Sonstiger Aufwand',                    'type' => 'expense', 'is_header' => false, 'parent_number' => '7000'],

            // ──── 7500 VERGÜTUNGEN ────
            ['number' => '7500', 'name' => 'Vergütungen an Bandmitglieder',         'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '7501', 'name' => 'Gage-Auszahlungen',                    'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7502', 'name' => 'Merch-Beteiligungen',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
            ['number' => '7503', 'name' => 'Streaming-Beteiligungen',              'type' => 'expense', 'is_header' => false, 'parent_number' => '7500'],
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
