<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;

class ZistribusiDashboard extends Dashboard
{

    use HasPageShield, HasFiltersForm;

    protected static ?string $title = 'Zistribusi Dashboard';
    protected static string $view = 'filament.pages.zistribusi-dashboard';
    protected static string $routePath = 'zistribusi';
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('data')
                            ->label('Data')
                            ->options([
                                'all' => 'All',
                                'pendis' => 'Pendistribusian',
                                'pendag' => 'Pendayagunaan',
                            ])
                            ->default('all')
                            ->native(false),
                        Select::make('year')
                            ->label('Tahun')
                            ->options([
                                date('Y') => date('Y'),
                                date('Y', strtotime('-1 year')) => date('Y', strtotime('-1 year')),
                                date('Y', strtotime('-2 year')) => date('Y', strtotime('-2 year')),
                            ])
                            ->placeholder('Select Year')
                            ->searchable(),
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->maxDate(fn(Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->minDate(fn(Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                        // ...
                    ])
                    ->columns(4),

            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\PendisOverview::class,
            \App\Filament\Widgets\PendisChart::class
        ];
    }
}
