<nav x-data="{ mobileMenuOpen: false }" class="sticky top-0 w-full z-50 bg-gallery-bg/80 backdrop-blur-md">
    <div class="flex justify-between items-center px-6 md:px-16 py-8 max-w-[1440px] mx-auto">
        <a href="/" class="font-headline font-extrabold text-2xl tracking-tighter uppercase z-50">Highcloud</a>

        <!-- Navigation Links (Desktop) -->
        <div class="hidden md:flex gap-12 font-headline font-bold text-[0.875rem] uppercase tracking-widest text-gallery-text/40">
            <a href="#home" class="hover:text-gallery-text transition-colors">Home</a>
            <a href="#collections" class="hover:text-gallery-text transition-colors">Collections</a>
            <a href="#categories" class="hover:text-gallery-text transition-colors">Categories</a>
            <a href="#locations" class="hover:text-gallery-text transition-colors">Locations</a>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-6 md:gap-8 font-headline font-bold text-[0.875rem] uppercase tracking-widest">
            <!-- Mobile Menu Toggle -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden z-50 p-2">
                <span class="material-symbols-outlined text-3xl" x-text="mobileMenuOpen ? 'close' : 'menu'">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-[-20px]"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-[-20px]"
         class="fixed inset-0 z-40 bg-gallery-bg flex flex-col items-center justify-center md:hidden"
         style="display: none;">
        <div class="flex flex-col items-center gap-8 font-headline font-extrabold text-4xl uppercase tracking-tighter">
            <a @click="mobileMenuOpen = false" href="#home" class="hover:text-gallery-dim transition-colors">Home</a>
            <a @click="mobileMenuOpen = false" href="#collections" class="hover:text-gallery-dim transition-colors">Collections</a>
            <a @click="mobileMenuOpen = false" href="#categories" class="hover:text-gallery-dim transition-colors">Categories</a>
            <a @click="mobileMenuOpen = false" href="#locations" class="hover:text-gallery-dim transition-colors">Locations</a>
        </div>
    </div>
</nav>
