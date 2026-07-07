<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $appName = config('app.name') === 'Laravel' ? 'Bloedwaarden' : config('app.name', 'Bloedwaarden');
@endphp

<title>
    {{ filled($title ?? null) ? $title.' - '.$appName : $appName }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- OS-voorkeur dark mode (default 'system', geen settings-UI): de spec eist
     dark-varianten op elk oppervlak en die zitten al in alle views. --}}
@fluxAppearance
