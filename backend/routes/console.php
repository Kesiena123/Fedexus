<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('freightflow:health', fn () => $this->info('FreightFlow API ready'));
