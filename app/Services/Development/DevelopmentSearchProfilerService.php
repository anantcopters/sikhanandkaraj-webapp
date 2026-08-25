<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Models\UserModel;
use App\Services\Matchmaking\MemberSearchService;
use App\Support\Development\PerformanceTimeline;
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
     * Profile one real Search request.
     *
     * Membership-27 extends the existing total Search measurement with named
     * application-pipeline checkpoints.
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
     *     page:int,
     *     stages:list<array{
     *         name:string,
     *         elapsedMs:float,
     *         totalMs:float
     *     }>
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
     * Membership-27.
     *
     * The timeline is created only by this development profiler.
     * Normal HTTP Search never creates or passes it.
     */
        $timeline =
            new PerformanceTimeline();

        $result =
            $this->searchService
            ->search(
                $memberId,
                $input,
                $timeline
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
                    $member['profile_ref_number']
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
            $timeline->totalElapsedMs(),

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

            'stages' =>
            $timeline->checkpoints(),
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
