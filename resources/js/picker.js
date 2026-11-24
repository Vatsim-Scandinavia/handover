// A text field that suggests matches as you type, for picking one thing by
// name: a user from a lookup URL, or a group from a list on the page. Items
// look like { id, name, detail?, disabled? }. Disabled items show, with a
// note, but cannot be picked. With acceptDigits, a typed number is the value
// even when nothing is picked, so a CID works for users not in the list.
export default ({ url = null, options = [], minChars = 0, acceptDigits = false }) => ({
    query: '',
    results: [],
    open: false,
    active: -1,
    picked: null,
    invalid: false,
    timer: null,
    request: 0,

    init() {
        this.$el.closest('form')?.addEventListener('submit', (event) => {
            if (this.value === '') {
                event.preventDefault();
                this.invalid = true;
                this.$refs.input.focus();
            }
        });
    },

    get value() {
        if (this.picked) {
            return String(this.picked.id);
        }
        const q = this.query.trim();

        return acceptDigits && /^\d+$/.test(q) ? q : '';
    },

    search() {
        if (this.picked) {
            this.picked = null;
            this.$dispatch('picked', null);
        }
        this.invalid = false;
        const q = this.query.trim();
        if (q.length < minChars) {
            this.results = [];
            this.open = false;

            return;
        }
        if (!url) {
            this.show(this.filter(q), q);

            return;
        }
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.lookup(q), 200);
    },

    async lookup(q) {
        const request = ++this.request;
        const response = await fetch(`${url}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        const results = response.ok ? await response.json() : [];
        if (request === this.request) {
            this.show(results, q);
        }
    },

    filter(q) {
        const words = q.toLowerCase().split(/\s+/).filter(Boolean);

        return options.filter((option) => words.every((word) => option.name.toLowerCase().includes(word)));
    },

    show(results, q) {
        this.results = results;
        // A typed CID only highlights its exact match, so Enter never swaps
        // it for a longer CID that merely starts with it.
        this.active = acceptDigits && /^\d+$/.test(q)
            ? results.findIndex((item) => String(item.id) === q && !item.disabled)
            : results.findIndex((item) => !item.disabled);
        this.open = true;
    },

    pick(item) {
        if (!item || item.disabled) {
            return;
        }
        this.picked = item;
        this.query = item.detail ? `${item.name} (${item.detail})` : item.name;
        this.open = false;
        this.$dispatch('picked', item);
    },

    // Step through the pickable items, skipping disabled ones.
    move(step) {
        if (!this.open) {
            this.search();

            return;
        }
        const n = this.results.length;
        for (let i = 1; i <= n; i++) {
            const next = (((this.active + step * i) % n) + n) % n;
            if (!this.results[next].disabled) {
                this.active = next;

                return;
            }
        }
    },

    enter(event) {
        if (this.open && this.active >= 0) {
            event.preventDefault();
            this.pick(this.results[this.active]);
        }
    },
});
