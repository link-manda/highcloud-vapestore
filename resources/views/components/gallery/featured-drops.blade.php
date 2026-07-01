<section id="collections" class="py-24 md:py-32 px-6 md:px-16 max-w-[1440px] mx-auto">
    <div class="mb-16">
        <span class="font-headline font-bold text-[0.75rem] uppercase tracking-[0.2em] text-gallery-dim">Selected
            Works</span>
        <h2 class="font-headline font-extrabold text-[2.5rem] md:text-[4rem] leading-tight tracking-tight mt-4">Featured
            Drops</h2>
    </div>

    <div class="grid grid-cols-12 gap-8 md:gap-12">
        <x-gallery.product-card class="col-span-12 md:col-span-7" category="Pod System" name="Phantom X Limited Edition"
            image="{{ asset('images/gallery/produk-featured-1.jpg') }}" />
        <x-gallery.product-card class="col-span-12 md:col-span-5 md:mt-32" category="E-Liquid"
            name="Neon Mist Signature" image="{{ asset('images/gallery/produk-featured-2.jpg') }}" />
        <x-gallery.product-card class="col-span-12 md:col-span-6 md:-mt-16" category="Disposable"
            name="Aura Zero Series"
            image="{{ asset('images/gallery/produk-featured-1.jpg') }}" />
        <x-gallery.product-card class="col-span-12 md:col-span-6" category="Accessories" name="Zenith Titanium Tank"
            image="{{ asset('images/gallery/produk-featured-2.jpg') }}" />
    </div>

    <div class="mt-24 flex justify-center">
        <a href="#"
            class="font-headline font-extrabold text-2xl border-b-2 border-black pb-2 hover:opacity-60 transition-opacity">View
            All Collections</a>
    </div>
</section>
