<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public const LABEL_ROLE = [
        User::ROLE_DRIVER => 'Driver (Sopir)',
        User::ROLE_ADMIN_GARASI => 'Admin Garasi',
        User::ROLE_KETUA_GARASI => 'Ketua Garasi',
    ];

    /** Admin Garasi hanya melihat dan mengelola akun Driver. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdminGarasi()
            ? $query->where('role', User::ROLE_DRIVER)
            : $query;
    }

    protected static function opsiRole(): array
    {
        return auth()->user()?->isKetuaGarasi()
            ? self::LABEL_ROLE
            : [User::ROLE_DRIVER => self::LABEL_ROLE[User::ROLE_DRIVER]];
    }

    public static function form(Form $form): Form
    {
        // Akun sendiri tidak boleh diubah role/status-nya agar tidak terkunci dari sistem.
        $akunSendiri = fn (?User $record): bool => $record?->id === auth()->id();

        return $form->schema([
            Section::make('Akun Pengguna')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')->label('Nama Lengkap')->required()->maxLength(255),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('role')
                        ->label('Role')
                        ->options(fn () => self::opsiRole())
                        ->default(User::ROLE_DRIVER)
                        ->required()
                        ->disabled($akunSendiri),
                    Toggle::make('is_active')
                        ->label('Akun aktif')
                        ->helperText('Akun nonaktif tidak dapat masuk ke sistem.')
                        ->default(true)
                        ->disabled($akunSendiri),
                    TextInput::make('password')
                        ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'Password Baru')
                        ->password()->revealable()->rule(Password::default())
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->same('passwordConfirmation')
                        ->helperText(fn (string $operation) => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka.'),
                    TextInput::make('passwordConfirmation')
                        ->label('Ulangi Password')
                        ->password()->revealable()
                        ->required(fn (Get $get) => filled($get('password')))
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->label('Role')->badge()
                    ->formatStateUsing(fn (string $state) => self::LABEL_ROLE[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_KETUA_GARASI => 'primary',
                        User::ROLE_ADMIN_GARASI => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->options(self::LABEL_ROLE)
                    ->visible(fn () => auth()->user()?->isKetuaGarasi()),
                TernaryFilter::make('is_active')->label('Status akun')
                    ->trueLabel('Aktif')->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('toggleAktif')
                    ->label(fn (User $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => auth()->user()?->can('update', $record) && $record->id !== auth()->id())
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),

                // Hanya muncul untuk akun tanpa riwayat BBM (lihat UserPolicy::delete).
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
