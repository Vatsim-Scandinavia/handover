<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <h5 class="mb-4">Your data has been shared to</h5>
        <div v-if="tokens.length > 0">

            <div v-for="token in tokens">
                {{ token.client.name }}
            </div>

        </div>
        <div v-if="tokens.length == 0">
            <p class="text-muted">No authorisations recorded.</p>
        </div>
    </div>
</template>

<script>

    import moment from 'moment';

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
                axios.get('/oauth/tokens')
                        .then(response => {
                            this.tokens = response.data;
                        });
            },

            /**
             * Revoke the given token.
             */
            revoke(token) {
                axios.delete('/oauth/tokens/' + token.id)
                        .then(response => {
                            this.getTokens();
                        });
            },

            /**
            * Format time
            */
            formatTime(timestamp){
                var date = new Date(timestamp);
                return date.toLocaleDateString("no-NO") + " " + date.toLocaleTimeString("no-NO");
            }
        }
    }
</script>
