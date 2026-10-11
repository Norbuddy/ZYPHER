<?php

namespace App\Providers;

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
        if ($this->app->environment('production') || env('FORCE_HTTPS', false) || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->ensurePrivateDocumentsSecured();
    }

    /**
     * Prevent direct public exposure of sensitive business permits and BIR documents.
     * Automatically migrates legacy public documents to protected storage and removes public folders.
     */
    protected function ensurePrivateDocumentsSecured(): void
    {
        // 1. Move legacy public/seller-permits to private storage and remove directory
        $publicSellerPermits = public_path('seller-permits');
        if (\Illuminate\Support\Facades\File::isDirectory($publicSellerPermits)) {
            $dest = storage_path('app/private/seller-permits');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($dest);
            foreach (\Illuminate\Support\Facades\File::files($publicSellerPermits) as $f) {
                $target = $dest . DIRECTORY_SEPARATOR . $f->getFilename();
                if (!\Illuminate\Support\Facades\File::exists($target)) {
                    \Illuminate\Support\Facades\File::copy($f->getPathname(), $target);
                }
            }
            \Illuminate\Support\Facades\File::deleteDirectory($publicSellerPermits);
        }

        // 2. Move legacy public/supplier-permits to private storage and remove directory
        $publicSupplierPermits = public_path('supplier-permits');
        if (\Illuminate\Support\Facades\File::isDirectory($publicSupplierPermits)) {
            $dest = storage_path('app/private/supplier-permits');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($dest);
            foreach (\Illuminate\Support\Facades\File::files($publicSupplierPermits) as $f) {
                $target = $dest . DIRECTORY_SEPARATOR . $f->getFilename();
                if (!\Illuminate\Support\Facades\File::exists($target)) {
                    \Illuminate\Support\Facades\File::copy($f->getPathname(), $target);
                }
            }
            \Illuminate\Support\Facades\File::deleteDirectory($publicSupplierPermits);
        }

        // 3. Move legacy storage/app/public/bir_documents to private storage and remove from public disk
        $publicBir = storage_path('app/public/bir_documents');
        if (\Illuminate\Support\Facades\File::isDirectory($publicBir)) {
            $dest = storage_path('app/private/bir_documents');
            \Illuminate\Support\Facades\File::ensureDirectoryExists($dest);
            foreach (\Illuminate\Support\Facades\File::files($publicBir) as $f) {
                $target = $dest . DIRECTORY_SEPARATOR . $f->getFilename();
                if (!\Illuminate\Support\Facades\File::exists($target)) {
                    \Illuminate\Support\Facades\File::copy($f->getPathname(), $target);
                }
            }
            \Illuminate\Support\Facades\File::deleteDirectory($publicBir);
        }
    }
}
