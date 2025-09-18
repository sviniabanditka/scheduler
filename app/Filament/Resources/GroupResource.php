<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Группы';

    protected static ?string $modelLabel = 'Группа';

    protected static ?string $pluralModelLabel = 'Группы';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Управление данными';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название группы')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('course_id')
                    ->label('Курс')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название группы')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Курс')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('course.number')
                    ->label('Номер курса')
                    ->formatStateUsing(fn (int $state): string => "{$state} курс")
                    ->colors([
                        'primary' => 1,
                        'success' => 2,
                        'warning' => 3,
                        'danger' => 4,
                    ]),
                
                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('Занятий')
                    ->counts('schedules')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->label('Курс')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('course_number')
                    ->label('Номер курса')
                    ->relationship('course', 'number')
                    ->options([
                        1 => '1 курс',
                        2 => '2 курс',
                        3 => '3 курс',
                        4 => '4 курс',
                    ]),
                
                Tables\Filters\Filter::make('has_schedules')
                    ->label('С расписанием')
                    ->query(fn (Builder $query): Builder => $query->has('schedules')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
