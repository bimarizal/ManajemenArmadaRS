<?php

namespace App\Filament\Resources\ArmadaResource\RelationManagers;

use App\Models\LogBbm;
use App\Support\Rupiah;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LogBbmsRelationManager extends RelationManager
{
    protected static string $relationship = 'logBbms';

    protected static ?string $title = 'Riwayat BBM';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tgl_pengisian')
            ->defaultSort('tgl_pengisian', 'desc')
            ->recordClasses(fn (LogBbm $record) => $record->is_flagged_boros ? 'row-boros' : null)
            ->columns([
                TextColumn::make('tgl_pengisian')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('driver.name')->label('Driver'),
                TextColumn::make('jumlah_liter')->label('Liter')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.') . ' L'),
                TextColumn::make('total_biaya')->label('Biaya')
                    ->formatStateUsing(fn ($state) => Rupiah::format($state)),
                TextColumn::make('rasio_efisiensi')->label('Efisiensi (KM/L)')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
                    ->color(fn (LogBbm $record) => $record->is_flagged_boros ? 'danger' : 'success'),
            ]);
    }
}
