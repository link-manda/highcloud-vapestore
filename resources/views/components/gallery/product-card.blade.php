@props(['name', 'category', 'image', 'class' => ''])

<div class="group cursor-pointer transition-transform duration-500 hover:-translate-y-2 {{ $class }}">
    <div class="bg-white aspect-[3/4] flex justify-center items-center overflow-hidden rounded-sm">
        <img src="{{ $image }}" alt="{{ $name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
    </div>
    <div class="mt-8">
        <span class="text-[0.75rem] font-bold uppercase tracking-widest text-gallery-dim">{{ $category }}</span>
        <h3 class="font-headline font-bold text-xl mt-2">{{ $name }}</h3>
    </div>
</div>
