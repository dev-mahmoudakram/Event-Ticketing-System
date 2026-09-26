<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Event;
use App\Support\SiteText;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // @site('hero.headline') prints admin-edited Creators Hub copy, falling back to the
        // wording the page ships with. Escaped like any other Blade echo.
        Blade::directive('site', function (string $expression) {
            return "<?php echo e(\App\Support\SiteText::forExpression({$expression})); ?>";
        });

        // @siteRichText is the deliberately-unescaped counterpart, for the handful of fields
        // declared 'type' => 'richtext' in SiteContentRegistry. It is safe specifically
        // because SiteContentController runs every such field through App\Support\RichText
        // before it is ever stored — this directive must never be pointed at a plain field.
        Blade::directive('siteRichText', function (string $expression) {
            return "<?php echo \App\Support\SiteText::forExpression({$expression}); ?>";
        });

        // The admin menu lists every event, on every admin page.
        View::composer('admin.partials.sidebar', function ($view) {
            $view->with('sidebarEvents', Event::orderByDesc('start_date')->get(['id', 'slug', 'name_ar', 'name_en']));
        });

        // Content is cached for the life of a request; a queue worker or test that renders
        // more than once must not reuse a previous request's copy.
        SiteText::flush();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('invitation-otp', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip().'|'.$request->route('token')));

        // Forms that email the address typed in, or store an upload. Unlimited, a script could
        // make this domain spam a third party or fill the disk.
        RateLimiter::for('public-forms', fn (Request $request) => [
            Limit::perMinute(5)->by('minute|'.$request->ip())->response($this->tooManyAttempts(...)),
            Limit::perHour(20)->by('hour|'.$request->ip())->response($this->tooManyAttempts(...)),
        ]);

        RateLimiter::for('light-forms', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())->response($this->tooManyAttempts(...)));
    }

    /**
     * The ticket form submits with fetch and shows the JSON message; the other forms are plain
     * posts, so they go back with what was typed and the message under the email field every
     * one of them already displays.
     *
     * @param  array<string, string>  $headers
     */
    private function tooManyAttempts(Request $request, array $headers): Response
    {
        $message = __('Too many attempts. Please wait a minute and try again.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return back()->withInput($request->except(['_token']))->withErrors(['email' => $message]);
    }
}
