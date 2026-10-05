@extends('layouts.admin', ['title' => 'Pengaturan Tripay & Mailketing - RegRun'])

@section('page_title')
    Pengaturan Integrasi (Tripay &amp; Mailketing)
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Konfigurasi Gateway &amp; Notifikasi</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kredensial API Payment Gateway Tripay dan Layanan Email Mailketing.</p>
        </div>
        <a href="{{ route('admin.settings') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 px-3 py-1.5 rounded-lg border border-orange-200 transition flex items-center gap-1.5">
            <span>🔄</span>
            <span>Refresh Koneksi</span>
        </a>
    </div>

    <!-- Live Status Banner Tripay -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm" x-data="{ showChannels: false }">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ !empty($tripayStatus['connected']) ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }} flex items-center justify-center font-bold text-lg">
                    {{ !empty($tripayStatus['connected']) ? '⚡' : '❌' }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-extrabold text-slate-900 text-sm">Status Koneksi API Tripay</h3>
                        @if(!empty($tripayStatus['connected']))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                TERKONEKSI ({{ $tripayStatus['mode'] ?? 'Sandbox' }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                GAGAL TERHUBUNG
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        @if(!empty($tripayStatus['connected']))
                            Merchant Code: <strong class="font-mono text-slate-800">{{ $tripayStatus['merchant_code'] }}</strong> &bull; Total <strong class="text-emerald-700 font-bold">{{ $tripayStatus['channel_count'] }} Kanal Pembayaran Aktif</strong>
                        @else
                            Pesan error: <strong class="text-rose-600 font-mono text-xs">{{ $tripayStatus['error'] ?? 'Tidak dapat menjangkau server Tripay' }}</strong>
                        @endif
                    </p>
                </div>
            </div>

            @if(!empty($tripayStatus['connected']))
                <button type="button" 
                        @click="showChannels = !showChannels" 
                        class="text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 transition">
                    <span x-text="showChannels ? 'Tutup Daftar Kanal ▲' : 'Lihat Kanal Aktif ▼'"></span>
                </button>
            @endif
        </div>

        @if(!empty($tripayStatus['connected']) && !empty($tripayStatus['channels']))
            <div x-show="showChannels" x-collapse class="mt-5 pt-4 border-t border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($tripayStatus['channels'] as $ch)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 overflow-hidden">
                                @if(!empty($ch['icon_url']))
                                    <img src="{{ $ch['icon_url'] }}" alt="{{ $ch['name'] }}" class="h-5 w-auto object-contain bg-white px-1 py-0.5 rounded border border-slate-200">
                                @endif
                                <div class="truncate">
                                    <div class="font-bold text-slate-900 truncate">{{ $ch['name'] }}</div>
                                    <div class="text-[10px] font-mono text-slate-500">{{ $ch['code'] }} &bull; {{ $ch['group'] }}</div>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0">
                                Aktif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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
                    <h3 class="font-extrabold text-slate-900 text-sm">Mailketing Email API</h3>
                    <p class="text-[11px] text-slate-500">Pengiriman invoice &amp; E-Ticket resmi ke peserta lomba.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">API Token Mailketing</label>
                    <input type="text" name="mailketing_api_token" value="{{ $settings['mailketing_api_token'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Email</label>
                    <input type="email" name="mailketing_sender_email" value="{{ $settings['mailketing_sender_email'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Name</label>
                    <input type="text" name="mailketing_sender_name" value="{{ $settings['mailketing_sender_name'] }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <button type="submit" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs shadow-lg shadow-orange-600/30 transition flex items-center gap-2">
            <span>💾</span>
            <span>Simpan Seluruh Pengaturan</span>
        </button>
    </form>
</div>
@endsection
