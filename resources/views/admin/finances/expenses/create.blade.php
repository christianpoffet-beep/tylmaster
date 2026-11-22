@extends('admin.layouts.app')

@section('title', 'Neue Ausgabe')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.finances.expenses._form')

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Ausgabe erstellen</button>
            <a href="{{ route('admin.expenses.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
