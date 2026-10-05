@extends('layouts.admin', ['title' => 'Manajemen Event & Kuota Tiket - RegRun'])

@section('page_title')
    Manajemen Event &amp; Kuota Tiket
@endsection

@section('content')
<div class="space-y-6" x-data="{ 
    showCreateModal: false,
    showAddCategoryModal: false,
    selectedEventId: null,
    selectedEventTitle: '',
    openAddCategory(eventId, eventTitle) {
        this.selectedEventId = eventId;
        this.selectedEventTitle = eventTitle;
        this.showAddCategoryModal = true;
    }
}">
    <!-- Top Action Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Pengaturan Event Lari &amp; Tiket</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola identitas event, tanggal race, jam flag-off, lokasi venue &amp; RPC, custom domain, serta kuota &amp; harga early bird.</p>
        </div>
        <button type="button" 
                @click="showCreateModal = true" 
                class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 active:bg-orange-700 text-white font-bold text-xs shadow-lg shadow-orange-600/30 transition flex items-center gap-2 shrink-0">
            <span>➕</span>
            <span>Tambah Event Baru</span>
        </button>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-sm">
            <span>&check; {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between shadow-sm">
            <span>⚠️ {{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Events List -->
    @foreach($events as $ev)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6" x-data="{ showEditEvent: false }">
            <!-- Event Card Header -->
            <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-white via-white to-slate-50/50">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h3 class="text-lg font-black text-slate-900">{{ $ev->title }}</h3>
                            
                            @if($ev->is_default)
                                <span class="px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800 border border-orange-200 text-[10px] font-bold flex items-center gap-1">
                                    <span>⭐</span> Event Utama (Default)
                                </span>
                            @endif

                            @if($ev->is_active)
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 text-[10px] font-bold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                    Pendaftaran Dibuka
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold">
                                    Pendaftaran Ditutup
                                </span>
                            @endif

                            @if($ev->auto_generate_bib)
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold" title="BIB digenerate otomatis begitu pembayaran lunas">
                                    ⚡ BIB: Otomatis
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold" title="BIB tidak digenerate saat pendaftaran (dialokasikan nanti)">
                                    ⏳ BIB: Tunda (Manual Nanti)
                                </span>
                            @endif

                            <span class="text-[11px] text-slate-500 font-medium">
                                ({{ $ev->transactions_count }} Transaksi &bull; {{ $ev->participants_count }} Peserta)
                            </span>
                        </div>

                        <!-- Meta Info Line -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-1 gap-x-4 text-xs text-slate-600">
                            <div>
                                <span class="text-slate-400">📅 Race:</span> 
                                <strong>{{ $ev->race_date ? $ev->race_date->format('d F Y') : '-' }}</strong> 
                                <span class="text-orange-600 font-bold">({{ substr($ev->race_start_time, 0, 5) }} WIB)</span>
                            </div>
                            <div>
                                <span class="text-slate-400">📍 Lokasi:</span> 
                                <strong>{{ $ev->venue_name }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400">📦 RPC:</span> 
                                <strong>{{ $ev->rpc_start_date ? $ev->rpc_start_date->format('d M') : '-' }} s/d {{ $ev->rpc_end_date ? $ev->rpc_end_date->format('d M Y') : '-' }}</strong>
                            </div>
                            @if($ev->custom_domain)
                                <div>
                                    <span class="text-slate-400">🌐 Domain:</span> 
                                    <code class="font-mono text-orange-600 bg-orange-50 px-1 rounded">{{ $ev->custom_domain }}</code>
                                </div>
                            @endif
                            <div>
                                <span class="text-slate-400">🔗 Slug URL:</span> 
                                <code class="font-mono text-slate-700 bg-slate-100 px-1 rounded">/event/{{ $ev->slug }}</code>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap shrink-0">
                        <button type="button" 
                                @click="showEditEvent = !showEditEvent" 
                                class="px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                            <span>✏️</span>
                            <span x-text="showEditEvent ? 'Tutup Edit Event' : 'Edit Event'"></span>
                        </button>

                        <a href="{{ route('register.event', ['slug' => $ev->slug]) }}" 
                           target="_blank" 
                           class="px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 font-bold text-xs transition flex items-center gap-1">
                            <span>🌐</span>
                            <span>Halaman Daftar ↗</span>
                        </a>

                        @if(!$ev->is_default)
                            <form action="{{ route('admin.events.set_default', ['id' => $ev->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Tetapkan event ini sebagai event utama?')" 
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition" 
                                        title="Jadikan Event Utama">
                                    ⭐ Jadikan Default
                                </button>
                            </form>
                        @endif

                        @if($ev->transactions_count === 0)
                            <form action="{{ route('admin.events.delete', ['id' => $ev->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Yakin ingin menghapus event ini? Seluruh kategori tiket juga akan terhapus.')" 
                                        class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition" 
                                        title="Hapus Event">
                                    🗑️
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Inline Edit Event Form Accordion -->
            <div x-show="showEditEvent" x-collapse class="p-6 bg-slate-50/80 border-b border-slate-200">
                <form action="{{ route('admin.events.update', ['id' => $ev->id]) }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h4 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span>⚙️</span> Edit Pengaturan Event: {{ $ev->title }}
                        </h4>
                        <span class="text-[11px] text-slate-500">Perubahan langsung berlaku ke halaman publik &amp; invoice.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Nama Event Lari <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" value="{{ $ev->title }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold text-slate-900">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">URL Slug <span class="text-rose-500">*</span></label>
                            <input type="text" name="slug" value="{{ $ev->slug }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-mono text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Custom Subdomain / Domain</label>
                            <input type="text" name="custom_domain" value="{{ $ev->custom_domain }}" placeholder="event.domain.com" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-mono text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Race <span class="text-rose-500">*</span></label>
                            <input type="date" name="race_date" value="{{ $ev->race_date ? $ev->race_date->format('Y-m-d') : '' }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jam Start Flag-Off <span class="text-rose-500">*</span></label>
                            <input type="time" name="race_start_time" value="{{ substr($ev->race_start_time, 0, 5) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lokasi / Venue <span class="text-rose-500">*</span></label>
                            <input type="text" name="venue_name" value="{{ $ev->venue_name }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Venue</label>
                            <input type="text" name="venue_address" value="{{ $ev->venue_address }}" placeholder="Jl. Raya Utama No. 1..." class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai RPC</label>
                            <input type="date" name="rpc_start_date" value="{{ $ev->rpc_start_date ? $ev->rpc_start_date->format('Y-m-d') : '' }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai RPC</label>
                            <input type="date" name="rpc_end_date" value="{{ $ev->rpc_end_date ? $ev->rpc_end_date->format('Y-m-d') : '' }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Lokasi &amp; Ketentuan RPC</label>
                            <input type="text" name="rpc_location" value="{{ $ev->rpc_location }}" placeholder="Contoh: Atrium Mall, Booth Panitia Lt. 2..." class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block font-bold text-slate-700 mb-1">Deskripsi Singkat / Catatan Event</label>
                            <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs" placeholder="Deskripsi informasi event untuk peserta...">{{ $ev->description }}</textarea>
                        </div>

                        <div class="sm:col-span-4 bg-white p-3.5 rounded-xl border border-slate-200">
                            <label class="block font-bold text-slate-800 mb-1">Pengaturan Penomoran Nomor BIB Peserta</label>
                            <select name="auto_generate_bib" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold text-slate-800 text-xs">
                                <option value="1" {{ $ev->auto_generate_bib ? 'selected' : '' }}>⚡ Otomatis: Generate nomor BIB langsung saat transaksi lunas (Rekomendasi)</option>
                                <option value="0" {{ !$ev->auto_generate_bib ? 'selected' : '' }}>⏳ Tunda: Tidak perlu generate BIB dahulu (Akan dialokasikan panitia nanti / saat RPC)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Jika memilih <strong>Tunda</strong>, nomor BIB pada e-ticket/invoice akan bertuliskan "Menyusul" sampai panitia mengalokasikan nomor BIB massal di menu Data Peserta.</p>
                        </div>

                        <!-- Branding & Appearance -->
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Logo Event URL</label>
                            <input type="text" name="logo_url" value="{{ $ev->logo_url }}" placeholder="https://domain.com/logo.png" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-xs">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Warna Aksen Brand (Hex Code)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="primary_color" value="{{ $ev->primary_color ?: '#ea580c' }}" class="w-10 h-9 p-1 rounded-lg border border-slate-300 cursor-pointer">
                                <input type="text" value="{{ $ev->primary_color ?: '#ea580c' }}" class="flex-1 px-3 py-2 rounded-xl border border-slate-300 bg-white font-mono text-xs" readonly>
                            </div>
                        </div>

                        <!-- Optional Custom Gateway Credentials Accordion -->
                        <div class="sm:col-span-4 p-4 rounded-xl bg-white border border-slate-200" x-data="{ openCreds: {{ ($ev->tripay_merchant_code || $ev->mailketing_api_token) ? 'true' : 'false' }} }">
                            <div class="flex items-center justify-between cursor-pointer" @click="openCreds = !openCreds">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🔐</span>
                                    <div>
                                        <span class="font-extrabold text-xs text-slate-800">Kredensial Gateway &amp; Notifikasi Khusus Event (Opsional)</span>
                                        <p class="text-[11px] text-slate-400">Kosongkan jika ingin menggunakan akun Tripay &amp; Mailketing utama sistem.</p>
                                    </div>
                                </div>
                                <button type="button" class="text-xs font-bold text-orange-600 hover:text-orange-700">
                                    <span x-text="openCreds ? 'Tutup ▲' : 'Buka Pengaturan Gateway Khusus ▼'"></span>
                                </button>
                            </div>

                            <div x-show="openCreds" x-collapse class="mt-4 pt-3 border-t border-slate-100 space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tripay Merchant Code</label>
                                        <input type="text" name="tripay_merchant_code" value="{{ $ev->tripay_merchant_code }}" placeholder="T..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tripay API Key</label>
                                        <input type="password" name="tripay_api_key" value="{{ $ev->tripay_api_key }}" placeholder="Khusus event ini" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tripay Private Key</label>
                                        <input type="password" name="tripay_private_key" value="{{ $ev->tripay_private_key }}" placeholder="Khusus event ini" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-mono text-xs">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Mailketing API Token</label>
                                        <input type="password" name="mailketing_api_token" value="{{ $ev->mailketing_api_token }}" placeholder="Token khusus organizer" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Sender Email</label>
                                        <input type="email" name="mailketing_sender_email" value="{{ $ev->mailketing_sender_email }}" placeholder="panitia@event.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Sender Name</label>
                                        <input type="text" name="mailketing_sender_name" value="{{ $ev->mailketing_sender_name }}" placeholder="Panitia Race" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-4 flex items-center gap-6 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input type="checkbox" name="is_active" value="1" {{ $ev->is_active ? 'checked' : '' }} class="w-4 h-4 rounded text-orange-600 border-slate-300 focus:ring-orange-500">
                                <span>Pendaftaran Aktif Dibuka</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input type="checkbox" name="is_default" value="1" {{ $ev->is_default ? 'checked' : '' }} class="w-4 h-4 rounded text-orange-600 border-slate-300 focus:ring-orange-500">
                                <span>Jadikan Event Utama (Default)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button type="button" @click="showEditEvent = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs shadow-md shadow-orange-600/20 transition">
                            Simpan Perubahan Event &rarr;
                        </button>
                    </div>
                </form>
            </div>

            <!-- Multi-Domain Management Section (CloudPanel Integration) -->
            <div class="p-6 border-b border-slate-100 bg-slate-50/50" x-data="{ showAddDomain: false }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span>🌐</span> Domain &amp; Subdomain Terhubung (CloudPanel Multi-Domain)
                        </h4>
                        <p class="text-xs text-slate-500">Daftar domain/subdomain yang otomatis diarahkan ke pendaftaran event ini.</p>
                    </div>
                    <button type="button" 
                            @click="showAddDomain = !showAddDomain" 
                            class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <span>➕</span>
                        <span x-text="showAddDomain ? 'Batal' : 'Tambah Domain Alias'"></span>
                    </button>
                </div>

                <!-- Form Add Domain Alias -->
                <div x-show="showAddDomain" x-collapse class="mb-4 p-4 rounded-xl bg-white border border-slate-200">
                    <form action="{{ route('admin.events.domains.store', ['id' => $ev->id]) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="flex-1 w-full">
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Nama Domain / Subdomain Baru</label>
                                <input type="text" 
                                       name="domain" 
                                       required 
                                       placeholder="contoh: tiket.marathonjakarta.id atau marathonjakarta.id" 
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs focus:ring-2 focus:ring-orange-500 focus:outline-none">
                            </div>
                            <div class="flex items-center gap-2 pt-4 sm:pt-6">
                                <label class="flex items-center gap-1.5 text-xs text-slate-700 font-bold cursor-pointer">
                                    <input type="checkbox" name="is_primary" value="1" class="rounded text-orange-600">
                                    <span>Jadikan Domain Utama</span>
                                </label>
                            </div>
                            <div class="pt-4 sm:pt-6">
                                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs rounded-lg transition shadow-sm">
                                    Hubungkan Domain
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            💡 <em>Langkah CloudPanel:</em> Setelah menambahkan domain di sini, pastikan domain tersebut sudah didaftarkan pada tab <strong>Domain Names</strong> di site CloudPanel Anda dan SSL Let's Encrypt sudah diterbitkan.
                        </p>
                    </form>
                </div>

                <!-- Domain List Table / Cards -->
                @if($ev->domains->isEmpty() && empty($ev->custom_domain))
                    <div class="p-3 bg-white rounded-xl border border-dashed border-slate-300 text-center text-xs text-slate-400">
                        Belum ada domain khusus. Event ini diakses via slug: <code class="font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">/event/{{ $ev->slug }}</code> atau domain default portal.
                    </div>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($ev->domains as $dom)
                            <div class="p-2.5 px-3 rounded-xl bg-white border border-slate-200 shadow-sm flex items-center gap-3 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $dom->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' }}"></span>
                                    <a href="https://{{ $dom->domain }}" target="_blank" class="font-mono font-bold text-slate-900 hover:text-orange-600 hover:underline">
                                        {{ $dom->domain }} ↗
                                    </a>
                                </div>

                                @if($dom->is_primary)
                                    <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-black tracking-wide border border-orange-200">
                                        PRIMARY
                                    </span>
                                @else
                                    <form action="{{ route('admin.events.domains.set_primary', ['id' => $dom->id]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[10px] font-bold text-slate-500 hover:text-orange-600 hover:underline">
                                            Jadikan Utama
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.events.domains.delete', ['id' => $dom->id]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Hapus domain {{ $dom->domain }}?')" class="text-rose-500 hover:text-rose-700 text-xs font-bold" title="Hapus Domain">
                                        &times;
                                    </button>
                                </form>
                            </div>
                        @endforeach

                        @if($ev->custom_domain && !$ev->domains->contains('domain', $ev->custom_domain))
                            <div class="p-2.5 px-3 rounded-xl bg-white border border-slate-200 shadow-sm flex items-center gap-2 text-xs">
                                <span class="font-mono font-bold text-slate-800">{{ $ev->custom_domain }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">LEGACY PRIMARY</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Categories Section for This Event -->
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span>🎫</span> Kategori Tiket &amp; Pengaturan Kuota
                        </h4>
                        <p class="text-xs text-slate-500">Atur kuota, harga reguler, promo early bird, dan status aktif tiket.</p>
                    </div>
                    <button type="button" 
                            @click="openAddCategory({{ $ev->id }}, '{{ addslashes($ev->title) }}')" 
                            class="px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <span>➕</span>
                        <span>Tambah Kategori</span>
                    </button>
                </div>

                @if($ev->ticketCategories->isEmpty())
                    <div class="p-8 rounded-xl border border-dashed border-slate-300 text-center">
                        <p class="text-xs text-slate-500 mb-2">Belum ada kategori tiket untuk event ini.</p>
                        <button type="button" 
                                @click="openAddCategory({{ $ev->id }}, '{{ addslashes($ev->title) }}')" 
                                class="text-xs font-bold text-orange-600 hover:underline">
                            + Tambah Kategori Tiket Sekarang
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($ev->ticketCategories as $cat)
                            <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-lg bg-slate-900 text-white font-mono font-bold text-xs">{{ $cat->code }}</span>
                                            <span class="text-xs font-extrabold text-slate-900">{{ $cat->name }}</span>
                                        </div>
                                        @if($cat->is_active)
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Dibuka</span>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">Ditutup</span>
                                        @endif
                                    </div>

                                    <!-- Sales Progress Stats -->
                                    <div class="mb-4 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] space-y-1">
                                        <div class="flex justify-between text-slate-500">
                                            <span>Terjual: <strong class="text-emerald-700 font-bold">{{ $cat->sold_count }}</strong></span>
                                            <span>Sisa: <strong class="text-orange-700 font-bold">{{ $cat->remaining_quota }}</strong> / {{ $cat->quota }}</span>
                                        </div>
                                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                            @php
                                                $soldPercent = $cat->quota > 0 ? min(100, round(($cat->sold_count / $cat->quota) * 100)) : 0;
                                            @endphp
                                            <div class="bg-orange-500 h-full rounded-full" style="width: {{ $soldPercent }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Edit Category Form -->
                                    <form action="{{ route('admin.category.update', ['id' => $cat->id]) }}" method="POST" class="space-y-3 text-xs">
                                        @csrf

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block font-bold text-slate-600 mb-0.5">Kode Kategori</label>
                                                <input type="text" name="code" value="{{ $cat->code }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-mono text-xs uppercase font-bold">
                                            </div>
                                            <div>
                                                <label class="block font-bold text-slate-600 mb-0.5">Min. Usia</label>
                                                <input type="number" name="min_age" value="{{ $cat->min_age }}" min="0" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block font-bold text-slate-600 mb-0.5">Nama Kategori</label>
                                            <input type="text" name="name" value="{{ $cat->name }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-semibold text-slate-900 text-xs">
                                        </div>

                                        <div>
                                            <label class="block font-bold text-slate-600 mb-0.5">Total Kuota Tiket</label>
                                            <input type="number" name="quota" value="{{ $cat->quota }}" required min="0" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-bold text-slate-900 text-xs">
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block font-bold text-slate-600 mb-0.5">Harga Reguler (Rp)</label>
                                                <input type="number" name="price" value="{{ (int)$cat->price }}" required min="0" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-bold text-slate-900 text-xs">
                                            </div>
                                            <div>
                                                <label class="block font-bold text-slate-600 mb-0.5">Harga Early Bird (Rp)</label>
                                                <input type="number" name="early_bird_price" value="{{ (int)$cat->early_bird_price }}" min="0" placeholder="Opsional" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 font-bold text-amber-700 text-xs">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block font-bold text-slate-600 mb-0.5">Batas Waktu Early Bird</label>
                                            <input type="datetime-local" name="early_bird_end_date" value="{{ $cat->early_bird_end_date ? $cat->early_bird_end_date->format('Y-m-d\TH:i') : '' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs">
                                        </div>

                                        <div>
                                            <label class="block font-bold text-slate-600 mb-0.5">Status Penjualan</label>
                                            <select name="is_active" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold">
                                                <option value="1" {{ $cat->is_active ? 'selected' : '' }}>Aktif Dibuka</option>
                                                <option value="0" {{ !$cat->is_active ? 'selected' : '' }}>Nonaktif / Ditutup</option>
                                            </select>
                                        </div>

                                        <div class="pt-2 flex items-center justify-between gap-2">
                                            <button type="submit" class="flex-1 py-2 px-3 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold transition shadow-sm text-xs text-center">
                                                Simpan
                                            </button>
                                            
                                            @if($cat->sold_count === 0 && $cat->reserved_count === 0)
                                                <button type="button" 
                                                        onclick="if(confirm('Hapus kategori tiket ini?')) document.getElementById('delete-cat-{{ $cat->id }}').submit();" 
                                                        class="p-2 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-700 transition" 
                                                        title="Hapus Kategori">
                                                    🗑️
                                                </button>
                                            @endif
                                        </div>
                                    </form>

                                    @if($cat->sold_count === 0 && $cat->reserved_count === 0)
                                        <form id="delete-cat-{{ $cat->id }}" action="{{ route('admin.category.delete', ['id' => $cat->id]) }}" method="POST" class="hidden">
                                            @csrf
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <!-- Modal: Tambah Event Baru -->
    <div x-show="showCreateModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showCreateModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-lg">Tambah Event Lari Baru</h3>
                    <p class="text-xs text-slate-500">Sistem multi-event siap digunakan untuk berbagai event lari.</p>
                </div>
                <button type="button" @click="showCreateModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.events.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Nama Event Lari <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="Contoh: Borobudur Heritage Marathon 2026" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-semibold text-sm">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">URL Slug <span class="text-rose-500">*</span></label>
                        <input type="text" name="slug" required placeholder="borobudur-marathon-2026" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Custom Subdomain / Domain</label>
                        <input type="text" name="custom_domain" placeholder="borobudur.regrun.test" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Race <span class="text-rose-500">*</span></label>
                        <input type="date" name="race_date" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-semibold">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jam Start Flag-Off <span class="text-rose-500">*</span></label>
                        <input type="time" name="race_start_time" value="06:00" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-semibold">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lokasi / Venue <span class="text-rose-500">*</span></label>
                        <input type="text" name="venue_name" required placeholder="Taman Wisata Candi Borobudur" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Venue</label>
                        <input type="text" name="venue_address" placeholder="Magelang, Jawa Tengah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai RPC</label>
                        <input type="date" name="rpc_start_date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai RPC</label>
                        <input type="date" name="rpc_end_date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Lokasi Pengambilan Racepack (RPC)</label>
                        <input type="text" name="rpc_location" placeholder="Ballroom Hotel Grand Artos Magelang" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Pengaturan Nomor BIB</label>
                        <select name="auto_generate_bib" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-semibold text-slate-800 text-xs">
                            <option value="1" selected>⚡ Otomatis: Generate nomor BIB langsung saat transaksi lunas (Rekomendasi)</option>
                            <option value="0">⏳ Tunda: Tidak perlu generate BIB dahulu (Dialokasikan nanti / saat RPC)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2 space-y-2 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input type="checkbox" name="seed_default_categories" value="1" checked class="w-4 h-4 rounded text-orange-600 border-slate-300 focus:ring-orange-500">
                            <span>Buat Otomatis Kategori Tiket Awal (5K Fun Run &amp; 10K Open)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded text-orange-600 border-slate-300 focus:ring-orange-500">
                            <span>Jadikan Sebagai Event Utama (Default)</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white font-bold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold shadow-lg shadow-orange-600/30 transition">
                        Simpan &amp; Buat Event &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Tambah Kategori Tiket -->
    <div x-show="showAddCategoryModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showAddCategoryModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Tambah Kategori Tiket</h3>
                    <p class="text-xs text-slate-500" x-text="'Event: ' + selectedEventTitle"></p>
                </div>
                <button type="button" @click="showAddCategoryModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form :action="'/admin/events/' + selectedEventId + '/categories'" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Kategori <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" required placeholder="Contoh: 21K" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono uppercase font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Min. Usia Peserta</label>
                        <input type="number" name="min_age" value="12" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: 21K Half Marathon" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Total Kuota Tiket <span class="text-rose-500">*</span></label>
                    <input type="number" name="quota" required min="1" placeholder="300" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Reguler (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" required min="0" placeholder="350000" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold text-slate-900">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Harga Early Bird (Rp)</label>
                        <input type="number" name="early_bird_price" min="0" placeholder="300000" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-bold text-amber-700">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Batas Waktu Early Bird</label>
                    <input type="datetime-local" name="early_bird_end_date" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddCategoryModal = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white font-bold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold shadow-md shadow-orange-600/20 transition">
                        Tambahkan Kategori &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
