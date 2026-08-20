<?php

namespace App\Providers;

use App\Constants\Status;
use App\Lib\Searchable;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Builder::mixin(new Searchable);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {

        $viewShare['emptyMessage'] = 'Data not found';
        view()->share($viewShare);

        try {
            if (\Schema::hasTable('general_settings')) {
                view()->composer('admin.partials.sidenav', function ($view) {
                    $view->with([
                        'bannedUsersCount'           => User::banned()->count(),
                        'emailUnverifiedUsersCount'  => User::emailUnverified()->count(),
                        'mobileUnverifiedUsersCount' => User::mobileUnverified()->count(),
                        'kycUnverifiedUsersCount'    => User::kycUnverified()->count(),
                        'kycPendingUsersCount'       => User::kycPending()->count(),
                        'pendingTicketCount'         => SupportTicket::whereIN('status', [Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
                        'pendingDepositsCount'       => Deposit::pending()->count(),
                        'pendingWithdrawCount'       => Withdrawal::pending()->count(),
                        'updateAvailable'            => version_compare(gs('available_version'), systemDetails()['version'], '>') ? 'v' . gs('available_version') : false,
                    ]);
                });

                view()->composer('admin.partials.topnav', function ($view) {
                    $view->with([
                        'adminNotifications'     => AdminNotification::where('is_read', Status::NO)->with('user')->orderBy('id', 'desc')->take(10)->get(),
                        'adminNotificationCount' => AdminNotification::where('is_read', Status::NO)->count(),
                    ]);
                });

                view()->composer('partials.seo', function ($view) {
                    $seo = Frontend::where('data_keys', 'seo.data')->first();
                    $view->with([
                        'seo' => $seo ? $seo->data_values : $seo,
                    ]);
                });

                try {
                    // Railway handles SSL at the proxy level
                    // Only force scheme if the proxy tells us it's HTTPS
                    if (request()->header('X-Forwarded-Proto') === 'https') {
                        \URL::forceScheme('https');
                    }
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
            // Database may not exist during build/install
        }

        Paginator::useBootstrapFive();
    }
}
