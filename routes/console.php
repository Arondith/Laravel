<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('pulsedesk:about', function () {
    $this->info('PulseDesk — Laravel helpdesk and SLA management portfolio project.');
})->purpose('Display information about PulseDesk');
