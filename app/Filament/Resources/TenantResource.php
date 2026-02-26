<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Мій університет';

    protected static ?string $modelLabel = 'Університет';

    protected static ?string $pluralModelLabel = 'Університет';

    protected static ?string $navigationGroup = 'Налаштування';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Назва')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('subdomain')
                    ->label('Піддомен')
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\TextInput::make('public_slug')
                    ->label('Публічне посилання')
                    ->disabled()
                    ->maxLength(64),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активний')
                    ->default(true),
                Forms\Components\KeyValue::make('settings')
                    ->label('Налаштування'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                Tables\Columns\TextColumn::make('public_slug')
                    ->label('Публічне посилання')
                    ->formatStateUsing(fn (string $state): string => url("/s/{$state}"))
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    // Only show the current user's tenant
    public static function getEloquentQuery(): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        return parent::getEloquentQuery()
            ->when($tenantId, fn (Builder $q) => $q->where('id', $tenantId));
    }

    // Disable creating new tenants from admin
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
