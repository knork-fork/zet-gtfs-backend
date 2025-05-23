<?php
declare(strict_types=1);

namespace App\System;

use App\Dto\AbstractRequestDto;
use App\Exception\BadRequestException;
use RuntimeException;
use Throwable;

final class ParameterLoader
{
    /**
     * @return string[]
     */
    public static function getUriParameters(string $path, string $uri): array
    {
        $pathParts = explode('/', ltrim($path, '/'));
        $uriParts = explode('/', ltrim($uri, '/'));

        $parameters = [];
        foreach ($pathParts as $index => $pathPart) {
            if ($pathPart === $uriParts[$index]) {
                continue;
            }

            if (str_starts_with($pathPart, '{') && str_ends_with($pathPart, '}')) {
                $parameters[] = $uriParts[$index];
            }
        }

        return $parameters;
    }

    public static function getDto(?string $dtoClass): AbstractRequestDto
    {
        if (!\is_string($dtoClass) || !class_exists($dtoClass)) {
            throw new RuntimeException('Invalid or missing DTO class.');
        }
        if (!is_subclass_of($dtoClass, AbstractRequestDto::class)) {
            throw new RuntimeException(\sprintf('%s must extend AbstractRequestDto', $dtoClass));
        }

        $requestBody = file_get_contents('php://input');
        if ($requestBody === false) {
            throw new BadRequestException('Failed to read request body.');
        }

        $data = json_decode($requestBody, true);
        if (!\is_array($data)) {
            throw new BadRequestException('Invalid JSON payload.');
        }

        try {
            $dto = self::loadDto($dtoClass, $data);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (str_starts_with($message, 'Too few arguments to function')) {
                $message = 'Too few arguments to function';
            } elseif (str_contains($message, 'must be of type')) {
                $message = 'Wrong type for argument';
            } elseif (str_contains($message, 'Unknown named parameter')) {
                $message = $message;
            } else {
                $message = 'Invalid arguments';
            }

            throw new BadRequestException('Bad Request: ' . $message);
        }

        return $dto;
    }

    /**
     * @param class-string<AbstractRequestDto> $dtoClass
     * @param array<mixed>                     $data
     */
    private static function loadDto(string $dtoClass, array $data): AbstractRequestDto
    {
        return new $dtoClass(...$data);
    }
}
