<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('subdomain')->unique(),
            Forms\Components\TextInput::make('domain')->unique(),
            Forms\Components\KeyValue::make('settings'),
        ];
    }
}
