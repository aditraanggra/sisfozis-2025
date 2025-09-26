<?php

namespace App\Filament\Widgets;

use App\Models\Pendag;
use App\Models\Pendis;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PendisChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Total Pendistribusian per Bulan';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $filters = $this->filters ?? [];
        $selectedData = $filters['data'] ?? 'all';

        $includePendis = in_array($selectedData, ['all', 'pendis'], true);
        $includePendag = in_array($selectedData, ['all', 'pendag'], true);

        $totalsByPeriod = [];

        if ($includePendis) {
            $this->accumulateMonthlyTotals($totalsByPeriod, $this->getFilteredPendisQuery());
        }

        if ($includePendag) {
            $this->accumulateMonthlyTotals($totalsByPeriod, $this->getFilteredPendagQuery());
        }

        ksort($totalsByPeriod);

        $labels = [];
        $data = [];

        foreach ($totalsByPeriod as $period => $total) {
            $labels[] = Carbon::createFromFormat('Y-m', $period)->format('M Y');
            $data[] = round($total, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Pendistribusian',
                    'data' => $data,
                    'fill' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
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

    protected function accumulateMonthlyTotals(array &$totalsByPeriod, Builder $query): void
    {
        $driver = DB::getDriverName();

        $periodExpression = match ($driver) {
            'pgsql' => "TO_CHAR(distribution_date, 'YYYY-MM')",
            'sqlite' => "STRFTIME('%Y-%m', distribution_date)",
            default => "DATE_FORMAT(distribution_date, '%Y-%m')",
        };

        (clone $query)
            ->selectRaw($periodExpression . " as period, SUM(financial_aid) as total")
            ->groupByRaw($periodExpression)
            ->orderBy('period')
            ->get()
            ->each(function ($row) use (&$totalsByPeriod) {
                if (empty($row->period)) {
                    return;
                }

                $totalsByPeriod[$row->period] = ($totalsByPeriod[$row->period] ?? 0) + (float) $row->total;
            });
    }
}
