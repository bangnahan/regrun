@extends('layouts.admin', ['title' => 'Manajemen Transaksi - RegRun'])

@section('page_title')
    Daftar Transaksi Tiket
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.transactions') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-600 mb-1">Cari Invoice / Nama / Email / No HP</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">Status Pembayaran</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="">Semua Status</option>
                    <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>PAID (Lunas)</option>
                    <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>UNPAID (Menunggu)</option>
                    <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>EXPIRED (Kedaluwarsa)</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">Event</label>
                <select name="event_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="">Semua Event</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}" {{ request('event_id') == $ev->id ? 'selected' : '' }}>{{ $ev->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold transition">
                    Filter Data
                </button>
                <a href="{{ route('admin.transactions') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-4">No. Invoice</th>
                        <th class="p-4">Event</th>
                        <th class="p-4">Data Pembeli</th>
                        <th class="p-4">Rincian Tiket</th>
                        <th class="p-4">Total Tagihan</th>
                        <th class="p-4">Metode Bayar</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-4">
                                <div class="font-mono font-bold text-slate-900">{{ $trx->invoice_number }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $trx->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td class="p-4 font-semibold text-slate-700">
                                {{ $trx->event->title ?? '-' }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-900">{{ $trx->buyer_name }}</div>
                                <div class="text-slate-500 text-[11px]">{{ $trx->buyer_email }}</div>
                                <div class="text-slate-400 text-[10px]">{{ $trx->buyer_phone }}</div>
                            </td>
                            <td class="p-4 text-slate-700">
                                <div class="font-bold text-slate-900">{{ $trx->participants->count() }} Pelari</div>
                                <div class="text-[11px] text-slate-500">
                                    @foreach($trx->items as $it)
                                        <span>{{ $it->quantity }}x {{ $it->ticketCategory->name }}</span>{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </div>
                            </td>
                            <td class="p-4 font-extrabold text-slate-900">
                                Rp {{ number_format($trx->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-slate-600 font-medium">
                                {{ $trx->payment_method ?? 'Tripay' }}
                            </td>
                            <td class="p-4">
                                @if($trx->status === 'PAID')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">PAID</span>
                                    @if($trx->paid_at)
                                        <div class="text-[9px] text-slate-400 mt-0.5">{{ $trx->paid_at->format('d/m H:i') }}</div>
                                    @endif
                                @elseif($trx->status === 'UNPAID')
                                    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">UNPAID</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 font-bold text-[10px]">{{ $trx->status }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-y-1">
                                <a href="{{ route('order.show', ['invoice' => $trx->invoice_number]) }}" target="_blank" class="inline-block text-[11px] font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
                                    Lihat Tiket
                                </a>

                                @if($trx->status !== 'PAID')
                                    <form action="{{ route('admin.transactions.mark_paid', ['invoice' => $trx->invoice_number]) }}" method="POST" onsubmit="return confirm('Konfirmasi tandai invoice ini sebagai PAID? (Akan generate BIB dan memicu email Mailketing)')">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                            Set LUNAS
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.transactions.resend_email', ['invoice' => $trx->invoice_number]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                        Kirim Email Lagi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">Tidak ada transaksi yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
