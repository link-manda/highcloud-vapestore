@props(['name', 'image', 'tag' => null, 'color' => 'primary'])

<div class="group relative bg-surface-container/60 backdrop-blur-[20px] rounded-[2rem] p-6 flex flex-col h-[400px] transition-all duration-300 hover:scale-[1.02] hover:bg-surface-variant/80 hover:shadow-[0_0_40px_rgba(174,141,255,0.08)] cursor-pointer overflow-hidden isolate border border-outline-variant/10">
    @if($tag)
        <div class="absolute top-4 left-4 bg-{{ $color }}/20 text-{{ $color }}-fixed text-xs font-bold px-3 py-1 rounded-full label-font tracking-wide">
            {{ $tag }}
        </div>
    @endif
    
    <div class="flex-grow flex items-center justify-center relative mt-8 mb-4">
        <div class="absolute inset-0 bg-{{ $color }}/5 blur-[40px] rounded-full group-hover:bg-{{ $color }}/15 transition-colors"></div>
        <img alt="{{ $name }}" 
             class="h-48 w-auto object-contain relative z-10 drop-shadow-2xl group-hover:-translate-y-2 transition-transform duration-500" 
             src="{{ $image }}" />
    </div>
    
    <div class="flex flex-col gap-1 z-10 relative">
        <h3 class="headline font-bold text-center text-xl text-on-background truncate">{{ $name }}</h3>
        <div class="flex justify-center mt-4">
            <span class="text-xs uppercase tracking-[0.2em] text-{{ $color }}-dim font-bold label-font">View Details</span>
        </div>
    </div>
</div>
