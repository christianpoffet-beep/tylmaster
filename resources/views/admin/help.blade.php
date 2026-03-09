@extends('admin.layouts.app')

@section('title', 'Benutzeranleitung')

@section('content')
<div class="max-w-4xl" x-data="{ activeSection: window.location.hash?.replace('#', '') || '' }">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Benutzeranleitung</h2>
        <p class="text-sm text-gray-500 mt-1">Willkommen im TYL Admin Panel. Hier findest du eine Übersicht aller Funktionen.</p>
    </div>

    {{-- Inhaltsverzeichnis --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Inhaltsverzeichnis</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1">
            <a href="#dashboard" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">1. Dashboard</a>
            <a href="#kontakte" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">2. Kontakte</a>
            <a href="#organisationen" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">3. Organisationen</a>
            <a href="#dokumente" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">4. Dokumente</a>
            <a href="#projekte" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">5. Projekte</a>
            <a href="#aufgaben" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">6. Aufgaben</a>
            <a href="#vertraege" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">7. Verträge</a>
            <a href="#musik" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">8. Musik (Tracks & Releases)</a>
            <a href="#submissions" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">9. Submissions</a>
            <a href="#artwork" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">10. Logo & Artwork</a>
            <a href="#fotos" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">11. Fotos / Bilder</a>
            <a href="#finanzen" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">12. Finanzen (Rechnungen)</a>
            <a href="#buchhaltung" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">13. Buchhaltung</a>
            <a href="#settings" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">14. Settings</a>
            <a href="#navigation" class="text-sm text-blue-600 hover:text-blue-800 py-0.5">15. Navigation & Sidebar</a>
        </div>
    </div>

    <div class="space-y-6">

        {{-- 1. Dashboard --}}
        <div id="dashboard" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">1. Dashboard</h3>
            <p class="text-sm text-gray-600 mb-3">Das Dashboard ist die Startseite nach dem Login und bietet einen schnellen Überblick:</p>
            <ul class="text-sm text-gray-600 space-y-1.5 list-disc list-inside">
                <li><strong>Statistiken:</strong> Anzahl Kontakte, Organisationen, aktive Verträge, offene Rechnungen</li>
                <li><strong>Ablaufende Verträge:</strong> Verträge, die in den nächsten 30 Tagen auslaufen</li>
                <li><strong>Überfällige Rechnungen:</strong> Offene Rechnungen, deren Fälligkeitsdatum überschritten ist</li>
                <li><strong>Anstehende Aufgaben:</strong> Die nächsten offenen Aufgaben</li>
                <li><strong>Geburtstage:</strong> Kommende Geburtstage deiner Kontakte</li>
            </ul>
        </div>

        {{-- 2. Kontakte --}}
        <div id="kontakte" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">2. Kontakte</h3>
            <p class="text-sm text-gray-600 mb-3">Die zentrale Kontaktverwaltung für Personen (Artists, Labels, Venues, etc.).</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Neuen Kontakt erstellen</h4>
                    <p class="text-sm text-gray-600">Klicke auf <strong>«Neuer Kontakt»</strong>. Pflichtfelder sind Vorname und Nachname. Optional kannst du Typen (z.B. Artist, Label), Tags, Adresse, Telefon, E-Mail, Geburtsdatum und Notizen erfassen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Typen</h4>
                    <p class="text-sm text-gray-600">Jeder Kontakt kann mehrere Typen haben (z.B. Artist + Label). Die Typen werden unter <strong>Settings &rarr; Labels &rarr; Kont. Typ</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Suche & Filter</h4>
                    <p class="text-sm text-gray-600">Nutze das Suchfeld für Namen und das Typ-Dropdown zum Filtern. Klicke auf Spaltenüberschriften zum Sortieren.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600">Kontakte können mit Organisationen, Verträgen, Projekten und Tracks verknüpft werden. Diese Verknüpfungen siehst du auf der Detailseite des Kontakts.</p>
                </div>
            </div>
        </div>

        {{-- 3. Organisationen --}}
        <div id="organisationen" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">3. Organisationen</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung von Firmen, Labels, Verlagen und anderen Organisationen.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Erstellen & Bearbeiten</h4>
                    <p class="text-sm text-gray-600">Erfasse den Organisationsnamen, Typ, Adresse und Kontaktdaten. Organisationstypen werden unter <strong>Settings &rarr; Labels &rarr; Org. Typen</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Kontakte zuordnen</h4>
                    <p class="text-sm text-gray-600">Organisationen können mit Kontakten verknüpft werden. Diese Zuordnung wird auch bei der Vertragspartei-Auswahl genutzt: Wählst du eine Organisation als Vertragspartei, werden dir deren Kontakte als Ansprechperson angeboten.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Schnellerstellung</h4>
                    <p class="text-sm text-gray-600">An einigen Stellen (z.B. bei Rechnungen) kannst du direkt eine neue Organisation erstellen, ohne die aktuelle Seite zu verlassen.</p>
                </div>
            </div>
        </div>

        {{-- 4. Dokumente --}}
        <div id="dokumente" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">4. Dokumente</h3>
            <p class="text-sm text-gray-600 mb-3">Das zentrale Dokumentenmanagement. Dokumente können an verschiedene Bereiche angehängt werden.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Upload</h4>
                    <p class="text-sm text-gray-600">Lade Dateien hoch und vergib eine Kategorie (z.B. Vertrag, Rechnung, Sonstiges). Füge optional eine Notiz hinzu.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600">Dokumente können mit Kontakten, Verträgen, Projekten, Tracks und Aufgaben verknüpft werden. Du siehst verknüpfte Dokumente jeweils auf der Detailseite des entsprechenden Objekts.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vorschau & Download</h4>
                    <p class="text-sm text-gray-600">Klicke auf ein Dokument, um eine Vorschau zu öffnen (für PDFs und Bilder). Über den Download-Button kannst du die Datei herunterladen.</p>
                </div>
            </div>
        </div>

        {{-- 5. Projekte --}}
        <div id="projekte" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">5. Projekte</h3>
            <p class="text-sm text-gray-600 mb-3">Projekte bündeln zusammengehörige Aktivitäten (Releases, Events, Admin-Aufgaben).</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Projekttypen</h4>
                    <p class="text-sm text-gray-600">Jedes Projekt hat einen Typ (z.B. Release, Event, Administration). Diese werden unter <strong>Settings &rarr; Labels &rarr; Projekt Typ</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Status</h4>
                    <p class="text-sm text-gray-600">Projekte haben einen Status: <em>Planung</em>, <em>Aktiv</em>, <em>Abgeschlossen</em> oder <em>Abgebrochen</em>.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600">Projekte können mit Kontakten, Verträgen und Tracks verknüpft werden. Auf der Detailseite siehst du alle verknüpften Elemente und Aufgaben.</p>
                </div>
            </div>
        </div>

        {{-- 6. Aufgaben --}}
        <div id="aufgaben" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">6. Aufgaben</h3>
            <p class="text-sm text-gray-600 mb-3">Aufgaben helfen bei der Organisation von To-Dos, sowohl projektbezogen als auch allgemein.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Erstellen</h4>
                    <p class="text-sm text-gray-600">Erstelle Aufgaben mit Titel, Beschreibung, Fälligkeitsdatum und Priorität. Optional kannst du sie einem Projekt zuordnen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Erledigen</h4>
                    <p class="text-sm text-gray-600">Klicke auf die Checkbox, um eine Aufgabe als erledigt zu markieren. Erledigte Aufgaben werden durchgestrichen angezeigt.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Dokumente</h4>
                    <p class="text-sm text-gray-600">An Aufgaben können Dokumente angehängt werden.</p>
                </div>
            </div>
        </div>

        {{-- 7. Verträge --}}
        <div id="vertraege" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">7. Verträge</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung aller Verträge mit Parteien, Dokumenten und Status-Tracking.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vorlagen nutzen</h4>
                    <p class="text-sm text-gray-600">Beim Erstellen eines neuen Vertrags kannst du oben eine <strong>Vorlage</strong> wählen. Diese füllt automatisch Typ, Status, Bedingungen und Parteien aus. Vorlagen werden unter <strong>Settings &rarr; Vorlagen &rarr; Vertragsvorlagen</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vertragsparteien</h4>
                    <p class="text-sm text-gray-600">Jeder Vertrag braucht mindestens 2 Parteien. Eine Partei kann eine Organisation (mit optionaler Ansprechperson) oder ein einzelner Kontakt sein. Jede Partei hat einen prozentualen Anteil &ndash; die Summe muss 100% ergeben.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vertragsnummer</h4>
                    <p class="text-sm text-gray-600">Die Vertragsnummer wird automatisch generiert (Format: TYL-JJJJ-NNNN).</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Dokumente</h4>
                    <p class="text-sm text-gray-600">Lade das Vertragsdokument (PDF, Word, etc.) direkt beim Erstellen hoch. Weitere Dokumente können nachträglich hinzugefügt werden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Status</h4>
                    <p class="text-sm text-gray-600"><em>Entwurf</em> &rarr; <em>Aktiv</em> &rarr; <em>Ausgelaufen</em> / <em>Gekündigt</em>. Ablaufende Verträge werden auf dem Dashboard angezeigt.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vertragstypen</h4>
                    <p class="text-sm text-gray-600">Typen wie Label, Publishing, Management, Licensing etc. werden unter <strong>Settings &rarr; Labels &rarr; Vertragstypen</strong> verwaltet.</p>
                </div>
            </div>
        </div>

        {{-- 8. Musik --}}
        <div id="musik" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">8. Musik (Tracks & Releases)</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung deines Musikkatalogs mit Tracks und Releases.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Tracks</h4>
                    <p class="text-sm text-gray-600">Erfasse Songs mit Titel, ISRC-Code, Dauer, Genre, Status und verknüpften Artists (Kontakte). Optional kannst du eine Audiodatei hochladen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Releases</h4>
                    <p class="text-sm text-gray-600">Fasse Tracks zu Releases (Singles, EPs, Alben) zusammen. Erfasse UPC-Code, Veröffentlichungsdatum und verknüpfe die enthaltenen Tracks.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Navigation</h4>
                    <p class="text-sm text-gray-600">Unter <strong>Musik</strong> findest du Tabs für Tracks und Releases. Wechsle zwischen den Ansichten über die Tab-Navigation oben.</p>
                </div>
            </div>
        </div>

        {{-- 9. Submissions --}}
        <div id="submissions" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">9. Submissions</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung eingehender Musikeinsendungen.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Übersicht</h4>
                    <p class="text-sm text-gray-600">Submissions werden aus der externen Plattform (musicsubmission.theyellinglight.ch) importiert. Du siehst alle Einsendungen mit Status und kannst diese verwalten.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Status</h4>
                    <p class="text-sm text-gray-600">Ändere den Status einer Submission (z.B. Neu, In Prüfung, Angenommen, Abgelehnt).</p>
                </div>
            </div>
        </div>

        {{-- 10. Logo & Artwork --}}
        <div id="artwork" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">10. Logo & Artwork</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung von Album-Cover, Logos und grafischen Materialien.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Erstellen</h4>
                    <p class="text-sm text-gray-600">Erstelle ein Artwork mit Titel und lade eine oder mehrere Bilddateien hoch. Vergib optionale Credits (Fotograf, Designer etc.).</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600">Artworks können mit Releases, Tracks und Projekten verknüpft werden.</p>
                </div>
            </div>
        </div>

        {{-- 11. Fotos --}}
        <div id="fotos" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">11. Fotos / Bilder</h3>
            <p class="text-sm text-gray-600 mb-3">Fotogalerie mit Ordnerstruktur und Teilen-Funktion.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Ordner</h4>
                    <p class="text-sm text-gray-600">Organisiere Fotos in Ordnern. Erstelle neue Ordner und lade Bilder per Batch-Upload hoch.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Teilen</h4>
                    <p class="text-sm text-gray-600">Generiere einen <strong>Share-Link</strong> für einen Ordner. Externe Personen können über diesen Link die Galerie ansehen und Fotos herunterladen &ndash; ohne Login.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Metadaten</h4>
                    <p class="text-sm text-gray-600">Bearbeite Titel, Beschreibung und Credits einzelner Fotos.</p>
                </div>
            </div>
        </div>

        {{-- 12. Finanzen --}}
        <div id="finanzen" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">12. Finanzen (Rechnungen & Ausgaben)</h3>
            <p class="text-sm text-gray-600 mb-3">Rechnungs- und Ausgabenverwaltung mit Mehrwährungsunterstützung.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Rechnungen erstellen</h4>
                    <p class="text-sm text-gray-600">Erstelle Rechnungen mit Rechnungsvorlage, Empfänger (Organisation/Kontakt), Positionen, Währung (CHF/EUR/USD) und Fälligkeitsdatum. Du kannst eine <strong>Rechnungsvorlage</strong> wählen, um Layout und Standardwerte vorzubelegen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">PDF-Export</h4>
                    <p class="text-sm text-gray-600">Generiere professionelle PDF-Rechnungen mit Swiss QR-Bill (für CHF-Rechnungen). Klicke auf <strong>«PDF»</strong> in der Rechnungsliste.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Bezahlt markieren</h4>
                    <p class="text-sm text-gray-600">Markiere Rechnungen als bezahlt. Überfällige offene Rechnungen erscheinen auf dem Dashboard.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Ausgaben</h4>
                    <p class="text-sm text-gray-600">Erfasse Ausgaben mit Betrag, Kategorie, Datum und optionalem Beleg-Upload.</p>
                </div>
            </div>
        </div>

        {{-- 13. Buchhaltung --}}
        <div id="buchhaltung" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">13. Buchhaltung</h3>
            <p class="text-sm text-gray-600 mb-3">Vollständige doppelte Buchhaltung mit Kontenplan, Journal und Bilanz.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Buchhaltung erstellen</h4>
                    <p class="text-sm text-gray-600">Erstelle eine neue Buchhaltung (z.B. pro Jahr). Nutze eine <strong>Kontenplan-Vorlage</strong> aus <strong>Settings &rarr; Vorlagen &rarr; Kontopläne</strong>, um die Konten automatisch anzulegen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Konten</h4>
                    <p class="text-sm text-gray-600">Verwalte die Konten deiner Buchhaltung (Aktiven, Passiven, Aufwand, Ertrag). Konten haben eine Nummer, einen Namen und einen Typ.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Buchungen</h4>
                    <p class="text-sm text-gray-600">Erfasse Buchungen mit Soll- und Haben-Konto, Betrag, Datum und Beschreibung. An Buchungen können Belege (Dokumente) angehängt werden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Auswertungen</h4>
                    <p class="text-sm text-gray-600">
                        <strong>Journal:</strong> Chronologische Liste aller Buchungen.<br>
                        <strong>Kontoblatt:</strong> Alle Buchungen eines bestimmten Kontos.<br>
                        <strong>Probebilanz:</strong> Übersicht aller Konten mit Soll/Haben-Summen.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Bilanz</h4>
                    <p class="text-sm text-gray-600">Die <strong>Bilanz</strong> zeigt Aktiven vs. Passiven. Du siehst sowohl die <strong>Eröffnungsbilanz</strong> (Anfangssaldi) als auch die <strong>Schlussbilanz</strong> (nach allen Buchungen). Die Differenz ergibt den Gewinn oder Verlust der Periode.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Erfolgsrechnung</h4>
                    <p class="text-sm text-gray-600">Die <strong>Erfolgsrechnung</strong> zeigt alle Ertrags- und Aufwandskonten mit ihren Saldi. Ertrag minus Aufwand ergibt den Gewinn (oder Verlust) der Periode. Dieser Wert stimmt mit der Differenz in der Bilanz überein.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Eröffnungssaldi</h4>
                    <p class="text-sm text-gray-600">Beim Erstellen einer neuen Buchhaltung solltest du die <strong>Eröffnungssaldi</strong> der Aktiv- und Passivkonten vom Vorjahr übertragen. Dies geschieht über das Feld «Eröffnungssaldo» beim Hinzufügen oder Bearbeiten eines Kontos.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Perioden</h4>
                    <p class="text-sm text-gray-600">Schliesse eine Buchhaltungsperiode ab, um weitere Buchungen zu verhindern. Bei Bedarf kann die Periode wieder geöffnet werden.</p>
                </div>
            </div>
        </div>

        {{-- 14. Settings --}}
        <div id="settings" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">14. Settings</h3>
            <p class="text-sm text-gray-600 mb-3">Unter Settings findest du zwei Bereiche: <strong>Labels</strong> und <strong>Vorlagen</strong>.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Labels</h4>
                    <p class="text-sm text-gray-600">Verwalte die Kategorien und Typen, die im System verwendet werden:</p>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Genres:</strong> Musikgenres für Tracks</li>
                        <li><strong>Kont. Typ:</strong> Kontakttypen (Artist, Label, Venue, etc.)</li>
                        <li><strong>Org. Typen:</strong> Organisationstypen</li>
                        <li><strong>Projekt Typ:</strong> Projekttypen (Release, Event, Administration)</li>
                        <li><strong>Vertragstypen:</strong> Vertragstypen (Label, Publishing, Management, etc.)</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-1">Jeder Label-Typ hat einen Namen, eine Farbe und eine Reihenfolge. Ein Typ kann nur gelöscht werden, wenn er nicht mehr verwendet wird.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Vorlagen</h4>
                    <p class="text-sm text-gray-600">Vorlagen beschleunigen die Erstellung neuer Einträge:</p>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Kontopläne:</strong> Vorlagen für Buchhaltungs-Konten. Beim Erstellen einer neuen Buchhaltung werden die Konten aus der Vorlage übernommen.</li>
                        <li><strong>Rechnungsvorlagen:</strong> Layout und Standardwerte für Rechnungen (Absender, Bankverbindung, Zahlungsbedingungen).</li>
                        <li><strong>Vertragsvorlagen:</strong> Vertragstexte mit vordefinierten Parteien, Status und Bedingungen. Beim Erstellen eines Vertrags füllt die Vorlage automatisch alle Felder aus.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 15. Navigation --}}
        <div id="navigation" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">15. Navigation & Sidebar</h3>
            <p class="text-sm text-gray-600 mb-3">Tipps zur Nutzung der Sidebar-Navigation:</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Aufklappbare Bereiche</h4>
                    <p class="text-sm text-gray-600"><strong>Projekte</strong> und <strong>Finanzen</strong> sind aufklappbare Gruppen. Klicke auf den Namen, um zur Hauptseite zu navigieren. Klicke auf den <strong>Pfeil</strong> (Chevron), um die Untermenüpunkte ein-/auszuklappen. Die Gruppe öffnet sich automatisch, wenn du dich auf einer Unterseite befindest.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Sidebar einklappen</h4>
                    <p class="text-sm text-gray-600">Klicke auf die <strong>Doppelpfeile</strong> unten in der Sidebar, um sie einzuklappen. Im eingeklappten Modus siehst du nur die Icons. Fahre mit der Maus über ein Icon, um den Tooltip mit dem Namen zu sehen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800">Mobile Ansicht</h4>
                    <p class="text-sm text-gray-600">Auf mobilen Geräten öffnest du die Sidebar über das <strong>Hamburger-Menü</strong> (drei Striche) oben links. Tippe ausserhalb der Sidebar, um sie wieder zu schliessen.</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Zurück nach oben --}}
    <div class="mt-6 text-center">
        <a href="#" class="text-sm text-gray-400 hover:text-gray-600">Nach oben</a>
    </div>
</div>
@endsection
