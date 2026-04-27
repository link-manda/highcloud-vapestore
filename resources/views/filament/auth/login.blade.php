<div class="flex min-h-screen bg-[#060e20]">
    <!-- Left Side: Visual -->
    <div class="relative hidden w-0 flex-1 lg:block">
        <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('login-bg.png') }}" alt="Vape Store Interior">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent to-[#060e20]"></div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-[#060e20] relative overflow-hidden">
        <!-- Background Glows -->
        <div class="absolute top-[-10%] right-[-10%] w-[300px] h-[300px] bg-[#ba9eff]/10 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[300px] h-[300px] bg-[#53ddfc]/10 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="mx-auto w-full max-w-sm lg:w-96 relative z-10">
            <div class="mb-10 text-center lg:text-left">
                <img src="{{ asset('storage/cabang-images/logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-2xl mb-6 mx-auto lg:ml-0 shadow-lg border border-white/10">
                <h2 style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-3xl font-bold tracking-tight text-[#dee5ff]">Welcome Back</h2>
                <p style="font-family: 'Manrope', sans-serif;" class="mt-2 text-sm text-[#a3aac4]">Please enter your credentials to access the inventory.</p>
            </div>

            <div class="mt-8">
                <form wire:submit="authenticate" class="space-y-6">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </form>
            </div>

            <footer class="mt-20 text-center lg:text-left">
                <p style="font-family: 'Manrope', sans-serif;" class="text-xs text-[#4d556b]">
                    &copy; {{ date('Y') }} Highcloud Vapestore. All rights reserved.
                </p>
            </footer>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Manrope:wght@400;500;600&display=swap');

        /* Custom styling for Filament form components within our theme */
        .fi-fo-field-wrp-label label {
            color: #a3aac4 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-weight: 700 !important;
        }

        .fi-input-wrp {
            background-color: #192540 !important;
            border: 1px solid rgba(64, 72, 93, 0.2) !important;
            border-radius: 0.75rem !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
        }

        .fi-input-wrp:focus-within {
            border-color: #53ddfc !important;
            box-shadow: 0 0 0 1px #53ddfc !important;
            background-color: #1f2b49 !important;
        }

        .fi-input {
            color: #dee5ff !important;
            font-family: 'Manrope', sans-serif !important;
        }

        .fi-input::placeholder {
            color: #4d556b !important;
        }

        /* Styling the primary button */
        .fi-btn-color-primary {
            background-image: linear-gradient(to bottom right, #ba9eff, #8455ef) !important;
            color: #000000 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 700 !important;
            border-radius: 0.75rem !important;
            border: none !important;
            box-shadow: 0 0 20px rgba(186, 158, 255, 0.3) !important;
            transition: all 0.3s ease !important;
            height: 3rem !important;
        }

        .fi-btn-color-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 0 30px rgba(186, 158, 255, 0.5) !important;
            opacity: 0.9 !important;
        }

        /* Checkbox styling */
        .fi-checkbox {
            background-color: #192540 !important;
            border-color: rgba(64, 72, 93, 0.5) !important;
        }

        .fi-checkbox:checked {
            background-color: #53ddfc !important;
            border-color: #53ddfc !important;
        }
    </style>
</div>
