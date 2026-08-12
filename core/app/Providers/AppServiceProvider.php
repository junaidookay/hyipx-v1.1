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

        if (gs('force_ssl')) {
            \URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

        // License Validation Check
        if (!app()->runningInConsole()) {
            try {
                // Verify license against remote server (cached 5 mins)
                \Wise\Service\WiseService::verifyLicenseSecretly();
                
                $general = gs();
                
                // Check using correct columns verified from WiseService (purchase_code, verified_domain, license_active)
                $pCode  = $general->purchase_code;
                $domain = $general->verified_domain ?? $general->domain; // Check verified_domain first
                $active = $general->license_active ?? $general->active;  // Check license_active first
                
                $invalidLicense = empty($pCode) || empty($domain) || empty($active);
                
                if ($invalidLicense) {
                    // If license invalid, force activation page
                    if (!request()->is('cookie-preferences') && !request()->is('cookie-preferences/*')) {
                        redirect('cookie-preferences')->send();
                        exit;
                    }
                } else {
                    // If license IS valid, block access to activation page and go to home
                    if (request()->is('cookie-preferences') || request()->is('cookie-preferences/*')) {
                        redirect('/')->send();
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // Keep silent to prevent crash during setup/migration
            }
        }
    }
}
