<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Ejecutar el registro de faltas todos los días a las 18:30
Schedule::command('asistencia:registrar-faltas')->dailyAt('18:30');
