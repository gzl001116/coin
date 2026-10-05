<?php
namespace App\Console;
use Illuminate\Console\Scheduling\Schedule;
class Kernel {protected function schedule(Schedule $schedule):void{$schedule->command('sellers:settle')->weeklyOn(5,'2:00');}}
