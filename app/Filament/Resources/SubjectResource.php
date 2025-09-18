<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Предметы';

    protected static ?string $modelLabel = 'Предмет';

    protected static ?string $pluralModelLabel = 'Предметы';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Управление данными';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название предмета')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('teacher_id')
                    ->label('Преподаватель')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                
                Forms\Components\Select::make('type')
                    ->label('Тип занятия')
                    ->options(Subject::TYPES)
                    ->required()
                    ->default(Subject::TYPE_LECTURE),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Преподаватель')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => Subject::TYPES[$state] ?? $state)
                    ->colors([
                        'success' => Subject::TYPE_LECTURE,
                        'warning' => Subject::TYPE_PRACTICE,
                    ]),
                
                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('Занятий')
                    ->counts('schedules')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('teacher')
                    ->label('Преподаватель')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип занятия')
                    ->options(Subject::TYPES),
                
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
