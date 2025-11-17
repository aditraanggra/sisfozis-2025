<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function summary()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $today = $now->toDateString();
        $startDate = $now->copy()->subDays(29)->toDateString();

        $totalSuratMasuk = SuratMasuk::count();
        $totalSuratKeluar = SuratKeluar::count();

        $suratMasukThisMonth = SuratMasuk::whereBetween('date_agenda', [$startOfMonth->toDateString(), $today])->count();
        $suratKeluarThisMonth = SuratKeluar::whereBetween('date_letter', [$startOfMonth->toDateString(), $today])->count();

        $suratMasukToday = SuratMasuk::whereDate('date_agenda', $today)->count();
        $suratKeluarToday = SuratKeluar::whereDate('date_letter', $today)->count();

        $masukDaily = SuratMasuk::selectRaw('date_agenda as date, COUNT(*) as total')
            ->whereBetween('date_agenda', [$startDate, $today])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $keluarDaily = SuratKeluar::selectRaw('date_letter as date, COUNT(*) as total')
            ->whereBetween('date_letter', [$startDate, $today])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $period = CarbonPeriod::create($startDate, $today);

        $daily = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');

            $daily[] = [
                'date' => $dateString,
                'surat_masuk' => (int) ($masukDaily[$dateString] ?? 0),
                'surat_keluar' => (int) ($keluarDaily[$dateString] ?? 0),
            ];
        }

        return response()->json([
            'total_surat_masuk' => $totalSuratMasuk,
            'total_surat_keluar' => $totalSuratKeluar,
            'bulan_ini' => [
                'surat_masuk' => $suratMasukThisMonth,
                'surat_keluar' => $suratKeluarThisMonth,
                'total' => $suratMasukThisMonth + $suratKeluarThisMonth,
            ],
            'hari_ini' => [
                'surat_masuk' => $suratMasukToday,
                'surat_keluar' => $suratKeluarToday,
                'total' => $suratMasukToday + $suratKeluarToday,
            ],
            'harian_30_hari' => $daily,
        ]);
    }
}
