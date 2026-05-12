@extends('layouts.gallery')

@section('content')
    <!-- Hero Section -->
    @include('components.hero')

    <!-- Featured Products -->
    <section id="collections" class="w-full py-24 px-6 lg:px-12 max-w-screen-2xl mx-auto bg-surface relative z-20">
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
            <div>
                <h2 class="headline text-4xl md:text-5xl font-extrabold tracking-tight text-on-background mb-4">Featured <span class="text-primary-dim">Drops</span></h2>
                <p class="text-on-surface-variant font-body">Curated selections for the modern connoisseur.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-product-card 
                name="Phantom X Pod System" 
                image="https://lh3.googleusercontent.com/aida-public/AB6AXuCcnBjAG24UEQRxP8TjLbV4rOwmAfzWOKT0l5lZGSJLr79EiQRrjtVkEofhzAbn6zR-J2p4c5t22txVyzPzV4qDWH4JuJ3wtlqCdajH0vilT_1n-tvfhncKABEKyZ3WmiKK_Jx5TuOOkJID3NUJbBantT1O2MrpB-IeYZ6HX0QyREpSyRRVnNDmCRp6bFoQj7k81CTLu2GedLSDInduq6DELNywwhYCzXhkb9VMyqoUFB7Dn4VRdVP1eue6r0lobNYCMS7dBbil5g7P" 
                tag="NEW" 
                color="primary" />
            
            <x-product-card 
                name="Neon Mist E-Juice 60ml" 
                image="https://lh3.googleusercontent.com/aida-public/AB6AXuCv2IlXep-J9RvTq4zYBTimwfp-xLU7MkchU8_Ax0sCILS3dYdyoVxbn4xVCye0kbQlVDg-ALhRmebgFPPxIgeCzEUg__wOSM0wz2-wV_IkUN5s-d0SE6AkxAhVdiJHdWXtamkPzgx8bQiibuY_n_l04mTxPlAEaaI2GTpYrxCsWZc93BywYNk9faZ2-_-RJExBoZ2DTiIzkOOTFgiIpKQxOglY53r2ILf3BAyJYdGIQCnhjTUEISWxY0uB12eUnA6yJl_OHKODThqS" 
                color="tertiary" />
            
            <x-product-card 
                name="Aura Disposable 5000" 
                image="https://lh3.googleusercontent.com/aida-public/AB6AXuD11SXum6GnU1xpr471sV3gheOZYt9D1pGtbsY1Io4wLHKmAcUJbZu0Bq_8FN0Rx1rJunY3LyNxTJc1bmgn_fACcWzSzx1gHTbnNWIKYT5pxeTqLX1RC4LRm0umt6aXkw_ZBDh7Nyt6AwaIDcFhuqnvCoBMSGg7O9PEOU58Y3rcvAV9qWCOoRZFcSq8BoCA5QSQN0_Fu_YQWXe6F9Fxqs6KuN1r7rfvBrvZKeRTDDAVwax48LRhBAHLeWseL_mo_9lmelQhiQk1dyyZ" 
                tag="BEST SELLER" 
                color="secondary" />
            
            <x-product-card 
                name="Zenith Sub-Ohm Tank" 
                image="https://lh3.googleusercontent.com/aida-public/AB6AXuANumws8jUVb0V_mPjGA1XLLpvB4CEKg5LV0D3A4DwCYYExGpH-S7558DlLmvEqJt9HGigLNzZn7AZ1TlOwrkUpbeKiKK6bIGshVS3N2-GKrS61OvQJ1Hs8ukn7j3F1GytLDU_d9INVqAi_4lTM_-UZoynPPgIKSDtXzGfcoStOBElkteaWitlK7_a244KEl4F4vrfow4HtZkU_D56UWF3_-sQPkQCOuVxcfO4lZq7pp_ElrfSYzVVHTDGuHhbSSNiFYuq7ktiHUWIL" 
                color="primary" />
        </div>
    </section>

    <!-- Category Grid -->
    <section id="categories" class="w-full py-24 px-6 lg:px-12 max-w-screen-2xl mx-auto bg-surface-container-low transition-all duration-700 ease-in-out border-y border-outline-variant/5">
        <div class="text-center mb-16 max-w-2xl mx-auto">
            <h2 class="headline text-3xl md:text-4xl font-extrabold tracking-tight text-on-background mb-4">Explore by <span class="text-secondary-dim">Category</span></h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-category-card name="Disposable" icon="vaping_rooms" color="primary" />
            <x-category-card name="Pod Systems" icon="battery_charging_full" color="tertiary" />
            <x-category-card name="E-Liquid" icon="water_drop" color="secondary" />
            <x-category-card name="Accessories" icon="hardware" color="primary" />
        </div>
    </section>

    <!-- Promo Banner -->
    @include('components.promo-banner')

    <!-- Testimonials -->
    <div id="testimonials">
        @include('components.testimonials')
    </div>

    <!-- Location/Map Section -->
    <div id="locations">
        @include('components.location-map', ['cabangs' => $cabangs])
    </div>
@endsection
