<?php
declare(strict_types=1);

namespace App\Service;

use KnorkFork\LoadEnvironment\Environment;

final class AppVersionService
{
    public const FRONTEND_COMMIT_FILENAME = '/application/var/cache/frontend_commit.txt';
    public const BACKEND_COMMIT_FILENAME = '/application/var/cache/backend_commit.txt';

    public static function addVersionInfoToCache(): void
    {
        // Cache frontend and backend commit hashes
        $frontendCommit = shell_exec(
            "git -c safe.directory=/application/frontend -C /application/frontend rev-parse HEAD | tr -d '\n'"
        );
        $backendCommit = shell_exec(
            "git -c safe.directory=/application -C /application rev-parse HEAD | tr -d '\n'"
        );
        file_put_contents(self::FRONTEND_COMMIT_FILENAME, $frontendCommit);
        file_put_contents(self::BACKEND_COMMIT_FILENAME, $backendCommit);
    }

    /**
     * @return array{
     *    'checkedOutFrontendRef': string,
     *    'checkedOutBackendRef': string,
     *    'should_update': bool
     * }
     */
    public static function getVersionInfoFromCache(): array
    {
        $frontendCommit = (string) file_get_contents(self::FRONTEND_COMMIT_FILENAME);
        $backendCommit = (string) file_get_contents(self::BACKEND_COMMIT_FILENAME);

        $frontendRepo = Environment::getStringEnv('FRONTEND_REPO');
        $backendRepo = Environment::getStringEnv('BACKEND_REPO');
        $commitFromRepoCommand = "git ls-remote %s HEAD | awk '{ print $1 }' | tr -d '\n'";
        $frontendCommitFromRepo = shell_exec(\sprintf($commitFromRepoCommand, $frontendRepo));
        $backendCommitFromRepo = shell_exec(\sprintf($commitFromRepoCommand, $backendRepo));

        $shouldUpdate = false;
        if ($frontendCommit !== $frontendCommitFromRepo) {
            $shouldUpdate = true;
        }
        if ($backendCommit !== $backendCommitFromRepo) {
            $shouldUpdate = true;
        }

        return [
            'checkedOutFrontendRef' => $frontendCommit,
            'checkedOutBackendRef' => $backendCommit,
            'should_update' => $shouldUpdate,
        ];
    }
}
