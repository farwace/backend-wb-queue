<?php

namespace App\Console\Commands;

use App\Models\Queue;
use App\Models\QueueLog;
use Illuminate\Console\Command;

class ClearQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workersQueue:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить очереди ранее чем 7 дней';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Queue::query()->where('created_at', '<=', now()->subDays(7))->delete();
        QueueLog::query()->where('created_at', '<=', now()->subDays(60))->delete();

    }
}
