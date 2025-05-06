<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('workersQueue:clear')->daily();
Schedule::command('tables:clear')->dailyAt('08:00')->timezone('Europe/Moscow');
Schedule::command('tables:clear')->dailyAt('20:00')->timezone('Europe/Moscow');
