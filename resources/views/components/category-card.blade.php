@props(['name', 'icon', 'color' => 'primary'])

<a href="#" class="group relative overflow-hidden rounded-[2rem] aspect-square bg-surface-container-highest isolate flex items-end p-8 border border-outline-variant/10">
    <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-surface-container-lowest/40 to-transparent z-10"></div>
    <div class="absolute inset-0 z-0">
        <div class="w-full h-full bg-{{ $color }}/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 absolute mix-blend-overlay"></div>
        <span class="material-symbols-outlined text-[8rem] text-{{ $color }}-dim/20 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 group-hover:scale-110 transition-transform duration-700">
            {{ $icon }}
        </span>
    </div>
    <div class="relative z-20 w-full flex justify-between items-end">
        <h3 class="headline font-bold text-2xl text-on-background">{{ $name }}</h3>
        <div class="w-10 h-10 rounded-full bg-surface-bright/50 backdrop-blur-md flex items-center justify-center text-on-surface-variant group-hover:bg-{{ $color }} group-hover:text-on-{{ $color }}-fixed transition-colors">
            <span class="material-symbols-outlined">arrow_forward</span>
        </div>
    </div>
</a>
