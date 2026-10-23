import Alpine from 'alpinejs';

/*
 * "Load More" for server-rendered lists: the link points at the same page with the next
 * ?page=, which renders the longer list. Fetch it and swap this section for the new one,
 * so it works without JavaScript too (the link just navigates).
 */
Alpine.data('loadMore', () => ({
    loading: false,

    async load(event) {
        const url = event.currentTarget.href;
        this.loading = true;

        try {
            const response = await fetch(url, { headers: { Accept: 'text/html' } });
            const html = await response.text();
            const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById(this.$root.id);

            if (!response.ok || !fresh) {
                throw new Error('Unexpected response');
            }

            history.replaceState(null, '', url);
            this.$root.replaceWith(fresh);
        } catch {
            window.location.href = url;
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();
