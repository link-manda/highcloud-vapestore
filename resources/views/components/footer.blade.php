<footer class="bg-[#000000] w-full py-16 px-8 border-t border-outline-variant/10">
    <div class="max-w-7xl mx-auto flex flex-col gap-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <!-- Brand & Tagline -->
            <div class="space-y-6">
                <div class="text-2xl font-extrabold tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-[#ba9eff] to-[#8455ef] headline">
                    Highcloud Vapestore
                </div>
                <p class="text-on-surface-variant font-body text-sm leading-relaxed max-w-xs">
                    Bali's premier destination for luxury vaping. Tropical aesthetics meets futuristic technology.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-on-primary-fixed transition-all duration-300">
                        <span class="material-symbols-outlined text-xl">language</span>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center text-on-surface-variant hover:bg-secondary hover:text-on-secondary-fixed transition-all duration-300">
                        <span class="material-symbols-outlined text-xl">share</span>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-6">
                <h4 class="headline font-bold text-on-background">Explore</h4>
                <nav class="flex flex-col gap-3">
                    <a href="#collections" class="text-on-surface-variant hover:text-secondary text-sm transition-colors">Collections</a>
                    <a href="#categories" class="text-on-surface-variant hover:text-secondary text-sm transition-colors">Categories</a>
                    <a href="#locations" class="text-on-surface-variant hover:text-secondary text-sm transition-colors">Bali Locations</a>
                    <a href="#testimonials" class="text-on-surface-variant hover:text-secondary text-sm transition-colors">Reviews</a>
                </nav>
            </div>

            <!-- Locations -->
            <div class="space-y-6">
                <h4 class="headline font-bold text-on-background">Our Sanctuaries</h4>
                <nav class="flex flex-col gap-3">
                    @forelse($cabangs->take(3) as $cabang)
                        <div class="text-on-surface-variant text-sm">
                            <div class="font-bold text-on-surface">{{ $cabang->nama_cabang }}</div>
                            <div class="opacity-60 text-xs">{{ Str::limit($cabang->alamat_cabang, 40) }}</div>
                        </div>
                    @empty
                        <div class="text-on-surface-variant text-sm opacity-60 italic">Opening soon...</div>
                    @endforelse
                </nav>
            </div>

            <!-- Newsletter -->
            <div class="space-y-6">
                <h4 class="headline font-bold text-on-background">Join the Cloud</h4>
                <p class="text-on-surface-variant text-sm">Get exclusive access to new drops and Bali events.</p>
                <div class="flex gap-2">
                    <input type="email" placeholder="Email address" class="flex-grow bg-surface-container-highest border border-outline-variant/20 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-primary transition-colors">
                    <button class="bg-primary text-on-primary-fixed px-4 py-2 rounded-xl headline font-bold text-sm hover:scale-105 transition-transform">Join</button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-12 border-t border-outline-variant/10">
            <p class="text-[#dee5ff]/40 text-xs uppercase tracking-widest font-bold">
                © {{ date('Y') }} Highcloud Vapestore. Tropical Futurism Crafted in Bali.
            </p>
            <div class="flex gap-8 text-xs text-[#dee5ff]/40 uppercase tracking-widest font-bold">
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-secondary"></span> 18+ Only</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-primary"></span> Authentic Only</span>
            </div>
        </div>
    </div>
</footer>
