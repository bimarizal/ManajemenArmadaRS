<?php

namespace App\Filament\Resources\ArmadaResource\RelationManagers;

use App\Support\Rupiah;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LogServisesRelationManager extends RelationManager
{
    protected static string $relationship = 'logServises';

    protected static ?string $title = 'Riwayat Servis';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jenis_servis')
            ->defaultSort('tgl_servis', 'desc')
            ->columns([
                TextColumn::make('tgl_servis')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('jenis_servis')->label('Jenis Servis')->searchable(),
                TextColumn::make('bengkel'),
                TextColumn::make('odometer_servis')->label('Odometer')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.') . ' KM'),
                TextColumn::make('biaya')->formatStateUsing(fn ($state) => Rupiah::format($state)),
                TextColumn::make('status_approval')->label('Approval')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'warning',
                    }),
            ]);
    }
}
