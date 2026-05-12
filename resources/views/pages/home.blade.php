@extends('layouts.gallery')

@section('title', 'Highcloud Vapestore - Bali\'s Premium Vape Destination')

@section('content')
    <!-- Hero Section -->
    <x-gallery.hero />

    <!-- Featured Products -->
    <x-gallery.featured-drops />

    <!-- Gallery Categories -->
    <x-gallery.categories />

    <!-- Location/Map Section (If needed in Gallery, but keeping it minimal) -->
@endsection
