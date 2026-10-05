@extends('layouts.admin', ['title' => 'Event & Kuota Tiket - RegRun'])

@section('page_title')
    Event &amp; Kuota Tiket
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Manajemen Kuota &amp; Harga Early Bird</h2>
            <p class="text-xs text-slate-500 mt-0.5">Atur kuota masing-masing kategori lomba dan aktifkan promo harga Early Bird.</p>
        </div>
    </div>

    @foreach($events as $ev)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-black text-slate-900">{{ $ev->title }}</h3>
                        @if($ev->is_default)
                            <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-bold">Default Event</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        Tanggal: <strong>{{ $ev->race_date ? $ev->race_date->format('d F Y') : '-' }}</strong> | Lokasi: <strong>{{ $ev->venue_name }}</strong>
                    </div>
                </div>

                <a href="{{ route('register.event', ['slug' => $ev->slug]) }}" target="_blank" class="text-xs font-semibold text-orange-600 hover:underline">
                    Buka Halaman Registrasi ↗
                </a>
            </div>

            <!-- Categories Grid / Edit Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach($ev->ticketCategories as $cat)
                    <div class="p-5 rounded-xl border border-slate-200 bg-slate-50/60 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono font-bold text-xs">{{ $cat->code }}</span>
                            <span class="text-xs font-bold text-slate-700">{{ $cat->name }}</span>
                        </div>

                        <form action="{{ route('admin.category.update', ['id' => $cat->id]) }}" method="POST" class="space-y-3 text-xs">
                            @csrf

                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Total Kuota Tiket</label>
                                <input type="number" name="quota" value="{{ $cat->quota }}" required min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-bold text-slate-900">
                                <div class="text-[10px] text-slate-400 mt-0.5">Terjual: {{ $cat->sold_count }} | Sisa: {{ $cat->remaining_quota }}</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Harga Reguler (Rp)</label>
                                <input type="number" name="price" value="{{ (int)$cat->price }}" required min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-bold text-slate-900">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Harga Early Bird (Rp)</label>
                                <input type="number" name="early_bird_price" value="{{ (int)$cat->early_bird_price }}" min="0" placeholder="Kosongkan jika tidak ada" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-bold text-amber-700">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Batas Waktu Early Bird</label>
                                <input type="datetime-local" name="early_bird_end_date" value="{{ $cat->early_bird_end_date ? $cat->early_bird_end_date->format('Y-m-d\TH:i') : '' }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Status Penjualan</label>
                                <select name="is_active" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs font-semibold">
                                    <option value="1" {{ $cat->is_active ? 'selected' : '' }}>Aktif Dibuka</option>
                                    <option value="0" {{ !$cat->is_active ? 'selected' : '' }}>Nonaktif / Ditutup</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full mt-2 py-2 px-3 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold transition shadow-sm">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
