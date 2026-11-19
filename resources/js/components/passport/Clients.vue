<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

/**
 * @typedef {Object} OAuthClient
 * @property {string} id
 * @property {string} name
 * @property {string[]} redirect_uris
 * @property {string[]} grant_types
 * @property {boolean} revoked
 */

const props = defineProps({
    /** Grant types an admin can give any client, mapped to their labels. */
    grantTypes: { type: Object, required: true },
    /** Grant types ticked for a new client. */
    defaultGrantTypes: { type: Array, required: true },
});

/** @param {string} grant */
function grantLabel(grant) {
    return props.grantTypes[grant] ?? `${grant} (legacy)`;
}

/** @type {import('vue').Ref<OAuthClient[]>} */
const clients = ref([]);
const loading = ref(true);

/** Create/edit form state. A null id means "create". */
const form = ref(emptyForm());
/** @type {import('vue').Ref<Record<string, string[]>>} */
const errors = ref({});
const saving = ref(false);

/** The one-time plaintext secret shown after create/regenerate. */
const revealedSecret = ref('');
const copied = ref(false);

const formModalEl = ref(null);
const secretModalEl = ref(null);
/** @type {Modal|null} */
let formModal = null;
/** @type {Modal|null} */
let secretModal = null;

onMounted(() => {
    formModal = new Modal(formModalEl.value);
    secretModal = new Modal(secretModalEl.value);
    fetchClients();
});

async function fetchClients() {
    loading.value = true;
    try {
        const { data } = await axios.get('/admin/oauth-clients/data');
        clients.value = data;
    } finally {
        loading.value = false;
    }
}

function emptyForm() {
    return { id: null, name: '', redirectUris: [''], grantTypes: [...props.defaultGrantTypes], legacyGrantTypes: [] };
}

/** Legacy grant types appear only on clients that already hold them. */
const grantTypeOptions = computed(() => [...Object.keys(props.grantTypes), ...form.value.legacyGrantTypes]);

function openCreate() {
    form.value = emptyForm();
    errors.value = {};
    formModal.show();
}

/** @param {OAuthClient} client */
function openEdit(client) {
    form.value = {
        id: client.id,
        name: client.name,
        redirectUris: client.redirect_uris.length ? [...client.redirect_uris] : [''],
        grantTypes: [...client.grant_types],
        legacyGrantTypes: client.grant_types.filter((grant) => !(grant in props.grantTypes)),
    };
    errors.value = {};
    formModal.show();
}

function addRedirectUri() {
    form.value.redirectUris.push('');
}

/** @param {number} index */
function removeRedirectUri(index) {
    form.value.redirectUris.splice(index, 1);
}

async function save() {
    saving.value = true;
    errors.value = {};
    const editing = form.value.id !== null;
    const payload = {
        name: form.value.name,
        redirect_uris: form.value.redirectUris,
        grant_types: form.value.grantTypes,
    };

    try {
        const { data } = editing
            ? await axios.put(`/admin/oauth-clients/${form.value.id}`, payload)
            : await axios.post('/admin/oauth-clients', payload);

        formModal.hide();
        await fetchClients();

        if (!editing) {
            reveal(data.secret);
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            throw error;
        }
    } finally {
        saving.value = false;
    }
}

/** @param {OAuthClient} client */
async function destroy(client) {
    if (!window.confirm(`Delete OAuth client "${client.name}"? Existing tokens will be revoked.`)) {
        return;
    }
    await axios.delete(`/admin/oauth-clients/${client.id}`);
    await fetchClients();
}

/** @param {OAuthClient} client */
async function regenerate(client) {
    if (!window.confirm(`Regenerate the secret for "${client.name}"? The current secret stops working immediately.`)) {
        return;
    }
    const { data } = await axios.post(`/admin/oauth-clients/${client.id}/secret`);
    reveal(data.secret);
}

/** @param {string} secret */
function reveal(secret) {
    revealedSecret.value = secret;
    copied.value = false;
    secretModal.show();
}

async function copySecret() {
    await navigator.clipboard.writeText(revealedSecret.value);
    copied.value = true;
}
</script>

<template>
    <div class="card">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <span class="fw-semibold">OAuth Clients</span>
            <button type="button" class="btn btn-sm btn-primary" @click="openCreate">
                New client
            </button>
        </div>

        <div class="card-body">
            <p v-if="loading" class="text-muted mb-0">Loading…</p>

            <p v-else-if="clients.length === 0" class="text-muted mb-0">
                No OAuth clients yet.
            </p>

            <div v-else class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Redirect URIs</th>
                            <th scope="col">Grant types</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="client in clients" :key="client.id">
                            <td class="text-muted">{{ client.id }}</td>
                            <td>{{ client.name }}</td>
                            <td class="text-break">
                                <div v-for="uri in client.redirect_uris" :key="uri"><code>{{ uri }}</code></div>
                            </td>
                            <td class="small">{{ client.grant_types.map(grantLabel).join(', ') }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="openEdit(client)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="regenerate(client)">
                                        Regenerate
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="destroy(client)">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create / edit modal -->
    <div ref="formModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" @submit.prevent="save">
                <div class="modal-header">
                    <h5 class="modal-title">{{ form.id === null ? 'New OAuth client' : 'Edit OAuth client' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="client-name">Name</label>
                        <input
                            id="client-name"
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.name }"
                        >
                        <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                    </div>
                    <fieldset class="mb-3">
                        <legend class="form-label fs-6">Redirect URIs</legend>
                        <div v-for="(uri, index) in form.redirectUris" :key="index" class="mb-2">
                            <div class="input-group has-validation">
                                <input
                                    v-model="form.redirectUris[index]"
                                    type="url"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors[`redirect_uris.${index}`] }"
                                    :aria-label="`Redirect URI ${index + 1}`"
                                    placeholder="https://example.test/callback"
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    :disabled="form.redirectUris.length === 1"
                                    :aria-label="`Remove redirect URI ${index + 1}`"
                                    @click="removeRedirectUri(index)"
                                >
                                    Remove
                                </button>
                                <div v-if="errors[`redirect_uris.${index}`]" class="invalid-feedback">
                                    {{ errors[`redirect_uris.${index}`][0] }}
                                </div>
                            </div>
                        </div>
                        <div v-if="errors.redirect_uris" class="text-danger small mb-2">{{ errors.redirect_uris[0] }}</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="addRedirectUri">
                            Add redirect URI
                        </button>
                    </fieldset>
                    <fieldset class="mb-0">
                        <legend class="form-label fs-6">Grant types</legend>
                        <div v-for="grant in grantTypeOptions" :key="grant" class="form-check">
                            <input
                                :id="`grant-${grant}`"
                                v-model="form.grantTypes"
                                type="checkbox"
                                class="form-check-input"
                                :value="grant"
                            >
                            <label class="form-check-label" :for="`grant-${grant}`">{{ grantLabel(grant) }}</label>
                        </div>
                        <div v-if="errors.grant_types" class="text-danger small">{{ errors.grant_types[0] }}</div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- One-time secret modal -->
    <div ref="secretModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Client secret</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Copy this secret now — it is shown only once and cannot be retrieved later.
                    </p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" :value="revealedSecret" readonly>
                        <button type="button" class="btn btn-outline-secondary" @click="copySecret">
                            {{ copied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</template>
