@extends('layouts.admin', ['title' => 'Master Data Pelari - RegRun'])

@section('page_title')
    Master Data Pelari &amp; Racepack Collection
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.participants') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-600 mb-1">Cari Nama / NIK / BIB / Tiket</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">Ukuran Jersey</label>
                <select name="jersey_size_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="">Semua Ukuran (XS - 5XL)</option>
                    @foreach($jerseySizes as $s)
                        <option value="{{ $s->id }}" {{ request('jersey_size_id') == $s->id ? 'selected' : '' }}>
                            Size {{ $s->size_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-600 mb-1">Status Racepack (RPC)</label>
                <select name="rpc_status" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('rpc_status') === '1' ? 'selected' : '' }}>Sudah Diambil</option>
                    <option value="0" {{ request('rpc_status') === '0' ? 'selected' : '' }}>Belum Diambil</option>
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
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold transition">
                    Filter
                </button>
                <a href="{{ route('admin.participants.export', ['event_id' => request('event_id')]) }}" class="py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition flex items-center gap-1 shadow-sm">
                    <span>📥</span> CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Alert Banner: Unassigned BIBs -->
    @if(!empty($unassignedBibCount) && $unassignedBibCount > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-2xl shrink-0">⏳</span>
                <div>
                    <h4 class="font-extrabold text-amber-900 text-sm">Terdapat {{ $unassignedBibCount }} Pelari yang Belum Memiliki Nomor BIB</h4>
                    <p class="text-xs text-amber-700 mt-0.5">Peserta mendaftar saat mode "Tunda/Manual BIB" aktif. Anda dapat men-generate nomor BIB secara otomatis sekarang atau mengisinya secara manual.</p>
                </div>
            </div>
            <form action="{{ route('admin.participants.generate_bibs') }}" method="POST">
                @csrf
                @if(request('event_id'))
                    <input type="hidden" name="event_id" value="{{ request('event_id') }}">
                @endif
                <button type="submit" 
                        onclick="return confirm('Alokasikan nomor BIB otomatis untuk {{ $unassignedBibCount }} peserta ini?')" 
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-600/20 transition flex items-center gap-1.5 shrink-0">
                    <span>⚡</span>
                    <span>Generate Nomor BIB Sekarang</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-4">No. BIB / Tiket</th>
                        <th class="p-4">Nama Pelari (KTP)</th>
                        <th class="p-4">Nama di BIB</th>
                        <th class="p-4">Kategori Lari</th>
                        <th class="p-4 text-center">Jersey</th>
                        <th class="p-4">Gol. Darah</th>
                        <th class="p-4">Kontak Darurat</th>
                        <th class="p-4 text-center">Racepack (RPC)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($participants as $p)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-4" x-data="{ editingBib: false, bibInput: '{{ $p->bib_number }}' }">
                                <div x-show="!editingBib" class="flex items-center gap-1.5">
                                    @if($p->bib_number)
                                        <span class="font-mono font-black text-sm text-slate-900">{{ $p->bib_number }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">
                                            Belum Ada BIB
                                        </span>
                                    @endif
                                    <button type="button" 
                                            @click="editingBib = true" 
                                            class="text-slate-400 hover:text-slate-600 text-xs transition" 
                                            title="Edit Nomor BIB">
                                        ✏️
                                    </button>
                                </div>

                                <!-- Inline edit form -->
                                <form x-show="editingBib" 
                                      action="{{ route('admin.participants.update_bib', ['id' => $p->id]) }}" 
                                      method="POST" 
                                      class="flex items-center gap-1 mt-1">
                                    @csrf
                                    <input type="text" 
                                           name="bib_number" 
                                           x-model="bibInput" 
                                           placeholder="No BIB..." 
                                           class="w-24 px-2 py-1 text-xs font-mono font-bold rounded border border-slate-300 focus:outline-none focus:ring-1 focus:ring-orange-500">
                                    <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold rounded">
                                        ✓
                                    </button>
                                    <button type="button" @click="editingBib = false" class="px-1.5 py-1 bg-slate-200 text-slate-600 text-[10px] rounded">
                                        ✕
                                    </button>
                                </form>

                                <div class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $p->ticket_code }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $p->full_name }}</div>
                                <div class="text-slate-500 text-[11px] font-mono">NIK: {{ $p->identity_number }}</div>
                                <div class="text-slate-400 text-[10px]">{{ $p->phone_number }} | {{ $p->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded bg-orange-50 text-orange-800 font-extrabold font-mono text-xs border border-orange-200 uppercase tracking-wider">
                                    {{ $p->bib_name }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-slate-800">{{ $p->ticketCategory->name }}</span>
                                <div class="text-[10px] text-slate-400">{{ $p->transaction->event->title ?? '' }}</div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-extrabold text-xs">
                                    {{ $p->jerseySize->size_code }}
                                </span>
                            </td>
                            <td class="p-4 font-bold text-slate-700">
                                {{ $p->blood_type }}
                            </td>
                            <td class="p-4 text-[11px]">
                                <div class="font-bold text-slate-800">{{ $p->emergency_contact_name }} ({{ $p->emergency_contact_relation }})</div>
                                <div class="text-slate-500">{{ $p->emergency_contact_phone }}</div>
                            </td>
                            <td class="p-4 text-center">
                                <form action="{{ route('admin.participants.toggle_rpc', ['id' => $p->id]) }}" method="POST">
                                    @csrf
                                    @if($p->is_racepack_collected)
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold text-[11px] border border-emerald-300 transition flex items-center justify-center gap-1 mx-auto">
                                            <span>✓</span> Sudah Diambil
                                        </button>
                                        <div class="text-[9px] text-slate-400 mt-1">{{ $p->racepack_collected_at ? $p->racepack_collected_at->format('d/m H:i') : '' }}</div>
                                    @else
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-[11px] border border-slate-300 transition mx-auto">
                                            Belum Diambil
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">Tidak ada data pelari yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $participants->links() }}
        </div>
    </div>
</div>
@endsection
