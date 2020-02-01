<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <h5>Your Authorized Sessions</h5>
        <div v-if="tokens.length > 0">

            <div v-for="token in tokens">
                <p><b>{{ token.client.name }}</b> | Created {{ token.created_at }} &nbsp;<a class="action-link badge badge-danger text-white" style="font-weight: normal" @click="revoke(token)">Revoke</a></p>
            </div>

        </div>
        <div v-if="tokens.length == 0">
            <p class="text-muted">No authorisations recorded.</p>
        </div>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                tokens: []
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        methods: {
            /**
             * Prepare the component (Vue 2.x).
             */
            prepareComponent() {
                this.getTokens();
            },

            /**
             * Get all of the authorized tokens for the user.
             */
            getTokens() {
                axios.get(process.env.MIX_APP_URL + '/oauth/tokens')
                        .then(response => {
                            this.tokens = response.data;
                        });
            },

            /**
             * Revoke the given token.
             */
            revoke(token) {
                axios.delete(process.env.MIX_APP_URL + '/oauth/tokens/' + token.id)
                        .then(response => {
                            this.getTokens();
                        });
            }
        }
    }
</script>
