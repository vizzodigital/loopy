<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --queue=high,default --stop-when-empty')->everyMinute()->withoutOverlapping();
