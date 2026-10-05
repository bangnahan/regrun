@extends('layouts.admin', ['title' => 'Pengaturan Tripay & Mailketing - RegRun'])

@section('page_title')
    Pengaturan Integrasi (Tripay &amp; Mailketing)
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Konfigurasi Gateway &amp; Notifikasi Email</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kredensial API Payment Gateway Tripay dan Layanan Notifikasi Email Mailketing.</p>
        </div>
        <a href="{{ route('admin.settings') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 px-3 py-1.5 rounded-lg border border-orange-200 transition flex items-center gap-1.5">
            <span>🔄</span>
            <span>Refresh Status</span>
        </a>
    </div>

    <!-- Live Status Grid: Tripay & Mailketing -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Live Status Card Tripay -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm" x-data="{ showChannels: false }">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ !empty($tripayStatus['connected']) ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }} flex items-center justify-center font-bold text-lg shrink-0">
                    {{ !empty($tripayStatus['connected']) ? '⚡' : '❌' }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h3 class="font-extrabold text-slate-900 text-sm">Status Koneksi API Tripay</h3>
                        @if(!empty($tripayStatus['connected']))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                TERKONEKSI ({{ $tripayStatus['mode'] ?? 'Sandbox' }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                GAGAL TERHUBUNG
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1 truncate">
                        @if(!empty($tripayStatus['connected']))
                            Merchant: <strong class="font-mono text-slate-800">{{ $tripayStatus['merchant_code'] }}</strong> &bull; <strong class="text-emerald-700">{{ $tripayStatus['channel_count'] }} Kanal Aktif</strong>
                        @else
                            Error: <span class="text-rose-600 font-mono text-[11px]">{{ $tripayStatus['error'] ?? 'Offline' }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @if(!empty($tripayStatus['connected']))
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" 
                            @click="showChannels = !showChannels" 
                            class="text-xs font-bold text-slate-700 hover:text-slate-900 transition flex items-center gap-1">
                        <span x-text="showChannels ? 'Tutup Kanal ▲' : 'Lihat 15 Kanal Pembayaran ▼'"></span>
                    </button>
                    <span class="text-[11px] text-slate-400 font-medium">Auto-Sync</span>
                </div>

                <div x-show="showChannels" x-collapse class="mt-3 pt-3 border-t border-slate-100 max-h-48 overflow-y-auto space-y-1.5 pr-1">
                    @foreach($tripayStatus['channels'] ?? [] as $ch)
                        <div class="p-1.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] flex items-center justify-between gap-2">
                            <span class="font-semibold text-slate-800 truncate">{{ $ch['name'] }}</span>
                            <span class="font-mono text-[10px] text-slate-500 shrink-0">{{ $ch['code'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Live Status Card Mailketing -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm" x-data="{ showTestForm: false }">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ !empty($mailketingStatus['connected']) ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }} flex items-center justify-center font-bold text-lg shrink-0">
                    {{ !empty($mailketingStatus['connected']) ? '✉️' : '⚠️' }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h3 class="font-extrabold text-slate-900 text-sm">Mailketing Email API</h3>
                        @if(!empty($mailketingStatus['connected']))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                TERKONEKSI (Aktif)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                PERLU CEK
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1 truncate">
                        Sender: <strong class="font-mono text-slate-800">{{ $settings['mailketing_sender_email'] }}</strong>
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" 
                        @click="showTestForm = !showTestForm" 
                        class="text-xs font-bold text-orange-600 hover:text-orange-700 transition flex items-center gap-1.5">
                    <span>🚀 Tes Kirim Email Transaksi</span>
                    <span class="text-[10px] bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded font-bold">Simulasi</span>
                </button>
                <span class="text-[11px] text-slate-400 font-medium">Transactional API</span>
            </div>

            <!-- Inline Test Email Box with Template Selection -->
            <div x-show="showTestForm" x-collapse class="mt-3 pt-3 border-t border-slate-100">
                <form action="{{ route('admin.settings.test_email') }}" method="POST" class="space-y-2.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Pilih Contoh Email Transaksi:</label>
                        <select name="sample_type" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white font-medium focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="invoice"> Bukti Pembayaran Lunas (Invoice)</option>
                            <option value="eticket"> Official E-Ticket Pelari (dengan QR Code)</option>
                            <option value="pending_payment"> Tagihan Menunggu Pembayaran (VA / QRIS)</option>
                            <option value="simple"> Tes Koneksi Sederhana</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Email Penerima Uji Coba:</label>
                        <div class="flex gap-2">
                            <input type="email" 
                                   name="recipient" 
                                   value="{{ auth()->user()->email ?? 'bangnahan@gmail.com' }}"
                                   required 
                                   placeholder="emailanda@gmail.com" 
                                   class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <button type="submit" class="px-4 py-1.5 bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs rounded-lg shadow-sm transition shrink-0">
                                Kirim Email
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.settings.save') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Tripay Settings Box with Separate Sandbox vs Production Schema -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm" x-data="{ 
            selectedMode: '{{ $settings['tripay_mode'] }}', 
            viewTab: '{{ $settings['tripay_mode'] }}' 
        }">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 flex-wrap gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💳</span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">Pengaturan Payment Gateway Tripay</h3>
                        <p class="text-[11px] text-slate-500">Pemisahan skema kredensial Sandbox (Testing) dan Live Produksi.</p>
                    </div>
                </div>

                <!-- Mode Indicator Badge -->
                <div>
                    <template x-if="selectedMode === 'production'">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-200">
                            <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                            MODE LIVE PRODUKSI
                        </span>
                    </template>
                    <template x-if="selectedMode === 'sandbox'">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            MODE SANDBOX (TESTING)
                        </span>
                    </template>
                </div>
            </div>

            <!-- Active Mode Switcher -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-6">
                <label class="block font-extrabold text-slate-900 text-xs mb-2">Pilih Lingkungan Aktif Gateway:</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label :class="selectedMode === 'sandbox' ? 'border-amber-500 bg-amber-50/60 ring-2 ring-amber-400/30' : 'border-slate-200 bg-white hover:border-slate-300'" 
                           class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition">
                        <input type="radio" name="tripay_mode" value="sandbox" x-model="selectedMode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                        <div>
                            <div class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <span>🧪</span> Mode Sandbox (Testing)
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Uji coba simulasi pembayaran tanpa uang nyata. Menggunakan kanal QRIS simulator dan endpoint sandbox Tripay.</p>
                        </div>
                    </label>

                    <label :class="selectedMode === 'production' ? 'border-rose-500 bg-rose-50/60 ring-2 ring-rose-400/30' : 'border-slate-200 bg-white hover:border-slate-300'" 
                           class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition">
                        <input type="radio" name="tripay_mode" value="production" x-model="selectedMode" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                        <div>
                            <div class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <span>🔴</span> Mode Live Produksi
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Transaksi resmi menggunakan uang riil nasabah. Terhubung ke QRIS Nasional &amp; Virtual Account Bank resmi.</p>
                        </div>
                    </label>
                </div>

                <!-- Alert Warning Banner for Production -->
                <div x-show="selectedMode === 'production'" x-collapse class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-800">
                    <strong>⚠️ PERINGATAN KEAMANAN PRODUKSI:</strong>
                    <p class="text-[11px] text-rose-700 mt-0.5">
                        Dalam mode Produksi, fitur simulasi bayar otomatis DIBLOKIR. Pastikan Merchant Code, API Key, dan Private Key produksi di bawah ini sudah sesuai dengan dashboard Tripay resmi Anda (<a href="https://tripay.co.id" target="_blank" class="underline font-bold">tripay.co.id</a>).
                    </p>
                </div>
            </div>

            <!-- Tab Navigation to Edit Sandbox vs Production Credentials Independently -->
            <div class="flex items-center gap-2 border-b border-slate-200 mb-4 pb-2">
                <button type="button" 
                        @click="viewTab = 'sandbox'" 
                        :class="viewTab === 'sandbox' ? 'border-amber-500 text-amber-800 bg-amber-50/70 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs border transition flex items-center gap-1.5">
                    <span>🧪</span>
                    <span>Kredensial Sandbox</span>
                    <span x-show="selectedMode === 'sandbox'" class="text-[9px] bg-amber-200 text-amber-900 px-1 rounded font-black">AKTIF</span>
                </button>
                <button type="button" 
                        @click="viewTab = 'production'" 
                        :class="viewTab === 'production' ? 'border-rose-500 text-rose-800 bg-rose-50/70 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs border transition flex items-center gap-1.5">
                    <span>🔴</span>
                    <span>Kredensial Produksi (Live)</span>
                    <span x-show="selectedMode === 'production'" class="text-[9px] bg-rose-200 text-rose-900 px-1 rounded font-black">AKTIF</span>
                </button>
            </div>

            <!-- Panel 1: Sandbox Credentials -->
            <div x-show="viewTab === 'sandbox'" class="space-y-4">
                <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                    <div>
                        <strong>Skema Sandbox:</strong> Endpoint API: <code class="bg-white px-2 py-0.5 rounded font-mono text-[11px] border border-amber-200">https://tripay.co.id/api-sandbox/</code>
                    </div>
                    <span class="text-[10px] text-amber-700 font-bold">Kanal QRIS: QRIS2</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Merchant Code (Sandbox)</label>
                        <input type="text" name="tripay_sandbox_merchant_code" value="{{ $settings['tripay_sandbox_merchant_code'] }}" placeholder="T39430" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="hidden sm:block"></div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">API Key (Sandbox - Prefix DEV-...)</label>
                        <input type="text" name="tripay_sandbox_api_key" value="{{ $settings['tripay_sandbox_api_key'] }}" placeholder="DEV-..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Private Key (Sandbox)</label>
                        <input type="password" name="tripay_sandbox_private_key" value="{{ $settings['tripay_sandbox_private_key'] }}" placeholder="yNQJm-..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
            </div>

            <!-- Panel 2: Production Credentials -->
            <div x-show="viewTab === 'production'" class="space-y-4">
                <div class="p-3 bg-rose-50/50 rounded-xl border border-rose-200 text-xs text-rose-900 flex items-center justify-between">
                    <div>
                        <strong>Skema Live Produksi:</strong> Endpoint API: <code class="bg-white px-2 py-0.5 rounded font-mono text-[11px] border border-rose-200">https://tripay.co.id/api/</code>
                    </div>
                    <span class="text-[10px] text-rose-700 font-bold">Kanal QRIS: QRIS Nasional</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Merchant Code (Produksi)</label>
                        <input type="text" name="tripay_prod_merchant_code" value="{{ $settings['tripay_prod_merchant_code'] }}" placeholder="T..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>
                    <div class="hidden sm:block"></div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">API Key (Produksi Live)</label>
                        <input type="text" name="tripay_prod_api_key" value="{{ $settings['tripay_prod_api_key'] }}" placeholder="Masukkan API Key Live dari Tripay" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Private Key (Produksi Live)</label>
                        <input type="password" name="tripay_prod_private_key" value="{{ $settings['tripay_prod_private_key'] }}" placeholder="Masukkan Private Key Live dari Tripay" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>
                </div>
            </div>

            <!-- Webhook Info Box -->
            <div class="mt-6 p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <strong class="font-bold text-slate-900">URL Callback Webhook Resmi untuk Dashboard Tripay:</strong>
                    <div class="mt-1">
                        <code class="font-mono text-orange-600 bg-white px-2.5 py-1 rounded border border-slate-200 text-xs inline-block" id="webhook_url">{{ url('/api/tripay/callback') }}</code>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Masukkan URL ini di menu Pengaturan &gt; Webhook Callback pada dashboard Tripay Anda (baik Sandbox maupun Produksi).</p>
                </div>
                <button type="button" 
                        onclick="navigator.clipboard.writeText(document.getElementById('webhook_url').innerText); alert('URL Callback Webhook berhasil disalin!');" 
                        class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 shadow-sm transition shrink-0">
                    📋 Salin URL Webhook
                </button>
            </div>

            <!-- Pre-Live Production Security Checklist -->
            <div class="mt-4 p-4 rounded-xl bg-slate-900 text-white text-xs">
                <div class="font-extrabold text-sm text-emerald-400 flex items-center gap-1.5 mb-2">
                    <span>🛡️</span> Checklist Keamanan Sebelum Live Production:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-slate-300">
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-400">✓</span>
                        <span>Simulasi pembayaran otomatis diblokir di mode Produksi</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-400">✓</span>
                        <span>Verifikasi Signature Webhook HMAC-SHA256 aktif</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-400">✓</span>
                        <span>Rate Limiting Anti-Spam aktif pada rute pendaftaran &amp; bayar</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-400">✓</span>
                        <span>Pessimistic Locking aktif mencegah tiket *overselling*</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mailketing Settings Box -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xl">✉️</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Mailketing Email API (Notifikasi Transaksi &amp; E-Ticket)</h3>
                    <p class="text-[11px] text-slate-500">Kirim email invoice, petunjuk pembayaran, dan E-Ticket dengan QR Code otomatis.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">API Token Mailketing</label>
                    <input type="text" name="mailketing_api_token" value="{{ $settings['mailketing_api_token'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-[11px] text-slate-400 mt-1">Dapatkan dari menu API / Integrasi di dashboard Mailketing Anda.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Email Terverifikasi</label>
                    <input type="email" name="mailketing_sender_email" value="{{ $settings['mailketing_sender_email'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 font-medium">
                    <p class="text-[11px] text-emerald-600 mt-1">Pastikan domain sudah diverifikasi di Mailketing (contoh: hi@jelatix.com).</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Name (Nama Pengirim)</label>
                    <input type="text" name="mailketing_sender_name" value="{{ $settings['mailketing_sender_name'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 font-medium">
                    <p class="text-[11px] text-slate-400 mt-1">Nama penyelenggara yang tampil di inbox penerima.</p>
                </div>
            </div>

            <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 leading-relaxed">
                <strong>Otomasi Notifikasi Email yang Aktif:</strong>
                <ul class="list-disc list-inside mt-1.5 space-y-1 text-[11px] text-slate-500">
                    <li><strong class="text-slate-700">Email Tagihan (UNPAID):</strong> Dikirim seketika saat registrasi disubmit, berisi batas waktu dan kode pembayaran / VA.</li>
                    <li><strong class="text-slate-700">Email Bukti Lunas (PAID):</strong> Dikirim otomatis saat pembayaran terverifikasi oleh Tripay.</li>
                    <li><strong class="text-slate-700">E-Ticket Pelari (PAID):</strong> Dikirim ke email masing-masing peserta lengkap dengan QR Code &amp; Nomor BIB resmi.</li>
                </ul>
            </div>
        </div>

        <!-- General System & BIB Allocation Settings -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xl">🎽</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Pengaturan Nomor BIB Pelari (Global Default)</h3>
                    <p class="text-[11px] text-slate-500">Tentukan apakah nomor BIB otomatis dibuat saat pembayaran lunas atau ditunda terlebih dahulu.</p>
                </div>
            </div>

            <div class="text-xs max-w-lg">
                <label class="block font-bold text-slate-700 mb-1.5">Kebijakan Penomoran BIB Otomatis</label>
                <select name="auto_generate_bib" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-semibold text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="1" {{ ($settings['auto_generate_bib'] ?? '1') == '1' ? 'selected' : '' }}>⚡ Otomatis: Langsung buat nomor BIB berurutan saat transaksi lunas</option>
                    <option value="0" {{ ($settings['auto_generate_bib'] ?? '1') == '0' ? 'selected' : '' }}>⏳ Tunda: Tidak perlu generate BIB dahulu (Dialokasikan manual/massal nanti)</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                    💡 <em>Catatan:</em> Pengaturan ini juga dapat diatur secara spesifik pada masing-masing lomba di menu <strong class="text-slate-600">Manajemen Event</strong>. Jika memilih <strong>Tunda</strong>, nomor BIB pada e-ticket akan bertuliskan "Menyusul" sampai panitia mengklik tombol <em>Generate Nomor BIB</em> di menu <strong>Data Peserta</strong>.
                </p>
            </div>
        </div>

        <button type="submit" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs shadow-lg shadow-orange-600/30 transition flex items-center gap-2">
            <span>💾</span>
            <span>Simpan Seluruh Pengaturan</span>
        </button>
    </form>
</div>
@endsection
