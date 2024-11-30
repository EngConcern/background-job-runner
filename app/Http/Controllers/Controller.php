<?php

namespace App\Http\Controllers;

use App\Jobs\ExampleJob;

abstract class Controller
{
    public function executeJob()
    {
        
        runBackgroundJob(ExampleJob::class, 'handle', ['key' => 'value']);
    }
}
