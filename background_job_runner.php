<?php

require_once __DIR__ . '/vendor/autoload.php';

class BackgroundJobRunner
{
    private const LOG_FILE = 'background_jobs.log';
    private const ERROR_LOG_FILE = 'background_jobs_errors.log';
    private int $maxRetries = 3;
    private int $retryDelay = 5; // in seconds

    public function run(string $className, string $methodName, array $parameters = [])
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                $attempts++;
                $this->validateClass($className);
                $this->validateMethod($className, $methodName);

                $instance = new $className();
                call_user_func_array([$instance, $methodName], $parameters);

                $this->logJobStatus($className, $methodName, 'success', $attempts);
                return;
            } catch (Exception $e) {
                if ($attempts >= $this->maxRetries) {
                    $this->logJobStatus($className, $methodName, 'failed', $attempts, $e->getMessage());
                    return;
                }
                sleep($this->retryDelay);
            }
        }
    }

    private function validateClass(string $className)
    {
        // Make sure the file returns an array of classes
        $allowedClasses = include __DIR__ . '/config/background_jobs.php';

        if (!is_array($allowedClasses)) {
            throw new Exception("Config file does not return an array of allowed classes.");
        }

        if (!in_array($className, $allowedClasses, true)) {
            throw new Exception("Unauthorized class: $className");
        }
    }

    private function validateMethod(string $className, string $methodName)
    {
        if (!method_exists($className, $methodName)) {
            throw new Exception("Method $methodName does not exist in class $className");
        }
    }

    private function logJobStatus(string $className, string $methodName, string $status, int $attempts, string $error = '')
    {
        $logMessage = [
            'class' => $className,
            'method' => $methodName,
            'status' => $status,
            'attempts' => $attempts,
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $error,
        ];

        // Get the correct log path manually (since we're not in a Laravel environment)
        $logDirectory = __DIR__ . '/logs'; // Adjust this if needed
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, 0777, true); // Create the logs directory if it doesn't exist
        }

        $logFile = $status === 'success' ? self::LOG_FILE : self::ERROR_LOG_FILE;
        file_put_contents($logDirectory . '/' . $logFile, json_encode($logMessage) . PHP_EOL, FILE_APPEND);
    }
}

// Parse command-line arguments
$options = getopt('', ['class:', 'method:', 'params:']);
$className = $options['class'] ?? '';
$methodName = $options['method'] ?? '';

// Ensure parameters are passed as a valid JSON string and decoded into an array
$parameters = isset($options['params']) ? json_decode($options['params'], true) : [];

if (!is_array($parameters)) {
    $parameters = []; // Set to empty array if invalid or not passed
}

// Run the job if class and method are provided
if ($className && $methodName) {
    $runner = new BackgroundJobRunner();
    $runner->run($className, $methodName, $parameters);
} else {
    echo "Error: Both class and method parameters are required.\n";
    exit(1);
}
