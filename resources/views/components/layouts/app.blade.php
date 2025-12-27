<!DOCTYPE html>
<html dir="rtl" lang="ar" class="dark">
    <head>
        @include('partials.head')
        <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Noto Kufi Arabic', sans-serif;
                -webkit-tap-highlight-color: transparent;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in {
                animation: fadeIn 0.5s ease-out forwards;
            }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-900 to-blue-700 text-white">
        {{ $slot }}
        @fluxScripts
    </body>
</html>