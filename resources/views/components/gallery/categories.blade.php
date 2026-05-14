<section id="categories" class="py-24 md:py-32 px-6 md:px-16 max-w-[1440px] mx-auto overflow-hidden">
    <div class="mb-16">
        <span class="font-headline font-bold text-[0.75rem] uppercase tracking-[0.2em] text-gallery-dim">Browse Departments</span>
        <h2 class="font-headline font-extrabold text-[2.5rem] md:text-[4rem] leading-tight tracking-tight mt-4">Collections</h2>
    </div>

    <div class="flex flex-col border-t border-gallery-border">
        @php
            $categories = [
                ['id' => '01', 'name' => 'Disposable', 'count' => '42'],
                ['id' => '02', 'name' => 'Pod Systems', 'count' => '18'],
                ['id' => '03', 'name' => 'E-Liquid', 'count' => '124'],
                ['id' => '04', 'name' => 'Accessories', 'count' => '25'],
            ];
        @endphp

        @foreach($categories as $category)
            <a href="#" class="group relative flex items-center justify-between py-8 md:py-14 border-b border-gallery-border transition-all duration-500 hover:px-6 md:hover:px-8 hover:bg-white">
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
