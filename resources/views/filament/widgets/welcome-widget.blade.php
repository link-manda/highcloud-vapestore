<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-2">
            <div class="flex items-center gap-6">
                <!-- Branch Image / Placeholder -->
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-[#ba9eff] to-[#53ddfc] rounded-[2rem] blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative w-20 h-20 md:w-28 md:h-28 rounded-[2rem] overflow-hidden border border-white/10 bg-[#192540] shadow-2xl">
                        @if($this->getCabang()?->image)
                            <img src="{{ asset('storage/' . $this->getCabang()->image) }}" alt="{{ $this->getCabang()->nama_cabang }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-[#53ddfc]">
                                <x-heroicon-o-building-storefront class="w-10 h-10 md:w-14 md:h-14" />
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Text Info -->
                <div>
                    <h2 class="text-lg font-medium text-gray-400">
                        {{ $this->getGreeting() }},
                    </h2>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white mt-1">
                        {{ $this->getUser()->name }}
                    </h1>
                    <div class="flex items-center gap-2 mt-3 text-sm font-semibold text-[#53ddfc] bg-[#53ddfc]/10 px-3 py-1 rounded-full w-fit border border-[#53ddfc]/20">
                        <x-heroicon-s-map-pin class="w-4 h-4" />
                        {{ $this->getCabang()?->nama_cabang ?? 'Pusat' }}
                    </div>
                </div>
            </div>

            <!-- Stats Quick Peek -->
            <div class="hidden lg:flex items-center gap-8 border-l border-white/5 pl-8">
                <div class="text-right">
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Status Sistem</p>
                    <div class="flex items-center gap-2 justify-end">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        <span class="text-white font-bold tracking-tight">ONLINE</span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Tanggal</p>
                    <p class="text-white font-bold tracking-tight">{{ date('d M Y') }}</p>
                </div>
            </div>
        </div>
    </x-filament::section>

    <style>
        .fi-section {
            background: rgba(6, 14, 32, 0.5) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
        }
    </style>
</x-filament-widgets::widget>
