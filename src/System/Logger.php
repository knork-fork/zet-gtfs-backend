<?php
declare(strict_types=1);

namespace App\System;

enum LogLevel: string
{
    case EMERGENCY = 'EMERGENCY'; // System is unusable.
    case ALERT = 'ALERT';     // Action must be taken immediately.
    case CRITICAL = 'CRITICAL';  // Critical conditions.
    case ERROR = 'ERROR';     // Error conditions.
    case WARNING = 'WARNING';   // Warning conditions.
    case NOTICE = 'NOTICE';    // Normal but significant conditions.
    case INFO = 'INFO';      // Informational messages.
    case DEBUG = 'DEBUG';     // Debug-level messages.
}

final class Logger
{
    public static function emergency(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::EMERGENCY);
    }

    public static function alert(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::ALERT);
    }

    public static function critical(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::CRITICAL);
    }

    public static function error(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::ERROR);
    }

    public static function warning(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::WARNING);
    }

    public static function notice(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::NOTICE);
    }

    public static function info(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::INFO);
    }

    public static function debug(string $message, string $destination = 'debug'): void
    {
        self::log($message, $destination, LogLevel::DEBUG);
    }

    private static function log(string $message, string $destination, LogLevel $logLevel): void
    {
        $logDestination = \sprintf(
            '/var/log/php_%s.log',
            preg_replace('/[^a-zA-Z0-9]/', '_', $destination)
        );

        $logMessage = \sprintf(
            '[%s] php.%s PID=%d: %s' . \PHP_EOL,
            date(\DATE_RFC2822),
            $logLevel->value,
            getmypid(),
            $message
        );

        if (!error_log($logMessage, 3, $logDestination)) {
            error_log("Error in Logger::log(): could not write message to {$logDestination}: {$message}");
        }
    }
}
