@extends('layouts.app', ['title' => $event->title . ' - Data Peserta'])

@section('content')
<div x-data="{
    copyEmergencyContact() {
        let firstContactName = document.querySelector('input[name=\'participants[0][emergency_contact_name]\']')?.value;
        let firstContactPhone = document.querySelector('input[name=\'participants[0][emergency_contact_phone]\']')?.value;
        let firstContactRelation = document.querySelector('select[name=\'participants[0][emergency_contact_relation]\']')?.value;

        if (!firstContactName || !firstContactPhone) {
            alert('Mohon isi kontak darurat pada Peserta 1 terlebih dahulu.');
            return;
        }

        for (let i = 1; i < {{ $totalQuantity }}; i++) {
            let nameInput = document.querySelector(`input[name='participants[${i}][emergency_contact_name]']`);
            let phoneInput = document.querySelector(`input[name='participants[${i}][emergency_contact_phone]']`);
            let relSelect = document.querySelector(`select[name='participants[${i}][emergency_contact_relation]']`);

            if (nameInput) nameInput.value = firstContactName;
            if (phoneInput) phoneInput.value = firstContactPhone;
            if (relSelect && firstContactRelation) relSelect.value = firstContactRelation;
        }
        alert('Kontak darurat Peserta 1 berhasil disalin ke seluruh peserta!');
    }
}">
    <!-- Stepper Navigation -->
    <div class="flex items-center justify-between mb-8 max-w-xl mx-auto text-xs font-semibold">
        <a href="{{ route('register.index') }}" class="flex items-center gap-2 text-emerald-600">
            <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">&check;</span>
            <span>Pilih Tiket</span>
        </a>
        <div class="h-0.5 flex-1 bg-orange-600 mx-3"></div>
        <div class="flex items-center gap-2 text-orange-600">
            <span class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">2</span>
            <span>Data Peserta</span>
        </div>
        <div class="h-0.5 flex-1 bg-slate-200 mx-3"></div>
        <div class="flex items-center gap-2 text-slate-400">
            <span class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold">3</span>
            <span>Pembayaran</span>
        </div>
    </div>

    <!-- Header & Quick Copy -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Pengisian Data Peserta Lari</h2>
            <p class="text-xs text-slate-500">Silakan lengkapi data pelari untuk {{ $totalQuantity }} tiket yang Anda pilih.</p>
        </div>
        @if($totalQuantity > 1)
            <button type="button" @click="copyEmergencyContact()" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3.5 py-2 rounded-xl border border-slate-300 transition flex items-center gap-2">
                <span>📋</span> Salin Kontak Darurat Peserta 1 ke Semua
            </button>
        @endif
    </div>

    <form action="{{ route('register.step_checkout') }}" method="POST">
        @csrf

        @php $currentIndex = 0; @endphp
        @foreach($selectedTickets as $item)
            @for($i = 0; $i < $item['quantity']; $i++)
                @php $pNum = $currentIndex + 1; @endphp
                <div class="bg-white rounded-2xl p-6 border border-slate-200 mb-6 shadow-sm">
                    <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-orange-600 text-white font-black text-xs flex items-center justify-center shadow">
                                #{{ $pNum }}
                            </span>
                            <h3 class="font-extrabold text-slate-900 text-base">
                                Peserta {{ $pNum }} &mdash; <span class="text-orange-600">{{ $item['category']->name }}</span>
                            </h3>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-600">
                            {{ $item['category']->code }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Nama Lengkap (Sesuai KTP/Paspor) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="participants[{{ $currentIndex }}][full_name]" 
                                   required 
                                   placeholder="Contoh: Budi Santoso"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <!-- NIK / Passport -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Nomor Identitas (NIK KTP / Paspor) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="participants[{{ $currentIndex }}][identity_number]" 
                                   required 
                                   placeholder="16 digit NIK atau Nomor Paspor"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
                            <select name="participants[{{ $currentIndex }}][gender]" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm bg-white">
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Tanggal Lahir <span class="text-rose-500">*</span></label>
                            <input type="date" 
                                   name="participants[{{ $currentIndex }}][date_of_birth]" 
                                   required 
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm bg-white" />
                        </div>

                        <!-- No. WhatsApp -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                            <input type="tel" 
                                   name="participants[{{ $currentIndex }}][phone_number]" 
                                   required 
                                   placeholder="08123456789"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Email Peserta (Untuk E-Ticket) <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   name="participants[{{ $currentIndex }}][email]" 
                                   required 
                                   placeholder="peserta@email.com"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>

                        <!-- Ukuran Jersey (XS to 5XL) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-bold text-slate-700">Ukuran Jersey Event <span class="text-rose-500">*</span></label>
                                <span class="text-[11px] text-slate-400">Size chart di domain utama</span>
                            </div>
                            <select name="participants[{{ $currentIndex }}][jersey_size_id]" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm bg-white font-semibold">
                                <option value="">-- Pilih Ukuran Jersey (XS - 5XL) --</option>
                                @foreach($jerseySizes as $size)
                                    <option value="{{ $size->id }}">
                                        Size {{ $size->size_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Nama di BIB (Maks 12 Karakter) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-bold text-slate-700">Nama di BIB Dada (Maks 12 Huruf) <span class="text-rose-500">*</span></label>
                                <span class="text-[11px] text-amber-600 font-medium">Hanya dicetak di nomor BIB</span>
                            </div>
                            <input type="text" 
                                   name="participants[{{ $currentIndex }}][bib_name]" 
                                   maxlength="12" 
                                   required 
                                   placeholder="Contoh: BUDI RUN"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm uppercase tracking-wider font-semibold" />
                        </div>

                        <!-- Golongan Darah -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Golongan Darah (Keperluan Medis) <span class="text-rose-500">*</span></label>
                            <select name="participants[{{ $currentIndex }}][blood_type]" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm bg-white">
                                <option value="UNKNOWN">Tidak Tahu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>

                        <!-- Komunitas Lari (Opsional) -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Nama Komunitas / Klub Lari (Opsional)</label>
                            <input type="text" 
                                   name="participants[{{ $currentIndex }}][running_club]" 
                                   placeholder="Contoh: Jakarta Runners"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
                        </div>
                    </div>

                    <!-- Kontak Darurat Box -->
                    <div class="mt-4 pt-4 border-t border-slate-100 bg-slate-50 p-4 rounded-xl">
                        <div class="font-bold text-slate-800 text-xs mb-3 flex items-center gap-1.5">
                            <span class="text-rose-500">🚨</span> Kontak Darurat (Wajib Diisi - Bukan Nomor Sendiri)
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <label class="block text-slate-600 mb-1 font-medium">Nama Kontak <span class="text-rose-500">*</span></label>
                                <input type="text" 
                                       name="participants[{{ $currentIndex }}][emergency_contact_name]" 
                                       required 
                                       placeholder="Nama kerabat"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white" />
                            </div>
                            <div>
                                <label class="block text-slate-600 mb-1 font-medium">No. Telepon Kontak <span class="text-rose-500">*</span></label>
                                <input type="tel" 
                                       name="participants[{{ $currentIndex }}][emergency_contact_phone]" 
                                       required 
                                       placeholder="08xxxxxxxx"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white" />
                            </div>
                            <div>
                                <label class="block text-slate-600 mb-1 font-medium">Hubungan <span class="text-rose-500">*</span></label>
                                <select name="participants[{{ $currentIndex }}][emergency_contact_relation]" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                    <option value="Orang Tua">Orang Tua</option>
                                    <option value="Suami/Istri">Suami / Istri</option>
                                    <option value="Saudara Kandung">Saudara Kandung</option>
                                    <option value="Teman / Kerabat">Teman / Kerabat</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                @php $currentIndex++; @endphp
            @endfor
        @endforeach

        <div class="flex items-center justify-between gap-4 mt-8">
            <a href="{{ route('register.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 transition">
                &larr; Kembali Ubah Tiket
            </a>
            <button type="submit" class="px-7 py-3 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-sm shadow-lg shadow-orange-600/30 transition flex items-center gap-2">
                <span>Lanjut ke Pembayaran</span>
                <span>&rarr;</span>
            </button>
        </div>
    </form>
</div>
@endsection
