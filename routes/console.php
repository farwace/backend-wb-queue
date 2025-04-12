<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('workersQueue:clear')->daily();
