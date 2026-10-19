<?php

namespace App\Console\Commands;

use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Worker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearTablesAndWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squares:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Освободить квадраты и почистить сотрудников';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Queue::query()->where('is_closed', false)->update(['is_closed' => true]);

        Worker::query()->update(['square_id' => null]);
        Worker::query()->delete();
        Cache::put('checkTables', []);
    }
}
