<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('hiredesk.company').' Careers')</title>
    <meta name="description" content="@yield('description', 'Join '.config('hiredesk.company').'. '.config('hiredesk.company_tagline'))">
    @hasSection('og')
        @yield('og')
    @endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ config('app.asset_version') }}">
    <style>:root { --brand: {{ config('hiredesk.brand_color') }}; }</style>
</head>
<body class="@yield('body_class')">
    @hasSection('nav')
        @yield('nav')
    @else
        <header class="s-nav">
            <div class="s-wrap s-nav-inner">
                <a href="{{ route('careers.index') }}" class="s-logo">
                    <span class="s-logo-mark">{{ mb_substr(config('hiredesk.company'), 0, 1) }}</span>
                    <span>{{ config('hiredesk.company') }}</span>
                </a>
                <nav class="s-nav-links">
                    <a href="{{ route('careers.index') }}">Jobs</a>
                    <a href="{{ route('careers.index') }}#benefits" class="s-hide-sm">Benefits</a>
                    <a href="{{ route('careers.talent-pool') }}" class="s-btn s-btn-outline s-btn-sm">Join talent pool</a>
                </nav>
            </div>
        </header>
    @endif

    <main>
        @yield('content')
    </main>

    @unless (View::hasSection('no_footer'))
        <footer class="s-footer">
            <div class="s-wrap s-footer-inner">
                <span>© {{ now()->year }} {{ config('hiredesk.company') }} · Equal Opportunity Employer</span>
                <span>Powered by <a href="{{ route('home') }}">HireDesk</a></span>
            </div>
        </footer>
    @endunless

    <script>
        // Signature pads: <canvas data-signature="inputId">
        document.querySelectorAll('canvas[data-signature]').forEach((canvas) => {
            const input = document.getElementById(canvas.dataset.signature);
            const ctx = canvas.getContext('2d');
            let drawing = false, dirty = false;
            const size = () => {
                const ratio = window.devicePixelRatio || 1, rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio; canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio); ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#1e1b4b';
            };
            size();
            const pos = (e) => { const r = canvas.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };
            canvas.addEventListener('pointerdown', (e) => { drawing = true; canvas.setPointerCapture(e.pointerId); ctx.beginPath(); ctx.moveTo(...pos(e)); });
            canvas.addEventListener('pointermove', (e) => { if (!drawing) return; ctx.lineTo(...pos(e)); ctx.stroke(); dirty = true; });
            const end = () => { if (!drawing) return; drawing = false; if (dirty) input.value = canvas.toDataURL('image/png'); };
            canvas.addEventListener('pointerup', end); canvas.addEventListener('pointerleave', end);
            document.querySelectorAll('[data-clear="' + canvas.dataset.signature + '"]').forEach((b) => b.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height); input.value = ''; dirty = false;
            }));
        });
        // File inputs: show the chosen file name.
        document.querySelectorAll('input[type=file][data-label]').forEach((input) => input.addEventListener('change', () => {
            const label = document.getElementById(input.dataset.label);
            if (label && input.files[0]) label.textContent = input.files[0].name;
        }));
    </script>
</body>
</html>
