@extends('layouts.admin', ['title' => 'Pengaturan Tripay & Mailketing - RegRun'])

@section('page_title')
    Pengaturan Integrasi (Tripay &amp; Mailketing)
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <h2 class="text-base font-extrabold text-slate-900">Konfigurasi Gateway</h2>
        <p class="text-xs text-slate-500 mt-0.5">Kredensial API Payment Gateway Tripay dan Layanan Email Mailketing.</p>
    </div>

    <form action="{{ route('admin.settings.save') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Tripay Settings Box -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xl">💳</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Tripay Payment Gateway (Closed Payment)</h3>
                    <p class="text-[11px] text-slate-500">Kredensial merchant akun Tripay Anda.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Merchant</label>
                    <input type="text" name="tripay_merchant_code" value="{{ $settings['tripay_merchant_code'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-sm">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Sandbox (Testing)</label>
                    <select name="tripay_sandbox" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white font-semibold">
                        <option value="1" {{ $settings['tripay_sandbox'] ? 'selected' : '' }}>Aktif (Sandbox / Testing)</option>
                        <option value="0" {{ !$settings['tripay_sandbox'] ? 'selected' : '' }}>Nonaktif (Production / Live)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">API Key</label>
                    <input type="text" name="tripay_api_key" value="{{ $settings['tripay_api_key'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-xs">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">Private Key</label>
                    <input type="text" name="tripay_private_key" value="{{ $settings['tripay_private_key'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-xs">
                </div>
            </div>

            <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600">
                <strong>URL Callback Webhook untuk Tripay Dashboard:</strong><br>
                <code class="font-mono text-orange-600 bg-white px-2 py-0.5 rounded border border-slate-200 mt-1 inline-block">{{ url('/api/tripay/callback') }}</code>
            </div>
        </div>

        <!-- Mailketing Settings Box -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xl">✉️</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Mailketing Email API</h3>
                    <p class="text-[11px] text-slate-500">Pengiriman invoice &amp; E-Ticket resmi ke peserta.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">API Token Mailketing</label>
                    <input type="text" name="mailketing_api_token" value="{{ $settings['mailketing_api_token'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Email</label>
                    <input type="email" name="mailketing_sender_email" value="{{ $settings['mailketing_sender_email'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sender Name</label>
                    <input type="text" name="mailketing_sender_name" value="{{ $settings['mailketing_sender_name'] }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>
        </div>

        <button type="submit" class="px-6 py-3 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs shadow-lg shadow-orange-600/30 transition">
            Simpan Seluruh Pengaturan &rarr;
        </button>
    </form>
</div>
@endsection
