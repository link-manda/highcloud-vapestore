<section id="categories" x-data="{ 
    hoveredImage: null, 
    mouseX: 0, 
    mouseY: 0,
    updateMouse(e) {
        this.mouseX = e.clientX;
        this.mouseY = e.clientY;
    }
}" @mousemove="updateMouse($event)" class="py-24 md:py-32 px-6 md:px-16 max-w-[1440px] mx-auto overflow-hidden relative">
    <div class="mb-16">
        <span class="font-headline font-bold text-[0.75rem] uppercase tracking-[0.2em] text-gallery-dim">Browse Departments</span>
        <h2 class="font-headline font-extrabold text-[2.5rem] md:text-[4rem] leading-tight tracking-tight mt-4">Collections</h2>
    </div>

    <div class="flex flex-col border-t border-gallery-border">
        @php
            $categories = [
                [
                    'id' => '01', 
                    'name' => 'Disposable', 
                    'count' => '42',
                    'image' => 'https://images.unsplash.com/photo-1574044536246-12002888a75a?auto=format&fit=crop&q=80&w=600'
                ],
                [
                    'id' => '02', 
                    'name' => 'Pod Systems', 
                    'count' => '18',
                    'image' => 'https://images.unsplash.com/photo-1552819056-421522630735?auto=format&fit=crop&q=80&w=600'
                ],
                [
                    'id' => '03', 
                    'name' => 'E-Liquid', 
                    'count' => '124',
                    'image' => 'https://images.unsplash.com/photo-1594465919760-441fe5908ab0?auto=format&fit=crop&q=80&w=600'
                ],
                [
                    'id' => '04', 
                    'name' => 'Accessories', 
                    'count' => '25',
                    'image' => 'https://images.unsplash.com/photo-1510166089176-b57564a542b1?auto=format&fit=crop&q=80&w=600'
                ],
            ];
        @endphp

        @foreach($categories as $category)
            <a href="#" 
               @mouseenter="hoveredImage = '{{ $category['image'] }}'" 
               @mouseleave="hoveredImage = null"
               class="group relative flex items-center justify-between py-8 md:py-14 border-b border-gallery-border transition-all duration-500 hover:px-6 md:hover:px-8 hover:bg-white z-10">
                <div class="flex items-center gap-6 md:gap-16">
                    <span class="font-headline font-bold text-xs md:text-base text-gallery-dim group-hover:text-black transition-colors">{{ $category['id'] }}</span>
                    <h3 class="font-headline font-extrabold text-2xl sm:text-4xl md:text-6xl tracking-tighter group-hover:translate-x-4 transition-transform duration-500">{{ $category['name'] }}</h3>
                </div>
                
                <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-all duration-500 -translate-x-8 group-hover:translate-x-0">
                    <span class="font-body text-gallery-dim text-sm">{{ $category['count'] }} Items</span>
                    <span class="material-symbols-outlined text-3xl md:text-4xl">arrow_forward</span>
                </div>
                
                <!-- Bottom expansion line effect -->
                <div class="absolute bottom-0 left-0 w-0 h-0.5 bg-black transition-all duration-500 group-hover:w-full"></div>
            </a>
        @endforeach
    </div>

    <!-- Floating Hover Image -->
    <template x-if="hoveredImage">
        <div class="fixed pointer-events-none z-50 overflow-hidden rounded-lg shadow-2xl transition-all duration-300 ease-out"
             :style="`left: ${mouseX + 20}px; top: ${mouseY - 150}px; width: 300px; height: 400px;`"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90">
            <img :src="hoveredImage" class="w-full h-full object-cover" alt="Category Preview">
        </div>
    </template>

    <div class="mt-24 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <p class="font-body text-xl text-gallery-muted leading-relaxed">
            Curating the finest selection of premium vaping essentials since 2018. Each department is meticulously vetted for quality, performance, and aesthetic excellence.
        </p>
        <div class="flex justify-start md:justify-end">
            <a href="#" class="group flex items-center gap-4 font-headline font-extrabold text-lg uppercase tracking-widest">
                <span>View Full Catalog</span>
                <div class="w-12 h-12 rounded-full border border-black flex items-center justify-center group-hover:bg-black group-hover:text-white transition-all duration-300">
                    <span class="material-symbols-outlined">arrow_outward</span>
                </div>
            </a>
        </div>
    </div>
</section>
