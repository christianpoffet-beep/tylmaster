<?php

namespace Database\Seeders;

use App\Models\ChartTemplate;
use App\Models\ChartTemplateAccount;
use Illuminate\Database\Seeder;

class ChartTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = ChartTemplate::create([
            'name' => 'Musiklabel / Verein',
            'slug' => 'musiklabel-verein',
            'description' => 'Kontenplan für ein Musiklabel oder einen Verein nach Schweizer KMU-Standard. Basiert auf der Buchhaltung 2024.',
            'organization_type_slug' => 'label',
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
            ['number' => '1501', 'name' => 'Material',                    'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],
            ['number' => '1510', 'name' => 'Übertragskonto Debitoren',    'type' => 'asset', 'is_header' => false, 'parent_number' => '1500'],

            // ──── 2xxx PASSIVEN (Liabilities / Equity) ────
            ['number' => '2000', 'name' => 'Fremdkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2001', 'name' => 'Kreditoren',                  'type' => 'liability', 'is_header' => false, 'parent_number' => '2000'],

            ['number' => '2800', 'name' => 'Eigenkapital',                'type' => 'liability', 'is_header' => true,  'parent_number' => null],
            ['number' => '2801', 'name' => 'Eigenkapital',                'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],
            ['number' => '2810', 'name' => 'Rückstellungen',              'type' => 'liability', 'is_header' => false, 'parent_number' => '2800'],

            // ──── 3xxx ERTRAG (Income) ────
            ['number' => '3000', 'name' => 'Ertrag',                                   'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3001', 'name' => 'Mitgliederbeiträge',                        'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3002', 'name' => 'Spenden',                                   'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],
            ['number' => '3003', 'name' => 'Einnahmen aus Veranstaltungen',             'type' => 'income', 'is_header' => false, 'parent_number' => '3000'],

            ['number' => '3100', 'name' => 'Weiterer Ertrag',                           'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3101', 'name' => 'Zinsen',                                    'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],
            ['number' => '3102', 'name' => 'Sonstiger Ertrag',                          'type' => 'income', 'is_header' => false, 'parent_number' => '3100'],

            ['number' => '3200', 'name' => 'Fotografie Ertrag',                         'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3201', 'name' => 'Bandfotografie',                            'type' => 'income', 'is_header' => false, 'parent_number' => '3200'],

            ['number' => '3300', 'name' => 'Musik Ertrag',                              'type' => 'income', 'is_header' => true,  'parent_number' => null],
            ['number' => '3301', 'name' => 'Rebeat - Uploads & Metadata',               'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3302', 'name' => 'Rebeat - Streaming & Download',             'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3303', 'name' => 'Physische Verkäufe CD',                     'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3304', 'name' => 'Physische Verkäufe Vinyl',                  'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3305', 'name' => 'Physische Verkäufe Merchandise',            'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3306', 'name' => 'SUISA',                                     'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3307', 'name' => 'SWISSPERFORM',                              'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],
            ['number' => '3308', 'name' => 'PR Zeitschriften',                          'type' => 'income', 'is_header' => false, 'parent_number' => '3300'],

            // ──── 4xxx ADMINISTRATIVER AUFWAND (Expense) ────
            ['number' => '4000', 'name' => 'Administrativer Aufwand',                   'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '4001', 'name' => 'Spesen',                                    'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4002', 'name' => 'Porto',                                     'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4003', 'name' => 'Büromaterial',                               'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4004', 'name' => 'Veranstaltungen',                           'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4005', 'name' => 'Microsoft Office',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4006', 'name' => 'Webling Buchhaltung',                       'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4007', 'name' => 'Reprtoir - Works Manager',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4008', 'name' => 'Metanet - Webhosting',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4009', 'name' => 'indiesuisse - Mitgliedschaft',               'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],
            ['number' => '4010', 'name' => 'Gebühren Kontoführung Bank',                'type' => 'expense', 'is_header' => false, 'parent_number' => '4000'],

            ['number' => '4500', 'name' => 'Weiterer Aufwand',                          'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '4501', 'name' => 'Sonstiger Aufwand',                         'type' => 'expense', 'is_header' => false, 'parent_number' => '4500'],

            // ──── 5xxx FOTOGRAFIE AUFWAND (Expense) ────
            ['number' => '5000', 'name' => 'Fotografie Aufwand',                        'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '5001', 'name' => 'Adobe Creative Cloud',                      'type' => 'expense', 'is_header' => false, 'parent_number' => '5000'],

            // ──── 6xxx MUSIK AUFWAND (Expense) ────
            ['number' => '6000', 'name' => 'Musik Aufwand',                             'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6001', 'name' => 'SUISA Aufwand',                             'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6002', 'name' => 'Rebeat - Uploads & Metadata',               'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6003', 'name' => 'Drucksachen',                               'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6004', 'name' => 'Herstellungskosten Tonträger',              'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6005', 'name' => 'Herstellungskosten Merchandise',            'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6006', 'name' => 'PR Internet, Social Media',                 'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6007', 'name' => 'PR Zeitschriften',                          'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],
            ['number' => '6008', 'name' => 'PR Radio, TV, usw.',                        'type' => 'expense', 'is_header' => false, 'parent_number' => '6000'],

            ['number' => '6500', 'name' => 'Vergütungen an Künstler',                   'type' => 'expense', 'is_header' => true,  'parent_number' => null],
            ['number' => '6501', 'name' => 'Streaming & Download',                     'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6502', 'name' => 'Physische Verkäufe CD',                     'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6503', 'name' => 'Physische Verkäufe Vinyl',                  'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
            ['number' => '6504', 'name' => 'Physische Verkäufe Merchandise',            'type' => 'expense', 'is_header' => false, 'parent_number' => '6500'],
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
