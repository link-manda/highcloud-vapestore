# Landing Page Redesign - "The Gallery" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the current Highcloud Vapestore landing page to follow "The Gallery" concept: a minimalist, high-end editorial layout in Pure Premium Light Mode.

**Architecture:** Use Laravel Blade components to modularize the new design. We will create a new set of "Gallery" components to avoid breaking existing ones until the migration is complete. The theme will be strictly utility-based using Tailwind CSS with custom configuration in the header.

**Tech Stack:** Laravel 11, Blade, Tailwind CSS, Material Symbols.

---

### Task 1: Initialize Gallery Layout & Tailwind Config

**Files:**
- Create: `resources/views/layouts/gallery.blade.php`
- Modify: `resources/views/pages/home.blade.php`

- [ ] **Step 1: Create the Gallery Layout**
Create a new layout file that implements the "The Gallery" design system tokens (colors, fonts).

```html
<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highcloud Gallery - Premium Vape Destination</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Manrope:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        'gallery-bg': '#FAFAFA',
                        'gallery-surface': '#FFFFFF',
                        'gallery-text': '#000000',
                        'gallery-muted': '#404040',
                        'gallery-dim': '#999999',
                        'gallery-border': '#EEEEEE',
                    },
                    fontFamily: {
                        'headline': ['Plus Jakarta Sans', 'sans-serif'],
                        'body': ['Manrope', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #FAFAFA; color: #000000; font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="antialiased">
    @yield('content')
</body>
</html>
```

- [ ] **Step 2: Update Home Route to use Gallery Layout**
Modify `resources/views/pages/home.blade.php` to use the new layout (temporary for development).

- [ ] **Step 3: Commit**
```bash
git add resources/views/layouts/gallery.blade.php resources/views/pages/home.blade.php
git commit -m "chore: init gallery layout and tailwind config"
```

---

### Task 2: Implement Gallery Navbar

**Files:**
- Create: `resources/views/components/gallery/navbar.blade.php`
- Modify: `resources/views/layouts/gallery.blade.php`

- [ ] **Step 1: Create Gallery Navbar Component**
```html
<nav class="flex justify-between items-center px-16 py-8 bg-transparent max-w-[1440px] mx-auto">
    <a href="/" class="font-headline font-extrabold text-2xl tracking-tighter uppercase">Highcloud</a>
    <div class="hidden md:flex gap-12 font-headline font-bold text-[0.875rem] uppercase tracking-widest text-gallery-text/40">
        <a href="#" class="hover:text-gallery-text transition-colors">Collections</a>
        <a href="#" class="hover:text-gallery-text transition-colors">Categories</a>
        <a href="#" class="hover:text-gallery-text transition-colors">Locations</a>
        <a href="#" class="hover:text-gallery-text transition-colors">Journal</a>
    </div>
    <div class="flex gap-8 font-headline font-bold text-[0.875rem] uppercase tracking-widest">
        <a href="#" class="opacity-40 hover:opacity-100 transition-opacity">Search</a>
        <a href="#" class="border-b border-black pb-0.5">Account</a>
    </div>
</nav>
```

- [ ] **Step 2: Include in Layout**
Add `@include('components.gallery.navbar')` to `gallery.blade.php`.

- [ ] **Step 3: Commit**
```bash
git commit -m "feat: add gallery navbar component"
```

---

### Task 3: Implement Gallery Hero Section

**Files:**
- Create: `resources/views/components/gallery/hero.blade.php`
- Modify: `resources/views/pages/home.blade.php`

- [ ] **Step 1: Create Gallery Hero Component**
Implement the asymmetric hero with large typography.

