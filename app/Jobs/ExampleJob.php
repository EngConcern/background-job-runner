<?php

namespace App\Jobs;

class ExampleJob
{
    public function handle(array $data, $params)
    {
        dd('Job executed', $data);

        // Your background task logic here
        file_put_contents(
            storage_path('logs/example_job.log'), 
            json_encode($data) . PHP_EOL, 
            FILE_APPEND
        );
    }
}



