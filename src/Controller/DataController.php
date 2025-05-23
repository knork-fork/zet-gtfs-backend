<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Response\JsonResponse;
use KnorkFork\LoadEnvironment\Environment;

final class DataController
{
    public static function getAllData(): JsonResponse
    {
        $zetUrl = Environment::getStringEnv('ZET_URL');

        // TO-DO: move this to cron, don't interact with third party directly
        $json = shell_exec(
            '/opt/venv/bin/python /application/scripts/gtfs2json.py '
            . escapeshellarg($zetUrl)
        );

        if (!\is_string($json)) {
            throw new BadRequestException(\sprintf('Error while executing script: %s', $json));
        }
        $jsonObject = json_decode($json, true);
        if (json_last_error() !== \JSON_ERROR_NONE || !\is_array($jsonObject)) {
            throw new BadRequestException(\sprintf('Error while decoding JSON: %s', json_last_error_msg()));
        }

        return new JsonResponse($jsonObject);
    }
}
