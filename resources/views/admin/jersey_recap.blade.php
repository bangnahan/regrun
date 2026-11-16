@extends('layouts.admin', ['title' => 'Rekap Ukuran Jersey Pabrik - RegRun'])

@section('page_title')
    Rekapitulasi Produksi Jersey Pabrik
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header & Action Controls -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🎽</span>
                <h2 class="text-lg font-black text-slate-900">Rekapitulasi Ukuran Jersey untuk Vendor / Pabrik Konveksi</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Data dihitung otomatis hanya dari transaksi yang telah <strong>LUNAS (PAID)</strong>. Rentang ukuran <strong>XS sampai 5XL</strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Event & Jersey Type Filter -->
            <form method="GET" action="{{ route('admin.jersey_recap') }}" class="flex items-center gap-2">
                <select name="event_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}" {{ ($selectedEvent && $selectedEvent->id === $ev->id) ? 'selected' : '' }}>
                            Event: {{ $ev->title }}
                        </option>
                    @endforeach
                </select>

                <select name="jersey_type" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="all" {{ ($jerseyType ?? 'all') === 'all' ? 'selected' : '' }}>Semua Model Lengan</option>
                    <option value="short" {{ ($jerseyType ?? '') === 'short' ? 'selected' : '' }}>👕 Lengan Pendek</option>
                    <option value="long" {{ ($jerseyType ?? '') === 'long' ? 'selected' : '' }}>👔 Lengan Panjang</option>
                    <option value="singlet" {{ ($jerseyType ?? '') === 'singlet' ? 'selected' : '' }}>🎽 Singlet</option>
                </select>
            </form>

            <!-- Export to CSV Button -->
            <a href="{{ route('admin.jersey_recap.export', ['event_id' => $selectedEvent->id ?? null, 'jersey_type' => $jerseyType ?? 'all']) }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
                <span>📥</span>
                <span>Download Rekap Pabrik (CSV)</span>
            </a>

            <!-- Print Button -->
            <button onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-300 transition flex items-center gap-1.5">
                <span>🖨️</span> Cetak Lembar PO
            </button>
        </div>
    </div>

    <!-- Summary Box -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-2xl p-5 text-white shadow-lg shadow-orange-600/20">
            <div class="text-xs uppercase font-extrabold tracking-wider text-orange-100">Total Jersey Wajib Diproduksi</div>
            <div class="text-3xl font-black mt-1">{{ number_format($overallTotal) }} <span class="text-base font-semibold">Pcs</span></div>
            <div class="text-[11px] text-orange-100/80 mt-1">Siap order ke pabrik untuk {{ $selectedEvent->title ?? 'Event' }}</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm sm:col-span-2">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Distribusi Cepat Ukuran:</div>
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach($sizes as $s)
                    <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <span class="font-extrabold text-slate-700">{{ $s->size_code }}:</span>
                        <span class="font-bold text-orange-600">{{ number_format($sizeTotals[$s->size_code] ?? 0) }} pcs</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Model Lengan Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.jersey_recap', ['event_id' => $selectedEvent->id ?? null, 'jersey_type' => 'short']) }}" 
           class="p-4 rounded-2xl border transition {{ ($jerseyType ?? '') === 'short' ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-slate-800' : 'bg-white border-slate-200 text-slate-800 hover:border-slate-300 shadow-sm' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ ($jerseyType ?? '') === 'short' ? 'text-slate-300' : 'text-slate-500' }}">👕 Lengan Pendek</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ ($jerseyType ?? '') === 'short' ? 'bg-slate-800 text-slate-200' : 'bg-slate-100 text-slate-600' }}">Standar</span>
            </div>
            <div class="text-2xl font-black mt-2">{{ number_format($typeTotals['short'] ?? 0) }} <span class="text-xs font-semibold">pcs</span></div>
            <div class="text-[11px] mt-1 {{ ($jerseyType ?? '') === 'short' ? 'text-slate-400' : 'text-slate-400' }}">Klik untuk filter matriks lengan pendek</div>
        </a>

        <a href="{{ route('admin.jersey_recap', ['event_id' => $selectedEvent->id ?? null, 'jersey_type' => 'long']) }}" 
           class="p-4 rounded-2xl border transition {{ ($jerseyType ?? '') === 'long' ? 'bg-amber-600 text-white border-amber-600 shadow-md ring-2 ring-amber-500' : 'bg-white border-slate-200 text-slate-800 hover:border-slate-300 shadow-sm' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ ($jerseyType ?? '') === 'long' ? 'text-amber-100' : 'text-amber-700' }}">👔 Lengan Panjang</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ ($jerseyType ?? '') === 'long' ? 'bg-amber-700 text-amber-100' : 'bg-amber-100 text-amber-800' }}">+Rp 20.000</span>
            </div>
            <div class="text-2xl font-black mt-2">{{ number_format($typeTotals['long'] ?? 0) }} <span class="text-xs font-semibold">pcs</span></div>
            <div class="text-[11px] mt-1 {{ ($jerseyType ?? '') === 'long' ? 'text-amber-100' : 'text-slate-400' }}">Klik untuk filter matriks lengan panjang</div>
        </a>

        <a href="{{ route('admin.jersey_recap', ['event_id' => $selectedEvent->id ?? null, 'jersey_type' => 'singlet']) }}" 
           class="p-4 rounded-2xl border transition {{ ($jerseyType ?? '') === 'singlet' ? 'bg-sky-700 text-white border-sky-700 shadow-md ring-2 ring-sky-600' : 'bg-white border-slate-200 text-slate-800 hover:border-slate-300 shadow-sm' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ ($jerseyType ?? '') === 'singlet' ? 'text-sky-100' : 'text-sky-700' }}">🎽 Singlet / Sleeveless</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ ($jerseyType ?? '') === 'singlet' ? 'bg-sky-800 text-sky-100' : 'bg-sky-100 text-sky-800' }}">Standar</span>
            </div>
            <div class="text-2xl font-black mt-2">{{ number_format($typeTotals['singlet'] ?? 0) }} <span class="text-xs font-semibold">pcs</span></div>
            <div class="text-[11px] mt-1 {{ ($jerseyType ?? '') === 'singlet' ? 'text-sky-100' : 'text-slate-400' }}">Klik untuk filter matriks singlet</div>
        </a>
    </div>

    <!-- The Pivot Matrix Table -->
    <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="font-bold text-sm">
                    Matriks Produksi: Kategori Lomba &times; Ukuran Jersey (XS &mdash; 5XL)
                </span>
                @if(($jerseyType ?? 'all') !== 'all')
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-orange-600 text-white">
                        Filter: {{ $jerseyType === 'long' ? 'Lengan Panjang' : ($jerseyType === 'singlet' ? 'Singlet' : 'Lengan Pendek') }}
                    </span>
                    <a href="{{ route('admin.jersey_recap', ['event_id' => $selectedEvent->id ?? null, 'jersey_type' => 'all']) }}" class="text-[10px] text-slate-400 hover:text-white underline">
                        Reset Filter
                    </a>
                @endif
            </div>
            <span class="text-[11px] text-slate-400">Pola: Unisex Standard Running Fit</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-center text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-extrabold border-b-2 border-slate-300">
                        <th class="p-3.5 text-left border-r border-slate-300 min-w-[180px]">Kategori Lari</th>
                        @foreach($sizes as $s)
                            <th class="p-3.5 border-r border-slate-200 min-w-[55px] font-black text-slate-900">
                                {{ $s->size_code }}
                            </th>
                        @endforeach
                        <th class="p-3.5 bg-orange-50 text-orange-950 font-black border-l-2 border-orange-200 min-w-[90px]">
                            TOTAL PCS
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-semibold text-slate-800">
                    @forelse($matrix as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3.5 text-left font-bold text-slate-900 border-r border-slate-200 bg-slate-50/50">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white text-[10px] font-mono">{{ $row['category_code'] }}</span>
                                    <span>{{ $row['category_name'] }}</span>
                                </div>
                            </td>
                            @foreach($sizes as $s)
                                @php $cnt = $row['sizes'][$s->size_code] ?? 0; @endphp
                                <td class="p-3.5 border-r border-slate-100 {{ $cnt > 0 ? 'font-bold text-slate-900' : 'text-slate-300 font-normal' }}">
                                    {{ $cnt > 0 ? $cnt : '-' }}
                                </td>
                            @endforeach
                            <td class="p-3.5 bg-orange-50/60 font-black text-orange-600 border-l-2 border-orange-200">
                                {{ number_format($row['total']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($sizes) + 2 }}" class="p-8 text-center text-slate-400">
                                Belum ada transaksi lunas untuk kategori lomba ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-900 text-white font-black text-sm border-t-2 border-slate-800">
                        <td class="p-4 text-left border-r border-slate-800">
                            TOTAL KESELURUHAN
                        </td>
                        @foreach($sizes as $s)
                            <td class="p-4 border-r border-slate-800 font-black text-amber-400">
                                {{ number_format($sizeTotals[$s->size_code] ?? 0) }}
                            </td>
                        @endforeach
                        <td class="p-4 bg-orange-600 text-white font-black text-base border-l border-orange-700">
                            {{ number_format($overallTotal) }} pcs
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- PO Guidance Notes -->
    <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 leading-relaxed">
        <strong>Catatan untuk Pabrik / Vendor Konveksi:</strong>
        <ul class="list-disc pl-5 mt-1 space-y-0.5">
            <li>Sesuai ketentuan, <strong>tidak ada cetak nama pelari pada jersey</strong> (nama pelari hanya dicetak pada nomor dada BIB).</li>
            <li>Jika ada pembagian batch produksi, gunakan tombol <em>Download Rekap Pabrik (CSV)</em> untuk memotong per tanggal pemesanan.</li>
        </ul>
    </div>
</div>
@endsection
