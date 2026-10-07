<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventDomain;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Homepage: If on a dedicated event domain, delegate to event ticket wizard.
     * Otherwise, render Jelatix Platform Homepage with all active events.
     */
    public function home(Request $request)
    {
        $host = $request->getHost();
        $cleanDomain = EventDomain::normalizeDomain($host);

        // Check if the current domain is a dedicated event domain (not the main platform domain)
        $isDedicatedDomain = false;
        if (!in_array($cleanDomain, ['jelatix.com', 'www.jelatix.com', '127.0.0.1', 'localhost', 'regrun.test', 'portal.regrun.test'])) {
            $matchedEvent = Event::getActiveEvent(null, $cleanDomain);
            if ($matchedEvent && $matchedEvent->is_active) {
                $isDedicatedDomain = true;
            }
        }

        if ($isDedicatedDomain) {
            return app(RegistrationController::class)->index($request);
        }

        // Load all active running events with active categories
        $events = Event::where('is_active', true)
            ->with(['ticketCategories' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderByDesc('is_default')
            ->orderBy('race_date')
            ->get();

        return view('pages.home', compact('events'));
    }

    /**
     * Syarat & Ketentuan (Terms & Conditions)
     */
    public function terms()
    {
        return view('pages.terms');
    }

    /**
     * Kebijakan Privasi (Privacy Policy)
     */
    public function privacy()
    {
        return view('pages.privacy');
    }

    /**
     * Kebijakan Pengembalian Dana (Refund Policy)
     */
    public function refund()
    {
        return view('pages.refund');
    }

    /**
     * Kontak Kami (Contact Us)
     */
    public function contact()
    {
        return view('pages.contact');
    }
}
