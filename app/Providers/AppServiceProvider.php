<?php

namespace App\Providers;

use App\Models\CatalogItem;
use App\Models\InventoryStock;
use App\Models\SaleInstallment;
use App\Policies\CollectionPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(SaleInstallment::class, CollectionPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        CatalogItem::saved(function (CatalogItem $item): void {
            if ($item->controla_stock) {
                InventoryStock::firstOrCreate(['catalog_item_id' => $item->id]);
            }
        });
    }
}
