<?php

namespace App\Providers;

use App\Models\Leave;
use DatePeriod;
use DateInterval;
use App\Models\Ticking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
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
        Builder::macro('dateFilter', function ($field, $period) {
            return $this->when($period == "last_15_days" && $period != "all_time", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subRealDays(15), now()]);
            })->when($period == "last_month", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subMonth(1)->startOfMonth(), now()->startOfMonth()]);
            })->when($period == "last_3_months" && $period != "all_time", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subRealMonths(3)->startOfMonth(), now()->startOfMonth()]);
            })->when($period == "last_6_months" && $period != "all_time", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subRealMonths(6)->startOfMonth(), now()->startOfMonth()]);
            })->when($period == "last_9_months" && $period != "all_time", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subRealMonths(9)->startOfMonth(), now()->startOfMonth()]);
            })->when($period == "last_year" && $period != "all_time", function ($query) use ($field) {
                return $query->whereBetween($field, [now()->subRealMonths(12)->startOfMonth(), now()->startOfMonth()]);
            });
        });


        //to be refactored
        Builder::macro('approvalStatusText', function ($status_owner = '', $type = '') {
            $model = $this->getModel();
            if ($model instanceof Ticking || $model instanceof  Leave) {
                $match = match ($status_owner) {
                    'supervisor' => match ($model->supervisor_approval_status) {
                        $model::SUPERVISOR_APPROVAL_PENDING => __('common.pending'),
                        $model::SUPERVISOR_APPROVAL_APPROVED => __('common.approved'),
                        $model::SUPERVISOR_APPROVAL_REJECTED => __('common.rejected'),
                        default => __('common.pending'),
                    },
                    'manager' => match ($model->manager_approval_status) {
                        $model::MANAGER_APPROVAL_PENDING => __('common.pending'),
                        $model::MANAGER_APPROVAL_APPROVED => __('common.approved'),
                        $model::MANAGER_APPROVAL_REJECTED => __('common.rejected'),
                        default => __('common.pending'),
                    },
                };
            } else {

                if ($type === 'boolean') {
                    $match = match ($model->is_active) {
                        true => __('common.active'),
                        false => __('Inactive'),
                        default => __('Inactive'),
                    };
                } else {
                    $match = match ($model->approval_status) {
                        $model::APPROVAL_STATUS_PENDING => __('common.pending'),
                        $model::APPROVAL_STATUS_APPROVED => __('common.approved'),
                        $model::APPROVAL_STATUS_REJECTED => __('common.rejected'),
                        default => __('common.pending'),
                    };
                }
            }
            return $match;
        });
        Builder::macro('approvalStatusStyle', function ($status_owner = '', $type = '') {
            $model = $this->getModel();
            if ($model instanceof Ticking || $model instanceof  Leave) {
                $match = match ($status_owner) {
                    'supervisor' => match ($model->supervisor_approval_status) {
                        $model::SUPERVISOR_APPROVAL_PENDING => 'warning',
                        $model::SUPERVISOR_APPROVAL_APPROVED => 'success',
                        $model::SUPERVISOR_APPROVAL_REJECTED => 'danger',
                        default => 'warning',
                    },
                    'manager' => match ($model->manager_approval_status) {
                        $model::MANAGER_APPROVAL_PENDING => 'warning',
                        $model::MANAGER_APPROVAL_APPROVED => 'success',
                        $model::MANAGER_APPROVAL_REJECTED => 'danger',
                        default => 'warning',
                    },
                };
            } else {

                if ($type === 'boolean') {
                    $match = match ($model->is_active) {
                        true => 'success',
                        false => 'danger',
                        default => 'danger',
                    };
                } else {
                    $match = match ($model->approval_status) {
                        $model::APPROVAL_STATUS_PENDING => 'warning',
                        $model::APPROVAL_STATUS_APPROVED => 'success',
                        $model::APPROVAL_STATUS_REJECTED => 'danger',
                        default => 'warning',
                    };
                }
            }
            return $match;
        });
    }
    
}
