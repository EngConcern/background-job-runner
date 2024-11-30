<?php

if (!function_exists('runBackgroundJob')) {
    function runBackgroundJob(string $className, string $methodName, array $parameters = [])
    {
        $paramsJson = json_encode($parameters);

        // Sanitize inputs
        $sanitizedClassName = escapeshellarg($className);
        $sanitizedMethodName = escapeshellarg($methodName);
        $sanitizedParams = escapeshellarg($paramsJson);

        // Build the command for background execution
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "start /B php background_job_runner.php --class=$sanitizedClassName --method=$sanitizedMethodName --params=$sanitizedParams";
        } else {
            $command = "php background_job_runner.php --class=$sanitizedClassName --method=$sanitizedMethodName --params=$sanitizedParams > /dev/null 2>&1 &";
        }

        dd($command);
        exec($command);
    }
}
