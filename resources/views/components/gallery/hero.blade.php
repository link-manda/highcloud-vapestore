<section id="home" class="relative flex flex-col lg:flex-row px-6 md:px-16 min-h-[90dvh] lg:min-h-[90vh] items-center max-w-[1440px] mx-auto overflow-hidden">
    <!-- Background Large Text -->
    <div class="absolute top-[10%] lg:top-[15%] left-[-2%] font-headline font-extrabold text-[6rem] md:text-[12rem] lg:text-[20rem] text-[#F3F3F3] select-none pointer-events-none z-0">GALLERY</div>
    
    <div class="flex-1 z-10 pt-12 lg:pt-20 text-center lg:text-left">
        <div class="inline-flex items-center gap-4 mb-6 lg:mb-8 justify-center lg:justify-start">
            <span class="w-8 lg:w-12 h-[1px] bg-black"></span>
            <span class="text-[0.65rem] lg:text-[0.75rem] font-bold uppercase tracking-[0.3em] text-black">Highcloud Selection</span>
        </div>
        <h1 class="font-headline font-extrabold text-[3.5rem] md:text-[6rem] lg:text-[8.5rem] leading-[0.85] tracking-[-0.05em] text-black">
            THE<br/>
            <span class="lg:ml-20">LUXURY</span>
        </h1>
        <p class="mt-8 lg:mt-10 text-base lg:text-lg max-w-[380px] mx-auto lg:mx-0 leading-relaxed text-gallery-muted font-body">
            A curated sanctuary of premium vaping technology. Experience editorial elegance and precision in every cloud.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-8 lg:gap-10 mt-12 lg:mt-14">
            <a href="#" class="w-full sm:w-auto text-center inline-block px-14 py-5 bg-black text-white font-headline font-bold text-sm tracking-widest uppercase rounded-sm hover:-translate-y-1 transition-transform">
                Shop Now
            </a>
            <a href="#" class="font-headline font-bold text-sm uppercase tracking-widest border-b border-transparent hover:border-black transition-all">
                Learn More
            </a>
        </div>
    </div>

    <div class="flex-1 relative flex justify-center lg:justify-end items-center h-full mt-16 lg:mt-0 w-full">
        <!-- Vertical Label -->
        <div class="absolute top-1/2 -left-4 lg:-left-10 -translate-y-1/2 -rotate-90 z-20">
            <span class="text-[0.55rem] lg:text-[0.65rem] font-black uppercase tracking-[0.5em] bg-black text-white px-3 py-1 lg:px-4 lg:py-2">New Arrival</span>
        </div>
        
        <!-- Product Image -->
        <div class="relative z-10 w-[80%] sm:w-[60%] lg:w-[110%] h-auto group">
            <div class="absolute inset-0 bg-black/5 blur-[60px] lg:blur-[100px] rounded-full scale-75 group-hover:scale-100 transition-transform duration-1000"></div>
            <img src="{{ asset('storage/Hexohm.webp') }}" 
                 alt="Hexohm Premium Device" 
                 class="relative z-10 w-full h-auto rotate-[-8deg] drop-shadow-[0_40px_80px_rgba(0,0,0,0.1)] lg:drop-shadow-[0_60px_100px_rgba(0,0,0,0.12)] group-hover:rotate-[-2deg] group-hover:scale-105 transition-transform duration-1000 ease-out">
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 lg:bottom-16 left-6 md:left-16 flex items-center gap-6 text-[0.65rem] lg:text-[0.75rem] font-bold uppercase tracking-[0.2em] text-gallery-dim">
        <div class="w-12 lg:w-20 h-[1px] bg-[#DDD] relative overflow-hidden">
            <div class="absolute inset-0 bg-black -translate-x-full animate-[scroll-line_2s_infinite]"></div>
        </div>
        <span>Explore Story</span>
    </div>
</section>

<style>
    @keyframes scroll-line {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0); }
        100% { transform: translateX(100%); }
    }
</style>
