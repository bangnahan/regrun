@extends('layouts.platform', [
    'title' => 'Kebijakan Pengembalian Dana (Refund Policy) - Jelatix Ticketing',
    'metaDescription' => 'Kebijakan resmi pembatalan tiket dan pengembalian dana (refund policy) untuk pendaftaran event lari di platform Jelatix (jelatix.com) sesuai kepatuhan merchant Tripay.'
])

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white py-16 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <span class="inline-block px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-bold border border-orange-500/30 mb-3">
            KEBIJAKAN PEMBATALAN & PENGEMBALIAN DANA
        </span>
        <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Kebijakan Pengembalian Dana (Refund Policy)</h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">Berlaku untuk seluruh transaksi pemesanan tiket resmi di platform jelatix.com</p>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 lg:py-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm space-y-8 text-slate-700 text-sm leading-relaxed">
        
        <!-- Summary Box -->
        <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs leading-relaxed space-y-2">
            <div class="font-extrabold text-sm flex items-center gap-2">
                <span>⚠️</span>
                <span>Ringkasan Kebijakan Refund:</span>
            </div>
            <p>
                Sesuai dengan standar operasional industri event olahraga lari (*running event*), seluruh tiket yang telah berhasil dibeli dan dibayar berstatus <strong>NON-REFUNDABLE (TIDAK DAPAT DIBATALKAN ATAU DIUANGKAN KEMBALI)</strong> atas inisiatif sepihak peserta.
            </p>
            <p>
                Pengembalian dana hanya dapat diproses apabila <strong>Event Lari Dibatalkan Resmi Sepenuhnya oleh Pihak Penyelenggara</strong> atau dalam kondisi keadaan kahar (*force majeure*) yang diinstruksikan oleh otoritas pemerintah.
            </p>
        </div>

        <!-- 1. Alasan Non-Refundable -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">1.</span> Mengapa Tiket Event Lari Non-Refundable?
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>Ketika Anda menyelesaikan pendaftaran dan pembayaran tiket event lari di Jelatix:</p>
                <ul class="list-disc list-inside pl-2 space-y-1">
                    <li>Slot kuota pelari langsung dikunci dan mengurangi kapasitas maksimal rute yang diizinkan pihak berwenang.</li>
                    <li>Data ukuran jersey khusus Anda langsung diteruskan ke vendor konveksi untuk proses jahit dan sablon produksi massal.</li>
                    <li>Nomor BIB resmi dan chip pencatat waktu (*timing chip*) telah dipersonalisasi atas nama Anda.</li>
                    <li>Polis asuransi perlindungan pelari telah didaftarkan ke pihak penyedia asuransi event.</li>
                </ul>
                <p>Oleh karena itu, pembatalan sepihak karena ketidakhadiran, halangan mendadak, cedera pribadi, atau perubahan rencana pribadi pelari tidak dapat dilayani untuk pengembalian dana.</p>
            </div>
        </section>

        <!-- 2. Kondisi Refund Diterima -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">2.</span> Kondisi Pengembalian Dana yang Dapat Diterima
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>Pengembalian dana (refund) <strong>HANYA</strong> dapat diajukan dalam kondisi-kondisi berikut:</p>
                <div class="space-y-2 pl-2">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <strong class="text-slate-800">A. Pembatalan Penuh oleh Penyelenggara:</strong>
                        <p class="mt-1">Pihak Race Organizer secara resmi mengumumkan pembatalan total event lari tanpa adanya tanggal pengganti (reschedule).</p>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <strong class="text-slate-800">B. Pembatalan Akibat Keadaan Kahar (Force Majeure):</strong>
                        <p class="mt-1">Event dilarang diselenggarakan oleh pemerintah pusat/daerah akibat bencana alam besar, konflik sipil, atau kondisi darurat nasional dan panitia menetapkan kebijakan refund penuh.</p>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <strong class="text-slate-800">C. Kesalahan Teknis Sistem Pembayaran (Double Charge):</strong>
                        <p class="mt-1">Terjadi pemotongan ganda (*duplicate payment*) pada satu transaksi invoice yang sama akibat kendala jaringan Tripay atau perbankan.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Prosedur & Jangka Waktu -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">3.</span> Mekanisme & Prosedur Pengajuan Refund
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>3.1. Apabila event resmi dibatalkan, panitia dan Jelatix akan mengirimkan formulir klaim pengembalian dana resmi ke alamat email pemesan tiket.</p>
                <p>3.2. Pemesan wajib mengisi nomor invoice, bukti pembayaran, nama bank, nomor rekening, dan nama pemilik rekening yang sesuai dengan identitas pembeli.</p>
                <p>3.3. <strong>Jangka Waktu Pemrosesan:</strong> Proses verifikasi dan transfer pengembalian dana berlangsung dalam waktu <strong>14 hingga 30 hari kerja</strong> terhitung sejak batas akhir pengumpulan data formulir refund ditutup.</p>
                <p>3.4. <strong>Nominal yang Dikembalikan:</strong> Pengembalian dana mencakup 100% dari harga pokok tiket lomba yang dibayarkan. Biaya administrasi payment gateway pihak ketiga (seperti biaya fee QRIS / Virtual Account) bersifat non-refundable karena biaya pemrosesan teknologi telah diselesaikan oleh pihak switching perbankan.</p>
            </div>
        </section>

        <!-- 4. Penjadwalan Ulang (Reschedule) -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">4.</span> Kebijakan Penjadwalan Ulang (Rescheduled Event)
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>4.1. Jika event lari ditunda (*postponed*) ke tanggal baru oleh Penyelenggara, tiket Anda secara otomatis tetap sah dan berlaku untuk tanggal pelaksanaan yang baru tanpa biaya tambahan.</p>
                <p>4.2. Apabila peserta tidak dapat mengikuti event di tanggal baru yang ditentukan, hak refund atau pengalihan tiket mengikuti ketentuan tertulis dari siaran pers resmi masing-masing panitia penyelenggara.</p>
            </div>
        </section>

        <!-- 5. Transfer Tiket / Ganti Nama BIB -->
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">5.</span> Kebijakan Pindah Nama (BIB Transfer)
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>Sebagai alternatif pembatalan, beberapa event lari menyediakan layanan <em>BIB Transfer</em> (pengalihan kepemilikan tiket kepada pelari lain). Kebijakan, biaya administrasi ganti nama, dan batas waktu pengalihan BIB ditentukan oleh masing-masing Penyelenggara Event dan diumumkan pada halaman detail event.</p>
            </div>
        </section>

        <!-- 6. Hubungi Tim Refund -->
        <section class="space-y-3 pt-4 border-t border-slate-100">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-orange-600">6.</span> Bantuan & Layanan Informasi Refund
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
                <p>Jika Anda memerlukan verifikasi kendala pembayaran ganda atau pertanyaan mengenai pembatalan event resmi, silakan hubungi tim kami:</p>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1 text-slate-700">
                    <div><strong>Customer Care Jelatix Ticketing</strong></div>
                    <div>Email: <a href="mailto:hi@jelatix.com" class="text-orange-600 font-bold underline">hi@jelatix.com</a> / <a href="mailto:support@jelatix.com" class="text-orange-600 font-bold underline">support@jelatix.com</a></div>
                    <div>WhatsApp CS: <a href="https://wa.me/6281916444458" class="text-orange-600 font-bold underline">0819-1644-4458</a></div>
                    <div class="text-[11px] text-slate-500 pt-1">Harap sertakan Nomor Invoice (contoh: <code>INV-20261005-XXXXX</code>) untuk penanganan lebih cepat.</div>
                </div>
            </div>
        </section>

        <!-- Navigation Buttons -->
        <div class="pt-6 border-t border-slate-100 flex flex-wrap gap-4 items-center justify-between">
            <a href="{{ route('page.terms') }}" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition flex items-center gap-1.5">
                &larr; Syarat & Ketentuan
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('page.privacy') }}" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition">
                    Kebijakan Privasi &rarr;
                </a>
                <a href="{{ route('register.index') }}" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white font-bold text-xs hover:bg-orange-700 transition shadow-sm">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
