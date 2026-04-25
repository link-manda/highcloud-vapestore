<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Highcloud Vapestore - Bali\'s Premium Vape Destination')</title>

    <!-- Tailwind & Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Manrope:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Config -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "inverse-on-surface": "#4d556b",
                        "surface-bright": "#1f2b49",
                        "on-error": "#490013",
                        "on-tertiary-fixed": "#00163b",
                        "surface-container-high": "#141f38",
                        "primary-fixed-dim": "#a27cff",
                        "surface-container-lowest": "#000000",
                        "on-tertiary-container": "#000311",
                        "secondary-fixed-dim": "#48d4f3",
                        "tertiary-fixed-dim": "#73a2ff",
                        "error": "#ff6e84",
                        "on-secondary-fixed": "#003a45",
                        "error-dim": "#d73357",
                        "inverse-surface": "#faf8ff",
                        "on-secondary-fixed-variant": "#005969",
                        "on-surface-variant": "#a3aac4",
                        "on-primary-fixed": "#000000",
                        "on-secondary-container": "#ecfaff",
                        "primary": "#ba9eff",
                        "primary-container": "#ae8dff",
                        "secondary-dim": "#40ceed",
                        "primary-fixed": "#ae8dff",
                        "on-tertiary": "#001e4a",
                        "secondary-container": "#00687a",
                        "error-container": "#a70138",
                        "outline-variant": "#40485d",
                        "outline": "#6d758c",
                        "on-background": "#dee5ff",
                        "background": "#060e20",
                        "surface-container-highest": "#192540",
                        "on-surface": "#dee5ff",
                        "tertiary-fixed": "#8ab0ff",
                        "on-error-container": "#ffb2b9",
                        "surface-container-low": "#091328",
                        "on-tertiary-fixed-variant": "#00377b",
                        "tertiary-dim": "#699cff",
                        "surface-variant": "#192540",
                        "on-secondary": "#004b58",
                        "on-primary": "#39008c",
                        "on-primary-fixed-variant": "#370086",
                        "on-primary-container": "#2b006e",
                        "secondary": "#53ddfc",
                        "surface-container": "#0f1930",
                        "primary-dim": "#8455ef",
                        "tertiary-container": "#4388fd",
                        "inverse-primary": "#6e3bd7",
                        "secondary-fixed": "#65e1ff",
                        "surface-tint": "#ba9eff",
                        "surface-dim": "#060e20",
                        "tertiary": "#699cff",
                        "surface": "#060e20"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "headline": ["Plus Jakarta Sans", "sans-serif"],
                        "body": ["Manrope", "sans-serif"],
                        "label": ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #060e20;
            color: #dee5ff;
            font-family: 'Manrope', sans-serif;
        }
        h1, h2, h3, h4, h5, h6, .headline {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .label-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        /* Glassmorphism utility */
        .glass {
            background: rgba(25, 37, 64, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="antialiased selection:bg-primary-container selection:text-on-primary-container min-h-screen flex flex-col relative overflow-x-hidden">
    
    <!-- Subtle ambient background glow -->
    <div class="fixed inset-0 pointer-events-none z-[-1] overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-primary/10 rounded-full blur-[120px] mix-blend-screen opacity-50"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-tertiary/10 rounded-full blur-[150px] mix-blend-screen opacity-40"></div>
    </div>

    @include('components.navbar')

    <main class="flex-grow flex flex-col">
        @yield('content')
    </main>

    @include('components.footer')

    <script>
        // Placeholder for future scripts
    </script>
</body>
</html>
