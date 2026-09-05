<?php

namespace App\Providers;

use App\Models\Event;
use App\Support\SiteText;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // @site('hero.headline') prints admin-edited Creators Hub copy, falling back to the
        // wording the page ships with. Escaped like any other Blade echo.
        Blade::directive('site', function (string $expression) {
            return "<?php echo e(\App\Support\SiteText::forExpression({$expression})); ?>";
        });

        // The admin menu lists every event, on every admin page.
        View::composer('admin.partials.sidebar', function ($view) {
            $view->with('sidebarEvents', Event::orderByDesc('start_date')->get(['id', 'slug', 'name_ar', 'name_en']));
        });

        // Content is cached for the life of a request; a queue worker or test that renders
        // more than once must not reuse a previous request's copy.
        SiteText::flush();
    }
}
