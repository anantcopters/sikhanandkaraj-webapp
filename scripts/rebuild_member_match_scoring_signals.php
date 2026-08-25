<?php

declare(strict_types=1);

/**
 * Backfill / repair cached intrinsic member Match Score signals.
 *
 * Usage:
 *
 *     php scripts/rebuild_member_match_scoring_signals.php
 *
 * Safe to rerun.
 */

use App\Models\UserModel;
use App\Services\Matchmaking\MemberMatchScoringSignalService;

require dirname(__DIR__)
    . '/vendor/autoload.php';

$appStarter =
    require dirname(__DIR__)
    . '/app/Config/Paths.php';

$paths =
    new Config\Paths();

require $paths->systemDirectory
    . '/Boot.php';

CodeIgniter\Boot::bootConsole(
    $paths
);

/** @var MemberMatchScoringSignalService $service */
$service =
    service(
        'memberMatchScoringSignalService'
    );

$userModel =
    new UserModel();

$processed = 0;
$failed = 0;

$lastId = 0;

echo PHP_EOL;
echo 'Rebuilding member Match Score signals';
echo PHP_EOL;
echo '====================================';
echo PHP_EOL;

while (true) {
    /*
     * Keyset pagination avoids OFFSET becoming progressively slower as the
     * member table grows.
     */
    $members =
        $userModel
        ->select(
            'id'
        )
        ->where(
            'id >',
            $lastId
        )
        ->orderBy(
            'id',
            'ASC'
        )
        ->findAll(
            250
        );

    if ($members === []) {
        break;
    }

    foreach ($members as $member) {
        $userId = max(
            0,
            (int) (
                $member['id']
                ?? 0
            )
        );

        if ($userId <= 0) {
            continue;
        }

        $lastId =
            $userId;

        try {
            $service->refreshForUser(
                $userId
            );

            ++$processed;
        } catch (Throwable $exception) {
            ++$failed;

            fwrite(
                STDERR,
                'Failed member '
                    . $userId
                    . ': '
                    . $exception->getMessage()
                    . PHP_EOL
            );
        }
    }
}

echo 'Processed: '
    . $processed
    . PHP_EOL;

echo 'Failed:    '
    . $failed
    . PHP_EOL;

exit($failed > 0
    ? 1
    : 0);
