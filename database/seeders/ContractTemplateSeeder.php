<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ===== DEUTSCH =====
            [
                'name' => 'Label Standard',
                'contract_type_slug' => 'label',
                'language' => 'de',
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
                'language' => 'de',
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
                'language' => 'de',
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
                'language' => 'de',
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
                'language' => 'de',
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
                'language' => 'de',
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
                'language' => 'de',
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

            // ===== ENGLISH =====
            [
                'name' => 'Label Standard (EN)',
                'contract_type_slug' => 'label',
                'language' => 'en',
                'sort_order' => 8,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Artist grants the Label the exclusive right to reproduce, distribute, and make publicly available the recordings listed in the appendix (hereinafter "Recordings") worldwide, in all known and future formats and distribution channels.

2. TERM
This agreement shall become effective upon execution and shall remain in force for a period of [X] years. Renewal shall only occur by written agreement of both parties.

3. COMPENSATION & ACCOUNTING
The Label shall pay the Artist a share of [X]% of net revenues from the distribution of the Recordings. Accounting shall be performed semi-annually, as of June 30 and December 31. Payment shall be due within 30 days of the accounting date.

4. ADVANCE
The Label grants the Artist a recoupable advance in the amount of CHF [X]. The advance shall be recouped against future royalty income.

5. MARKETING & PROMOTION
The Label commits to appropriately promoting the Recordings. The Artist shall provide press photos, biography, and other promotional materials.

6. POST-TERM RIGHTS
Upon expiration of this agreement, the master rights shall remain with the Label for an additional [X] years. Thereafter, all rights shall revert to the Artist.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Publishing Standard (EN)',
                'contract_type_slug' => 'publishing',
                'language' => 'en',
                'sort_order' => 9,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Author grants the Publisher the exclusive right to exploit the musical works listed in the appendix (hereinafter "Works") worldwide, including all types of usage pursuant to applicable copyright law.

2. TERM
This agreement shall apply for the statutory term of protection of the Works, unless otherwise agreed.

3. EXPLOITATION RIGHTS
The Publisher is entitled to reproduce, distribute, publicly perform, broadcast, and make available the Works. This includes sub-publishing and synchronization licenses.

4. COMPENSATION
Revenue shall be divided as follows:
- Mechanical rights: [X]% Author / [X]% Publisher
- Performance rights: as per the distribution of the collecting society (SUISA)
- Synchronization: [X]% Author / [X]% Publisher
- Other income: [X]% Author / [X]% Publisher

5. ACCOUNTING
Accounting shall be performed semi-annually. The Publisher shall provide the Author with a detailed statement of all income and deductions.

6. REGISTRATION
The Publisher shall register the Works with the relevant collecting society (SUISA) and ensure correct registration in all relevant international databases.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Management Standard (EN)',
                'contract_type_slug' => 'management',
                'language' => 'en',
                'sort_order' => 10,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Artist engages the Manager for the exclusive artistic and business representation in all areas of the music industry.

2. DUTIES OF THE MANAGER
The Manager commits to:
- Advising on all artistic and business matters
- Negotiating and executing agreements on behalf of the Artist
- Coordinating recordings, releases, and tours
- Developing a long-term career strategy
- Overseeing the Artist's financial interests

3. TERM
This agreement shall be effective for a period of [X] years from execution. Upon expiration, it shall automatically renew for successive one-year periods unless terminated with 3 months' notice prior to the end of the term.

4. COMPENSATION
The Manager shall receive a commission of [X]% on all gross income of the Artist from:
- Live performances and tours
- Record sales and streaming
- Merchandising
- Sponsorships and endorsements
- Synchronization licenses

5. ACCOUNTING
Accounting shall be performed monthly. The Manager shall provide the Artist with a detailed statement.

6. OBLIGATIONS OF THE ARTIST
The Artist commits to informing the Manager of all relevant business matters and to not entering into any agreements without prior consultation.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Licensing Standard (EN)',
                'contract_type_slug' => 'licensing',
                'language' => 'en',
                'sort_order' => 11,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Licensor grants the Licensee the non-exclusive right to use the recordings/works listed in the appendix (hereinafter "Licensed Material") for the agreed purpose.

2. SCOPE OF LICENSE
- Territory: [worldwide / Switzerland / ...]
- Media: [all / digital / TV / film / advertising / ...]
- Duration: [X months/years]
- Exclusivity: [exclusive / non-exclusive]

3. LICENSE FEE
The Licensee shall pay a one-time license fee of CHF [X] (net), due within 30 days of execution of this agreement.

4. CONDITIONS OF USE
The Licensee commits to:
- Using the Licensed Material only for the agreed purpose
- Correctly crediting the authors and performers
- Not sublicensing the Licensed Material to third parties
- Not making adaptations without written consent

5. LEVIES
Any SUISA fees or other collecting society levies shall be borne by the Licensee.

6. REVERSION OF RIGHTS
Upon expiration of the license term, all rights shall revert to the Licensor. The Licensee shall remove the Licensed Material from all media.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Booking Standard (EN)',
                'contract_type_slug' => 'booking',
                'language' => 'en',
                'sort_order' => 12,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Artist engages the Booking Agency for the exclusive procurement of live performances in the agreed territory.

2. TERRITORY
[Switzerland / DACH / Europe / worldwide]

3. TERM
This agreement shall be effective for a period of [X] years from execution.

4. DUTIES OF THE AGENCY
The Agency commits to:
- Acquiring and procuring concerts, festivals, and events
- Negotiating fees and contractual terms
- Coordinating event logistics
- Invoicing promoters

5. COMPENSATION
The Agency shall receive a commission of [X]% on the agreed gross fee per performance. The commission shall be deducted prior to payment to the Artist.

6. MINIMUM FEE
The Agency commits to not procuring any performances below a minimum fee of CHF [X], unless otherwise approved by the Artist.

7. CANCELLATIONS
In case of cancellation by the Artist within [X] days prior to the performance, a penalty of [X]% of the agreed fee shall be due. In case of force majeure, the penalty shall not apply.

8. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Promotion Standard (EN)',
                'contract_type_slug' => 'promotion',
                'language' => 'en',
                'sort_order' => 13,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
The Client engages the Promotion Agency for the promotion of the release/project defined in the appendix (hereinafter "Project").

2. SCOPE OF SERVICES
The Agency shall provide the following services:
- Development of a promotion strategy
- Press work (print, online, radio, TV)
- Distribution of promo copies to relevant media
- Social media campaigns
- Playlist pitching at streaming services
- Reporting and clippings

3. TERM
The promotion campaign shall commence on [date] and end on [date]. The period shall be [X] weeks.

4. COMPENSATION
The Client shall pay the Agency a flat fee of CHF [X] (net), payable as follows:
- 50% upon execution of this agreement
- 50% upon campaign launch

5. MATERIALS
The Client shall provide the Agency with all necessary materials in a timely manner (audio, photos, biography, EPK).

6. REPORTING
The Agency shall deliver weekly progress reports to the Client as well as a final report with all media placements.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
TERMS,
            ],
            [
                'name' => 'Admin Standard (EN)',
                'contract_type_slug' => 'admin',
                'language' => 'en',
                'sort_order' => 14,
                'default_terms' => <<<'TERMS'
1. SUBJECT MATTER
This agreement governs the administrative arrangements between the parties regarding [description].

2. TERM
This agreement shall become effective upon execution and shall remain in force until further notice. It may be terminated by either party with [X] months' notice as of the end of any month.

3. SERVICES
[Description of agreed services]

4. COMPENSATION
[Compensation arrangement]

5. CONFIDENTIALITY
Both parties commit to keeping confidential all information obtained in the course of this agreement.

6. LIABILITY
Liability shall be limited to intent and gross negligence.

7. GOVERNING LAW
This agreement shall be governed by Swiss law. The place of jurisdiction is Zurich.
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
