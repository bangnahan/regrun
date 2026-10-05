<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Registrasi Event Lari' }}</title>
    <!-- Tailwind CSS CDN for instant, zero-build deployment in CloudPanel -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col antialiased">
    <!-- Navbar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('register.index') }}" class="flex items-center gap-2 font-extrabold text-lg text-slate-900 tracking-tight">
                @if(!empty($brandLogoUrl ?? $currentEvent?->logo_url ?? $event?->logo_url))
                    <img src="{{ $brandLogoUrl ?? $currentEvent?->logo_url ?? $event?->logo_url }}" alt="Logo" class="h-8 max-w-[140px] object-contain">
                @else
                    <span class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white text-base shadow">🏃</span>
                @endif
                <span>{{ $event->title ?? $currentEvent?->title ?? 'RegRun Portal' }}</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-6 pb-24">
        <!-- Flash Messages -->
        @if($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                <div class="font-bold mb-1.5 flex items-center gap-2">
                    <span class="text-rose-500 font-bold text-lg">&times;</span>
                    <span>Mohon periksa kembali formulir pendaftaran:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 text-rose-700">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-sm">
                <span class="text-emerald-500 font-bold text-lg">&check;</span>
                <div class="flex-1 font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm">
                <span class="text-rose-500 font-bold text-lg">&times;</span>
                <div class="flex-1 font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-start gap-3 shadow-sm">
                <span class="text-blue-500 font-bold text-lg">&iexcl;</span>
                <div class="flex-1 font-medium">{{ session('info') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} {{ $event->title ?? 'Platform Registrasi Event Lari' }}. Didukung oleh Tripay &amp; Mailketing.</p>
    </footer>

    @stack('scripts')
</body>
</html>
