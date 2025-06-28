<?php

namespace App\Console\Commands;

use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Report;
use Illuminate\Console\Command;

class ClearReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reportEntities:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить отписки ранее чем 7 дней';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $arReports = Report::query()->where('created_at', '<=', now()->subDays(7))->get();
        foreach ($arReports as $report) {
            $report->delete();
        }
    }
}