```html
<section class="relative flex px-16 min-h-[80vh] items-center max-w-[1440px] mx-auto overflow-hidden">
    <div class="absolute top-[20%] right-[-5%] font-headline font-extrabold text-[15rem] text-[#F0F0F0] select-none pointer-events-none z-0">AESTHETIC</div>
    
    <div class="flex-1 z-10">
        <h1 class="font-headline font-extrabold text-[7rem] leading-[0.9] tracking-[-0.04em]">
            <span>Elevate</span><br/>
            <span class="pl-24">The Cloud</span>
        </h1>
        <p class="mt-12 text-lg max-w-[400px] leading-relaxed text-gallery-muted font-body">
            A curated gallery of premium vaping devices and liquids. Experience the intersection of tech and lifestyle in a minimalist sanctuary.
        </p>
        <a href="#" class="mt-14 inline-block px-14 py-5 bg-black text-white font-headline font-bold text-sm tracking-widest uppercase rounded-sm hover:-translate-y-1 transition-transform">
            Explore Gallery
        </a>
    </div>

    <div class="flex-1 relative flex justify-end items-center">
        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDi7YHHAR7VENfhP2xyT7rBEjvVrXK4OZ6bRkwFl4PrLbTfqKMFOT9DIJse2OQuJiS6HlPsQueGyHjQAgMxV_n-0zSNuWEDcgtOTTdlikGJJ_AJtpbkL9I4aFmxuLMNmDXKA8-V_L7yd_PVyTsQDma1WL0cL0P2cxgVDiUI_QRvyPUlFz4AuIa15fJ0QuxoVN2syTbjKJe7l33rZho1JM7WYNmZsg35H8ev_YKd2gUfHJoYdOdCu98nKanFh95C9QTTxoFyL8qobaQk" 
             alt="Premium Device" 
             class="w-full h-auto rotate-[-15deg] drop-shadow-[0_50px_100px_rgba(0,0,0,0.1)] z-10 hover:rotate-[-5deg] hover:scale-105 transition-transform duration-700">
    </div>

    <div class="absolute bottom-16 left-16 flex items-center gap-6 text-[0.75rem] font-bold uppercase tracking-[0.2em] text-gallery-dim">
        <div class="w-20 h-[1px] bg-[#DDD] relative overflow-hidden">
            <div class="absolute inset-0 bg-black -translate-x-full animate-[scroll-line_2s_infinite]"></div>
        </div>
        <span>Scroll to Discover</span>
    </div>
</section>

<style>
    @keyframes scroll-line {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0); }
        100% { transform: translateX(100%); }
    }
</style>
```

- [ ] **Step 2: Commit**
```bash
git commit -m "feat: add gallery hero component"
```

---

### Task 4: Implement Gallery Product Cards & Grid

**Files:**
- Create: `resources/views/components/gallery/product-card.blade.php`
- Create: `resources/views/components/gallery/featured-drops.blade.php`
- Modify: `resources/views/pages/home.blade.php`

- [ ] **Step 1: Create Gallery Product Card**
```html
@props(['name', 'category', 'image', 'class' => ''])

<div class="group cursor-pointer transition-transform duration-500 hover:-translate-y-2 {{ $class }}">
    <div class="bg-white p-16 flex justify-center items-center overflow-hidden">
        <img src="{{ $image }}" alt="{{ $name }}" class="max-w-full h-auto drop-shadow-sm group-hover:scale-110 transition-transform duration-500">
    </div>
    <div class="mt-8">
        <span class="text-[0.75rem] font-bold uppercase tracking-widest text-gallery-dim">{{ $category }}</span>
        <h3 class="font-headline font-bold text-xl mt-2">{{ $name }}</h3>
    </div>
</div>
```

- [ ] **Step 2: Create Featured Drops Component**
```html
<section class="py-32 px-16 max-w-[1440px] mx-auto">
    <div class="mb-16">
        <span class="font-headline font-bold text-[0.75rem] uppercase tracking-[0.2em] text-gallery-dim">Selected Works</span>
        <h2 class="font-headline font-extrabold text-[4rem] tracking-tight mt-4">Featured Drops</h2>
    </div>

    <div class="grid grid-cols-12 gap-8">
        <x-gallery.product-card class="col-span-7" category="Pod System" name="Phantom X Limited Edition" image="..." />
        <x-gallery.product-card class="col-span-5 mt-32" category="E-Liquid" name="Neon Mist Signature" image="..." />
        <x-gallery.product-card class="col-span-6 -mt-16" category="Disposable" name="Aura Zero Series" image="..." />
        <x-gallery.product-card class="col-span-6" category="Accessories" name="Zenith Titanium Tank" image="..." />
    </div>

    <div class="mt-24 flex justify-center">
        <a href="#" class="font-headline font-extrabold text-2xl border-b-2 border-black pb-2 hover:opacity-60 transition-opacity">View All Collections</a>
    </div>
</section>
```

- [ ] **Step 3: Commit**
```bash
git commit -m "feat: add gallery product grid components"
```

---

### Task 5: Implement Gallery Categories & Footer

**Files:**
- Create: `resources/views/components/gallery/categories.blade.php`
- Create: `resources/views/components/gallery/footer.blade.php`

- [ ] **Step 1: Create Gallery Categories Component**
List style with hover effect.

- [ ] **Step 2: Create Gallery Footer**
Solid black background with minimal grid.

- [ ] **Step 3: Commit**
```bash
git commit -m "feat: add gallery categories and footer"
```
