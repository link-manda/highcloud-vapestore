@props(['cabangs'])

<section id="locations" class="py-24 md:py-32 px-6 md:px-16 max-w-[1440px] mx-auto overflow-hidden">
    <div class="mb-16">
        <span class="font-headline font-bold text-[0.75rem] uppercase tracking-[0.2em] text-gallery-dim">Visit Us</span>
        <h2 class="font-headline font-extrabold text-[2.5rem] md:text-[4rem] leading-tight tracking-tight mt-4">Bali Sanctuaries</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-0 border border-gallery-border bg-white rounded-sm overflow-hidden">
        <!-- Branch List -->
        <div class="lg:col-span-4 border-b lg:border-b-0 lg:border-r border-gallery-border max-h-[600px] overflow-y-auto custom-scrollbar">
            @forelse($cabangs as $cabang)
                <div onclick="focusMap({{ $cabang->latitude ?? -8.65 }}, {{ $cabang->longitude ?? 115.22 }})" 
                     class="p-8 border-b border-gallery-border last:border-b-0 hover:bg-gallery-bg transition-colors cursor-pointer group">
                    <span class="text-[0.65rem] font-bold uppercase tracking-widest text-gallery-dim mb-2 block">Flagship {{ $loop->iteration }}</span>
                    <h3 class="font-headline font-bold text-xl mb-3 group-hover:text-black transition-colors">{{ $cabang->nama_cabang }}</h3>
                    <p class="font-body text-sm text-gallery-muted leading-relaxed mb-4">
                        {{ $cabang->alamat_cabang }}
                    </p>
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest opacity-40 group-hover:opacity-100 transition-opacity">
                        <span class="material-symbols-outlined text-sm">call</span>
                        <span>{{ $cabang->telepon_cabang ?? 'Contact info unavailable' }}</span>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <p class="font-body text-gallery-dim">No locations found.</p>
                </div>
            @endforelse
        </div>

        <!-- Map Container -->
        <div class="lg:col-span-8 relative min-h-[400px] lg:min-h-[600px] z-10">
            <div id="map" class="absolute inset-0"></div>
        </div>
    </div>
</section>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #ccc; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #999; }
    
    #map { height: 100%; width: 100%; }
    .leaflet-container { background: #FAFAFA !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        const map = L.map('map', {
            zoomControl: false,
            scrollWheelZoom: false
        }).setView([-8.65, 115.22], 12); // Default to Bali center

        // Add premium tiles with visible street details (CartoDB Voyager)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Add Zoom Control at bottom right
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Custom Marker Icon
        const galleryIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color: #000; width: 12px; height: 12px; border-radius: 50%; border: 3px solid #FFF; box-shadow: 0 0 15px rgba(0,0,0,0.2);'></div>",
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        });

        // Add Markers from Database
        const cabangs = @json($cabangs);
        const markers = [];

        cabangs.forEach(cabang => {
            if (cabang.latitude && cabang.longitude) {
                const marker = L.marker([cabang.latitude, cabang.longitude], { icon: galleryIcon })
                    .addTo(map)
                    .bindPopup(`
                        <div class="font-headline p-2">
                            <strong class="text-lg">${cabang.nama_cabang}</strong><br>
                            <p class="text-sm mt-1 text-gray-600">${cabang.alamat_cabang}</p>
                        </div>
                    `);
                markers.push(marker);
            }
        });

        // Function to focus map
        window.focusMap = function(lat, lng) {
            map.flyTo([lat, lng], 15, {
                duration: 1.5
            });
        };

        // If markers exist, fit bounds
        if (markers.length > 0) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    });
</script>
