<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }} @required($required) data-remote="{{ $url }}" data-placeholder="{{ $hint }}"@if($dependsOn) data-remote-depends="{{ $dependsOn }}"@endif>
    <option value="">{{ $hint }}</option>
    @if($selected !== null)<option value="{{ $selected }}" selected>{{ $selectedLabel }}</option>@endif
</select>
