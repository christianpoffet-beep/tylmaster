<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Label Standard',
                'contract_type_slug' => 'label',
                'sort_order' => 1,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Künstler überträgt dem Label das exklusive Recht zur Vervielfältigung, Verbreitung und öffentlichen Zugänglichmachung der im Anhang aufgeführten Aufnahmen (nachfolgend «Aufnahmen») weltweit, in allen bekannten und zukünftigen Formaten und Vertriebswegen.

2. VERTRAGSDAUER
Dieser Vertrag tritt mit Unterzeichnung in Kraft und gilt für eine Dauer von [X] Jahren. Eine Verlängerung erfolgt nur durch schriftliche Vereinbarung beider Parteien.

3. VERGÜTUNG & ABRECHNUNG
Das Label zahlt dem Künstler einen Anteil von [X]% der Netto-Einnahmen aus dem Vertrieb der Aufnahmen. Die Abrechnung erfolgt halbjährlich, jeweils per 30. Juni und 31. Dezember. Die Zahlung ist innerhalb von 30 Tagen nach Abrechnungsdatum fällig.

4. VORSCHUSS
Das Label gewährt dem Künstler einen rückzahlbaren Vorschuss in Höhe von CHF [X]. Der Vorschuss wird mit zukünftigen Lizenzeinnahmen verrechnet.

5. MARKETING & PROMOTION
Das Label verpflichtet sich, die Aufnahmen angemessen zu bewerben. Der Künstler stellt dafür Pressefotos, Biografie und weiteres Promotionmaterial zur Verfügung.

6. RECHTE NACH VERTRAGSENDE
Nach Ablauf des Vertrags verbleiben die Masterrechte beim Label für weitere [X] Jahre. Danach fallen sämtliche Rechte an den Künstler zurück.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Publishing Standard',
                'contract_type_slug' => 'publishing',
                'sort_order' => 2,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Urheber überträgt dem Verlag das exklusive Recht zur Verwertung der im Anhang aufgeführten musikalischen Werke (nachfolgend «Werke») weltweit, einschliesslich aller Nutzungsarten gemäss URG.

2. VERTRAGSDAUER
Dieser Vertrag gilt für die gesetzliche Schutzdauer der Werke, sofern nicht anders vereinbart.

3. VERWERTUNGSRECHTE
Der Verlag ist berechtigt, die Werke zu vervielfältigen, zu verbreiten, öffentlich aufzuführen, zu senden und zur Verfügung zu stellen. Dies schliesst Sub-Publishing und Synchronisationslizenzen ein.

4. VERGÜTUNG
Die Einnahmen werden wie folgt aufgeteilt:
- Mechanische Rechte: [X]% Urheber / [X]% Verlag
- Aufführungsrechte: gemäss Verteilung der Verwertungsgesellschaft (SUISA)
- Synchronisation: [X]% Urheber / [X]% Verlag
- Sonstige Einnahmen: [X]% Urheber / [X]% Verlag

5. ABRECHNUNG
Die Abrechnung erfolgt halbjährlich. Der Verlag legt dem Urheber eine detaillierte Aufstellung aller Einnahmen und Abzüge vor.

6. REGISTRIERUNG
Der Verlag meldet die Werke bei der zuständigen Verwertungsgesellschaft (SUISA) an und sorgt für die korrekte Registrierung bei allen relevanten internationalen Datenbanken.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Management Standard',
                'contract_type_slug' => 'management',
                'sort_order' => 3,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Künstler beauftragt den Manager mit der exklusiven künstlerischen und geschäftlichen Vertretung in allen Bereichen der Musikindustrie.

2. AUFGABEN DES MANAGERS
Der Manager verpflichtet sich zu:
- Beratung in allen künstlerischen und geschäftlichen Angelegenheiten
- Verhandlung und Abschluss von Verträgen im Namen des Künstlers
- Koordination von Aufnahmen, Veröffentlichungen und Tourneen
- Entwicklung einer langfristigen Karrierestrategie
- Überwachung der finanziellen Interessen des Künstlers

3. VERTRAGSDAUER
Dieser Vertrag gilt für eine Dauer von [X] Jahren ab Unterzeichnung. Nach Ablauf verlängert er sich automatisch um jeweils ein Jahr, sofern er nicht mit einer Frist von 3 Monaten zum Vertragsende gekündigt wird.

4. VERGÜTUNG
Der Manager erhält eine Kommission von [X]% auf alle Brutto-Einnahmen des Künstlers aus:
- Live-Auftritten und Tourneen
- Tonträgerverkäufen und Streaming
- Merchandising
- Sponsoring und Endorsements
- Synchronisationslizenzen

5. ABRECHNUNGSMODALITÄTEN
Die Abrechnung erfolgt monatlich. Der Manager legt dem Künstler eine detaillierte Aufstellung vor.

6. PFLICHTEN DES KÜNSTLERS
Der Künstler verpflichtet sich, den Manager über alle relevanten geschäftlichen Vorgänge zu informieren und keine Verträge ohne Rücksprache abzuschliessen.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Licensing Standard',
                'contract_type_slug' => 'licensing',
                'sort_order' => 4,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Lizenzgeber gewährt dem Lizenznehmer das nicht-exklusive Recht zur Nutzung der im Anhang aufgeführten Aufnahmen/Werke (nachfolgend «Lizenzgut») für den vereinbarten Zweck.

