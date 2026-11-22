<script setup>
import { computed } from 'vue';

/** A fieldset that edits a list of URIs, with the server's errors for each item and for the list. */
const uris = defineModel({ type: Array, required: true });

const props = defineProps({
    legend: { type: String, required: true },
    /** One item's name, such as "Redirect URI". */
    itemLabel: { type: String, required: true },
    placeholder: { type: String, required: true },
    /** The field's validation errors, keyed like Laravel's: `name` and `name.0`. */
    errors: { type: Object, required: true },
    errorKey: { type: String, required: true },
    /** The list can't shrink below this many items. */
    minItems: { type: Number, default: 0 },
});

/** Fires when the admin edits, adds or removes an item. */
const emit = defineEmits(['change']);

const itemName = computed(() => props.itemLabel[0].toLowerCase() + props.itemLabel.slice(1));

function add() {
    uris.value.push('');
    emit('change');
}

/** @param {number} index */
function remove(index) {
    uris.value.splice(index, 1);
    emit('change');
}
</script>

<template>
    <fieldset>
        <legend class="form-label fs-6">{{ legend }}</legend>
        <slot />
        <div v-for="(uri, index) in uris" :key="index" class="mb-2">
            <div class="input-group has-validation">
                <input
                    v-model="uris[index]"
                    type="url"
                    class="form-control"
                    :class="{ 'is-invalid': errors[`${errorKey}.${index}`] }"
                    :aria-label="`${itemLabel} ${index + 1}`"
                    :placeholder="placeholder"
                    @input="emit('change')"
                >
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="uris.length <= minItems"
                    :aria-label="`Remove ${itemName} ${index + 1}`"
                    @click="remove(index)"
                >
                    Remove
                </button>
                <div v-if="errors[`${errorKey}.${index}`]" class="invalid-feedback">
                    {{ errors[`${errorKey}.${index}`][0] }}
                </div>
            </div>
        </div>
        <div v-if="errors[errorKey]" class="text-danger small mb-2">{{ errors[errorKey][0] }}</div>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="add">
            Add {{ itemName }}
        </button>
    </fieldset>
</template>
