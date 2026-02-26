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

    protected static ?string $navigationLabel = 'Групи';

    protected static ?string $modelLabel = 'Група';

    protected static ?string $pluralModelLabel = 'Групи';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Управління даними';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Назва групи')
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
                    ->label('Назва групи')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Курс')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('course.number')
                    ->label('Номер курсу')
                    ->formatStateUsing(fn (int $state): string => "{$state} курс")
                    ->colors([
                        'primary' => 1,
                        'success' => 2,
                        'warning' => 3,
                        'danger' => 4,
                    ]),
                
                Tables\Columns\TextColumn::make('activities_count')
                    ->label('Занять')
                    ->counts('activities')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
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
                    ->label('Номер курсу')
                    ->relationship('course', 'number')
                    ->options([
                        1 => '1 курс',
                        2 => '2 курс',
                        3 => '3 курс',
                        4 => '4 курс',
                    ]),
                
                Tables\Filters\Filter::make('has_activities')
                    ->label('З заняттями')
                    ->query(fn (Builder $query): Builder => $query->has('activities')),
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
