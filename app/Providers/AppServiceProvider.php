<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        // Railway (and most PaaS hosts) terminate TLS at a proxy and forward
        // plain HTTP internally, so without this, url()/route() helpers would
        // generate http:// links even though the site is only ever reachable
        // over https:// from outside.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // The site is Bootstrap 5 throughout, but Laravel's pagination
        // default is Tailwind-styled SVG arrows. Without this, those SVGs
        // render with no matching CSS classes to size them, so they show
        // at their raw intrinsic size (huge, unstyled) instead of small
        // prev/next arrows. This fixes every ->links() call site-wide.
        Paginator::useBootstrapFive();

        $this->ensureStorageLinkExists();
    }

    /**
     * Railway (and most container hosts) rebuild the filesystem on every
     * deploy, so the `public/storage` symlink `php artisan storage:link`
     * creates never survives a redeploy unless that command runs again as
     * part of the deploy step. Without it, every uploaded vehicle photo
     * 404s (asset('storage/...') points at a link that doesn't exist),
     * which looks like "the photo I uploaded vanished". Recreating it here
     * on every boot costs one cheap file_exists() check and makes uploads
     * self-healing regardless of what the deploy pipeline does.
     */
    private function ensureStorageLinkExists(): void
    {
        $link = public_path('storage');

        if (! file_exists($link)) {
            @symlink(storage_path('app/public'), $link);
        }
    }
}
