<?php

namespace Modules\SuperAdmin\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class PlatformAnalyticsService
{
    /**
     * Return platform-level analytics.
     *
     * @return array<string, mixed>
     */
    public function overview(
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $from ??= now()->startOfMonth()->subMonths(11)->startOfMonth();
        $to ??= now()->endOfMonth();

        return [
            'summary' => $this->summary($from, $to),
            'revenue' => $this->revenueTrend($from, $to),
            'organizations' => $this->organizationTrend($from, $to),
            'subscriptions' => $this->subscriptionTrend($from, $to),
            'subscription_distribution' => $this->subscriptionDistribution(),
            'recent_growth' => $this->recentGrowth(),
        ];
    }

    /**
     * Return platform summary metrics.
     *
     * @return array<string, mixed>
     */
    private function summary(
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $organizationCount = Tenant::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $userCount = DB::table('users')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $subscriptionCount = TenantSubscription::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $revenue = PaymentTransaction::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        return [
            'organizations' => $organizationCount,
            'users' => $userCount,
            'subscriptions' => $subscriptionCount,
            'revenue' => (float) $revenue,
            'currency' => config('app.currency', 'USD'),
        ];
    }

    /**
     * Return monthly revenue trend.
     */
    private function revenueTrend(
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        $rows = PaymentTransaction::query()
            ->select([
                'paid_at',
                'amount',
            ])
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')
            ->get();

        return $this->groupByMonth(
            rows: $rows,
            dateColumn: 'paid_at',
            valueColumn: 'amount',
        );
    }

    /**
     * Return monthly organization growth.
     */
    private function organizationTrend(
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        $rows = Tenant::query()
            ->select('created_at')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        return $this->groupByMonth(
            rows: $rows,
            dateColumn: 'created_at',
        );
    }

    /**
     * Return monthly subscription growth.
     */
    private function subscriptionTrend(
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        $rows = TenantSubscription::query()
            ->select('created_at')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        return $this->groupByMonth(
            rows: $rows,
            dateColumn: 'created_at',
        );
    }

    /**
     * Return current subscription distribution by plan.
     */
    private function subscriptionDistribution(): Collection
    {
        return TenantSubscription::query()
            ->join(
                'subscription_plans',
                'subscription_plans.id',
                '=',
                'tenant_subscriptions.plan_id',
            )
            ->select(
                'subscription_plans.id',
                'subscription_plans.name',
                DB::raw('COUNT(tenant_subscriptions.id) as total'),
            )
            ->whereIn('tenant_subscriptions.status', [
                'active',
                'trialing',
            ])
            ->groupBy(
                'subscription_plans.id',
                'subscription_plans.name',
            )
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'value' => (int) $row->total,
            ]);
    }

    /**
     * Return recent platform growth metrics.
     *
     * @return array<string, int>
     */
    private function recentGrowth(): array
    {
        $from = now()->subDays(30);

        return [
            'organizations' => Tenant::query()
                ->where('created_at', '>=', $from)
                ->count(),

            'users' => DB::table('users')
                ->where('created_at', '>=', $from)
                ->count(),

            'subscriptions' => TenantSubscription::query()
                ->where('created_at', '>=', $from)
                ->count(),

            'payments' => PaymentTransaction::query()
                ->where('created_at', '>=', $from)
                ->count(),
        ];
    }

    /**
     * Group records by calendar month.
     *
     * @param  Collection<int, object>  $rows
     */
    private function groupByMonth(
        Collection $rows,
        string $dateColumn,
        ?string $valueColumn = null,
    ): Collection {
        return $rows
            ->groupBy(
                fn (object $row): string => $this->monthKey(
                    $row->{$dateColumn},
                ),
            )
            ->map(
                function (Collection $items, string $month) use (
                    $valueColumn,
                ): array {
                    $value = $valueColumn === null
                        ? $items->count()
                        : $items->sum(
                            fn (object $item): float => (float) (
                                $item->{$valueColumn} ?? 0
                            ),
                        );

                    return [
                        'month' => $month,
                        'value' => $valueColumn === null
                            ? (int) $value
                            : (float) $value,
                    ];
                },
            )
            ->sortBy('month')
            ->values();
    }

    /**
     * Convert a date value into YYYY-MM.
     */
    private function monthKey(mixed $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m');
        }

        return now()
            ->parse($date)
            ->format('Y-m');
    }

    /**
     * Return an empty paginator when sessions are unavailable.
     */
    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new PaginationLengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: $perPage,
            currentPage: 1,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }
}
