@extends('admin.layouts.app')

@section('title', 'Benutzeranleitung')

@section('content')
<div class="max-w-4xl" x-data="{ activeSection: window.location.hash?.replace('#', '') || '' }">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Benutzeranleitung</h2>
        <p class="text-sm text-gray-500 mt-1">Willkommen im TYL Admin Panel. Hier findest du eine Übersicht aller Funktionen.</p>
    </div>

    {{-- Inhaltsverzeichnis --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Inhaltsverzeichnis</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1">
            <a href="#dashboard" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">1. Dashboard</a>
            <a href="#kontakte" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">2. Kontakte</a>
            <a href="#organisationen" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">3. Organisationen</a>
            <a href="#dokumente" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">4. Dokumente</a>
            <a href="#projekte" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">5. Projekte</a>
            <a href="#aufgaben" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">6. Aufgaben</a>
            <a href="#vertraege" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">7. Verträge</a>
            <a href="#musik" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">8. Musik (Tracks & Releases)</a>
            <a href="#submissions" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">9. Submissions</a>
            <a href="#artwork" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">10. Logo & Artwork</a>
            <a href="#fotos" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">11. Fotos / Bilder</a>
            <a href="#finanzen" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">12. Finanzen (Rechnungen)</a>
            <a href="#buchhaltung" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">13. Buchhaltung</a>
            <a href="#settings" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">14. Settings</a>
            <a href="#navigation" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">15. Navigation & Sidebar</a>
            <a href="#benutzerverwaltung" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-0.5">16. Benutzerverwaltung</a>
        </div>
    </div>

    <div class="space-y-6">

        {{-- 1. Dashboard --}}
        <div id="dashboard" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
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
        <div id="kontakte" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">2. Kontakte</h3>
            <p class="text-sm text-gray-600 mb-3">Die zentrale Kontaktverwaltung für Personen (Artists, Labels, Venues, etc.).</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Neuen Kontakt erstellen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Klicke auf <strong>«Neuer Kontakt»</strong>. Pflichtfelder sind Vorname und Nachname. Optional kannst du Typen (z.B. Artist, Label), Tags, Adresse, Telefon, E-Mail, Geburtsdatum und Notizen erfassen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Typen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Jeder Kontakt kann mehrere Typen haben (z.B. Artist + Label). Die Typen werden unter <strong>Settings &rarr; Labels &rarr; Kont. Typ</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Suche & Filter</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Nutze das Suchfeld für Namen und das Typ-Dropdown zum Filtern. Klicke auf Spaltenüberschriften zum Sortieren.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Kontakte können mit Organisationen, Verträgen, Projekten und Tracks verknüpft werden. Diese Verknüpfungen siehst du auf der Detailseite des Kontakts.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Neue Felder</h4>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Geschlecht:</strong> Männlich, Weiblich oder Nicht definiert</li>
                        <li><strong>Nationalität:</strong> Auswahl aus der Länderliste</li>
                        <li><strong>AHV-Nr.:</strong> Schweizer Sozialversicherungsnummer im Format 756.XXXX.XXXX.XX mit automatischer Eingabemaske und Validierung</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">PLZ-Autovervollständigung</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Bei allen PLZ-Feldern (Adresse und Bankadresse) wird der Ort automatisch ausgefüllt, wenn eine gültige Schweizer oder deutsche Postleitzahl eingegeben wird. Der Ort kann jederzeit manuell überschrieben werden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Organisation zuordnen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Beim Erstellen oder Bearbeiten eines Kontakts kannst du über das Suchfeld Organisationen suchen und zuordnen. Klicke ins Feld, um alle verfügbaren Organisationen anzuzeigen, oder tippe einen Suchbegriff ein. Neue Organisationen können direkt inline erstellt werden.</p>
                </div>
            </div>
        </div>

        {{-- 3. Organisationen --}}
        <div id="organisationen" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">3. Organisationen</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung von Firmen, Labels, Verlagen und anderen Organisationen.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Erstellen & Bearbeiten</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erfasse den Organisationsnamen, Typ, Adresse und Kontaktdaten. Organisationstypen werden unter <strong>Settings &rarr; Labels &rarr; Org. Typen</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Kontakte zuordnen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Organisationen können mit Kontakten verknüpft werden. Diese Zuordnung wird auch bei der Vertragspartei-Auswahl genutzt: Wählst du eine Organisation als Vertragspartei, werden dir deren Kontakte als Ansprechperson angeboten.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Schnellerstellung</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">An einigen Stellen (z.B. bei Rechnungen) kannst du direkt eine neue Organisation erstellen, ohne die aktuelle Seite zu verlassen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Rechtsform</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Jede Organisation kann eine Rechtsform haben: AG, GmbH, Verein, Stiftung, Einzelfirma, Ltd, LLP (UK) oder LLC.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Beim Erstellen oder Bearbeiten einer Organisation kannst du über Suchfelder <strong>Kontakte</strong>, <strong>Projekte</strong> und <strong>Verträge</strong> suchen und zuordnen. Mehrfachauswahl ist möglich. Neue Kontakte können direkt inline erstellt werden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">PLZ-Autovervollständigung</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Bei allen PLZ-Feldern wird der Ort automatisch ausgefüllt, wenn eine gültige Schweizer oder deutsche Postleitzahl eingegeben wird.</p>
                </div>
            </div>
        </div>

        {{-- 4. Dokumente --}}
        <div id="dokumente" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">4. Dokumente</h3>
            <p class="text-sm text-gray-600 mb-3">Das zentrale Dokumentenmanagement. Dokumente können an verschiedene Bereiche angehängt werden.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Upload</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Lade Dateien hoch und vergib eine Kategorie (z.B. Vertrag, Rechnung, Sonstiges). Füge optional eine Notiz hinzu.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Dokumente können mit Kontakten, Verträgen, Projekten, Tracks und Aufgaben verknüpft werden. Du siehst verknüpfte Dokumente jeweils auf der Detailseite des entsprechenden Objekts.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vorschau & Download</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Klicke auf ein Dokument, um eine Vorschau zu öffnen (für PDFs und Bilder). Über den Download-Button kannst du die Datei herunterladen.</p>
                </div>
            </div>
        </div>

        {{-- 5. Projekte --}}
        <div id="projekte" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">5. Projekte</h3>
            <p class="text-sm text-gray-600 mb-3">Projekte bündeln zusammengehörige Aktivitäten (Releases, Events, Admin-Aufgaben).</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Projekttypen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Jedes Projekt hat einen Typ (z.B. Release, Event, Administration). Diese werden unter <strong>Settings &rarr; Labels &rarr; Projekt Typ</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Status</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Projekte haben einen Status: <em>Planung</em>, <em>Aktiv</em>, <em>Abgeschlossen</em> oder <em>Abgebrochen</em>.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Projekte können mit Kontakten, Organisationen, Verträgen und Tracks verknüpft werden. Kontakte und Organisationen werden über Suchfelder mit Mehrfachauswahl zugeordnet. Neue Kontakte können direkt inline erstellt werden. Auf der Detailseite siehst du alle verknüpften Elemente und Aufgaben.</p>
                </div>
            </div>
        </div>

        {{-- 6. Aufgaben --}}
        <div id="aufgaben" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">6. Aufgaben</h3>
            <p class="text-sm text-gray-600 mb-3">Aufgaben helfen bei der Organisation von To-Dos, sowohl projektbezogen als auch allgemein.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Erstellen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erstelle Aufgaben mit Titel, Beschreibung, Fälligkeitsdatum und Priorität. Optional kannst du sie einem Projekt zuordnen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Erledigen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Klicke auf die Checkbox, um eine Aufgabe als erledigt zu markieren. Erledigte Aufgaben werden durchgestrichen angezeigt.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Dokumente</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">An Aufgaben können Dokumente angehängt werden.</p>
                </div>
            </div>
        </div>

        {{-- 7. Verträge --}}
        <div id="vertraege" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">7. Verträge</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung aller Verträge mit Parteien, Dokumenten und Status-Tracking.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vorlagen nutzen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Beim Erstellen eines neuen Vertrags kannst du oben eine <strong>Vorlage</strong> wählen. Diese füllt automatisch Typ, Status, Bedingungen und Parteien aus. Vorlagen werden unter <strong>Settings &rarr; Vorlagen &rarr; Vertragsvorlagen</strong> verwaltet.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vertragsparteien</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Jeder Vertrag braucht mindestens 2 Parteien. Eine Partei kann eine Organisation (mit optionaler Ansprechperson) oder ein einzelner Kontakt sein. Jede Partei hat einen prozentualen Anteil &ndash; die Summe muss 100% ergeben.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vertragsnummer</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Die Vertragsnummer wird automatisch generiert (Format: TYL-JJJJ-NNNN).</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Dokumente</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Lade das Vertragsdokument (PDF, Word, etc.) direkt beim Erstellen hoch. Weitere Dokumente können nachträglich hinzugefügt werden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Status</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><em>Entwurf</em> &rarr; <em>Aktiv</em> &rarr; <em>Ausgelaufen</em> / <em>Gekündigt</em>. Ablaufende Verträge werden auf dem Dashboard angezeigt.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vertragstypen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Typen wie Label, Publishing, Management, Licensing etc. werden unter <strong>Settings &rarr; Labels &rarr; Vertragstypen</strong> verwaltet.</p>
                </div>
            </div>
        </div>

        {{-- 8. Musik --}}
        <div id="musik" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">8. Musik (Tracks & Releases)</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung deines Musikkatalogs mit Tracks und Releases.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Tracks</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erfasse Songs mit Titel, ISRC-Code, Dauer, Genre, Status und verknüpften Artists (Kontakte). Optional kannst du eine Audiodatei hochladen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Releases</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Fasse Tracks zu Releases (Singles, EPs, Alben) zusammen. Erfasse UPC-Code, Veröffentlichungsdatum und verknüpfe die enthaltenen Tracks.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Navigation</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Unter <strong>Musik</strong> findest du Tabs für Tracks und Releases. Wechsle zwischen den Ansichten über die Tab-Navigation oben.</p>
                </div>
            </div>
        </div>

        {{-- 9. Submissions --}}
        <div id="submissions" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">9. Submissions</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung eingehender Musikeinsendungen.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Übersicht</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Submissions werden aus der externen Plattform (musicsubmission.theyellinglight.ch) importiert. Du siehst alle Einsendungen mit Status und kannst diese verwalten.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Status</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Ändere den Status einer Submission (z.B. Neu, In Prüfung, Angenommen, Abgelehnt).</p>
                </div>
            </div>
        </div>

        {{-- 10. Logo & Artwork --}}
        <div id="artwork" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">10. Logo & Artwork</h3>
            <p class="text-sm text-gray-600 mb-3">Verwaltung von Album-Cover, Logos und grafischen Materialien.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Erstellen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erstelle ein Artwork mit Titel und lade eine oder mehrere Bilddateien hoch. Vergib optionale Credits (Fotograf, Designer etc.).</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Artworks können mit Releases, Tracks und Projekten verknüpft werden.</p>
                </div>
            </div>
        </div>

        {{-- 11. Fotos --}}
        <div id="fotos" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">11. Fotos / Bilder</h3>
            <p class="text-sm text-gray-600 mb-3">Fotogalerie mit Ordnerstruktur und Teilen-Funktion.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Ordner</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Organisiere Fotos in Ordnern. Erstelle neue Ordner und lade Bilder per Batch-Upload hoch.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Teilen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Generiere einen <strong>Share-Link</strong> für einen Ordner. Externe Personen können über diesen Link die Galerie ansehen und Fotos herunterladen &ndash; ohne Login.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Metadaten</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Bearbeite Titel, Beschreibung und Credits einzelner Fotos.</p>
                </div>
            </div>
        </div>

        {{-- 12. Finanzen --}}
        <div id="finanzen" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">12. Finanzen (Rechnungen)</h3>
            <p class="text-sm text-gray-600 mb-3">Hier verwaltest du alle Rechnungen &ndash; sowohl solche, die du verschickst (ausgehend), als auch solche, die du erhältst (eingehend).</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Ausgehende vs. eingehende Rechnungen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <strong>Ausgehend</strong> = Du stellst jemandem eine Rechnung (z.B. Kunde soll dir Geld zahlen).<br>
                        <strong>Eingehend</strong> = Du erhältst eine Rechnung (z.B. Studiokosten, die du zahlen musst).
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Rechnung erstellen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erstelle Rechnungen mit Empfänger (Organisation/Kontakt), Positionen, Währung (CHF/EUR/USD) und Fälligkeitsdatum. Du kannst eine <strong>Rechnungsvorlage</strong> wählen, um Layout und Standardwerte (Absender, Bankverbindung, Zahlungskonditionen) automatisch vorzubelegen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Positionen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Füge beliebig viele Positionen hinzu (Beschreibung, Menge, Einzelpreis). Das Total wird automatisch berechnet. Optional kannst du einen MWST-Satz angeben &ndash; die MWST wird dann separat ausgewiesen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">PDF-Export</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Für ausgehende Rechnungen kannst du ein professionelles PDF mit Swiss QR-Bill generieren (für CHF-Rechnungen). Klicke auf <strong>«PDF»</strong> auf der Rechnungs-Detailseite.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Status &amp; Bezahlt markieren</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Rechnungen haben drei Status: <strong>Offen</strong>, <strong>Überfällig</strong> (automatisch nach Fälligkeitsdatum) und <strong>Bezahlt</strong>.<br>
                        Überfällige Rechnungen erscheinen auf dem Dashboard als Erinnerung.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Verknüpfung mit der Buchhaltung</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Du kannst eine Rechnung mit einer <strong>Buchhaltung</strong> und den passenden <strong>Soll-/Haben-Konten</strong> verknüpfen. Was das bedeutet:
                    </p>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1.5 space-y-1">
                        <li>Beim <strong>Erstellen</strong> der Rechnung wird automatisch eine Buchung in der Buchhaltung erfasst (z.B. «Debitoren an Ertrag»).</li>
                        <li>Beim <strong>Bezahlt-Markieren</strong> wird automatisch eine zweite Buchung erstellt (z.B. «Bank an Debitoren»).</li>
                        <li>Du musst dich also nicht um die Buchhaltung kümmern &ndash; das System bucht für dich!</li>
                    </ul>
                    <p class="text-sm text-gray-500 mt-1.5 italic">Tipp: Wenn du keine Buchhaltung brauchst, kannst du dieses Feld einfach leer lassen. Die Rechnung funktioniert trotzdem.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Soll-Konto &amp; Haben-Konto &ndash; einfach erklärt</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Bei der Verknüpfung mit der Buchhaltung musst du ein Soll- und ein Haben-Konto wählen. Hier die wichtigsten Faustregeln:
                    </p>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-1.5 font-medium">Rechnungstyp</th>
                                    <th class="text-left py-1.5 font-medium">Soll-Konto</th>
                                    <th class="text-left py-1.5 font-medium">Haben-Konto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr>
                                    <td class="py-1.5 text-gray-700 dark:text-gray-300">Ausgehend (du stellst Rechnung)</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Debitoren (1100)</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Ertragskonto (3xxx&ndash;8xxx)</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-700 dark:text-gray-300">Eingehend (du erhältst Rechnung)</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Aufwandkonto (4xxx&ndash;7xxx)</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Kreditoren (2001)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-sm text-gray-500 mt-1.5 italic">Tipp: Im Suchfeld kannst du nach Kontonummer oder Name suchen.</p>
                </div>
            </div>
        </div>

        {{-- 13. Buchhaltung --}}
        <div id="buchhaltung" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">13. Buchhaltung</h3>
            <p class="text-sm text-gray-600 mb-3">Die Buchhaltung erfasst alle Geldbewegungen deines Unternehmens. Das System nutzt die <strong>doppelte Buchhaltung</strong> &ndash; das klingt kompliziert, ist aber im Grunde einfach: Jede Buchung hat zwei Seiten (woher kommt das Geld, wohin geht es).</p>

            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Grundprinzip: Soll an Haben</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Jede Buchung bewegt Geld von einem Konto zum anderen:<br>
                        <strong>Soll-Konto</strong> = Wohin geht das Geld? (Empfänger-Konto, wird belastet)<br>
                        <strong>Haben-Konto</strong> = Woher kommt das Geld? (Quell-Konto, wird entlastet)
                    </p>
                    <div class="mt-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Beispiel:</strong> Du erhältst 500 CHF von einem Kunden auf dein Bankkonto.</p>
                        <p class="text-sm text-gray-600 mt-1">Soll: <em>Bank</em> (1020) &mdash; Geld kommt auf die Bank<br>Haben: <em>Debitoren</em> (1100) &mdash; Forderung wird aufgelöst</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Die 4 Kontotypen &ndash; einfach erklärt</h4>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-1.5 font-medium">Typ</th>
                                    <th class="text-left py-1.5 font-medium">Was ist das?</th>
                                    <th class="text-left py-1.5 font-medium">Beispiele</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr>
                                    <td class="py-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">Aktiven</span></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Was du <strong>besitzt</strong></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Bank, Kasse, Debitoren (offene Forderungen)</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">Passiven</span></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Was du <strong>schuldest</strong></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Kreditoren (offene Rechnungen), Darlehen, Eigenkapital</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Ertrag</span></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Was du <strong>verdienst</strong></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Honorare, Lizenzeinnahmen, Verkäufe</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">Aufwand</span></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Was du <strong>ausgibst</strong></td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Studiokosten, Reisekosten, Werbung, Miete</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Buchungstypen im Journal</h4>
                    <p class="text-sm text-gray-600 mb-2">Im Buchungsjournal siehst du verschiedene Buchungstypen. Diese beschreiben den <strong>Anlass</strong> der Buchung:</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-1.5 font-medium">Typ</th>
                                    <th class="text-left py-1.5 font-medium">Bedeutung</th>
                                    <th class="text-left py-1.5 font-medium">Beispiel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr>
                                    <td class="py-1.5 font-medium text-gray-700 dark:text-gray-300">Forderung</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Du hast eine Rechnung gestellt &ndash; Geld steht dir zu</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Debitoren <em>an</em> Ertrag</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 font-medium text-gray-700 dark:text-gray-300">Eingang</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Zahlung für eine Forderung erhalten</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Bank <em>an</em> Debitoren</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 font-medium text-gray-700 dark:text-gray-300">Verbindlichkeit</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Du hast eine Rechnung erhalten &ndash; du schuldest Geld</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Aufwand <em>an</em> Kreditoren</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 font-medium text-gray-700 dark:text-gray-300">Zahlung</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Du hast eine Verbindlichkeit bezahlt</td>
                                    <td class="py-1.5 text-gray-600 dark:text-gray-300">Kreditoren <em>an</em> Bank</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Zusammenhang:</strong> Forderung &rarr; Eingang = Ausgehende Rechnung gestellt, dann Geld erhalten.<br>
                            Verbindlichkeit &rarr; Zahlung = Eingehende Rechnung erhalten, dann bezahlt.
                        </p>
                    </div>
                    <p class="text-sm text-gray-500 mt-1.5 italic">Diese Buchungen werden automatisch erstellt, wenn du Rechnungen mit einer Buchhaltung verknüpfst und als «Bezahlt» markierst.</p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Buchhaltung erstellen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Erstelle eine neue Buchhaltung (typischerweise pro Jahr, z.B. «TYL 2026»). Nutze eine <strong>Kontenplan-Vorlage</strong> aus <strong>Settings &rarr; Vorlagen &rarr; Kontopläne</strong>, um die Konten automatisch anzulegen &ndash; so musst du nicht jedes Konto einzeln erfassen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Kontenplan</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Der Kontenplan ist die Liste aller Konten deiner Buchhaltung. Jedes Konto hat eine <strong>Nummer</strong> (z.B. 1020 = Bank), einen <strong>Namen</strong> und einen <strong>Typ</strong> (Aktiven/Passiven/Ertrag/Aufwand). Du kannst jederzeit neue Konten hinzufügen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Manuelle Buchung erfassen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Für Buchungen, die nicht automatisch über Rechnungen entstehen, kannst du manuell buchen. Gib Datum, Soll-Konto, Haben-Konto, Betrag und eine kurze Beschreibung an. Den Betrag immer <strong>positiv</strong> eingeben &ndash; die Richtung wird durch Soll/Haben bestimmt.
                    </p>
                    <div class="mt-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Beispiel:</strong> Studiokosten 200 CHF bar bezahlt.</p>
                        <p class="text-sm text-gray-600 mt-1">Soll: <em>Studiokosten</em> (Aufwand) &mdash; Haben: <em>Kasse</em> (Aktiven) &mdash; 200.00 CHF</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Auswertungen</h4>
                    <div class="space-y-2 mt-1">
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Buchungsjournal:</strong> Chronologische Liste aller Buchungen &ndash; wie ein Tagebuch deiner Finanzen.</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Kontoblatt:</strong> Alle Buchungen eines bestimmten Kontos. Klicke im Kontenplan auf ein Konto, um sein Kontoblatt zu sehen.</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Probebilanz:</strong> Übersicht aller Konten mit ihren Soll- und Haben-Summen. Dient zur Kontrolle &ndash; die Summe aller Soll-Buchungen muss gleich der Summe aller Haben-Buchungen sein.</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Bilanz:</strong> Zeigt dein Vermögen (Aktiven) und deine Schulden (Passiven). Die Differenz ist dein Eigenkapital. Du siehst die Eröffnungsbilanz (Jahresanfang) und die Schlussbilanz (aktueller Stand).</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Erfolgsrechnung:</strong> Zeigt alle Einnahmen (Ertrag) und Ausgaben (Aufwand). Ertrag minus Aufwand = Gewinn (oder Verlust). Dieses Ergebnis stimmt mit der Differenz in der Bilanz überein.</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Eröffnungssaldi</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Wenn du eine neue Buchhaltung für ein neues Jahr erstellst, solltest du die <strong>Endsaldi des Vorjahres</strong> als Anfangswerte übertragen (z.B. Bankstand, offene Forderungen). Dies geschieht über das Feld «Eröffnungssaldo» beim Hinzufügen eines Kontos.</p>
                    <p class="text-sm text-gray-500 mt-1 italic">Nur Aktiv- und Passivkonten haben Eröffnungssaldi. Ertrags- und Aufwandskonten starten immer bei 0.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Periode abschliessen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Am Ende eines Geschäftsjahres schliesst du die Buchhaltung ab. Danach können keine neuen Buchungen mehr erfasst werden. Falls nötig, kann die Periode wieder geöffnet werden.</p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Zusammenspiel Rechnungen &amp; Buchhaltung</h4>
                    <div class="mt-2 bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                        <p class="text-sm text-green-800 font-medium mb-2">So funktioniert der automatische Ablauf:</p>
                        <ol class="text-sm text-green-800 list-decimal list-inside space-y-1">
                            <li>Du erstellst eine <strong>Rechnung</strong> und verknüpfst sie mit einer Buchhaltung + Konten</li>
                            <li>Das System erstellt automatisch eine <strong>Buchung</strong> (Forderung oder Verbindlichkeit)</li>
                            <li>Wenn du die Rechnung als <strong>«Bezahlt»</strong> markierst, erstellt das System automatisch die <strong>Zahlungsbuchung</strong> (Eingang oder Zahlung)</li>
                        </ol>
                        <p class="text-sm text-green-700 mt-2">Du musst also nur Rechnungen erfassen und als bezahlt markieren &ndash; die Buchhaltung führt sich weitgehend von selbst!</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 14. Settings --}}
        <div id="settings" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">14. Settings</h3>
            <p class="text-sm text-gray-600 mb-3">Unter Settings findest du zwei Bereiche: <strong>Labels</strong> und <strong>Vorlagen</strong>.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Labels</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Verwalte die Kategorien und Typen, die im System verwendet werden:</p>
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
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Vorlagen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Vorlagen beschleunigen die Erstellung neuer Einträge:</p>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Kontopläne:</strong> Vorlagen für Buchhaltungs-Konten. Beim Erstellen einer neuen Buchhaltung werden die Konten aus der Vorlage übernommen.</li>
                        <li><strong>Rechnungsvorlagen:</strong> Layout und Standardwerte für Rechnungen (Absender, Bankverbindung, Zahlungsbedingungen).</li>
                        <li><strong>Vertragsvorlagen:</strong> Vertragstexte mit vordefinierten Parteien, Status und Bedingungen. Beim Erstellen eines Vertrags füllt die Vorlage automatisch alle Felder aus.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 15. Navigation --}}
        <div id="navigation" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">15. Navigation & Sidebar</h3>
            <p class="text-sm text-gray-600 mb-3">Tipps zur Nutzung der Sidebar-Navigation:</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Aufklappbare Bereiche</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Projekte</strong> und <strong>Finanzen</strong> sind aufklappbare Gruppen. Klicke auf den Namen, um zur Hauptseite zu navigieren. Klicke auf den <strong>Pfeil</strong> (Chevron), um die Untermenüpunkte ein-/auszuklappen. Die Gruppe öffnet sich automatisch, wenn du dich auf einer Unterseite befindest.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Sidebar einklappen</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Klicke auf die <strong>Doppelpfeile</strong> unten in der Sidebar, um sie einzuklappen. Im eingeklappten Modus siehst du nur die Icons. Fahre mit der Maus über ein Icon, um den Tooltip mit dem Namen zu sehen.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Mobile Ansicht</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Auf mobilen Geräten öffnest du die Sidebar über das <strong>Hamburger-Menü</strong> (drei Striche) oben links. Tippe ausserhalb der Sidebar, um sie wieder zu schliessen.</p>
                </div>
            </div>
        </div>

        {{-- 16. Benutzerverwaltung --}}
        <div id="benutzerverwaltung" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 scroll-mt-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">16. Benutzerverwaltung</h3>
            <p class="text-sm text-gray-600 mb-3">Unter <strong>Benutzerverwaltung</strong> in der Sidebar findest du verschiedene Werkzeuge und Informationen zur Systemverwaltung.</p>
            <div class="space-y-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Benutzeranleitung</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Diese Seite hier — die vollständige Dokumentation aller Funktionen des TYL Admin Panels.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Change Log</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Zeigt die Versionshistorie des Systems. Hier siehst du, welche neuen Funktionen, Änderungen und Fehlerbehebungen in jeder Version vorgenommen wurden.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Passwort ändern</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Öffnet die Profilseite, auf der du dein Passwort und deine E-Mail-Adresse ändern kannst.</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-100">Logfile (Aktivitätslog)</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Das Logfile zeigt alle Änderungen im System in chronologischer Reihenfolge. Für jeden Eintrag siehst du:</p>
                    <ul class="text-sm text-gray-600 list-disc list-inside mt-1 space-y-0.5">
                        <li><strong>Datum:</strong> Zeitpunkt der Änderung</li>
                        <li><strong>Benutzer:</strong> Wer die Änderung vorgenommen hat</li>
                        <li><strong>Aktion:</strong> Erstellt, Geändert oder Gelöscht</li>
                        <li><strong>Bereich:</strong> Welcher Datentyp betroffen ist (z.B. Kontakt, Vertrag, Rechnung)</li>
                        <li><strong>Objekt:</strong> Name des betroffenen Datensatzes</li>
                        <li><strong>Feld:</strong> Welches Feld geändert wurde (nur bei Änderungen)</li>
                        <li><strong>Alter / Neuer Wert:</strong> Der bisherige und der neue Wert des Felds</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-2">Du kannst das Logfile nach Aktion, Bereich, Datum und Suchbegriff filtern.</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Zurück nach oben --}}
    <div class="mt-6 text-center">
        <a href="#" class="text-sm text-gray-400 hover:text-gray-600 dark:text-gray-300">Nach oben</a>
    </div>
</div>
@endsection
