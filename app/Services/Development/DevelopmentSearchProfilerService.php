<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Models\UserModel;
use App\Services\Matchmaking\MemberSearchService;
use DomainException;
use RuntimeException;

/**
 * Development-only Search performance profiler.
 *
 * This service does not implement Search.
 *
 * It invokes the normal MemberSearchService so profiling always measures the
 * same Search architecture used by authenticated members.
 */
final class DevelopmentSearchProfilerService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberSearchService
        $searchService
    ) {}

    /**
     * Profile one Search request.
     *
     * @param array<string, mixed> $input
     *
     * @return array{
     *     memberId:int,
     *     profileReference:string,
     *     mode:string,
     *     sort:string,
     *     elapsedMs:float,
     *     resultCount:int,
     *     total:int,
     *     page:int
     * }
     */
    public function profile(
        int $memberId,
        array $input
    ): array {
        $this->assertAllowedEnvironment();

        if ($memberId <= 0) {
            throw new DomainException(
                'A valid member ID is required.'
            );
        }

        $member =
            $this->userModel
            ->find(
                $memberId
            );

        if (!is_array($member)) {
            throw new DomainException(
                'The requested member account could not be found.'
            );
        }

        /*
         * Measure the complete real Search pipeline.
         *
         * No diagnostic Search implementation is introduced here.
         */
        $startedAt =
            hrtime(
                true
            );

        $result =
            $this->searchService
            ->search(
                $memberId,
                $input
            );

        $finishedAt =
            hrtime(
                true
            );

        $profiles =
            is_array(
                $result['profiles']
                    ?? null
            )
            ? $result['profiles']
            : [];

        return [
            'memberId' =>
            $memberId,

            'profileReference' =>
            trim(
                (string) (
                    $member['profile_reference']
                    ?? ''
                )
            ),

            'mode' =>
            (string) (
                $result['mode']
                ?? 'basic'
            ),

            'sort' =>
            (string) (
                $result['sort']
                ?? 'match'
            ),

            'elapsedMs' =>
            round(
                (
                    $finishedAt
                    - $startedAt
                )
                    / 1_000_000,
                3
            ),

            'resultCount' =>
            count(
                $profiles
            ),

            'total' =>
            max(
                0,
                (int) (
                    $result['total']
                    ?? 0
                )
            ),

            'page' =>
            max(
                1,
                (int) (
                    $result['page']
                    ?? 1
                )
            ),
        ];
    }

    /**
     * Prevent this diagnostic service from being used in production.
     */
    private function assertAllowedEnvironment(): void
    {
        $deployment =
            strtolower(
                trim(
                    (string) env(
                        'APP_DEPLOYMENT',
                        ''
                    )
                )
            );

        if (
            !in_array(
                $deployment,
                [
                    'development',
                    'qa',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Search profiling is available only in development or QA.'
            );
        }

        if (
            filter_var(
                env(
                    'DEVELOPMENT_SEARCH_PROFILER_ENABLED',
                    false
                ),
                FILTER_VALIDATE_BOOLEAN
            ) !== true
        ) {
            throw new RuntimeException(
                'Development Search profiling is disabled.'
            );
        }
    }
}
