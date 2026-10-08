@extends('layouts.platform', [
    'title' => 'Jelatix - Platform Registrasi & Penjualan Tiket Event Lari Indonesia',
    'metaDescription' => 'Pesan tiket resmi event lari favorit Anda (Fun Run, 10K, Half Marathon, Marathon) dengan mudah, cepat, dan aman di Jelatix. Pembayaran instan via Tripay.'
])

@section('content')
@php
    $featuredEvent = $events->where('is_default', true)->first() ?? $events->first();
    $featuredHeroImage = $featuredEvent?->hero_image_url;
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white overflow-hidden py-20 lg:py-28 border-b border-slate-800">
    @if($featuredHeroImage)
        <!-- Dynamic Hero Image Backdrop -->
        <div class="absolute inset-0 bg-cover bg-center opacity-25 filter blur-[0.5px] scale-105" style="background-image: url('{{ $featuredHeroImage }}');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-slate-950/60"></div>
    @endif

    <!-- Subtle Background Glow -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(234,88,12,0.18),transparent_50%)]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(249,115,22,0.12),transparent_40%)]"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="{{ $featuredEvent && $featuredHeroImage ? 'lg:col-span-7' : 'lg:col-span-8' }} space-y-6">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/30 text-orange-300 text-xs font-bold tracking-wide">
                    <img src="{{ asset('images/jelatix-icon.png') }}" alt="Jelatix" class="w-4 h-4 object-contain">
                    <span>PLATFORM TIKETING RESMI EVENT LARI INDONESIA</span>
                </div>

                <!-- Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1] text-white">
                    Dapatkan Tiket Race Impian Anda di <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 via-amber-400 to-orange-500">Jelatix</span>
                </h1>

                <!-- Subtitle -->
                <p class="text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl font-normal">
                    Platform pemesanan tiket resmi untuk event lari di seluruh Indonesia. Dari 5K Fun Run, 10K Challenge, hingga Half & Full Marathon. Terintegrasi pembayaran instan Tripay (QRIS & Virtual Account) dan E-Ticket QR Code otomatis.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-4">
                    <a href="#events" class="px-7 py-4 rounded-xl bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 text-white font-extrabold text-sm shadow-xl shadow-orange-600/30 transition text-center flex items-center justify-center gap-2">
                        <span>Eksplorasi Event Lari</span>
                        <span>&darr;</span>
                    </a>
                    <a href="{{ route('page.contact') }}" class="px-7 py-4 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700 font-bold text-sm transition text-center flex items-center justify-center gap-2">
                        <span>Kerjasama Organizer</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <!-- Trust Highlights -->
                <div class="pt-8 flex flex-wrap items-center gap-6 text-xs text-slate-400 border-t border-slate-800/80">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-base">&check;</span>
                        <span>100% Tiket Resmi & Terverifikasi</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-base">&check;</span>
                        <span>Pembayaran Cepat QRIS & VA Tripay</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-base">&check;</span>
                        <span>E-Ticket QR Langsung ke Email</span>
                    </div>
                </div>
            </div>

            @if($featuredEvent && $featuredHeroImage)
                <!-- Right Featured Race Hero Card -->
                <div class="hidden lg:flex lg:col-span-5 justify-center">
                    @php
                        $featDomain = $featuredEvent->getPrimaryDomain();
                        $featUrl = $featDomain ? ('https://' . $featDomain) : route('register.event', ['slug' => $featuredEvent->slug]);
                        $featMinPrice = $featuredEvent->ticketCategories->min('current_price');
                    @endphp
                    <div class="relative w-full max-w-md rounded-3xl bg-slate-900/90 backdrop-blur-2xl border border-white/10 shadow-2xl shadow-orange-500/20 overflow-hidden group hover:border-orange-500/50 transition duration-300">
                        <!-- Hero Image Container with Badges -->
                        <div class="relative h-60 w-full overflow-hidden bg-slate-950">
                            <img src="{{ $featuredHeroImage }}" alt="{{ $featuredEvent->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-black/30"></div>
                            
                            <div class="absolute top-3.5 left-3.5 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-600/90 backdrop-blur-md text-white text-[10px] font-black tracking-wider uppercase shadow-lg">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                    FEATURED RACE
                                </span>
                            </div>

                            @if($featuredEvent->custom_domain)
                                <div class="absolute top-3.5 right-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-orange-200 text-[10px] font-mono border border-white/10">
                                        🌐 {{ $featuredEvent->custom_domain }}
                                    </span>
                                </div>
                            @endif

                            <div class="absolute bottom-3 left-4 right-4 text-white">
                                <h3 class="font-black text-xl leading-tight line-clamp-1 group-hover:text-orange-400 transition">{{ $featuredEvent->title }}</h3>
                                <p class="text-xs text-slate-300 flex items-center gap-1.5 mt-1">
                                    <span>📍</span> <span>{{ $featuredEvent->venue_name }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Event Meta Details & CTA -->
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-2 gap-2 text-xs bg-slate-800/70 p-3 rounded-2xl border border-slate-700/60">
                                <div>
                                    <div class="text-slate-400 text-[10px] font-semibold uppercase tracking-wider">Tanggal Race</div>
                                    <div class="font-extrabold text-white text-xs mt-0.5">
                                        {{ $featuredEvent->race_date ? $featuredEvent->race_date->translatedFormat('d M Y') : 'Segera' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px] font-semibold uppercase tracking-wider">Mulai Dari</div>
                                    <div class="font-black text-orange-400 text-xs mt-0.5">
                                        {{ $featMinPrice ? 'Rp ' . number_format($featMinPrice, 0, ',', '.') : 'Hubungi Panitia' }}
                                    </div>
                                </div>
                            </div>

                            <a href="{{ $featUrl }}" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 text-white font-extrabold text-xs tracking-wider uppercase transition shadow-xl shadow-orange-600/30 flex items-center justify-center gap-2">
                                <span>Daftar Race Ini Sekarang</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <!-- Default Jelatix Ticketing Brand Card -->
                <div class="hidden lg:flex lg:col-span-4 justify-center">
                    <div class="relative w-72 p-6 rounded-3xl bg-slate-900/80 backdrop-blur-xl border border-slate-700/80 shadow-2xl shadow-orange-500/10 text-center">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="w-36 h-36 mx-auto mb-4 p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shadow-inner">
                            <img src="{{ asset('images/jelatix-logo.png') }}" alt="Jelatix Official" class="w-full h-full object-contain filter drop-shadow">
                        </div>
                        <h3 class="font-black text-xl text-white tracking-tight">Jelatix Ticketing</h3>
                        <p class="text-xs text-slate-400 mt-1">Spesialis Sistem Pendaftaran &amp; Tiket Lari Indonesia</p>
                        <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-around text-center">
                            <div>
                                <div class="font-extrabold text-sm text-orange-400">QRIS &amp; VA</div>
                                <div class="text-[10px] text-slate-400">Tripay Gateway</div>
                            </div>
                            <div class="w-px h-6 bg-slate-800"></div>
                            <div>
                                <div class="font-extrabold text-sm text-emerald-400">Instant QR</div>
                                <div class="text-[10px] text-slate-400">Check-in RPC</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Active Running Events Section -->
<section id="events" class="py-16 lg:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-12">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-100 text-orange-800 text-xs font-bold mb-2">
                <span>🔴 LIVE REGISTRATION</span>
            </div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Event Lari yang Sedang Buka Pendaftaran</h2>
            <p class="text-slate-500 text-sm mt-1">Pilih kategori lomba, isi data diri, dan amankan slot BIB nomor lari Anda sekarang.</p>
        </div>
        <div class="text-xs text-slate-500 font-medium">
            Menampilkan <span class="font-bold text-slate-800">{{ $events->count() }} Event Aktif</span>
        </div>
    </div>

    @if($events->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center max-w-xl mx-auto">
            <div class="text-4xl mb-3">🏃‍♂️</div>
            <h3 class="font-bold text-slate-800 text-base mb-1">Belum Ada Event Aktif Saat Ini</h3>
            <p class="text-xs text-slate-500 mb-6">Penyelenggaraan event lari baru akan segera hadir. Pantau terus jelatix.com untuk pembukaan registrasi selanjutnya.</p>
            <a href="{{ route('page.contact') }}" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white font-bold text-xs shadow hover:bg-orange-500 transition">
                Hubungi Panitia &rarr;
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($events as $event)
                @php
                    $primaryDomain = $event->getPrimaryDomain();
                    $eventUrl = $primaryDomain 
                        ? ('https://' . $primaryDomain) 
                        : route('register.event', ['slug' => $event->slug]);
                    $activeCategories = $event->ticketCategories;
                    $minPrice = $activeCategories->min('current_price') ?? 0;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group">
                    <!-- Event Banner / Poster Header -->
                    <div class="relative h-48 bg-gradient-to-br from-slate-900 to-orange-950 overflow-hidden flex items-center justify-center p-6 text-white text-center">
                        @if($event->hero_image_url)
                            <img src="{{ $event->hero_image_url }}" alt="{{ $event->title }}" class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:scale-105 transition duration-500">
                        @elseif($event->banner_image)
                            <img src="{{ $event->banner_image }}" alt="{{ $event->title }}" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition duration-500">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/40 to-transparent"></div>
                        <div class="relative z-10 space-y-1">
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-orange-500/90 text-white font-bold text-[10px] uppercase tracking-wider mb-1">
                                Registrasi Dibuka
                            </span>
                            <h3 class="font-black text-xl leading-tight group-hover:text-orange-400 transition">
                                {{ $event->title }}
                            </h3>
                            <p class="text-[11px] text-slate-300 flex items-center justify-center gap-1">
                                <span>📍</span>
                                <span>{{ $event->venue_name }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Event Details Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                        <div class="space-y-4">
                            <!-- Date & Time -->
                            <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div>
                                    <div class="text-slate-400 text-[10px] font-semibold uppercase">Tanggal Race</div>
                                    <div class="font-bold text-slate-800">{{ $event->race_date ? $event->race_date->translatedFormat('d M Y') : 'Segera Diumumkan' }}</div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px] font-semibold uppercase">Waktu Start</div>
                                    <div class="font-bold text-slate-800">{{ $event->race_start_time ? substr($event->race_start_time, 0, 5) . ' WIB' : 'Pagi Hari' }}</div>
                                </div>
                            </div>

                            <!-- Description Snippet -->
                            @if($event->description)
                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ $event->description }}
                                </p>
                            @endif

                            <!-- Available Categories -->
                            <div>
                                <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Kategori Jarak:</div>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($activeCategories as $cat)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-50 border border-orange-200 text-orange-800 text-[11px] font-bold">
                                            <span>🏃</span>
                                            <span>{{ $cat->name }}</span>
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Kategori tiket sedang diperbarui</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Price & CTA -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-slate-400 text-[10px]">Mulai dari</div>
                                <div class="font-black text-slate-900 text-base">
                                    Rp {{ number_format($minPrice, 0, ',', '.') }}
                                </div>
                            </div>
                            <a href="{{ $eventUrl }}" class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs shadow-md shadow-orange-600/20 transition flex items-center gap-1.5">
                                <span>Daftar Sekarang</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

<!-- Features Grid Section -->
<section id="features" class="py-16 lg:py-24 bg-white border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
            <span class="text-orange-600 text-xs font-black uppercase tracking-widest">Keunggulan Jelatix</span>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Dibuat Khusus untuk Komunitas & Penyelenggara Lari</h2>
            <p class="text-slate-500 text-sm">Pengalaman registrasi yang mulus, aman, dan tanpa kendala dari pemilihan tiket hingga hari pengambilan racepack.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl font-black">
                    ⚡
                </div>
                <h3 class="font-extrabold text-base text-slate-900">Pembayaran Instan via Tripay</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Mendukung semua dompet digital nasional via QRIS (GoPay, OVO, ShopeePay, DANA) serta Virtual Account seluruh bank utama di Indonesia dengan konfirmasi lunas otomatis.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl font-black">
                    🎫
                </div>
                <h3 class="font-extrabold text-base text-slate-900">E-Ticket & QR Code Resmi</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Setiap pelari mendapatkan kode tiket unik dan QR Code berkeamanan tinggi yang dikirimkan langsung ke email serta dapat diunduh kapan saja untuk pengambilan paket lomba (RPC).
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-black">
                    🎽
                </div>
                <h3 class="font-extrabold text-base text-slate-900">Ukuran Jersey & Data Medis</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Formulir lengkap mengumpulkan ukuran jersey lomba (XS hingga 5XL), nama BIB dada khusus, golongan darah, hingga kontak darurat medis demi keselamatan peserta.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl font-black">
                    📊
                </div>
                <h3 class="font-extrabold text-base text-slate-900">Rekap Jersey Pabrik Otomatis</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Bagi panitia, data ukuran jersey per kategori lomba direkap dalam tabel matriks silang yang siap diekspor ke format CSV / Excel untuk langsung diserahkan ke vendor konveksi.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-black">
                    🏷️
                </div>
                <h3 class="font-extrabold text-base text-slate-900">Nomor BIB Otomatis / Manual</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Penyelenggara dapat memilih pembuatan nomor BIB otomatis sesuai prefix kategori saat lunas atau melakukan penugasan nomor BIB massal sesaat sebelum race day.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-black">
                    🌐
                </div>
                <h3 class="font-extrabold text-base text-slate-900">Dukungan Domain Khusus Event</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Setiap event dapat menggunakan domain sendiri (misal: tiket.marathon2026.com) dengan identitas visual, logo, dan rekening payment gateway tersendiri.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action for Organizers -->
<section id="organizer" class="py-16 lg:py-20 bg-slate-950 text-white relative overflow-hidden">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
        <span class="inline-block px-3 py-1 rounded-full bg-orange-600/20 text-orange-400 text-xs font-bold border border-orange-500/30">
            KEMITRAAN RACE ORGANIZER
        </span>
        <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-white">
            Ingin Membuka Registrasi Event Lari Anda di Jelatix?
        </h2>
        <p class="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
            Dapatkan sistem tiketing profesional yang siap pakai, tanpa ribet setup server. Pengaturan kuota lomba, early bird, integrasi Tripay resmi, dan rekap jersey pabrik instan.
        </p>
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('page.contact') }}" class="px-8 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-sm shadow-xl shadow-orange-600/30 transition flex items-center gap-2">
                <span>Hubungi Tim Kemitraan Jelatix</span>
                <span>&rarr;</span>
            </a>
            <a href="mailto:hi@jelatix.com" class="px-8 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 font-semibold text-sm transition">
                Email: hi@jelatix.com
            </a>
        </div>
    </div>
</section>
@endsection
