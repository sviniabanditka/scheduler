<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\ScheduleVersion;
use App\Models\SoftWeight;
use App\Services\ScheduleGenerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ScheduleGenerationPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.pages.schedule-generation';

    protected static ?string $navigationLabel = 'Генерація розкладу';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Розклад';

    protected static ?string $title = 'Генерація розкладу';

    public ?int $calendar_id = null;
    public string $algorithm = 'greedy';
    public float $w_windows = 10;
    public float $w_prefs = 5;
    public float $w_balance = 2;
    public int $timeout = 420;
    public ?string $version_name = null;
    public bool $isGenerating = false;

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    public function mount(): void
    {
        // Load default weights from SoftWeights
        $weights = SoftWeight::first();
        if ($weights) {
            $this->w_windows = $weights->w_windows;
            $this->w_prefs = $weights->w_prefs;
            $this->w_balance = $weights->w_balance;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Параметри генерації')
                    ->description('Налаштуйте параметри для створення розкладу')
                    ->schema([
                        Select::make('calendar_id')
                            ->label('Календар')
                            ->options(Calendar::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Оберіть календар'),

                        TextInput::make('version_name')
                            ->label('Назва версії')
                            ->placeholder('Автоматична назва')
                            ->helperText('Залиште порожнім для автоматичної назви'),

                        TextInput::make('timeout')
                            ->label('Таймаут (секунд)')
                            ->integer()
                            ->default(420)
                            ->minValue(30)
                            ->maxValue(1800)
                            ->helperText('Максимальний час роботи солвера'),

                        Select::make('algorithm')
                            ->label('Алгоритм')
                            ->options([
                                'greedy' => 'Швидкий (жадібний)',
                                'cpsat' => 'Оптимальний (CP-SAT)',
                            ])
                            ->default('greedy')
                            ->helperText('CP-SAT дає кращий результат, але працює довше'),
                    ])
                    ->columns(4),

                Section::make('Ваги м\'яких обмежень')
                    ->description('Чим більше значення — тим важливіше обмеження')
                    ->schema([
                        TextInput::make('w_windows')
                            ->label('Мінімізація вікон')
                            ->numeric()
                            ->step(0.5)
                            ->default(10)
                            ->helperText('Штраф за порожні пари між заняттями'),

                        TextInput::make('w_prefs')
                            ->label('Преференції викладачів')
                            ->numeric()
                            ->step(0.5)
                            ->default(5)
                            ->helperText('Врахування побажань викладачів'),

                        TextInput::make('w_balance')
                            ->label('Баланс навантаження')
                            ->numeric()
                            ->step(0.5)
                            ->default(2)
                            ->helperText('Рівномірний розподіл по днях'),
                    ])
                    ->columns(3),
            ]);
    }

    public function generate(): void
    {
        if (!$this->calendar_id) {
            Notification::make()
                ->title('Оберіть календар')
                ->warning()
                ->send();
            return;
        }

        $user = auth()->user();

        $service = app(ScheduleGenerationService::class);

        try {
            $version = $service->generate(
                tenantId: $user->tenant_id,
                calendarId: $this->calendar_id,
                createdBy: $user->id,
                weights: [
                    'w_windows' => $this->w_windows,
                    'w_prefs' => $this->w_prefs,
                    'w_balance' => $this->w_balance,
                ],
                timeoutSeconds: $this->timeout,
                name: $this->version_name,
                algorithm: $this->algorithm,
            );

            Notification::make()
                ->title('Розклад згенеровано!')
                ->body("Версія: {$version->name}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Помилка генерації')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function publishVersion(int $versionId): void
    {
        $version = ScheduleVersion::findOrFail($versionId);
        $service = app(ScheduleGenerationService::class);
        $service->publish($version);

        Notification::make()
            ->title('Розклад опубліковано!')
            ->body("Версія \"{$version->name}\" тепер доступна публічно")
            ->success()
            ->send();
    }

    public function archiveVersion(int $versionId): void
    {
        $version = ScheduleVersion::findOrFail($versionId);
        $service = app(ScheduleGenerationService::class);
        $service->archive($version);

        Notification::make()
            ->title('Версію архівовано')
            ->body("Версія \"{$version->name}\" тепер в архіві")
            ->warning()
            ->send();
    }

    public function getRecentVersionsProperty()
    {
        return ScheduleVersion::with('calendar', 'creator')
            ->withCount(['assignments', 'violations'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function getTenantPublicUrlProperty(): string
    {
        $tenant = auth()->user()->tenant;
        return $tenant ? $tenant->getPublicUrl() : '#';
    }
}
