<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
    <title>{{ $title ?? 'Page Title' }}</title>
</head>

<body>
    <livewire:inc.header />
    <main>
        {{ $slot }}
    </main>
    <livewire:inc.footer />
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    @livewireScripts
    @stack('js')
</body>

</html>
