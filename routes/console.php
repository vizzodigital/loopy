<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --timeout=300 --memory=256')->everyMinute()->withoutOverlapping(5);
