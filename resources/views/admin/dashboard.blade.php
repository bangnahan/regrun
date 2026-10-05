@extends('layouts.admin', ['title' => 'Dashboard Overview - RegRun'])

@section('page_title')
    Dashboard Overview
@endsection

@section('content')
<div class="space-y-6">
    <!-- Multi-Event Selector Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="text-xl">🏆</span>
            <div>
                <div class="text-xs text-slate-500 font-medium">Event yang Dipilih:</div>
                <div class="text-base font-extrabold text-slate-900">{{ $selectedEvent->title ?? 'Semua Event' }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Pilih Event:</label>
            <select name="event_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-500">
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ ($selectedEvent && $selectedEvent->id === $ev->id) ? 'selected' : '' }}>
                        {{ $ev->title }} ({{ $ev->race_date ? $ev->race_date->format('M Y') : '' }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- 4 Key Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Revenue -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pendapatan</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">💰</span>
            </div>
            <div class="text-2xl font-black text-slate-900">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-emerald-600 font-semibold mt-1">
                {{ $paidTransactionsCount }} transaksi berhasil
            </div>
        </div>

        <!-- Card 2: Total Pelari Terbayar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Peserta Terdaftar</span>
                <span class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center text-sm font-bold">🏃</span>
            </div>
            <div class="text-2xl font-black text-slate-900">
                {{ number_format($totalParticipants) }} Pelari
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Data resmi siap cetak BIB &amp; Jersey
            </div>
        </div>

        <!-- Card 3: Status Transaksi -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Menunggu Bayar</span>
                <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-bold">⏳</span>
            </div>
            <div class="text-2xl font-black text-amber-600">
                {{ number_format($unpaidTransactionsCount) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Transaksi belum transfer Tripay
            </div>
        </div>

        <!-- Card 4: Shortcut Rekap Jersey Pabrik -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-100/60 rounded-2xl p-5 border border-amber-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-black text-amber-900 uppercase tracking-wider">Rekap Jersey</span>
                    <span class="text-base">🎽</span>
                </div>
                <div class="text-xs text-amber-800 font-medium">
                    Matriks ukuran XS &mdash; 5XL siap kirim vendor garmen.
                </div>
            </div>
            <a href="{{ route('admin.jersey_recap', ['event_id' => $selectedEvent->id ?? null]) }}" class="mt-3 inline-flex items-center justify-center px-3 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs shadow transition">
                Buka Matriks Pabrik &rarr;
            </a>
        </div>
    </div>

    <!-- Ticket Category Quota Breakdown -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h3 class="font-extrabold text-slate-900 text-base">Kuota &amp; Progres Penjualan per Kategori</h3>
            <a href="{{ route('admin.events') }}" class="text-xs text-orange-600 hover:underline font-semibold">
                Kelola Kuota &amp; Harga &rarr;
            </a>
        </div>

        <div class="space-y-4">
            @foreach($categories as $cat)
                @php
                    $percent = $cat->quota > 0 ? min(100, round(($cat->sold_count / $cat->quota) * 100)) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800 mb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-[10px]">{{ $cat->code }}</span>
                            <span>{{ $cat->name }}</span>
                            @if($cat->is_early_bird_active)
                                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">Early Bird Aktif</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-orange-600">{{ number_format($cat->sold_count) }}</span> / {{ number_format($cat->quota) }} tiket 
                            <span class="text-slate-400 font-normal">({{ $percent }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner">
                        <div class="bg-orange-600 h-3 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-900 text-base">Transaksi Pendaftaran Terkini</h3>
            <a href="{{ route('admin.transactions') }}" class="text-xs text-orange-600 font-semibold hover:underline">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-4">Invoice</th>
                        <th class="p-4">Pembeli</th>
                        <th class="p-4">Tiket</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Metode</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Waktu</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($recentTransactions as $trx)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-4 font-mono font-bold text-slate-800">
                                {{ $trx->invoice_number }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-900">{{ $trx->buyer_name }}</div>
                                <div class="text-slate-400 text-[11px]">{{ $trx->buyer_phone }}</div>
                            </td>
                            <td class="p-4 text-slate-700">
                                {{ $trx->items->sum('quantity') }} tiket
                            </td>
                            <td class="p-4 font-bold text-slate-900">
                                Rp {{ number_format($trx->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ $trx->payment_method }}
                            </td>
                            <td class="p-4">
                                @if($trx->status === 'PAID')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">PAID</span>
                                @elseif($trx->status === 'UNPAID')
                                    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">UNPAID</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 font-bold text-[10px]">{{ $trx->status }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-500">
                                {{ $trx->created_at->format('d/m H:i') }}
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <a href="{{ route('order.show', ['invoice' => $trx->invoice_number]) }}" target="_blank" class="text-blue-600 hover:underline">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">Belum ada transaksi pendaftaran masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
