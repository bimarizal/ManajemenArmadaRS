<?php

namespace App\Filament\Resources\LogServisResource\Pages;

use App\Filament\Resources\LogServisResource;
use App\Models\LogServis;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLogServis extends ViewRecord
{
    protected static string $resource = LogServisResource::class;

    protected function getHeaderActions(): array
    {
        $kembali = fn (LogServis $record) => LogServisResource::getUrl('view', ['record' => $record]);

        return [
            Actions\EditAction::make(),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui pengajuan servis?')
                ->visible(fn (LogServis $record) => auth()->user()?->isKetuaGarasi() && $record->status_approval === 'Pending')
                ->action(function (LogServis $record, Actions\Action $action) {
                    LogServisResource::putuskan($record, 'Approved');
                    $action->success();
                })
                ->successNotificationTitle('Pengajuan servis disetujui')
                ->successRedirectUrl($kembali),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak pengajuan servis?')
                ->visible(fn (LogServis $record) => auth()->user()?->isKetuaGarasi() && $record->status_approval === 'Pending')
                ->action(function (LogServis $record, Actions\Action $action) {
                    LogServisResource::putuskan($record, 'Rejected');
                    $action->success();
                })
                ->successNotificationTitle('Pengajuan servis ditolak')
                ->successRedirectUrl($kembali),
        ];
    }
}
