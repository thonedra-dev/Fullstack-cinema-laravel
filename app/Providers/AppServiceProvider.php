<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /*
         * Share $bmUnreadNotifCount with every branch_manager.* view
         * so the dashboard notification badge always reflects the live count.
         *
         * Counts manager_notifications where is_read = false for the
         * currently authenticated branch manager (session key bm_manager_id).
         * Returns 0 when the manager is not logged in.
         */
        View::composer('branch_manager.*', function ($view) {
            $managerId = session('bm_manager_id');

            $count = $managerId
                ? DB::table('manager_notifications')
                    ->where('manager_id', (int) $managerId)
                    ->where('is_read', false)
                    ->count()
                : 0;

            // Cap display at 10, flag overflow separately
            $view->with('bmUnreadNotifCount',  $count);
            $view->with('bmUnreadNotifCapped', min($count, 10));
            $view->with('bmUnreadNotifOver',   $count > 10);
        });
    }
}