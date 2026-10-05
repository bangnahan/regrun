<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveEventDomain
{
    /**
     * Handle an incoming request and resolve event context based on domain or slug.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't override if already resolved via slug in route parameter
        $slug = $request->route('slug');
        $host = $request->getHost();

        $event = Event::getActiveEvent($slug, $host);

        if ($event) {
            // Bind to request attribute and app container
            $request->attributes->set('currentEvent', $event);
            app()->instance('currentEvent', $event);

            // Share with all Blade views automatically
            View::share('currentEvent', $event);
            View::share('brandPrimaryColor', $event->primary_color ?: '#ea580c');
            View::share('brandLogoUrl', $event->logo_url);
        }

        return $next($request);
    }
}
