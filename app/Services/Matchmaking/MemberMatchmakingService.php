<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use App\Services\Profile\MemberPhotoUrlService;
use Config\Matchmaking;
use DateTimeImmutable;
use DomainException;
use Throwable;

/**
 * Produces member-facing matchmaking collections.
 *
 * Partner matching remains isolated in PartnerPreferenceMatchService.
 * This service coordinates:
 *
 * - eligible candidates;
 * - configurable minimum match percentage;
 * - New Match age;
 * - interests;
 * - profile views;
 * - viewer-authorized thumbnail URLs.
 */
final class MemberMatchmakingService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly PartnerPreferenceMatchService
        $matchService,

        private readonly MemberInteractionService
        $interactionService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly Matchmaking
        $configuration
    ) {}

    /**
     * Return all matchmaking collections required by the dashboard.
     *
     * @return array<string, mixed>
     */
    public function dashboardCollections(
        int $userId
    ): array {
        $viewer = $this->userModel
            ->find(
                $userId
            );

        if (!is_array($viewer)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $viewerGender = trim(
            (string) (
                $viewer['gender']
                ?? ''
            )
        );

        /*
         * Candidate eligibility already excludes:
         *
         * - the logged-in member;
         * - non-ACTIVE accounts;
         * - soft-deleted accounts;
         * - same-gender candidates under the current M/F model;
         * - blocked relationships in either direction.
         */
        $candidateRows = $this
            ->candidateModel
            ->eligibleCandidates(
                $userId,
                $viewerGender
            );

        /*
         * Product matching IP stays in the dedicated algorithm service.
         */
        $scoredCandidates = $this
            ->matchService
            ->scoreCandidates(
                $userId,
                $candidateRows
            );

        $minimumPercentage =
            $this->configuration
            ->minimumMatchPercentage;

        /*
         * A candidate is a match only when:
         *
         * 1. at least one structured preference is configured;
         * 2. compulsory preferences passed;
         * 3. score meets configured threshold.
         *
         * Compulsory failures have already been removed by
         * PartnerPreferenceMatchService.
         */
        $matchedCandidates = array_values(
            array_filter(
                $scoredCandidates,
                static function (
                    array $candidate
                ) use (
                    $minimumPercentage
                ): bool {
                    $totalPreferences = max(
                        0,
                        (int) (
                            $candidate['total_preferences']
                            ?? 0
                        )
                    );

                    $percentage = max(
                        0,
                        (int) (
                            $candidate['match_percentage']
                            ?? 0
                        )
                    );

                    return $totalPreferences > 0
                        && $percentage
                        >= $minimumPercentage;
                }
            )
        );

        /*
         * New Match is a subset of All Matches.
         */
        $newMatches = array_values(
            array_filter(
                $matchedCandidates,
                fn(array $candidate): bool =>
                $this->isNewMatch(
                    $candidate['created_at']
                        ?? null
                )
            )
        );

        /*
         * Interest/activity lists also pass through the shared visible
         * candidate query. A previously recorded interaction therefore
         * disappears from member-facing UI when either side blocks the other.
         */
        $interestReceived =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->interestReceivedIds(
                        $userId
                    )
            );

        $interestSent =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->interestSentIds(
                        $userId
                    )
            );

        $profileVisitors =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->profileVisitorIds(
                        $userId
                    )
            );

        $profilesViewed =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->profilesViewedIds(
                        $userId
                    )
            );

        return [
            'minimumMatchPercentage' =>
            $minimumPercentage,

            'newMatchDays' =>
            $this->configuration
                ->newMatchDays,

            'allMatches' =>
            $this->presentationProfiles(
                $userId,
                $matchedCandidates
            ),

            'newMatches' =>
            $this->presentationProfiles(
                $userId,
                $newMatches
            ),

            'interestReceived' =>
            $this->presentationProfiles(
                $userId,
                $interestReceived
            ),

            'interestSent' =>
            $this->presentationProfiles(
                $userId,
                $interestSent
            ),

            'profileVisitors' =>
            $this->presentationProfiles(
                $userId,
                $profileVisitors
            ),

            'profilesViewed' =>
            $this->presentationProfiles(
                $userId,
                $profilesViewed
            ),
        ];
    }

    /**
     * Return currently visible member rows while preserving the supplied
     * interaction ordering.
     *
     * @param list<int> $memberIds
     *
     * @return list<array<string, mixed>>
     */
    private function visibleRowsForIds(
        int $viewerUserId,
        string $viewerGender,
        array $memberIds
    ): array {
        return $this
            ->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                $viewerGender,
                $memberIds
            );
    }

    /**
     * Convert candidate rows into the contract consumed by Dashboard.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function presentationProfiles(
        int $viewerUserId,
        array $rows
    ): array {
        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $memberId = max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            );

            $profileReference = trim(
                (string) (
                    $row['profile_ref_number']
                    ?? ''
                )
            );

            if (
                $memberId <= 0
                || $profileReference === ''
            ) {
                continue;
            }

            /*
             * INTERESTED_MEMBERS photo visibility is satisfied by an
             * interest in either direction.
             */
            $hasInterestRelationship =
                $this
                ->interactionService
                ->hasInterestBetween(
                    $viewerUserId,
                    $memberId
                );

            /*
             * Project rule:
             * match/search/multi-profile listings use THUMBNAIL only.
             */
            $profileImage = $this
                ->photoUrlService
                ->getApprovedPrimaryUrlForViewer(
                    memberId: $memberId,

                    viewerUserId: $viewerUserId,

                    hasInterestRelationship: $hasInterestRelationship,

                    variant: 'thumbnail'
                );

            $result[] = [
                'referenceId' =>
                $profileReference,

                'name' =>
                trim(
                    (string) (
                        $row['full_name']
                        ?? 'Member'
                    )
                ),

                'age' =>
                $this->age(
                    $row['date_of_birth']
                        ?? null
                ),

                'city' =>
                trim(
                    (string) (
                        $row['city_name']
                        ?? ''
                    )
                ),

                'image' =>
                $profileImage,

                /*
                 * Never expose the internal numeric user ID in member URLs.
                 */
                'profileUrl' =>
                route_to(
                    'web.members.view',
                    $profileReference
                ),

                'matchPercentage' =>
                isset(
                    $row['match_percentage']
                )
                    && is_numeric(
                        $row['match_percentage']
                    )
                    ? (int) $row['match_percentage']
                    : null,
            ];
        }

        return $result;
    }

    /**
     * Determine whether a matched member falls inside the configured
     * New Match window.
     */
    private function isNewMatch(
        mixed $createdAt
    ): bool {
        $value = trim(
            (string) $createdAt
        );

        if ($value === '') {
            return false;
        }

        try {
            $created = new DateTimeImmutable(
                $value
            );

            $now = new DateTimeImmutable(
                'now'
            );

            /*
             * Do not treat a malformed future creation timestamp as new.
             */
            if ($created > $now) {
                return false;
            }

            $cutOff = $now->modify(
                '-'
                    . $this
                    ->configuration
                    ->newMatchDays
                    . ' days'
            );

            return $created >= $cutOff;
        } catch (Throwable) {
            return false;
        }
    }

    private function age(
        mixed $dateOfBirth
    ): ?int {
        $value = trim(
            (string) $dateOfBirth
        );

        if ($value === '') {
            return null;
        }

        try {
            $birthDate =
                DateTimeImmutable
                ::createFromFormat(
                    '!Y-m-d',
                    mb_substr(
                        $value,
                        0,
                        10
                    )
                );

            if (
                !$birthDate
                    instanceof DateTimeImmutable
            ) {
                return null;
            }

            $today =
                new DateTimeImmutable(
                    'today'
                );

            if ($birthDate > $today) {
                return null;
            }

            return $birthDate
                ->diff(
                    $today
                )
                ->y;
        } catch (Throwable) {
            return null;
        }
    }
}
