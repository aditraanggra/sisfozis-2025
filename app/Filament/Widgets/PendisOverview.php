<?php

namespace App\Filament\Widgets;

use App\Models\Pendag;
use App\Models\Pendis;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PendisOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $filters = $this->filters ?? [];
        $selectedData = $filters['data'] ?? 'all';

        $includePendis = in_array($selectedData, ['all', 'pendis'], true);
        $includePendag = in_array($selectedData, ['all', 'pendag'], true);

        $totalDistribution = 0;
        $totalMustahik = 0;

        if ($includePendis) {
            $pendisQuery = $this->getFilteredPendisQuery();
            $totalDistribution += (clone $pendisQuery)->sum('financial_aid');
            $totalMustahik += (clone $pendisQuery)->sum('total_benef');
        }

        if ($includePendag) {
            $pendagQuery = $this->getFilteredPendagQuery();
            $totalDistribution += (clone $pendagQuery)->sum('financial_aid');
            $totalMustahik += (clone $pendagQuery)->sum('total_benef');
        }

        return [
            Stat::make('Total Pendistribusian', 'Rp ' . number_format($totalDistribution, 0, ',', '.'))
                ->extraAttributes(['class' => 'md:col-span-2']),
            Stat::make('Total Penerima Manfaat', number_format($totalMustahik, 0, ',', '.')),
        ];
    }

    protected function getFilteredPendisQuery(): Builder
    {
        $query = Pendis::query()->where('status', 'Selesai');

        return $this->applyDateFilters($query);
    }

    protected function getFilteredPendagQuery(): Builder
    {
        $query = Pendag::query()->where('status', 'Selesai');

        return $this->applyDateFilters($query);
    }

    protected function applyDateFilters(Builder $query): Builder
    {
        $filters = $this->filters ?? [];

        if (! empty($filters['year'])) {
            $query->whereYear('distribution_date', $filters['year']);
        }

        if (! empty($filters['startDate'])) {
            $query->whereDate('distribution_date', '>=', Carbon::parse($filters['startDate']));
        }

        if (! empty($filters['endDate'])) {
            $query->whereDate('distribution_date', '<=', Carbon::parse($filters['endDate']));
        }

        return $query;
    }

    protected function getCachedStats(): array
    {
        return $this->getStats();
    }
}
