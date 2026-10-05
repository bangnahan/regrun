@extends('layouts.app', ['title' => $event->title . ' - Pembayaran'])

@section('content')
@php
    $defaultChannel = collect($paymentChannels)->firstWhere('code', 'QRIS2') 
        ?? collect($paymentChannels)->firstWhere('code', 'QRIS') 
        ?? ($paymentChannels[0] ?? null);
    $defaultMethod = $defaultChannel['code'] ?? 'QRIS2';
    $defaultFlat = (float)($defaultChannel['fee_customer']['flat'] ?? $defaultChannel['total_fee']['flat'] ?? 750);
    $defaultPercent = (float)($defaultChannel['fee_customer']['percent'] ?? $defaultChannel['total_fee']['percent'] ?? 0.7);
    $defaultFee = round($defaultFlat + ($subtotal * ($defaultPercent / 100)));
    $defaultIcon = $defaultChannel['icon_url'] ?? '';
    $defaultName = $defaultChannel['name'] ?? 'QRIS';
@endphp

<div x-data="{
    subtotal: {{ (int)$subtotal }},
    selectedMethod: '{{ $defaultMethod }}',
    selectedFee: {{ (int)$defaultFee }},
    selectedIcon: '{{ $defaultIcon }}',
    selectedName: '{{ $defaultName }}',
    fillFromParticipant1() {
        let p1Name = '{{ addslashes($participantsData[0]['full_name'] ?? '') }}';
        let p1Email = '{{ addslashes($participantsData[0]['email'] ?? '') }}';
        let p1Phone = '{{ addslashes($participantsData[0]['phone_number'] ?? '') }}';

        if (p1Name) document.getElementById('buyer_name').value = p1Name;
        if (p1Email) document.getElementById('buyer_email').value = p1Email;
        if (p1Phone) document.getElementById('buyer_phone').value = p1Phone;
    },
    updateChannel(event) {
        const opt = event.target.options[event.target.selectedIndex];
        if (!opt) return;
        const flat = parseFloat(opt.dataset.flat || 0);
        const percent = parseFloat(opt.dataset.percent || 0);
        this.selectedMethod = opt.value;
        this.selectedName = opt.dataset.name || opt.text;
        this.selectedIcon = opt.dataset.icon || '';
        this.selectedFee = Math.round(flat + (this.subtotal * (percent / 100)));
    },
    formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID');
    }
}" x-init="
    const sel = document.getElementById('payment_method_select');
    if (sel && sel.selectedIndex >= 0) {
        updateChannel({ target: sel });
    }
