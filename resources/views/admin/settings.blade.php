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

        <!-- Tripay Settings Box -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xl">💳</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Pengaturan Kredensial Tripay</h3>
                    <p class="text-[11px] text-slate-500">Dapatkan API Key &amp; Private Key dari Dashboard Merchant Tripay.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Merchant</label>
                    <input type="text" name="tripay_merchant_code" value="{{ $settings['tripay_merchant_code'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Sandbox (Testing)</label>
                    <select name="tripay_sandbox" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-semibold focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="1" {{ $settings['tripay_sandbox'] ? 'selected' : '' }}>Aktif (Sandbox / Testing)</option>
                        <option value="0" {{ !$settings['tripay_sandbox'] ? 'selected' : '' }}>Nonaktif (Production / Live)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">API Key</label>
                    <input type="text" name="tripay_api_key" value="{{ $settings['tripay_api_key'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">Private Key</label>
                    <input type="text" name="tripay_private_key" value="{{ $settings['tripay_private_key'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="mt-5 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <strong class="font-bold text-slate-900">URL Callback Webhook untuk Tripay Dashboard:</strong>
                    <div class="mt-1">
                        <code class="font-mono text-orange-600 bg-white px-2.5 py-1 rounded border border-slate-200 text-xs inline-block" id="webhook_url">{{ url('/api/tripay/callback') }}</code>
                    </div>
                </div>
                <button type="button" 
                        onclick="navigator.clipboard.writeText(document.getElementById('webhook_url').innerText); alert('URL Callback Webhook berhasil disalin!');" 
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 shadow-sm transition">
                    📋 Salin URL
                </button>
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

        <button type="submit" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs shadow-lg shadow-orange-600/30 transition flex items-center gap-2">
            <span>💾</span>
            <span>Simpan Seluruh Pengaturan</span>
        </button>
    </form>
</div>
@endsection
