<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sellers:settle')->weeklyOn(5, '02:00');
