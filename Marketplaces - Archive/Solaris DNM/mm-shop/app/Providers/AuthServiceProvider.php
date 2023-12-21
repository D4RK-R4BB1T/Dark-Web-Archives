<?php

namespace App\Providers;

use App\City;
use App\Employee;
use App\Good;
use App\GoodsPosition;
use App\GoodsReview;
use App\User;
use ClassesWithParents\E;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        \Gate::define('management-owner', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            return $employee && $employee->role == Employee::ROLE_OWNER;
        });

        \Gate::define('management-goods-create', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->goods_create;
        });

        \Gate::define('management-goods-edit', function (User $user, Good $good) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($employee->goods_only_own_city) {
                $cities = $good->cities->map(function($city) {
                    return $city->id;
                });
                if (!$cities->contains($employee->city_id)) {
                    return false;
                }
            }

            return $employee->goods_edit;
        });

        \Gate::define('management-goods-delete', function (User $user, Good $good) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($employee->goods_only_own_city) {
                $cities = $good->cities->map(function($city) {
                    return $city->id;
                });
                if (!$cities->contains($employee->city_id)) {
                    return false;
                }
            }
            return $employee->goods_delete;
        });

        \Gate::define('management-quests-create', function (User $user, Good $good, City $city = null) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($employee->quests_only_own_city) {
                $cities = !is_null($city) ? collect([$city->id]) : $good->cities->map(function($city) {
                    return $city->id;
                });
                if (!$cities->contains($employee->city_id)) {
                    return false;
                }
            }

            return $employee->quests_create ||
                (is_array($employee->quests_allowed_goods) && in_array($good->id, $employee->quests_allowed_goods));
        });

        \Gate::define('management-quests-edit', function (User $user, Good $good, City $city) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($employee->quests_only_own_city) {
                $cities = !is_null($city) ? collect([$city->id]) : $good->cities->map(function($city) {
                    return $city->id;
                });
                if (!$cities->contains($employee->city_id)) {
                    return false;
                }
            }

            return $employee->quests_edit ||
                (is_array($employee->quests_allowed_goods) && in_array($good->id, $employee->quests_allowed_goods));
        });

        \Gate::define('management-quests-delete', function (User $user, Good $good, City $city) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($employee->quests_only_own_city) {
                $cities = !is_null($city) ? collect([$city->id]) : $good->cities->map(function($city) {
                    return $city->id;
                });
                if (!$cities->contains($employee->city_id)) {
                    return false;
                }
            }

            return $employee->quests_delete ||
                (is_array($employee->quests_allowed_goods) && in_array($good->id, $employee->quests_allowed_goods));
        });

        \Gate::define('management-quests-own', function (User $user, GoodsPosition $position = null) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            if ($position && $position->employee_id == $employee->id) {
                return true;
            }

            return $employee->quests_not_only_own;
        });

        \Gate::define('management-quests-preorder', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_orders || $employee->quests_preorders;
        });


        \Gate::define('management-sections-messages', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_messages;
        });

        \Gate::define('management-sections-settings', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_settings;
        });

        \Gate::define('management-sections-orders', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_orders;
        });

        \Gate::define('management-sections-paid-services', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_paid_services;
        });

        \Gate::define('management-sections-employees', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_employees;
        });

        \Gate::define('management-sections-finances', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_finances;
        });

        \Gate::define('management-sections-qiwi', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_qiwi;
        });

        \Gate::define('management-sections-discounts', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_discounts;
        });

        \Gate::define('management-sections-pages', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_pages;
        });

        \Gate::define('management-sections-system', function (User $user) {
            return $user->can('management-owner');
        });

        \Gate::define('management-sections-stats', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_stats;
        });

        \Gate::define('management-reviews', function (User $user, GoodsReview $review) {
            if (!$review->good) {
                return FALSE;
            }
            return $user->can('management-sections-orders');
        });

        \Gate::define('management-sections-own-orders', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_own_orders;
        });

        \Gate::define('management-quests-moderated', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return false;
            }

            return $employee->quests_moderate;
        });

        \Gate::define('management-sections-moderate', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->sections_moderate;
        });

        \Gate::define('management-quests-map', function (User $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            if ($employee->role == Employee::ROLE_OWNER) {
                return true;
            }

            return $employee->quests_not_only_own;
        });
    }
}