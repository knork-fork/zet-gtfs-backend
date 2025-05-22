<?php
declare(strict_types=1);

namespace App;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Response\ExceptionResponse;
use App\Response\JsonResponse;
use App\Response\Response;
use KnorkFork\LoadEnvironment\Environment;

final class Router
{
    public const API_PREFIX = '/api';

    public static function getResponse(string $uri): Response
    {
        $cronPollInterval = Environment::getStringEnv('CRON_POLLING_INTERVAL_IN_SECONDS');
        $zetUrl = Environment::getStringEnv('ZET_URL');

        // ultra simple hardcoded routing, for now

        if ($uri === self::API_PREFIX . '/status') {
            return new JsonResponse(['status' => 'up']);
        }

        if ($uri === self::API_PREFIX . '/info') {
            return new JsonResponse([
                'description' => 'GTFS-RT to JSON converter',
                'polling_interval_in_seconds' => $cronPollInterval,
                'zet_url' => $zetUrl,
            ]);
        }

        if ($uri === self::API_PREFIX . '/get_data') {
            // TO-DO: move this to cron, don't interact with third party directly
            $json = shell_exec(
                '/opt/venv/bin/python /application/scripts/gtfs2json.py '
                . escapeshellarg($zetUrl)
            );

            if (!\is_string($json)) {
                return new ExceptionResponse(
                    new BadRequestException(
                        \sprintf('Error while executing script: %s', $json)
                    )
                );
            }
            $jsonObject = json_decode($json, true);
            if (json_last_error() !== \JSON_ERROR_NONE || !\is_array($jsonObject)) {
                return new ExceptionResponse(
                    new BadRequestException(
                        \sprintf('Error while decoding JSON: %s', json_last_error_msg())
                    )
                );
            }

            return new JsonResponse($jsonObject);
        }

        return new ExceptionResponse(
            new NotFoundException(
                \sprintf('Route %s not found', $uri)
            )
        );
    }
}
