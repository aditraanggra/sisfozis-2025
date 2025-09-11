<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendisResource\Pages;
use App\Models\Pendis;
use App\Models\Program;
use App\Models\SubProgram;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rule;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PendisResource extends Resource
{
    protected static ?string $model = Pendis::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'ZIStribusi';

    protected static ?string $navigationLabel = 'Data Pendistribusian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Permohonan')
                        ->schema([
                            ToggleButtons::make('status')
                                ->label('Status')
                                ->options([
                                    'Diproses' => 'Diproses',
                                    'Selesai' => 'Selesai',
                                ])
                                ->colors([
                                    'Diproses' => 'warning',
                                    'Selesai' => 'success',
                                ])
                                ->icons([
                                    'Diproses' => 'heroicon-o-arrow-path',
                                    'Selesai' => 'heroicon-o-check-badge',
                                ])
                                ->default('Diproses')
                                ->inline()
                                ->required(),
                            Select::make('no_ref')
                                ->label('No Agenda')
                                ->searchable()
                                ->options(function () {
                                    $existingRefs = \App\Models\Pendis::pluck('no_ref')->toArray();
                                    return \App\Models\SuratMasuk::where('dept_disposition', 'Pendistribusian')
                                        ->whereNotIn('no_agenda', $existingRefs)
                                        ->pluck('no_agenda', 'no_agenda');
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $s = \App\Models\SuratMasuk::where('no_agenda', $state)->first();
                                    if ($s) {
                                        $set('application_date', $s->date_agenda);
                                        $set('applicant_name', $s->sender);
                                        $set('district', $s->district);
                                        $set('village', $s->village);
                                        $set('subject', $s->subject);
                                    }
                                })
                                ->required(),
                            //->preload()
                            //->live()
                            DatePicker::make('application_date')
                                ->label('Tanggal Permohonan')
                                ->required()
                                ->native(false)
                                ->displayFormat('Y/m/d')
                                ->prefixIcon('heroicon-o-calendar'),
                            Radio::make('applicant_type')
                                ->label('Jenis Pemohon')
                                ->options([
                                    'Perorangan' => 'Perorangan',
                                    'Lembaga' => 'Lembaga'
                                ]),
                            TextInput::make('applicant_name')
                                ->label('Nama Pemohon')
                                ->required(),
                        ]),

                    Wizard\Step::make('Penerima Manfaat')
                        ->schema([
                            Repeater::make('beneficiary')
                                ->label('Penerima Manfaat')
                                ->schema([
                                    TextInput::make('nama')
                                        ->label('Nama')
                                        ->required(),
                                    TextInput::make('nik')
                                        ->label('Nomor Induk Kependudukan (NIK)')
                                        ->required()
                                        ->length(16),
                                    DatePicker::make('tanggal_lahir')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('Y/m/d')
                                        ->prefixIcon('heroicon-o-calendar'),
                                    Textarea::make('alamat'),
                                ])
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('total_benef', is_array($state) ? count($state) : 0);
                                })

                                ->addActionLabel('Tambah penerima manfaat'),
                            TextInput::make('district')
                                ->label('Kecamatan')
                                ->autocomplete(),
                            TextInput::make('village')
                                ->label('Desa/Kelurahan')
                                ->autocomplete(),
                        ]),

                    Wizard\Step::make('Detail Pendistribusian')
                        ->schema([
                            ToggleButtons::make('fund_type')
                                ->label('Sumber Dana')
                                ->options([
                                    'Zakat' => 'Zakat',
                                    'Infak Tidak Terikat' => 'Infak Tidak Terikat',
                                    'Infak Terikat' => 'Infak Terikat',
                                    'DSKL' => 'DSKL',
                                ])
                                ->inline()
                                ->colors([
                                    'Zakat' => 'success',
                                    'Infak Tidak Terikat' => 'warning',
                                    'Infak Terikat' => 'danger',
                                    'DSKL' => 'info',
                                ]),
                            ToggleButtons::make('program')
                                ->label('Program')
                                // Ambil dari DB
                                ->options(fn() => Program::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->inline()
                                ->live()
                                // Kosongkan sub program ketika program berubah
                                ->afterStateUpdated(fn(Set $set) => $set('cat_program', null)),

                            Select::make('cat_program')
                                ->label('Sub Program')
                                // Filter berdasarkan program_id yang dipilih di atas
                                ->options(function (Get $get) {
                                    $pid = $get('program');
                                    return SubProgram::query()
                                        ->when($pid, fn($q) => $q->where('program_id', $pid))
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->disabled(fn(Get $get) => blank($get('program')))
                                ->searchable()
                                ->preload()
                                // Validasi silang: sub_program harus milik program terpilih
                                ->rule(function (Get $get) {
                                    $pid = $get('program');
                                    return Rule::exists('sub_programs', 'id')->where('program_id', $pid);
                                }),
                            ToggleButtons::make('asnaf')
                                ->label('Asnaf')
                                ->options([
                                    'Fakir' => 'Fakir',
                                    'Miskin' => 'Miskin',
                                    'Gharimin' => 'Gharimin',
                                    'Fisabilillah' => 'Fisabilillah',
                                    'Mualaf' => 'Mualaf',
                                    'Riqob' => 'Riqob',
                                    'Ibnusabil' => 'Ibnusabil',
                                    '-' => '-',
                                ])
                                ->inline()
                                ->colors([
                                    'Fakir' => 'info',
                                    'Miskin' => 'warning',
                                    'Gharimin' => 'success',
                                    'Fisabilillah' => 'danger',
                                    'Mualaf' => 'primary',
                                    'Riqob' => 'indigo',
                                    'Ibnusabil' => 'cian',
                                    '-' => 'gray',
                                ]),
                            Textarea::make('subject')
                                ->label('Perihal'),
                            TextInput::make('financial_aid')
                                ->label('Besar Bantuan'),
                            DatePicker::make('distribution_date')
                                ->native(false)
                                ->displayFormat('Y/m/d')
                                ->prefixIcon('heroicon-o-calendar'),
                            TextInput::make('receiver')
                                ->label('Nama Penerima Bantuan'),
                            Radio::make('rec')
                                ->label('Ada Rekomendasi?')
                                ->options([
                                    'Ya' => 'Ya',
                                    'Tidak' => 'Tidak'
                                ])
                                ->default('Tidak'),
                            TextInput::make('total_benef')
                                ->label('Jumlah Penerima Manfaat')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            FileUpload::make('doc_upload')
                                ->label('Upload Berkas')
                                ->disk('public')
                                ->directory('pendis/berkas')
                                ->openable()
                                ->preserveFilenames()
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(4096),
                            FileUpload::make('photos')
                                ->label('Upload Dokumentasi')
                                // ->multiple()
                                ->disk('public')
                                ->directory('pendis/foto')
                                ->image()
                                ->openable()
                                ->preserveFilenames()
                                ->maxFiles(10)
                                ->maxSize(4096),
                            Textarea::make('desc')
                                ->label('Keterangan'),

                        ])
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->sortable()
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->sortable()
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Diproses' => 'warning',
                        'Selesai' => 'success',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Diproses' => 'heroicon-o-arrow-path',
                        'Selesai' => 'heroicon-o-check-badge',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('no_ref')
                    ->sortable()
                    ->label('No Registrasi')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('application_date')
                    ->sortable()
                    ->label('Tanggal Permohonan')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('applicant_type')
                    ->sortable()
                    ->label('Jenis Pemohon')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('applicant_name')
                    ->sortable()
                    ->label('Nama Pemohon')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('beneficiary')
                    ->label('Penerima Manfaat')
                    ->getStateUsing(function ($record) {
                        // Ambil data langsung dari record (pastikan ada di database)
                        $beneficiary = $record->beneficiary;

                        // Jika string JSON, decode menjadi array
                        if (is_string($beneficiary)) {
                            $beneficiary = json_decode($beneficiary, true);
                        }

                        // Pastikan data adalah array dan olah datanya
                        if (is_array($beneficiary)) {
                            $names = array_column($beneficiary, 'nama');
                            return implode(', ', $names);
                        }

                        return '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                /* ViewColumn::make('beneficiary->nama')
                    ->label('Penerima Manfaat')
                    ->view('tables.columns.pm-column'), */
                TextColumn::make('district')
                    ->sortable()
                    ->label('Kecamatan')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('village')
                    ->sortable()
                    ->label('Desa/Kelurahan')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('fund_type')
                    ->sortable()
                    ->label('Sumber Dana')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'DSKL' => 'info',
                        'Infak Tidak Terikat' => 'warning',
                        'Zakat' => 'success',
                        'Infak Terikat' => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('program_relation.name')
                    ->label('Program')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->color(fn(?string $state): string => match ($state) {
                        'Kemanusiaan'      => 'primary',
                        'Pendidikan'       => 'warning',
                        'Kesehatan'        => 'danger',
                        'Ekonomi'          => 'success',
                        'Dakwah Advokasi'  => 'primary',
                        default            => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('sub_program_relation.name')
                    ->label('Sub Program')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('asnaf')
                    ->sortable()
                    ->label('Asnaf')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Fakir' => 'info',
                        'Miskin' => 'warning',
                        'Gharimin' => 'success',
                        'Fisabilillah' => 'danger',
                        'Mualaf' => 'primary',
                        'Riqob' => 'purple',
                        'Ibnusabil' => 'yellow',
                        '-' => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('subject')
                    ->sortable()
                    ->label('Perihal')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('financial_aid')
                    ->sortable()
                    ->money('IDR', true)
                    ->label('Besaran Bantuan')
                    ->summarize(Sum::make()->label('Total: ')->formatStateUsing(fn($state) => 'IDR ' . number_format($state, 0, ',', '.')))
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('distribution_date')
                    ->sortable()
                    ->label('Tanggal Penyaluran')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('receiver')
                    ->sortable()
                    ->label('Penerima Bantuan')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('desc')
                    ->sortable()
                    ->label('Keterangan')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('rec')
                    ->sortable()
                    ->label('Rekomendasi')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_benef')
                    ->sortable()
                    ->label('Jumlah PM')
                    ->summarize(Sum::make()->label('Total PM: '))
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('doc_upload')
                    ->sortable()
                    ->label('Upload Berkas')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('photos')
                    ->sortable()
                    ->label('Dokumentasi')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
                SelectFilter::make('status')->options([
                    'Diproses' => 'Diproses',
                    'Selesai' => 'Selesai',
                ]),
                SelectFilter::make('fund_type')->options([
                    'Zakat' => 'Zakat',
                    'Infak Tidak Terikat' => 'Infak Tidak Terikat',
                    'Infak Terikat' => 'Infak Terikat',
                    'DSKL' => 'DSKL',
                ])->label('Sumber Dana'),
                SelectFilter::make('asnaf')->options([
                    'Fakir' => 'Fakir',
                    'Miskin' => 'Miskin',
                    'Gharimin' => 'Gharimin',
                    'Fisabilillah' => 'Fisabilillah',
                    'Mualaf' => 'Mualaf',
                    'Riqob' => 'Riqob',
                    'Ibnusabil' => 'Ibnusabil',
                    '-' => '-',
                ])->label('Asnaf'),
            ])
            ->actions([
                /* Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Data berhasil dihapus'), */
                Tables\Actions\Action::make('markAsDone')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn($record) => (string) $record->status !== 'Selesai')
                    ->action(function ($record) {
                        $record->status = 'Selesai';
                        if (blank($record->distribution_date)) {
                            $record->distribution_date = now()->toDateString();
                        }
                        $record->save();
                    })
                    ->successNotificationTitle('Data berhasil diupdate'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data pendistribusian masuk')
            ->emptyStateDescription('Input data pendistribusian terlebih dahulu.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendis::route('/'),
            'create' => Pages\CreatePendis::route('/create'),
            'edit' => Pages\EditPendis::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $existingRefs = \App\Models\Pendis::pluck('no_ref')->toArray();
        $count = \App\Models\SuratMasuk::where('dept_disposition', 'Pendistribusian')
            ->whereNotIn('no_agenda', $existingRefs)
            ->count();
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getWidgets(): array
    {
        return [
            PendisResource\Widgets\PendisOverview::class,
        ];
    }
}
