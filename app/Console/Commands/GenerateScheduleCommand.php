<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\MonthlyScheduleSeeder;

class GenerateScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:generate 
                            {--year= : Год для генерации расписания (по умолчанию текущий)}
                            {--month= : Месяц для генерации расписания (1-12, по умолчанию текущий)}
                            {--clear : Очистить существующее расписание перед генерацией}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует расписание на указанный месяц';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: date('Y');
        $month = $this->option('month') ?: date('m');
        
        // Валидация
        if ($month < 1 || $month > 12) {
            $this->error('Месяц должен быть от 1 до 12');
            return 1;
        }
        
        if ($year < 2020 || $year > 2030) {
            $this->error('Год должен быть от 2020 до 2030');
            return 1;
        }
        
        // Очистка существующего расписания
        if ($this->option('clear')) {
            $this->info('Очищаем существующее расписание...');
            \App\Models\Schedule::truncate();
        }
        
        $this->info("Генерируем расписание для {$month}/{$year}...");
        
        // Создаем экземпляр сидера и генерируем расписание
        $seeder = new MonthlyScheduleSeeder();
        $seeder->setCommand($this);
        $seeder->generateScheduleForMonth((int)$year, (int)$month);
        
        $this->info('Расписание успешно сгенерировано!');
        
        return 0;
    }
}
