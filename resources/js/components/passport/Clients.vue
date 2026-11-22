<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import UriListField from './UriListField.vue';

/**
 * @typedef {Object} OAuthClient
 * @property {string} id
 * @property {string} name
 * @property {boolean} confidential
 * @property {string[]} redirect_uris
 * @property {string[]} grant_types
 * @property {string[]} allowed_scopes
 * @property {string[]} allowed_origins
 * @property {string[]} post_logout_redirect_uris
 * @property {boolean} single_session
 * @property {string[]} required_groups
 * @property {boolean} revoked
 */

const props = defineProps({
    /** Grant types an admin can give any client, mapped to their labels. */
    grantTypes: { type: Object, required: true },
    /** Grant types ticked for a new client, and the only ones a public client may have. */
    defaultGrantTypes: { type: Array, required: true },
    /** Every scope, mapped to its description. */
    scopes: { type: Object, required: true },
    /** Every group, as { id, name }, sorted by name. */
    groups: { type: Array, required: true },
});

const groupNames = computed(() => Object.fromEntries(props.groups.map((group) => [group.id, group.name])));

/** @param {OAuthClient} client */
function requiredGroupsLabel(client) {
    if (client.required_groups.length === 0) {
        return 'All members';
    }
    return client.required_groups.map((id) => groupNames.value[id] ?? id).join(', ');
}

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
    return {
        id: null,
        name: '',
        confidential: true,
        redirectUris: [''],
        grantTypes: [...props.defaultGrantTypes],
        legacyGrantTypes: [],
        // Admins pick a new client's scopes by hand, so none are ticked.
        allowedScopes: [],
        allowedOrigins: [],
        // Until the admin edits them, a new public client's allowed origins follow its redirect URIs.
        originsFollowRedirectUris: true,
        postLogoutRedirectUris: [],
        singleSession: true,
        requiredGroups: [],
    };
}

/**
 * The grant types a client of the type may have.
 * @param {boolean} confidential
 * @returns {string[]}
 */
function grantTypesFor(confidential) {
    return confidential ? Object.keys(props.grantTypes) : props.defaultGrantTypes;
}

/** Other grant types appear only on clients that already hold them, so an admin can untick them. */
const grantTypeOptions = computed(() => [...grantTypesFor(form.value.confidential), ...form.value.legacyGrantTypes]);

/** The first error for the grant types list or any of its entries. */
const grantTypesError = computed(() => Object.entries(errors.value)
    .find(([key]) => key === 'grant_types' || key.startsWith('grant_types.'))?.[1][0]);

/**
 * The origin of a URI, as the browser sends it in the Origin header.
 * @param {string} uri
 * @returns {string|null}
 */
function originOf(uri) {
    try {
        const { origin } = new URL(uri);
        return origin === 'null' ? null : origin;
    } catch {
        return null;
    }
}

watch(
    () => [form.value.confidential, form.value.originsFollowRedirectUris, ...form.value.redirectUris],
    () => {
        if (form.value.id === null && !form.value.confidential && form.value.originsFollowRedirectUris) {
            form.value.allowedOrigins = [...new Set(form.value.redirectUris.map(originOf).filter(Boolean))];
        }
    },
);

/** Switching a new client to public drops the grant types it may not have. */
watch(
    () => form.value.confidential,
    (confidential) => {
        if (!confidential) {
            form.value.grantTypes = form.value.grantTypes.filter((grant) => props.defaultGrantTypes.includes(grant));
        }
    },
);

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
        confidential: client.confidential,
        redirectUris: client.redirect_uris.length ? [...client.redirect_uris] : [''],
        grantTypes: [...client.grant_types],
        legacyGrantTypes: client.grant_types.filter((grant) => !grantTypesFor(client.confidential).includes(grant)),
        allowedScopes: [...client.allowed_scopes],
        allowedOrigins: [...client.allowed_origins],
        originsFollowRedirectUris: false,
        postLogoutRedirectUris: [...client.post_logout_redirect_uris],
        singleSession: client.single_session,
        requiredGroups: [...client.required_groups],
    };
    errors.value = {};
    formModal.show();
}

