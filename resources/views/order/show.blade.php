@extends('layouts.app', ['title' => 'Detail Pesanan - ' . $transaction->invoice_number, 'event' => $transaction->event])

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Invoice Status Banner -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm mb-6 text-center">
        @if($transaction->status === 'PAID')
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl font-black mx-auto mb-4 shadow-sm">
                &check;
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-2">
                <span>● PEMBAYARAN LUNAS</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mb-1">Registrasi Anda Berhasil!</h1>
            <p class="text-slate-500 text-xs max-w-md mx-auto">
                Invoice dan E-Ticket resmi telah dikirimkan ke email <strong>{{ $transaction->buyer_email }}</strong> via Mailketing.
            </p>
        @elseif($transaction->status === 'UNPAID')
            <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-black mx-auto mb-3 shadow-sm">
                ⏳
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold mb-2">
                <span>● MENUNGGU PEMBAYARAN</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mb-1">Selesaikan Pembayaran Anda</h1>
            <p class="text-slate-500 text-xs">
                Silakan lakukan transfer sebelum batas waktu berakhir agar tiket tidak dibatalkan.
            </p>
        @else
            <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-black mx-auto mb-3">
                &times;
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 text-rose-800 text-xs font-bold mb-2">
                <span>● KEDALUWARSA</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 mb-1">Waktu Pembayaran Telah Habis</h1>
            <p class="text-slate-500 text-xs">
                Transaksi ini telah kedaluwarsa. Silakan lakukan pendaftaran ulang jika kuota masih tersedia.
            </p>
        @endif

        <!-- Invoice Details Bar -->
        <div class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-3 text-left text-xs bg-slate-50 p-4 rounded-xl">
            <div>
                <div class="text-slate-400 font-medium">No. Invoice</div>
                <div class="font-bold text-slate-800">{{ $transaction->invoice_number }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-medium">Metode</div>
                <div class="font-bold text-slate-800">{{ $transaction->payment_method }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-medium">Total Bayar</div>
                <div class="font-bold text-orange-600">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-medium">Waktu Transaksi</div>
                <div class="font-bold text-slate-800">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <!-- If UNPAID: Payment Guide (Tripay QRIS / Virtual Account) -->
    @if($transaction->status === 'UNPAID')
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm mb-6">
            <h2 class="font-bold text-slate-900 text-base mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span>Instruksi Pembayaran Tripay</span>
                <span class="text-xs font-normal text-slate-500">Masa berlaku: 60 Menit</span>
            </h2>

            <!-- QRIS Display -->
            @if($transaction->payment_channel_code === 'QRIS' || !empty($transaction->tripay_qr_url))
                <div class="text-center py-4">
                    <p class="text-xs text-slate-600 mb-3 font-medium">Scan QRIS menggunakan BCA Mobile, GoPay, OVO, ShopeePay, atau Dana:</p>
                    <div class="inline-block p-4 bg-white border-2 border-slate-200 rounded-2xl shadow-md">
                        <img src="{{ $transaction->tripay_qr_url ?? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($transaction->invoice_number) }}" 
                             alt="QR Code Tripay" 
                             class="w-56 h-56 mx-auto rounded-lg" />
                    </div>
                </div>
            @endif

            <!-- VA Number Display -->
            @if(!empty($transaction->tripay_pay_code))
                <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200" x-data="{ copied: false }">
                    <div class="text-xs text-slate-500 font-medium mb-1">Nomor Rekening / Kode Pembayaran:</div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-xl sm:text-2xl font-black text-slate-900 tracking-wider">
                            {{ $transaction->tripay_pay_code }}
                        </span>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ $transaction->tripay_pay_code }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Sandbox Testing Simulator Button -->
            <div class="mt-6 pt-5 border-t border-slate-100 bg-amber-50/70 p-4 rounded-xl border border-amber-200">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <div class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                            <span>🛠️</span> Mode Pengujian Sandbox
                        </div>
                        <div class="text-[11px] text-amber-700">Simulasikan pembayaran lunas seketika untuk menguji alur tiket &amp; email.</div>
                    </div>
                    <form action="{{ route('order.simulate_pay', ['invoice' => $transaction->invoice_number]) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow transition">
                            Simulasikan Bayar Lunas &rarr;
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- If PAID: Runner Official E-Tickets -->
    @if($transaction->status === 'PAID')
        <div class="space-y-6 mb-8">
            <h2 class="font-extrabold text-slate-900 text-lg flex items-center justify-between">
                <span>E-Ticket Resmi Peserta ({{ $transaction->participants->count() }} Pelari)</span>
                <span class="text-xs font-semibold text-slate-500">Tunjukkan saat Racepack Collection</span>
            </h2>

            @foreach($transaction->participants as $idx => $p)
                <div class="bg-white rounded-2xl p-6 border-2 border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 bg-slate-900 text-white text-[11px] font-black px-3.5 py-1 rounded-bl-xl tracking-wider">
                        {{ $p->ticketCategory->name }}
                    </div>

                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                        <!-- QR Code for Racepack Collection -->
                        <div class="flex-shrink-0 text-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($p->ticket_code) }}" 
                                 alt="QR Code Tiket" 
                                 class="w-32 h-32 rounded-xl border border-slate-200 p-1 shadow-sm" />
                            <div class="font-mono text-xs font-extrabold text-slate-900 mt-2">{{ $p->ticket_code }}</div>
                        </div>

                        <!-- Runner Info -->
                        <div class="flex-1 text-xs space-y-2 w-full">
                            <div class="grid grid-cols-2 gap-2 pb-3 border-b border-slate-100">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                    <span class="font-bold text-slate-900 text-sm">{{ $p->full_name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nama di Nomor BIB</span>
                                    <span class="font-black text-orange-600 text-sm uppercase tracking-wider">{{ $p->bib_name }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 pt-1">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nomor BIB</span>
                                    <span class="font-mono font-bold text-slate-800">
                                        @if($p->bib_number)
                                            {{ $p->bib_number }}
                                        @else
                                            <span class="text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded text-[10px] font-bold">Menyusul (Diberikan saat RPC)</span>
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Ukuran Jersey</span>
                                    <span class="font-extrabold text-slate-900 text-sm bg-slate-100 px-2 py-0.5 rounded">{{ $p->jerseySize->size_code }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Gol. Darah</span>
                                    <span class="font-bold text-slate-800">{{ $p->blood_type }}</span>
                                </div>
                            </div>

                            <div class="pt-2 text-[11px] text-slate-500 border-t border-slate-100 flex items-center justify-between">
                                <span>Kontak Darurat: {{ $p->emergency_contact_name }} ({{ $p->emergency_contact_phone }})</span>
                                <span class="font-semibold text-emerald-600">✓ Terverifikasi</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Racepack Collection Info Box -->
            <div class="bg-blue-50/80 border border-blue-200 p-5 rounded-2xl text-xs text-blue-900 leading-relaxed shadow-sm">
                <div class="font-bold text-sm mb-1.5 flex items-center gap-2">
                    <span>📦</span> Informasi Pengambilan Paket Lomba (Racepack Collection)
                </div>
                <p>
                    <strong>Lokasi:</strong> {{ $transaction->event->rpc_location ?? $transaction->event->venue_name }}<br>
                    <strong>Jadwal:</strong> {{ $transaction->event->rpc_start_date ? $transaction->event->rpc_start_date->format('d M Y') : '-' }} s/d {{ $transaction->event->rpc_end_date ? $transaction->event->rpc_end_date->format('d M Y') : '-' }}<br>
                    Harap membawa kartu identitas resmi (KTP/Paspor) asli dan menunjukkan QR Code E-Ticket ini saat pengambilan.
                </p>
            </div>
        </div>
    @endif

    <div class="text-center pt-2">
        <a href="{{ route('register.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold underline">
            &larr; Kembali ke Halaman Utama Pendaftaran
        </a>
    </div>
</div>
@endsection
