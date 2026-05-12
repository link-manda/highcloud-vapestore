<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Highcloud Gallery - Premium Vape Destination')</title>
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