async function save() {
    saving.value = true;
    errors.value = {};
    const editing = form.value.id !== null;
    const payload = {
        name: form.value.name,
        redirect_uris: form.value.redirectUris,
        grant_types: form.value.grantTypes,
        allowed_scopes: form.value.allowedScopes,
        post_logout_redirect_uris: form.value.postLogoutRedirectUris,
        single_session: form.value.singleSession,
        required_groups: form.value.requiredGroups,
    };
    if (!editing) {
        payload.confidential = form.value.confidential;
    }
    if (!form.value.confidential) {
        payload.allowed_origins = form.value.allowedOrigins;
    }

    try {
        const { data } = editing
            ? await axios.put(`/admin/oauth-clients/${form.value.id}`, payload)
            : await axios.post('/admin/oauth-clients', payload);

        formModal.hide();
        await fetchClients();

        if (!editing && data.secret) {
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
                            <th scope="col">Scopes</th>
                            <th scope="col">Required groups</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="client in clients" :key="client.id">
                            <td class="text-muted">{{ client.id }}</td>
                            <td>
                                {{ client.name }}
                                <span v-if="!client.confidential" class="badge text-bg-secondary ms-1">Public</span>
                            </td>
                            <td class="text-break">
                                <div v-for="uri in client.redirect_uris" :key="uri"><code>{{ uri }}</code></div>
                            </td>
                            <td class="small">{{ client.grant_types.map(grantLabel).join(', ') }}</td>
                            <td class="small"><code>{{ client.allowed_scopes.join(' ') }}</code></td>
                            <td class="small">{{ requiredGroupsLabel(client) }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="openEdit(client)">
                                        Edit
                                    </button>
                                    <button
                                        v-if="client.confidential"
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="regenerate(client)"
                                    >
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
                    <fieldset v-if="form.id === null" class="mb-3">
                        <legend class="form-label fs-6">Client type</legend>
                        <div class="form-check">
                            <input
                                id="client-type-confidential"
                                v-model="form.confidential"
                                type="radio"
                                class="form-check-input"
                                :value="true"
                            >
                            <label class="form-check-label" for="client-type-confidential">Confidential</label>
                            <div class="text-muted small">The service runs on a server and keeps a secret.</div>
                        </div>
                        <div class="form-check">
                            <input
                                id="client-type-public"
                                v-model="form.confidential"
                                type="radio"
                                class="form-check-input"
                                :value="false"
                            >
                            <label class="form-check-label" for="client-type-public">Public</label>
                            <div class="text-muted small">The service runs in the browser, has no secret and must use PKCE. You cannot change the type later.</div>
                        </div>
                        <div v-if="errors.confidential" class="text-danger small">{{ errors.confidential[0] }}</div>
                    </fieldset>
                    <p v-else class="text-muted small">
                        {{ form.confidential ? 'Confidential client' : 'Public client: no secret, PKCE required' }}
                    </p>
                    <UriListField
                        v-model="form.redirectUris"
                        class="mb-3"
                        legend="Redirect URIs"
                        item-label="Redirect URI"
                        placeholder="https://example.test/callback"
                        :errors="errors"
                        error-key="redirect_uris"
                        :min-items="1"
                    />
                    <UriListField
                        v-model="form.postLogoutRedirectUris"
                        class="mb-3"
                        legend="Post-logout redirect URIs"
                        item-label="Post-logout redirect URI"
                        placeholder="https://example.test/logged-out"
                        :errors="errors"
                        error-key="post_logout_redirect_uris"
                    >
                        <p class="text-muted small mb-1">Where the service may send members after it logs them out of Handover. Leave empty if the service does not log members out through Handover.</p>
                    </UriListField>
                    <UriListField
                        v-if="!form.confidential"
                        v-model="form.allowedOrigins"
                        class="mb-3"
                        legend="Allowed origins"
                        item-label="Allowed origin"
                        placeholder="https://example.test"
                        :errors="errors"
                        error-key="allowed_origins"
                        @change="form.originsFollowRedirectUris = false"
                    >
                        <p class="text-muted small mb-1">The origins the service may call the token and UserInfo endpoints from, such as <code>https://example.test</code>.</p>
                    </UriListField>
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
                        <div v-if="grantTypesError" class="text-danger small">{{ grantTypesError }}</div>
                    </fieldset>
                    <fieldset class="mt-3 mb-0">
                        <legend class="form-label fs-6">Allowed scopes</legend>
                        <div v-for="(description, scope) in scopes" :key="scope" class="form-check">
                            <input
                                :id="`scope-${scope}`"
                                v-model="form.allowedScopes"
                                type="checkbox"
                                class="form-check-input"
                                :value="scope"
                            >
                            <label class="form-check-label" :for="`scope-${scope}`">
                                <code>{{ scope }}</code> <span class="text-muted small">{{ description }}</span>
                            </label>
                        </div>
                        <div v-if="errors.allowed_scopes" class="text-danger small">{{ errors.allowed_scopes[0] }}</div>
                    </fieldset>
                    <fieldset class="mt-3 mb-0">
                        <legend class="form-label fs-6">Required groups</legend>
                        <p class="text-muted small mb-1">Only members of a ticked group may sign in. Tick none to admit every member.</p>
                        <div class="border rounded p-2 overflow-auto" style="max-height: 12rem">
                            <p v-if="groups.length === 0" class="text-muted small mb-0">No groups yet.</p>
                            <div v-for="group in groups" :key="group.id" class="form-check">
                                <input
                                    :id="`group-${group.id}`"
                                    v-model="form.requiredGroups"
                                    type="checkbox"
                                    class="form-check-input"
                                    :value="group.id"
                                >
                                <label class="form-check-label" :for="`group-${group.id}`">{{ group.name }}</label>
                            </div>
                        </div>
                        <div v-if="errors.required_groups" class="text-danger small">{{ errors.required_groups[0] }}</div>
                    </fieldset>
                    <div class="form-check mt-3">
                        <input id="client-single-session" v-model="form.singleSession" type="checkbox" class="form-check-input">
                        <label class="form-check-label" for="client-single-session">Single session</label>
                        <div class="text-muted small">A new sign-in revokes the member's earlier tokens for this client. Untick it to let members stay signed in on several devices.</div>
                    </div>
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
