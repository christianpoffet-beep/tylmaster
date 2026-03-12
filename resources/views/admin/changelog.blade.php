@extends('admin.layouts.app')

@section('title', 'Change Log')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Change Log</h2>
        <p class="text-sm text-gray-500 mt-1">Versionshistorie und Änderungen am TYL Admin Panel.</p>
    </div>

    <div class="space-y-6">

        {{-- v1.2.0 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900">v1.2.0</h3>
                <span class="text-sm text-gray-500">11.03.2026</span>
            </div>
            <div class="space-y-2">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Kontakte: Neue Felder Geschlecht (Männlich/Weiblich/Nicht definiert), Nationalität (Länderliste) und AHV-Nr. (mit Eingabemaske und Validierung)</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Organisationen: Neues Feld Rechtsform (AG, GmbH, Verein, Stiftung, Einzelfirma, Ltd, LLP, LLC)</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">PLZ-Autovervollständigung: Bei Eingabe einer Schweizer oder deutschen PLZ wird der Ort automatisch ausgefüllt (manuell überschreibbar)</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Länderliste als Dropdown für alle Adressfelder (Land, Land Bank, Nationalität) mit umfassender weltweiter Länderliste</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Organisationen: Suchbare Verknüpfung von Kontakten, Projekten und Verträgen mit Mehrfachauswahl und Inline-Schnellerstellung</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Projekte: Suchbare Verknüpfung von Kontakten und Organisationen mit Mehrfachauswahl und Inline-Schnellerstellung</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-2">Verbessert</span>
                    <span class="text-sm text-gray-600">Organisation-Suche: Zeigt jetzt alle Organisationen beim Klick ins Suchfeld an und unterstützt Mehrwort-Suche</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-2">Verbessert</span>
                    <span class="text-sm text-gray-600">Benutzeranleitung aktualisiert mit Dokumentation aller neuen Felder und Funktionen</span>
                </div>
            </div>
        </div>

        {{-- v1.1.0 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900">v1.1.0</h3>
                <span class="text-sm text-gray-500">11.03.2026</span>
            </div>
            <div class="space-y-2">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Benutzerverwaltung: Neuer Navigationsbereich mit Benutzeranleitung, Change Log, Passwort ändern und Logfile</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Aktivitätslog (Logfile): Automatische Protokollierung aller Änderungen mit Datum, Benutzer, Feld, alter/neuer Wert und Aktionstyp</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Change Log: Versionshistorie des Systems</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-2">Geändert</span>
                    <span class="text-sm text-gray-600">Benutzeranleitung von der Footer-Navigation in die Benutzerverwaltung verschoben</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-2">Geändert</span>
                    <span class="text-sm text-gray-600">Verträge können nicht mehr gelöscht werden (Löschschutz)</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 mr-2">Geändert</span>
                    <span class="text-sm text-gray-600">Vertragsdokumente sind vor versehentlichem Löschen geschützt</span>
                </div>
            </div>
        </div>

        {{-- v1.0.0 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900">v1.0.0</h3>
                <span class="text-sm text-gray-500">10.03.2026</span>
            </div>
            <div class="space-y-2">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Initiale Version: Dashboard, Kontakte, Organisationen, Dokumente, Projekte, Aufgaben, Verträge, Musik (Tracks & Releases), Submissions, Logo & Artwork, Fotos, Finanzen, Buchhaltung, Settings</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Searchable Dropdowns für alle Auswahlfelder</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Buchhaltungs-Wizard für geführte Einrichtung</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 mr-2">Neu</span>
                    <span class="text-sm text-gray-600">Benutzeranleitung (Handbuch)</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
