<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\Participant;
use App\Models\SystemSetting;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Services\MailketingService;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    protected MailketingService $mailketingService;
    protected TripayService $tripayService;

    public function __construct(MailketingService $mailketingService, TripayService $tripayService)
    {
        $this->mailketingService = $mailketingService;
        $this->tripayService = $tripayService;
    }

    /**
     * Admin Dashboard Overview
     */
    public function dashboard(Request $request)
    {
        $eventId = $request->get('event_id');
        $events = Event::orderBy('race_date', 'desc')->get();

        $selectedEvent = $eventId ? Event::find($eventId) : Event::where('is_default', true)->first() ?? $events->first();

        $trxQuery = Transaction::query();
        $participantQuery = Participant::query();

        if ($selectedEvent) {
            $trxQuery->where('event_id', $selectedEvent->id);
            $participantQuery->whereHas('transaction', fn($q) => $q->where('event_id', $selectedEvent->id));
        }

        $totalRevenue = (clone $trxQuery)->where('status', 'PAID')->sum('subtotal');
        $paidTransactionsCount = (clone $trxQuery)->where('status', 'PAID')->count();
        $unpaidTransactionsCount = (clone $trxQuery)->where('status', 'UNPAID')->count();
        $expiredTransactionsCount = (clone $trxQuery)->where('status', 'EXPIRED')->count();

        $totalParticipants = (clone $participantQuery)->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))->count();

        // Ticket Categories quota & sales breakdown
        $categories = $selectedEvent ? $selectedEvent->ticketCategories : TicketCategory::all();

        // Recent Transactions
        $recentTransactions = (clone $trxQuery)->with('items.ticketCategory')->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'events',
            'selectedEvent',
            'totalRevenue',
            'paidTransactionsCount',
            'unpaidTransactionsCount',
            'expiredTransactionsCount',
            'totalParticipants',
            'categories',
            'recentTransactions'
        ));
    }

    /**
     * Modul Rekap Produksi Jersey Pabrik (Pivot Matrix XS - 5XL)
     */
    public function jerseyRecap(Request $request)
    {
        $eventId = $request->get('event_id');
        $events = Event::all();
        $selectedEvent = $eventId ? Event::find($eventId) : Event::where('is_default', true)->first() ?? $events->first();

        $sizes = JerseySize::orderBy('sort_order')->get();
        $categories = $selectedEvent ? $selectedEvent->ticketCategories : TicketCategory::all();

        // Build matrix data
        $matrix = [];
        $sizeTotals = array_fill_keys($sizes->pluck('size_code')->toArray(), 0);
        $overallTotal = 0;

        foreach ($categories as $cat) {
            $row = [
                'category_name' => $cat->name,
                'category_code' => $cat->code,
                'sizes' => [],
                'total' => 0,
            ];

            foreach ($sizes as $s) {
                $count = Participant::where('ticket_category_id', $cat->id)
                    ->where('jersey_size_id', $s->id)
                    ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
                    ->count();

                $row['sizes'][$s->size_code] = $count;
                $row['total'] += $count;
                $sizeTotals[$s->size_code] += $count;
                $overallTotal += $count;
            }

            $matrix[] = $row;
        }

        return view('admin.jersey_recap', compact('events', 'selectedEvent', 'sizes', 'matrix', 'sizeTotals', 'overallTotal'));
    }

    /**
     * Export Rekap Jersey Pabrik ke file CSV
     */
    public function exportJerseyCsv(Request $request): StreamedResponse
    {
        $eventId = $request->get('event_id');
        $event = Event::find($eventId) ?? Event::first();
        $sizes = JerseySize::orderBy('sort_order')->get();
        $categories = $event ? $event->ticketCategories : TicketCategory::all();

        $fileName = 'rekap_jersey_pabrik_' . ($event ? $event->slug : 'all') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($categories, $sizes) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Header
            $headers = ['Kategori Lomba'];
            foreach ($sizes as $s) {
                $headers[] = $s->size_code;
            }
            $headers[] = 'TOTAL JERSEY';
            fputcsv($handle, $headers, ',', '"', "\\");

            $sizeTotals = array_fill_keys($sizes->pluck('size_code')->toArray(), 0);
            $grandTotal = 0;

            foreach ($categories as $cat) {
                $row = [$cat->name];
                $rowTotal = 0;
                foreach ($sizes as $s) {
                    $count = Participant::where('ticket_category_id', $cat->id)
                        ->where('jersey_size_id', $s->id)
                        ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
                        ->count();

                    $row[] = $count;
                    $rowTotal += $count;
                    $sizeTotals[$s->size_code] += $count;
                    $grandTotal += $count;
                }
                $row[] = $rowTotal;
                fputcsv($handle, $row, ',', '"', "\\");
            }

            // Footer row
            $footer = ['TOTAL KESELURUHAN'];
            foreach ($sizes as $s) {
                $footer[] = $sizeTotals[$s->size_code];
            }
            $footer[] = $grandTotal;
            fputcsv($handle, $footer, ',', '"', "\\");

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Transactions List
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['event', 'items.ticketCategory', 'participants'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhere('buyer_name', 'like', "%{$s}%")
                    ->orWhere('buyer_email', 'like', "%{$s}%")
                    ->orWhere('buyer_phone', 'like', "%{$s}%")
                    ->orWhere('tripay_reference', 'like', "%{$s}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $events = Event::all();

        return view('admin.transactions', compact('transactions', 'events'));
    }

    /**
     * Mark Transaction as PAID manually (by Admin)
     */
    public function markAsPaid(string $invoice)
    {
        $transaction = Transaction::where('invoice_number', $invoice)->firstOrFail();

        if ($transaction->status !== 'PAID') {
            $transaction->update([
                'status' => 'PAID',
                'paid_at' => now(),
            ]);

            foreach ($transaction->items as $item) {
                $cat = $item->ticketCategory;
                $cat->decrement('reserved_count', $item->quantity);
                $cat->increment('sold_count', $item->quantity);
            }

            foreach ($transaction->participants as $p) {
                if (!$p->bib_number) {
                    $codePrefix = substr($p->ticketCategory->code, 0, 2);
                    $p->update(['bib_number' => $codePrefix . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT)]);
                }
            }

            $this->mailketingService->sendInvoiceEmail($transaction);
            foreach ($transaction->participants as $p) {
                $this->mailketingService->sendTicketEmail($p);
            }
        }

        return back()->with('success', "Transaksi {$invoice} berhasil diubah menjadi PAID dan email telah dikirim.");
    }

    /**
     * Resend E-Tickets and Invoice via Mailketing
     */
    public function resendEmail(string $invoice)
    {
        $transaction = Transaction::where('invoice_number', $invoice)->firstOrFail();

        $this->mailketingService->sendInvoiceEmail($transaction);
        foreach ($transaction->participants as $p) {
            $this->mailketingService->sendTicketEmail($p);
        }

        return back()->with('success', "Email invoice dan e-ticket untuk {$invoice} berhasil dikirim ulang ke Mailketing.");
    }

    /**
     * Participants Master List
     */
    public function participants(Request $request)
    {
        $query = Participant::with(['transaction.event', 'ticketCategory', 'jerseySize'])->latest();

        if ($request->filled('event_id')) {
            $query->whereHas('transaction', fn($q) => $q->where('event_id', $request->event_id));
        }

        if ($request->filled('category_id')) {
            $query->where('ticket_category_id', $request->category_id);
        }

        if ($request->filled('jersey_size_id')) {
            $query->where('jersey_size_id', $request->jersey_size_id);
        }

        if ($request->filled('rpc_status')) {
            $query->where('is_racepack_collected', $request->rpc_status === '1');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('ticket_code', 'like', "%{$s}%")
                    ->orWhere('qr_code_hash', 'like', "%{$s}%")
                    ->orWhere('bib_number', 'like', "%{$s}%")
                    ->orWhere('bib_name', 'like', "%{$s}%")
                    ->orWhere('identity_number', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }

        $participants = $query->paginate(25)->withQueryString();
        $events = Event::all();
        $categories = TicketCategory::all();
        $jerseySizes = JerseySize::orderBy('sort_order')->get();

        return view('admin.participants', compact('participants', 'events', 'categories', 'jerseySizes'));
    }

    /**
     * Toggle Racepack Collection status
     */
    public function toggleRacepack(int $participantId)
    {
        $participant = Participant::findOrFail($participantId);
        $newStatus = !$participant->is_racepack_collected;

        $participant->update([
            'is_racepack_collected' => $newStatus,
            'racepack_collected_at' => $newStatus ? now() : null,
            'racepack_collected_by' => $newStatus ? Auth::id() : null,
        ]);

        return back()->with('success', "Status pengambilan racepack untuk {$participant->full_name} berhasil diperbarui.");
    }

    /**
     * Export Participants CSV
     */
    public function exportParticipantsCsv(Request $request): StreamedResponse
    {
        $query = Participant::with(['transaction.event', 'ticketCategory', 'jerseySize'])
            ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
            ->latest();

        if ($request->filled('event_id')) {
            $query->whereHas('transaction', fn($q) => $q->where('event_id', $request->event_id));
        }

        $participants = $query->get();
        $fileName = 'data_pelari_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($participants) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No Tiket',
                'No BIB',
                'Nama di BIB',
                'Nama Lengkap',
                'NIK / Paspor',
                'Gender',
                'Tgl Lahir',
                'Kategori',
                'Ukuran Jersey',
                'Golongan Darah',
                'WhatsApp',
                'Email',
                'Kontak Darurat',
                'No HP Darurat',
                'Hubungan Darurat',
                'Catatan Medis',
                'Komunitas',
                'Status RPC',
            ], ',', '"', "\\");

            foreach ($participants as $p) {
                fputcsv($handle, [
                    $p->ticket_code,
                    $p->bib_number ?? '-',
                    $p->bib_name,
                    $p->full_name,
                    $p->identity_number,
                    $p->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                    $p->date_of_birth ? $p->date_of_birth->format('Y-m-d') : '',
                    $p->ticketCategory->name ?? '',
                    $p->jerseySize->size_code ?? '',
                    $p->blood_type,
                    $p->phone_number,
                    $p->email,
                    $p->emergency_contact_name,
                    $p->emergency_contact_phone,
                    $p->emergency_contact_relation,
                    $p->medical_notes ?? '-',
                    $p->running_club ?? '-',
                    $p->is_racepack_collected ? 'Sudah Diambil' : 'Belum Diambil',
                ], ',', '"', "\\");
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Manage Events & Quota / Early Bird
     */
    public function events()
    {
        $events = Event::with('ticketCategories')
            ->withCount(['transactions', 'participants'])
            ->orderByDesc('is_default')
            ->latest('race_date')
            ->get();

        return view('admin.events', compact('events'));
    }

    /**
     * Create New Event
     */
    public function storeEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'required|string|max:100|alpha_dash|unique:events,slug',
            'race_date' => 'required|date',
            'race_start_time' => 'required|string',
            'venue_name' => 'required|string|max:200',
            'venue_address' => 'nullable|string',
            'rpc_start_date' => 'nullable|date',
            'rpc_end_date' => 'nullable|date',
            'rpc_location' => 'nullable|string',
            'custom_domain' => 'nullable|string|max:150|unique:events,custom_domain',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Event::query()->update(['is_default' => false]);
        }

        $event = Event::create([
            'title' => $request->title,
            'slug' => Str::slug($request->slug),
            'race_date' => $request->race_date,
            'race_start_time' => $request->race_start_time,
            'venue_name' => $request->venue_name,
            'venue_address' => $request->venue_address,
            'rpc_start_date' => $request->rpc_start_date,
            'rpc_end_date' => $request->rpc_end_date,
            'rpc_location' => $request->rpc_location,
            'custom_domain' => $request->custom_domain ? trim(str_replace(['http://', 'https://'], '', $request->custom_domain), '/') : null,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $isDefault,
        ]);

        // Auto-seed starter categories if requested
        if ($request->boolean('seed_default_categories')) {
            $categories = [
                [
                    'name' => '5K Fun Run',
                    'code' => '5K',
                    'description' => 'Kategori 5K Fun Run untuk pelari pemula & keluarga.',
                    'price' => 175000,
                    'early_bird_price' => 145000,
                    'early_bird_end_date' => now()->addDays(20),
                    'quota' => 500,
                    'min_age' => 10,
                    'sort_order' => 1,
                ],
                [
                    'name' => '10K Open Category',
                    'code' => '10K',
                    'description' => 'Kategori 10K untuk pelari umum & kompetitif.',
                    'price' => 275000,
                    'early_bird_price' => 225000,
                    'early_bird_end_date' => now()->addDays(20),
                    'quota' => 350,
                    'min_age' => 14,
                    'sort_order' => 2,
                ],
            ];
            foreach ($categories as $cat) {
                $event->ticketCategories()->create($cat);
            }
        }

        return back()->with('success', "Event '{$event->title}' berhasil dibuat.");
    }

    /**
     * Update Event Details
     */
    public function updateEvent(Request $request, int $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:200',
            'slug' => "required|string|max:100|alpha_dash|unique:events,slug,{$id}",
            'race_date' => 'required|date',
            'race_start_time' => 'required|string',
            'venue_name' => 'required|string|max:200',
            'venue_address' => 'nullable|string',
            'rpc_start_date' => 'nullable|date',
            'rpc_end_date' => 'nullable|date',
            'rpc_location' => 'nullable|string',
            'custom_domain' => "nullable|string|max:150|unique:events,custom_domain,{$id}",
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            Event::where('id', '!=', $id)->update(['is_default' => false]);
        }

        $event->update([
            'title' => $request->title,
            'slug' => Str::slug($request->slug),
            'race_date' => $request->race_date,
            'race_start_time' => $request->race_start_time,
            'venue_name' => $request->venue_name,
            'venue_address' => $request->venue_address,
            'rpc_start_date' => $request->rpc_start_date,
            'rpc_end_date' => $request->rpc_end_date,
            'rpc_location' => $request->rpc_location,
            'custom_domain' => $request->custom_domain ? trim(str_replace(['http://', 'https://'], '', $request->custom_domain), '/') : null,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $isDefault,
        ]);

        return back()->with('success', "Pengaturan event '{$event->title}' berhasil diperbarui.");
    }

    /**
     * Set Event as Default
     */
    public function setDefaultEvent(int $id)
    {
        Event::query()->update(['is_default' => false]);
        $event = Event::findOrFail($id);
        $event->update(['is_default' => true]);

        return back()->with('success', "'{$event->title}' berhasil ditetapkan sebagai Event Utama (Default).");
    }

    /**
     * Delete Event
     */
    public function deleteEvent(int $id)
    {
        $event = Event::withCount('transactions')->findOrFail($id);

        if ($event->transactions_count > 0) {
            return back()->with('error', "Event '{$event->title}' tidak dapat dihapus karena sudah memiliki {$event->transactions_count} transaksi. Anda dapat mengubah status event menjadi Nonaktif.");
        }

        $eventTitle = $event->title;
        $event->delete();

        return back()->with('success', "Event '{$eventTitle}' berhasil dihapus.");
    }

    /**
     * Add Ticket Category to Event
     */
    public function storeCategory(Request $request, int $eventId)
    {
        $event = Event::findOrFail($eventId);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'quota' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'early_bird_price' => 'nullable|numeric|min:0',
            'early_bird_end_date' => 'nullable|date',
            'min_age' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $event->ticketCategories()->create([
            'name' => $request->name,
            'code' => strtoupper(trim($request->code)),
            'quota' => $request->quota,
            'price' => $request->price,
            'early_bird_price' => $request->early_bird_price,
            'early_bird_end_date' => $request->early_bird_end_date,
            'min_age' => $request->input('min_age', 12),
            'description' => $request->description,
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori tiket '{$request->name}' berhasil ditambahkan ke {$event->title}.");
    }

    /**
     * Update Category Quota and Early Bird
     */
    public function updateCategory(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'quota' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'early_bird_price' => 'nullable|numeric|min:0',
            'early_bird_end_date' => 'nullable|date',
            'min_age' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $category = TicketCategory::findOrFail($id);
        $category->update([
            'name' => $request->name,
            'code' => strtoupper(trim($request->code)),
            'quota' => $request->quota,
            'price' => $request->price,
            'early_bird_price' => $request->early_bird_price,
            'early_bird_end_date' => $request->early_bird_end_date,
            'min_age' => $request->input('min_age', $category->min_age),
            'is_active' => (bool) $request->is_active,
        ]);

        return back()->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    /**
     * Delete Ticket Category
     */
    public function deleteCategory(int $id)
    {
        $category = TicketCategory::findOrFail($id);

        if ($category->sold_count > 0 || $category->reserved_count > 0) {
            return back()->with('error', "Kategori '{$category->name}' tidak dapat dihapus karena sudah ada tiket terjual/dipesan. Anda dapat menonaktifkan status penjualan.");
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }

    /**
     * Settings Page (Tripay & Mailketing)
     */
    public function settings()
    {
        $settings = [
            'tripay_merchant_code' => SystemSetting::get('tripay_merchant_code', env('TRIPAY_MERCHANT_CODE', 'T39430')),
            'tripay_api_key' => SystemSetting::get('tripay_api_key', env('TRIPAY_API_KEY', '')),
            'tripay_private_key' => SystemSetting::get('tripay_private_key', env('TRIPAY_PRIVATE_KEY', '')),
            'tripay_sandbox' => SystemSetting::get('tripay_sandbox', env('TRIPAY_SANDBOX', true)),
            'mailketing_api_token' => SystemSetting::get('mailketing_api_token', env('MAILKETING_API_TOKEN', '')),
            'mailketing_sender_email' => SystemSetting::get('mailketing_sender_email', env('MAILKETING_SENDER_EMAIL', 'hi@jelatix.com')),
            'mailketing_sender_name' => SystemSetting::get('mailketing_sender_name', env('MAILKETING_SENDER_NAME', 'Panitia Event Lari')),
        ];

        $tripayStatus = $this->tripayService->testConnection();
        $mailketingStatus = $this->mailketingService->testConnection();

        return view('admin.settings', compact('settings', 'tripayStatus', 'mailketingStatus'));
    }

    /**
     * Test send email via Mailketing
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'recipient' => 'required|email|max:150',
        ]);

        $res = $this->mailketingService->testConnection($request->recipient);

        if (!empty($res['connected'])) {
            return back()->with('success', "Email uji coba berhasil dikirim ke {$request->recipient} melalui Mailketing API.");
        }

        return back()->with('error', "Gagal mengirim email uji coba: " . ($res['error'] ?? 'Terjadi kesalahan pada server Mailketing.'));
    }

    /**
     * Save Settings
     */
    public function saveSettings(Request $request)
    {
        $keys = [
            'tripay_merchant_code',
            'tripay_api_key',
            'tripay_private_key',
            'tripay_sandbox',
            'mailketing_api_token',
            'mailketing_sender_email',
            'mailketing_sender_name',
        ];

        foreach ($keys as $k) {
            if ($request->has($k)) {
                $group = str_starts_with($k, 'tripay') ? 'tripay' : 'mailketing';
                SystemSetting::set($k, $request->input($k), $group);
            }
        }

        // Invalidate payment channel cache so changes apply immediately
        cache()->flush();

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
