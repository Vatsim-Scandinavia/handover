{{-- A field that suggests matches as you type and submits the picked item's
     id as $name. Items come from $url (JSON) or from $options on the page;
     see resources/js/picker.js. It emits a `picked` event with the item. --}}
@props([
    'id',
    'name',
    'label',
    'button',
    'placeholder' => null,
    'url' => null,
    'options' => [],
    'minChars' => 0,
    'acceptDigits' => false,
    'disabledNote' => 'already added',
    'invalidHint' => 'Pick one from the list.',
    'buttonClass' => 'btn-outline-primary',
])
<div {{ $attributes->class('picker') }}
     x-data="picker(@js(['url' => $url, 'options' => $options, 'minChars' => $minChars, 'acceptDigits' => $acceptDigits]))">
    <label class="visually-hidden" for="{{ $id }}">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" :value="value">
    <div class="d-flex gap-2">
        <div class="picker-field">
            <input type="text" id="{{ $id }}" class="form-control" autocomplete="off" x-ref="input"
                   placeholder="{{ $placeholder }}" role="combobox" aria-autocomplete="list"
                   aria-controls="{{ $id }}-options" :aria-expanded="open"
                   :aria-activedescendant="open && active >= 0 ? '{{ $id }}-option-' + active : null"
                   :aria-invalid="invalid" aria-describedby="{{ $id }}-hint"
                   x-model="query" x-on:input="search()" x-on:focus="picked || search()"
                   x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
                   x-on:keydown.enter="enter($event)" x-on:keydown.escape="open = false" x-on:blur="open = false">
            <ul id="{{ $id }}-options" role="listbox" class="list-group picker-options" x-show="open" x-cloak>
                <template x-for="(item, i) in results" :key="item.id">
                    <li role="option" :id="'{{ $id }}-option-' + i" :aria-selected="i === active" :aria-disabled="!!item.disabled"
                        class="list-group-item list-group-item-action" :class="{ active: i === active, disabled: item.disabled }"
                        x-on:mousedown.prevent="pick(item)">
                        <span x-text="item.name"></span>
                        <span class="picker-detail" x-show="item.detail" x-text="item.detail"></span>
                        <span class="picker-note" x-show="item.disabled">{{ $disabledNote }}</span>
                    </li>
                </template>
                <li x-show="!results.length" class="list-group-item picker-empty">No matches.</li>
            </ul>
        </div>
        <button class="btn {{ $buttonClass }} text-nowrap">{{ $button }}</button>
    </div>
    <div id="{{ $id }}-hint" class="form-text text-danger" x-show="invalid" x-cloak>{{ $invalidHint }}</div>
</div>
