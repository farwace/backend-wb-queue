<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\Queue;
use App\Models\QueueLog;
use Illuminate\Console\Command;

class ClearIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'incidents:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить инциденты ранее 14 дней';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $arIncidents = Incident::query()->where('created_at', '<=', now()->subDays(14))->limit(100)->get();
        foreach ($arIncidents as $incident) {
            $incident->delete();
        }
    }
}
