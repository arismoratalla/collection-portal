import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

const mapElement = document.querySelector('[data-collection-map]');

if (mapElement) {
    const container = document.querySelector('#collection-map');
    const empty = mapElement.querySelector('[data-map-empty]');
    const status = mapElement.querySelector('[data-map-status]');
    const toggle = mapElement.querySelector('[data-map-toggle]');
    const fit = mapElement.querySelector('[data-map-fit]');
    const form = document.querySelector('#collection-search-form');
    const tileUrl = document.querySelector('meta[name="portal-map-tile-url"]')?.content;
    const attribution = document.querySelector('meta[name="portal-map-attribution"]')?.content;
    const map = L.map(container, { scrollWheelZoom: false }).setView([20, 0], 2);
    const markers = L.markerClusterGroup();

    L.tileLayer(tileUrl, { attribution, maxZoom: 19 }).addTo(map);
    map.addLayer(markers);

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));

    const loadMarkers = async () => {
        const params = new URLSearchParams(new FormData(form));
        const response = await fetch(`${mapElement.dataset.mapUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            status.textContent = 'Map data could not be loaded.';
            return;
        }

        const data = await response.json();
        markers.clearLayers();

        data.markers.forEach((point) => {
            const marker = L.marker([point.latitude, point.longitude]);
            const url = `${mapElement.dataset.specimenUrl}/${encodeURIComponent(point.occurrence_id)}`;
            marker.bindPopup(`<strong>${escapeHtml(point.catalog_number)}</strong><br><em>${escapeHtml(point.scientific_name)}</em><br>${escapeHtml(point.locality)}<br>${escapeHtml(point.collection)}<br><a href="${url}">View specimen</a>`);
            markers.addLayer(marker);
        });

        if (data.markers.length === 0) {
            container.classList.add('hidden');
            empty.classList.remove('hidden');
            status.textContent = 'No public coordinates in these results.';
            return;
        }

        container.classList.remove('hidden');
        empty.classList.add('hidden');
        status.textContent = `${data.markers.length.toLocaleString()} public mapped records${data.truncated ? ' (bounded map response)' : ''}.`;
        map.fitBounds(markers.getBounds(), { padding: [24, 24], maxZoom: 12 });
    };

    toggle.addEventListener('click', () => {
        const hidden = container.classList.toggle('hidden');
        empty.classList.toggle('hidden', !hidden || markers.getLayers().length > 0);
        toggle.textContent = hidden ? 'Show map' : 'Hide map';
        if (!hidden) map.invalidateSize();
    });
    fit.addEventListener('click', () => markers.getLayers().length && map.fitBounds(markers.getBounds(), { padding: [24, 24], maxZoom: 12 }));
    loadMarkers();

    document.querySelectorAll('[data-facet]').forEach((facet) => {
        const input = facet.querySelector('[data-facet-search]');
        const more = facet.querySelector('[data-load-more]');
        let page = 1;
        const fetchFacet = async (nextPage = 1) => {
            const params = new URLSearchParams(new FormData(form));
            params.set('facet_search', input.value);
            params.set('facet_page', String(nextPage));
            const response = await fetch(`${facet.dataset.url}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            facet.dataset.loaded = 'true';
            const selected = new Set(params.getAll(`${data.key}[]`));
            const values = facet.querySelector('[data-facet-values]');
            if (nextPage === 1) values.innerHTML = '';
            data.values.forEach((option) => {
                const id = `facet-${data.key}-${btoa(unescape(encodeURIComponent(option.value))).replace(/=/g, '')}`;
                values.insertAdjacentHTML('beforeend', `<label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700"><input id="${id}" type="checkbox" name="${data.key}[]" value="${escapeHtml(option.value)}" ${selected.has(option.value) ? 'checked' : ''} class="mt-0.5 rounded border-slate-300 text-teal-700 focus:ring-teal-600"><span class="min-w-0 flex-1 break-words">${escapeHtml(option.value)}</span><span class="text-xs text-slate-400">${Number(option.count).toLocaleString()}</span></label>`);
            });
            page = data.page;
            if (more) more.hidden = !data.has_more;
        };
        input.addEventListener('input', () => fetchFacet(1));
        more?.addEventListener('click', () => fetchFacet(page + 1));
        facet.closest('details')?.addEventListener('toggle', (event) => {
            if (event.currentTarget.open && facet.dataset.loaded !== 'true') {
                fetchFacet();
            }
        });
    });
}