">
    <!-- Stepper Navigation -->
    <div class="flex items-center justify-between mb-8 max-w-xl mx-auto text-xs font-semibold">
        <a href="{{ route('register.index') }}" class="flex items-center gap-2 text-emerald-600 hover:text-emerald-700 transition">
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

    @if(session('error'))
        <div class="max-w-4xl mx-auto mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm">
            <span class="text-xl">⚠️</span>
            <div>
                <strong class="font-bold">Kendala Pembayaran:</strong>
                <p class="mt-0.5 text-xs">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-4xl mx-auto mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                            <p class="text-xs text-slate-500">Invoice, e-ticket, dan link pembayaran akan dikirimkan ke kontak ini.</p>
                        </div>
                        <button type="button" @click="fillFromParticipant1()" class="text-[11px] bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold px-3 py-1.5 rounded-lg border border-orange-200 transition flex items-center gap-1">
                            <span>📋</span>
                            <span>Salin dari Peserta 1</span>
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
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm font-medium" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Email Pembeli <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   id="buyer_email"
                                   name="buyer_email" 
                                   value="{{ old('buyer_email', $participantsData[0]['email'] ?? '') }}" 
                                   required 
                                   placeholder="email@pembeli.com"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm font-medium" />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Pembeli <span class="text-rose-500">*</span></label>
                            <input type="tel" 
                                   id="buyer_phone"
                                   name="buyer_phone" 
                                   value="{{ old('buyer_phone', $participantsData[0]['phone_number'] ?? '') }}" 
                                   required 
                                   placeholder="08123456789"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm font-medium" />
                        </div>
                    </div>
                </div>

                <!-- Dropdown Pemilihan Metode Pembayaran Tripay -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Metode Pembayaran (Tripay)</h3>
                            <p class="text-xs text-slate-500">Pilih kanal pembayaran resmi yang tersedia di bawah ini.</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Terhubung Langsung
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold text-slate-700 text-xs mb-1.5">
                                Pilih Kanal Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            
                            <div class="relative">
                                <select name="payment_method" 
                                        id="payment_method_select" 
                                        required 
                                        @change="updateChannel($event)" 
                                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-300 hover:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm font-semibold bg-white text-slate-800 shadow-sm cursor-pointer transition">
                                    
                                    @php
                                        // Sort groups: E-Wallet first, then Virtual Account, then Convenience Store, then others
                                        $grouped = collect($paymentChannels)->groupBy('group')->sortBy(function($val, $key) {
                                            if (stripos($key, 'wallet') !== false || stripos($key, 'qris') !== false) return 1;
                                            if (stripos($key, 'virtual') !== false || stripos($key, 'va') !== false) return 2;
                                            if (stripos($key, 'store') !== false || stripos($key, 'convenience') !== false || stripos($key, 'retail') !== false) return 3;
                                            return 4;
                                        });
                                    @endphp

                                    @foreach($grouped as $groupName => $channels)
                                        @php
                                            $groupLabel = match(true) {
                                                stripos($groupName, 'wallet') !== false || stripos($groupName, 'qris') !== false => '⚡ QRIS & E-Wallet (Instan & Praktis)',
                                                stripos($groupName, 'virtual') !== false => '🏦 Virtual Account Bank (Konfirmasi Otomatis 24 Jam)',
                                                stripos($groupName, 'store') !== false || stripos($groupName, 'convenience') !== false => '🏪 Gerai Minimarket (Alfamart / Indomaret)',
                                                default => '💳 ' . $groupName,
                                            };
                                        @endphp
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach($channels as $channel)
                                                @php
                                                    $cCode = $channel['code'];
                                                    $flat = (float)($channel['fee_customer']['flat'] ?? $channel['total_fee']['flat'] ?? 0);
                                                    $percent = (float)($channel['fee_customer']['percent'] ?? $channel['total_fee']['percent'] ?? 0);
                                                    $feeLabel = '';
                                                    if ($flat > 0 && $percent > 0) {
                                                        $feeLabel = 'Biaya: +Rp ' . number_format($flat, 0, ',', '.') . ' + ' . $percent . '%';
                                                    } elseif ($flat > 0) {
                                                        $feeLabel = 'Biaya: +Rp ' . number_format($flat, 0, ',', '.');
                                                    } elseif ($percent > 0) {
                                                        $feeLabel = 'Biaya: +' . $percent . '%';
                                                    } else {
                                                        $feeLabel = 'Bebas Biaya Admin';
                                                    }

                                                    $isSelected = ($cCode === $defaultMethod);
                                                @endphp
                                                <option value="{{ $cCode }}" 
                                                        data-flat="{{ $flat }}"
                                                        data-percent="{{ $percent }}"
                                                        data-name="{{ $channel['name'] }}"
                                                        data-icon="{{ $channel['icon_url'] ?? '' }}"
                                                        {{ $isSelected ? 'selected' : '' }}>
                                                    {{ $channel['name'] }} — {{ $feeLabel }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Selected Channel Info Badge -->
                        <div class="p-3.5 rounded-xl bg-orange-50/70 border border-orange-200 text-xs flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <template x-if="selectedIcon">
                                    <img :src="selectedIcon" :alt="selectedName" class="h-7 w-auto object-contain bg-white px-2 py-0.5 rounded border border-orange-200">
                                </template>
                                <div>
                                    <div class="font-extrabold text-slate-900" x-text="selectedName"></div>
                                    <div class="text-[11px] text-orange-800">
                                        Biaya Layanan: <strong x-text="formatRupiah(selectedFee)"></strong>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[11px] font-bold text-orange-700 bg-white px-2.5 py-1 rounded-lg border border-orange-200">
                                Otomatis
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 leading-relaxed flex items-start gap-2">
                            <span class="text-orange-500">🛡️</span>
                            <div>
                                Setelah klik tombol <strong>Lanjut Pembayaran</strong>, Anda akan diarahkan ke halaman resmi Tripay untuk menyelesaikan pembayaran. Sistem akan memverifikasi secara instan begitu dana terkirim.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="agree_terms" value="1" required class="mt-1 w-4 h-4 text-orange-600 rounded border-slate-300 focus:ring-orange-500">
                        <span class="text-xs text-slate-600 leading-relaxed">
                            Saya menyatakan bahwa data yang diisikan adalah benar dan sah. Seluruh peserta dalam keadaan sehat jasmani dan rohani serta menyetujui seluruh syarat &amp; peraturan keselamatan event lari ini.
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
                                <div class="font-bold text-slate-900">{{ $item['category_name'] ?? (isset($item['category']) ? (is_array($item['category']) ? $item['category']['name'] : $item['category']->name) : 'Tiket Event') }}</div>
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
                <div class="mt-4 pt-4 border-t border-slate-200 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Subtotal Tiket</span>
                        <span class="font-semibold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Biaya Layanan Gateway (Tripay)</span>
                        <span class="font-semibold text-orange-600" x-text="formatRupiah(selectedFee)"></span>
                    </div>
                    <div class="flex items-center justify-between text-base font-extrabold text-slate-900 pt-3 border-t border-slate-200">
                        <span>Total Tagihan</span>
                        <span class="text-orange-600 text-lg font-black" x-text="formatRupiah(subtotal + selectedFee)"></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full mt-6 py-3.5 px-6 rounded-xl bg-orange-600 hover:bg-orange-500 active:bg-orange-700 text-white font-extrabold text-sm shadow-xl shadow-orange-600/30 transition flex items-center justify-center gap-2 group">
                    <span>Lanjut Pembayaran Tripay</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </button>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] text-slate-400">
                    <span>🔒</span>
                    <span>Terenkripsi SSL 256-bit via Tripay Payment Gateway</span>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