2. LIZENZUMFANG
- Territorium: [weltweit / Schweiz / ...]
- Medien: [alle / digital / TV / Film / Werbung / ...]
- Dauer: [X Monate/Jahre]
- Exklusivität: [exklusiv / nicht-exklusiv]

3. LIZENZGEBÜHR
Der Lizenznehmer zahlt eine einmalige Lizenzgebühr von CHF [X] (netto), fällig innerhalb von 30 Tagen nach Vertragsunterzeichnung.

4. NUTZUNGSBEDINGUNGEN
Der Lizenznehmer verpflichtet sich:
- Das Lizenzgut nur für den vereinbarten Zweck zu verwenden
- Die Urheber und Interpreten korrekt zu nennen (Credits)
- Das Lizenzgut nicht an Dritte weiterzulizenzieren
- Keine Bearbeitungen ohne schriftliche Zustimmung vorzunehmen

5. ABGABEN
Allfällige SUISA-Gebühren oder sonstige Verwertungsgesellschafts-Abgaben gehen zulasten des Lizenznehmers.

6. RÜCKFALL DER RECHTE
Nach Ablauf der Lizenzdauer fallen sämtliche Rechte an den Lizenzgeber zurück. Der Lizenznehmer entfernt das Lizenzgut aus allen Medien.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Booking Standard',
                'contract_type_slug' => 'booking',
                'sort_order' => 5,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Künstler beauftragt die Booking-Agentur mit der exklusiven Vermittlung von Live-Auftritten im vereinbarten Territorium.

2. TERRITORIUM
[Schweiz / DACH / Europa / weltweit]

3. VERTRAGSDAUER
Dieser Vertrag gilt für eine Dauer von [X] Jahren ab Unterzeichnung.

4. AUFGABEN DER AGENTUR
Die Agentur verpflichtet sich zu:
- Akquise und Vermittlung von Konzerten, Festivals und Events
- Verhandlung der Gagen und Vertragsbedingungen
- Koordination der Veranstaltungslogistik
- Rechnungsstellung an Veranstalter

5. VERGÜTUNG
Die Agentur erhält eine Kommission von [X]% auf die vereinbarte Brutto-Gage pro Auftritt. Die Kommission wird vor Auszahlung an den Künstler einbehalten.

6. MINDESTGAGE
Die Agentur verpflichtet sich, keine Auftritte unter einer Mindestgage von CHF [X] zu vermitteln, sofern vom Künstler nicht anders genehmigt.

7. ABSAGEN
Bei Absage durch den Künstler innerhalb von [X] Tagen vor dem Auftritt wird eine Konventionalstrafe von [X]% der vereinbarten Gage fällig. Bei höherer Gewalt entfällt die Strafe.

8. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Promotion Standard',
                'contract_type_slug' => 'promotion',
                'sort_order' => 6,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Der Auftraggeber beauftragt die Promotionsagentur mit der Promotion des im Anhang definierten Releases/Projekts (nachfolgend «Projekt»).

2. LEISTUNGSUMFANG
Die Agentur erbringt folgende Leistungen:
- Erstellung einer Promotionsstrategie
- Pressearbeit (Print, Online, Radio, TV)
- Versand von Promo-Kopien an relevante Medien
- Social-Media-Kampagnen
- Playlist-Pitching bei Streaming-Diensten
- Reportings und Clippings

3. VERTRAGSDAUER
Die Promotionskampagne beginnt am [Datum] und endet am [Datum]. Der Zeitraum beträgt [X] Wochen.

4. VERGÜTUNG
Der Auftraggeber zahlt der Agentur ein Pauschalhonorar von CHF [X] (netto), zahlbar wie folgt:
- 50% bei Vertragsunterzeichnung
- 50% bei Kampagnenstart

5. MATERIALIEN
Der Auftraggeber stellt der Agentur rechtzeitig alle notwendigen Materialien zur Verfügung (Audio, Fotos, Biografie, EPK).

6. REPORTING
Die Agentur liefert dem Auftraggeber wöchentliche Reports über den Fortschritt der Kampagne sowie einen Abschlussbericht mit allen Medienplatzierungen.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
            [
                'name' => 'Admin Standard',
                'contract_type_slug' => 'admin',
                'sort_order' => 7,
                'default_terms' => <<<'TERMS'
1. VERTRAGSGEGENSTAND
Dieser Vertrag regelt die administrativen Vereinbarungen zwischen den Parteien bezüglich [Beschreibung].

2. VERTRAGSDAUER
Dieser Vertrag tritt mit Unterzeichnung in Kraft und gilt bis auf Weiteres. Er kann von beiden Parteien mit einer Frist von [X] Monaten zum Monatsende gekündigt werden.

3. LEISTUNGEN
[Beschreibung der vereinbarten Leistungen]

4. VERGÜTUNG
[Vergütungsvereinbarung]

5. VERTRAULICHKEIT
Beide Parteien verpflichten sich, alle im Rahmen dieses Vertrags erlangten vertraulichen Informationen geheim zu halten.

6. HAFTUNG
Die Haftung ist auf Vorsatz und grobe Fahrlässigkeit beschränkt.

7. ANWENDBARES RECHT
Dieser Vertrag unterliegt schweizerischem Recht. Gerichtsstand ist Zürich.
TERMS,
            ],
        ];

        foreach ($templates as $template) {
            ContractTemplate::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($template['name'])],
                $template
            );
        }
    }
}
