@extends('layouts.gallery')

@section('title', 'Highcloud Vapestore - Bali\'s Premium Vape Destination')

@section('content')
    <!-- Hero Section -->
    <x-gallery.hero />

    <!-- Featured Products -->
    <x-gallery.featured-drops />

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
