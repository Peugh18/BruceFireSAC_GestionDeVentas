<?php

namespace App\Providers;

use App\Models\CatalogItem;
use App\Models\InventoryStock;
use App\Policies\InventoryPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(InventoryStock::class, InventoryPolicy::class);

        CatalogItem::saved(function (CatalogItem $item): void {
            if ($item->controla_stock) {
                InventoryStock::firstOrCreate(['catalog_item_id' => $item->id]);
            }
        });
    }
}
