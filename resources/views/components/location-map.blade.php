<section class="w-full py-24 px-6 lg:px-12 max-w-screen-2xl mx-auto bg-surface-container-low border-y border-outline-variant/5">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div class="space-y-8">
            <h2 class="headline text-3xl md:text-5xl font-extrabold tracking-tight text-on-background">
                Visit Our <span class="text-secondary-dim">Bali Sanctuaries</span>
            </h2>
            <p class="text-on-surface-variant font-body text-lg leading-relaxed">
                Step into a world of tropical futurism. Our flagship stores are more than just shops—they are experiences designed to elevate your senses.
            </p>
            
            <div class="space-y-6">
                @forelse($cabangs as $cabang)
                    <div class="flex items-start gap-4 p-6 rounded-[1.5rem] bg-surface-container-highest border border-outline-variant/10 hover:border-secondary transition-colors cursor-pointer group">
                        <span class="material-symbols-outlined text-secondary mt-1">location_on</span>
                        <div>
                            <h3 class="headline font-bold text-xl text-on-background group-hover:text-secondary transition-colors">{{ $cabang->nama_cabang }}</h3>
                            <p class="text-on-surface-variant text-sm mt-1">{{ $cabang->alamat_cabang }}</p>
                            @if($cabang->telepon_cabang)
                                <div class="mt-2 text-xs text-secondary/60 uppercase tracking-widest font-bold">Tel: {{ $cabang->telepon_cabang }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 rounded-[2.5rem] bg-surface-container-highest border border-dashed border-outline-variant/20 flex flex-col items-center justify-center text-center">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/20 mb-4">storefront</span>
                        <h3 class="headline font-bold text-xl text-on-surface-variant">Expansion in Progress</h3>
                        <p class="text-on-surface-variant text-sm mt-2">New Bali sanctuaries are being prepared. Stay tuned.</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        <!-- Mock Map -->
        <div class="relative rounded-[3rem] overflow-hidden aspect-square lg:aspect-auto lg:h-full min-h-[400px] border border-outline-variant/20 shadow-2xl shadow-secondary/5">
            <div class="absolute inset-0 bg-[#0a0f1d]">
                <!-- Abstract Map Pattern -->
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#dee5ff 1px, transparent 1px); background-size: 30px 30px;"></div>
                <!-- Animated Ocean -->
                <div class="absolute bottom-0 left-0 right-0 h-1/3 bg-gradient-to-t from-secondary/10 to-transparent"></div>
                
                <!-- Map Pins (Mock) -->
                <div class="absolute top-1/4 left-1/3 w-4 h-4 bg-secondary rounded-full animate-ping"></div>
                <div class="absolute top-1/4 left-1/3 w-3 h-3 bg-secondary rounded-full shadow-[0_0_20px_rgba(83,221,252,0.8)]"></div>
                
                <div class="absolute top-1/2 left-2/3 w-4 h-4 bg-primary rounded-full animate-ping"></div>
                <div class="absolute top-1/2 left-2/3 w-3 h-3 bg-primary rounded-full shadow-[0_0_20px_rgba(186,158,255,0.8)]"></div>
                
                <div class="absolute bottom-1/4 left-1/2 w-4 h-4 bg-tertiary rounded-full animate-ping"></div>
                <div class="absolute bottom-1/4 left-1/2 w-3 h-3 bg-tertiary rounded-full shadow-[0_0_20px_rgba(105,156,255,0.8)]"></div>
                
                <!-- Map Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-surface/40 via-transparent to-surface/40 pointer-events-none"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="px-6 py-3 bg-surface-container-highest/80 backdrop-blur-xl rounded-full border border-outline-variant/30 text-dee5ff headline font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">map</span>
                        Interactive Map Coming Soon
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
