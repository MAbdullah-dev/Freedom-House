<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <livewire:inc.header />
    <main>
        {{ $slot }}
    </main>
    <livewire:inc.footer />
    @livewireScripts
    <script></script>
</body>
</html>
