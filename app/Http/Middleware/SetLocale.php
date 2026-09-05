<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang', $request->session()->get('locale', 'ar'));
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
