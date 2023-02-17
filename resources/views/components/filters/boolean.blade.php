@php
    $name = $filter->queryName();
    $classes = 'boolean ' . $name;
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    @foreach ($filter->options() as $key => $option)
        <label class="filter-input">
            <input
            class="filter-checkbox"
            name="{{ $name }}[{{ $option }}]"
            type="checkbox"
            onchange="this.form.submit()"
            value="1"
            {{ request()->get($name . '.' . $option, false) ? 'checked' : '' }}
        > {{ is_numeric($key) ? $option : $key }}</label>
    @endforeach
</x-lacodix-filter::filters.layout>