{{-- An account of the statement's accounting; $options already filtered by type --}}
<select name="{{ $name }}" class="{{ $input }}">
    <option value="">— wählen —</option>
    @foreach($options as $option)
        <option value="{{ $option->id }}" @selected((string) $selected === (string) $option->id)>{{ $option->number }} {{ $option->name }}</option>
    @endforeach
</select>
