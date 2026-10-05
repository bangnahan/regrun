@extends('layouts.app', ['title' => $event->title . ' - Pembayaran'])

@section('content')
<div x-data="{
    fillFromParticipant1() {
        let p1Name = '{{ addslashes($participantsData[0]['full_name'] ?? '') }}';
        let p1Email = '{{ addslashes($participantsData[0]['email'] ?? '') }}';
        let p1Phone = '{{ addslashes($participantsData[0]['phone_number'] ?? '') }}';

        if (p1Name) document.getElementById('buyer_name').value = p1Name;
        if (p1Email) document.getElementById('buyer_email').value = p1Email;
        if (p1Phone) document.getElementById('buyer_phone').value = p1Phone;
    }
}">
    <!-- Stepper Navigation -->
    <div class="flex items-center justify-between mb-8 max-w-xl mx-auto text-xs font-semibold">
        <a href="{{ route('register.index') }}" class="flex items-center gap-2 text-emerald-600">
            <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">&check;</span>
            <span>Pilih Tiket</span>
        </a>
        <div class="h-0.5 flex-1 bg-emerald-600 mx-3"></div>
        <div class="flex items-center gap-2 text-emerald-600">
            <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">&check;</span>
            <span>Data Peserta</span>
        </div>
        <div class="h-0.5 flex-1 bg-orange-600 mx-3"></div>
        <div class="flex items-center gap-2 text-orange-600">
            <span class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">3</span>
            <span>Pembayaran</span>
        </div>
    </div>

    <form action="{{ route('register.process_payment') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Form Pembeli & Pilihan Tripay Dropdown -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Kontak Pembeli Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Data Pemesan / Penanggung Jawab</h3>
                            <p class="text-xs text-slate-500">Invoice dan bukti pembayaran akan dikirimkan ke kontak ini.</p>
                        </div>
                        <button type="button" @click="fillFromParticipant1()" class="text-[11px] bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold px-2.5 py-1.5 rounded-lg border border-orange-200 transition">
                            Saya Peserta 1
                        </button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Pembeli <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   id="buyer_name"
                                   name="buyer_name" 
                                   value="{{ old('buyer_name', $participantsData[0]['full_name'] ?? '') }}" 
                                   required 
                                   placeholder="Contoh: Budi Santoso"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email Pembeli <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   id="buyer_email"
                                   name="buyer_email" 
                                   value="{{ old('buyer_email', $participantsData[0]['email'] ?? '') }}" 
                                   required 
                                   placeholder="email@pembeli.com"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp Pembeli <span class="text-rose-500">*</span></label>
                            <input type="tel" 
                                   id="buyer_phone"
                                   name="buyer_phone" 
                                   value="{{ old('buyer_phone', $participantsData[0]['phone_number'] ?? '') }}" 
                                   required 
                                   placeholder="08123456789"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>
                    </div>
                </div>

                <!-- Dropdown Pemilihan Metode Pembayaran Tripay -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="pb-3 mb-4 border-b border-slate-100">
                        <h3 class="font-extrabold text-slate-900 text-base">Metode Pembayaran (Tripay)</h3>
                        <p class="text-xs text-slate-500">Pilih channel pembayaran yang Anda inginkan melalui menu dropdown di bawah ini.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block font-bold text-slate-700 text-xs">Pilih Kanal Pembayaran <span class="text-rose-500">*</span></label>
                        
                        <div class="relative">
                            <select name="payment_method" required class="w-full px-4 py-3 rounded-xl border-2 border-slate-300 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm font-semibold bg-white text-slate-800 shadow-sm cursor-pointer">
                                <option value="">-- Silakan Pilih Metode Pembayaran --</option>
                                
                                <optgroup label="⚡ Instant Payment / E-Wallet">
                                    <option value="QRIS" selected>QRIS (BCA Mobile, GoPay, OVO, ShopeePay, Dana, LinkAja)</option>
                                </optgroup>

                                <optgroup label="🏦 Virtual Account Bank (Konfirmasi Otomatis)">
                                    <option value="BCAVA">BCA Virtual Account</option>
                                    <option value="MANDIRIVA">Mandiri Virtual Account</option>
                                    <option value="BNIVA">BNI Virtual Account</option>
                                    <option value="BRIVA">BRI Virtual Account</option>
                                    <option value="PERMATAVA">Permata Virtual Account</option>
                                    <option value="BSIVA">BSI (Bank Syariah Indonesia) Virtual Account</option>
                                </optgroup>

                                <optgroup label="🏪 Gerai Retail">
                                    <option value="ALFAMART">Alfamart / Alfamidi</option>
                                    <option value="INDOMARET">Indomaret</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="p-3 rounded-xl bg-orange-50 border border-orange-200/80 text-[11px] text-orange-900 leading-relaxed flex items-start gap-2">
                            <span>💡</span>
                            <div>
                                Pembayaran diverifikasi <strong>secara otomatis</strong> oleh Payment Gateway Tripay dalam hitungan detik setelah Anda transfer/scan QRIS.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="agree_terms" value="1" required class="mt-1 w-4 h-4 text-orange-600 rounded border-slate-300 focus:ring-orange-500">
                        <span class="text-xs text-slate-600 leading-relaxed">
                            Saya menyatakan bahwa data yang diisikan adalah benar dan sah. Seluruh peserta dalam keadaan sehat jasmani dan rohani serta menyetujui seluruh syarat &amp; peraturan keselamatan lomba.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Right Column: Order Summary Card -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-6 border border-slate-200 shadow-sm sticky top-24">
                <h3 class="font-extrabold text-slate-900 text-base pb-3 mb-4 border-b border-slate-100">
                    Ringkasan Pemesanan
                </h3>

                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($selectedTickets as $item)
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-bold text-slate-900">{{ $item['category_name'] ?? (is_array($item['category']) ? $item['category']['name'] : $item['category']->name) }}</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $item['quantity'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="font-bold text-slate-900 text-sm">
                                Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Price Calculations -->
                <div class="mt-4 pt-4 border-t border-slate-200 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Subtotal Tiket</span>
                        <span class="font-semibold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Biaya Transaksi Tripay</span>
                        <span class="font-semibold text-slate-900">Rp 4.500</span>
                    </div>
                    <div class="flex items-center justify-between text-base font-extrabold text-slate-900 pt-3 border-t border-slate-200">
                        <span>Total Tagihan</span>
                        <span class="text-orange-600 text-lg">Rp {{ number_format($subtotal + 4500, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full mt-6 py-3.5 px-6 rounded-xl bg-orange-600 hover:bg-orange-500 active:bg-orange-700 text-white font-extrabold text-sm shadow-xl shadow-orange-600/30 transition flex items-center justify-center gap-2">
                    <span>Lanjut ke Tripay &rarr;</span>
                </button>

                <p class="text-[11px] text-center text-slate-400 mt-3">
                    Transaksi aman &amp; terenkripsi via Tripay Payment Gateway.
                </p>
            </div>
        </div>
    </form>
</div>
@endsection
