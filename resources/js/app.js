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

/*
 * Event route map: draws the GPX track ([lat, lng] pairs) on OpenStreetMap tiles.
 * Leaflet is loaded on demand so other pages don't download it.
 */
Alpine.data('routeMap', (points) => ({
    ready: false,

    async init() {
        const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);

        const map = L.map(this.$root, { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        const route = L.polyline(points, { color: '#ee8f12', weight: 4 }).addTo(map);
        L.circleMarker(points[0], { radius: 7, color: '#fff', weight: 2, fillColor: '#2f7d52', fillOpacity: 1 }).bindTooltip('Start').addTo(map);
        L.circleMarker(points[points.length - 1], { radius: 7, color: '#fff', weight: 2, fillColor: '#b91c1c', fillOpacity: 1 }).bindTooltip('Finish').addTo(map);

        map.fitBounds(route.getBounds(), { padding: [24, 24] });
        this.ready = true;
    },
}));

/*
 * Difficulty calculator: live effort score and level. The formula parameters and level
 * texts come from the server (DifficultyCalculator::toArray), so this always matches it.
 */
Alpine.data('difficultyCalculator', ({ calculator, levels, km, gain, loss }) => ({
    km: km ?? '',
    gain: gain ?? '',
    loss: loss ?? '',

    get ready() {
        return Number(this.km) > 0;
    },

    get equivalentKm() {
        return Math.max(0, Number(this.km) || 0)
            + Math.max(0, Number(this.gain) || 0) / calculator.gainPerKm
            + Math.max(0, Number(this.loss) || 0) / calculator.lossPerKm;
    },

    get score() {
        return Math.round(calculator.multiplier * this.equivalentKm);
    },

    get level() {
        const value = calculator.levels.find((l) => l.max === null || this.score <= l.max).value;
        return levels.find((l) => l.value === value);
    },
}));

window.Alpine = Alpine;
Alpine.start();
