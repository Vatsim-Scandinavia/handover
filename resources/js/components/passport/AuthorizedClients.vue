<style scoped>
    .action-link {
        cursor: pointer;
    }
</style>

<template>
    <div>
        <h2 class="mb-4">Account Linked to</h2>
        <div v-if="clients.length > 0">

            <div v-for="client in clients">
                <a v-bind:href="client.url" target="_blank">{{ client.name }}</a>
                <i class="fa-solid fa-arrow-up-right-from-square text-muted"></i>
            </div>

        </div>
        <div v-if="clients.length == 0">
            <p class="text-muted">Your data is not shared to any service yet.</p>
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
                clients: []
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            this.getClients();
        },

        methods: {
            /**
             * Get all of the third-party clients the user has authorized.
             */
            getClients() {
                axios.get('/account/authorized-clients')
                        .then(response => {
                            this.clients = response.data;
                        });
            }
        }
    }
</script>
