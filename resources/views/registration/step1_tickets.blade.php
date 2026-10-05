@extends('layouts.app', ['title' => $event->title . ' - Pendaftaran Tiket'])

@section('content')
<div x-data="{
    quantities: {
        @foreach($categories as $cat)
            '{{ $cat->id }}': 0,
        @endforeach
    },
    prices: {
        @foreach($categories as $cat)
            '{{ $cat->id }}': {{ $cat->current_price }},
        @endforeach
    },
    quotas: {
        @foreach($categories as $cat)
            '{{ $cat->id }}': {{ $cat->remaining_quota }},
        @endforeach
    },
    changeQty(id, delta) {
        let current = this.quantities[id] || 0;
        let next = current + delta;
        if (next < 0) next = 0;
        if (next > this.quotas[id]) next = this.quotas[id];
        this.quantities[id] = next;
    },
    get totalTickets() {
        return Object.values(this.quantities).reduce((a, b) => a + Number(b), 0);
    },
    get totalPrice() {
        let sum = 0;
        for (let id in this.quantities) {
            sum += (this.quantities[id] * this.prices[id]);
        }
        return sum;
    },
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
    }
}">
    <!-- Event Banner & Header -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-orange-950 text-white p-6 sm:p-8 rounded-2xl shadow-xl mb-8 border border-slate-700/50">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-500/30 text-orange-300 text-xs font-semibold mb-3">
            <span>🔴 REGISTRASI RESMI DIBUKA</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-2">{{ $event->title }}</h1>
        <p class="text-slate-300 text-sm mb-6 leading-relaxed max-w-2xl">{{ $event->description }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-slate-700/60 text-xs">
            <div class="flex items-center gap-2 text-slate-200">
                <span class="text-base">📅</span>
                <div>
                    <div class="text-slate-400 font-medium">Tanggal Race</div>
                    <div class="font-bold">{{ $event->race_date ? $event->race_date->format('d F Y') : '-' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2 text-slate-200">
                <span class="text-base">⏰</span>
                <div>
                    <div class="text-slate-400 font-medium">Flag-Off Lari</div>
                    <div class="font-bold">{{ substr($event->race_start_time, 0, 5) }} WIB</div>
                </div>
            </div>
            <div class="flex items-center gap-2 text-slate-200">
                <span class="text-base">📍</span>
                <div>
                    <div class="text-slate-400 font-medium">Lokasi Venue</div>
                    <div class="font-bold">{{ $event->venue_name }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stepper Navigation -->
    <div class="flex items-center justify-between mb-8 max-w-xl mx-auto text-xs font-semibold">
        <div class="flex items-center gap-2 text-orange-600">
            <span class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">1</span>
            <span>Pilih Tiket</span>
        </div>
        <div class="h-0.5 flex-1 bg-slate-200 mx-3"></div>
        <div class="flex items-center gap-2 text-slate-400">
            <span class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold">2</span>
            <span>Data Peserta</span>
        </div>
        <div class="h-0.5 flex-1 bg-slate-200 mx-3"></div>
        <div class="flex items-center gap-2 text-slate-400">
            <span class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold">3</span>
            <span>Pembayaran</span>
        </div>
    </div>

    <!-- Form Submission to Step 2 -->
    <form action="{{ route('register.step_participants') }}" method="POST">
        @csrf
        <input type="hidden" name="event_id" value="{{ $event->id }}">

        <div class="space-y-4 mb-10">
            @forelse($categories as $cat)
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 hover:border-orange-300 transition shadow-sm hover:shadow-md flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2.5 mb-2">
                            <span class="px-2.5 py-0.5 rounded-md bg-slate-900 text-white font-extrabold text-xs tracking-wider">{{ $cat->code }}</span>
                            <h3 class="font-bold text-slate-900 text-lg">{{ $cat->name }}</h3>
                            
                            @if($cat->is_early_bird_active)
                                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-300">
                                    🔥 Early Bird
                                </span>
                            @endif

                            @if($cat->remaining_quota > 0)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-200">
                                    Sisa {{ number_format($cat->remaining_quota) }} tiket
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200">
                                    Habis (Sold Out)
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-500 mb-3">{{ $cat->description }}</p>

                        <!-- Price Section -->
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-slate-900">
                                Rp {{ number_format($cat->current_price, 0, ',', '.') }}
                            </span>
                            @if($cat->is_early_bird_active)
                                <span class="text-xs text-slate-400 line-through">
                                    Rp {{ number_format($cat->price, 0, ',', '.') }}
                                </span>
                            @endif
                            <span class="text-xs text-slate-500">/ tiket</span>
                        </div>
                    </div>

                    <!-- Quantity Stepper -->
                    <div class="flex items-center justify-between md:justify-end gap-3 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                        @if($cat->remaining_quota > 0)
                            <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50 overflow-hidden shadow-inner">
                                <button type="button" @click="changeQty('{{ $cat->id }}', -1)" class="w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-200 active:bg-slate-300 font-black text-lg transition select-none">
                                    &minus;
                                </button>
                                <input type="number" 
                                       name="tickets[{{ $cat->id }}]" 
                                       x-model.number="quantities['{{ $cat->id }}']" 
                                       min="0" 
                                       :max="quotas['{{ $cat->id }}']" 
                                       class="w-14 text-center bg-white h-10 border-x border-slate-300 font-bold text-slate-900 text-sm focus:outline-none focus:bg-orange-50/50" />
                                <button type="button" @click="changeQty('{{ $cat->id }}', 1)" class="w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-200 active:bg-slate-300 font-black text-lg transition select-none">
                                    &plus;
                                </button>
                            </div>
                        @else
                            <button type="button" disabled class="px-4 py-2 rounded-xl bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed">
                                Kuota Habis
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-500 text-sm">
                    Belum ada kategori tiket yang dibuka saat ini.
                </div>
            @endforelse
        </div>

        <!-- Sticky Floating Bottom Summary -->
        <div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 p-4 z-40 shadow-2xl">
            <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
                <div>
                    <div class="text-xs text-slate-500">Total Pembelian:</div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-extrabold text-orange-600" x-text="formatRupiah(totalPrice)">Rp 0</span>
                        <span class="text-xs text-slate-500 font-medium" x-show="totalTickets > 0">
                            (<span x-text="totalTickets"></span> tiket)
                        </span>
                    </div>
                </div>

                <button type="submit" 
                        :disabled="totalTickets === 0" 
                        :class="totalTickets > 0 ? 'bg-orange-600 hover:bg-orange-500 text-white shadow-lg shadow-orange-600/30' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                        class="px-6 py-3 rounded-xl font-bold text-sm transition flex items-center gap-2">
                    <span>Isi Data Peserta</span>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
