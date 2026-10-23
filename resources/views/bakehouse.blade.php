<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
        <title>Janahitha Bake House</title>
        <link rel="icon" href="{{ asset('images/logo.png') }}" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet" />
        @vite([
            'resources/css/standalone/base.css',
            'resources/css/standalone/orders.css',
            'resources/css/standalone/reports.css',
            'resources/css/standalone/admin.css',
            'resources/css/standalone/account-dashboard.css',
        ])
        <script>
            window.BH_CONFIG = {{ Js::from([
                'api' => dirname(route('api.session.show', [], false)),
                'setup' => route('setup.store', [], false),
                'logo' => parse_url(asset('images/logo.png'), PHP_URL_PATH) ?: '/images/logo.png',
            ]) }};
        </script>
    </head>
    <body>
        <div id="root"><div class="boot">Loading routes…</div></div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.3.1/umd/react.production.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.3.1/umd/react-dom.production.min.js"></script>
        <script src="https://unpkg.com/htm@3.1.1/dist/htm.umd.js"></script>
        @vite('resources/js/standalone/app.js')
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js" defer></script>
    </body>
</html>
