<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
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
 * - shortlists;
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

        private readonly MemberProfilePresentationService
        $profilePresentationService,

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
        * A candidate becomes an All Match only through the common Match rule.
        *
        * The same helper is used by the Search Matches menu implementation.
        */
        $matchedCandidates =
            array_values(
                array_filter(
                    $scoredCandidates,
                    fn(
                        array $candidate
                    ): bool =>
                    $this->qualifiesAsMatch(
                        $candidate,
                        $minimumPercentage
                    )
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
        // $interestReceived =
        //     $this->visibleRowsForIds(
        //         $userId,
        //         $viewerGender,
        //         $this
        //             ->interactionService
        //             ->interestReceivedIds(
        //                 $userId
        //             )
        //     );

        // $interestSent =
        //     $this->visibleRowsForIds(
        //         $userId,
        //         $viewerGender,
        //         $this
        //             ->interactionService
        //             ->interestSentIds(
        //                 $userId
        //             )
        //     );

        /*
        * Shortlist collections shown on Dashboard.
        *
        * These still pass through the common visible-candidate query,
        * therefore blocked, inactive, deleted or otherwise unavailable
        * profiles do not leak into the member-facing dashboard.
        */

        /*
        * Profiles that the logged-in member has shortlisted.
        */
        $profilesShortlistedByYou =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->shortlistedMemberIds(
                        $userId
                    )
            );

        /*
        * Members who have shortlisted the logged-in member.
        */
        $whoShortlistedYou =
            $this->visibleRowsForIds(
                $userId,
                $viewerGender,
                $this
                    ->interactionService
                    ->shortlistedByMemberIds(
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

        /*
        * Partner Preference setup progress.
        *
        * Use the exact matchmaking algorithm as the source of truth rather
        * than maintaining another list of preference fields for Dashboard.
        */
        $partnerPreferenceSetup =
            $this->matchService
            ->preferenceSetupSummary(
                $userId
            );

        return [
            'partnerPreferenceSetup' =>
            $partnerPreferenceSetup,

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

            // 'interestReceived' =>
            // $this->presentationProfiles(
            //     $userId,
            //     $interestReceived
            // ),

            // 'interestSent' =>
            // $this->presentationProfiles(
            //     $userId,
            //     $interestSent
            // ),

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

            'profilesShortlistedByYou' =>
            $this->presentationProfiles(
                $userId,
                $profilesShortlistedByYou
            ),

            'whoShortlistedYou' =>
            $this->presentationProfiles(
                $userId,
                $whoShortlistedYou
            ),
        ];
    }

    /**
     * Return eligible member IDs that qualify as All Matches.
     *
     * This uses exactly the same Partner Preference matching definition as the
     * Dashboard All Matches collection:
     *
     * - normal candidate eligibility;
     * - logged-in member's Partner Preferences;
     * - compulsory preferences must pass;
     * - at least one structured preference must exist;
     * - configured minimum match percentage must be met.
     *
     * Only IDs are returned because Search Results performs its own paginated
     * candidate projection and presentation.
     *
     * @return list<int>
     */
    public function allMatchCandidateIds(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        /*
     * ----------------------------------------------------------------------
     * Resolve authenticated member
     * ----------------------------------------------------------------------
     */

        $viewer =
            $this->userModel
            ->find(
                $userId
            );

        if (!is_array($viewer)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $viewerGender =
            trim(
                (string) (
                    $viewer['gender']
                    ?? ''
                )
            );

        /*
     * ----------------------------------------------------------------------
     * Resolve common eligible candidates
     * ----------------------------------------------------------------------
     *
     * Candidate eligibility already handles ACTIVE/deleted/self/gender/block
     * restrictions centrally.
     */

        $candidateRows =
            $this->candidateModel
            ->eligibleCandidates(
                $userId,
                $viewerGender
            );

        if ($candidateRows === []) {
            return [];
        }

        /*
     * ----------------------------------------------------------------------
     * Score against existing Partner Preferences
     * ----------------------------------------------------------------------
     */

        $scoredCandidates =
            $this->matchService
            ->scoreCandidates(
                $userId,
                $candidateRows
            );

        $minimumPercentage =
            $this->configuration
            ->minimumMatchPercentage;

        $memberIds = [];

        foreach (
            $scoredCandidates
            as $candidate
        ) {
            if (!is_array($candidate)) {
                continue;
            }

            if (
                !$this->qualifiesAsMatch(
                    $candidate,
                    $minimumPercentage
                )
            ) {
                continue;
            }

            $memberId =
                max(
                    0,
                    (int) (
                        $candidate['id']
                        ?? 0
                    )
                );

            if ($memberId > 0) {
                $memberIds[] =
                    $memberId;
            }
        }

        return array_values(
            array_unique(
                $memberIds
            )
        );
    }

    /**
     * Calculate how well the viewed member satisfies the
     * logged-in member's Partner Preferences.
     *
     * This intentionally uses the exact same direction as
     * Dashboard All Matches:
     *
     * logged-in member's Partner Preferences
     *                  ↓
     *          viewed member profile
     *
     * Therefore:
     *
     * Dashboard match %
     *        ===
     * Profile View match %
     *
     * @return array{
     *     percentage:int,
     *     matched:int,
     *     total:int,
     *     unmatched:int,
     *     passesCompulsory:bool
     * }
     */
    public function profilePreferenceMatch(
        int $viewerUserId,
        int $viewedUserId
    ): array {
        $emptyResult = [
            'percentage' => 0,
            'matched' => 0,
            'total' => 0,
            'unmatched' => 0,
            'passesCompulsory' => true,
        ];

        if (
            $viewerUserId <= 0
            || $viewedUserId <= 0
            || $viewerUserId === $viewedUserId
        ) {
            return $emptyResult;
        }

        /*
     * Load the logged-in member because the common candidate
     * query uses their gender when determining visible candidates.
     */
        $viewer = $this
            ->userModel
            ->find(
                $viewerUserId
            );

        if (!is_array($viewer)) {
            return $emptyResult;
        }

        $viewerGender = trim(
            (string) (
                $viewer['gender']
                ?? ''
            )
        );

        /*
     * Retrieve the viewed member through the exact same
     * candidate projection used by Dashboard matching.
     *
     * This preserves:
     *
     * - active-account filtering;
     * - deleted-account filtering;
     * - gender eligibility;
     * - block filtering;
     * - the same candidate columns used by the scoring engine.
     */
        $candidateRows = $this
            ->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                $viewerGender,
                [
                    $viewedUserId,
                ]
            );

        if ($candidateRows === []) {
            return $emptyResult;
        }

        $viewedCandidate =
            $candidateRows[0];

        /*
     * IMPORTANT:
     *
     * Use the logged-in member's Partner Preferences.
     *
     * This is the same direction used by:
     *
     * scoreCandidates(
     *     $viewerUserId,
     *     $candidateRows
     * )
     *
     * on Dashboard.
     */
        $score = $this
            ->matchService
            ->scoreProfile(
                $viewerUserId,
                $viewedCandidate
            );

        $matched = max(
            0,
            (int) (
                $score['matched']
                ?? 0
            )
        );

        $total = max(
            0,
            (int) (
                $score['total']
                ?? 0
            )
        );

        return [
            'percentage' =>
            max(
                0,
                min(
                    100,
                    (int) (
                        $score['percentage']
                        ?? 0
                    )
                )
            ),

            'matched' =>
            $matched,

            'total' =>
            $total,

            'unmatched' =>
            max(
                0,
                $total - $matched
            ),

            'passesCompulsory' => (
                $score['passesCompulsory']
                ?? false
            ) === true,
        ];
    }

    /**
     * Determine whether one scored candidate qualifies as a Match.
     *
     * Dashboard All Matches and Search All Matches must use this same rule so
     * their definitions cannot drift.
     *
     * @param array<string, mixed> $candidate
     */
    private function qualifiesAsMatch(
        array $candidate,
        int $minimumPercentage
    ): bool {
        $totalPreferences =
            max(
                0,
                (int) (
                    $candidate['total_preferences']
                    ?? 0
                )
            );

        $percentage =
            max(
                0,
                (int) (
                    $candidate['match_percentage']
                    ?? 0
                )
            );

        /*
     * PartnerPreferenceMatchService has already removed compulsory failures.
     */
        return $totalPreferences > 0
            && $percentage
            >= $minimumPercentage;
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
     * Convert visible candidate rows into the common member presentation
     * contract consumed by Dashboard.
     *
     * Dashboard adds only its own match-percentage context to the shared
     * member-summary contract.
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

        foreach (
            $rows
            as $row
        ) {
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

            if ($memberId <= 0) {
                continue;
            }

            /*
         * INTERESTED_MEMBERS photo visibility is satisfied by an
         * Interest relationship in either direction.
         */
            $hasInterestRelationship =
                $this
                ->interactionService
                ->hasInterestBetween(
                    $viewerUserId,
                    $memberId
                );

            $profile =
                $this
                ->profilePresentationService
                ->summary(
                    viewerUserId: $viewerUserId,
                    member: $row,
                    hasInterestRelationship: $hasInterestRelationship
                );

            if ($profile === null) {
                continue;
            }

            /*
         * Match percentage belongs specifically to matchmaking context,
         * not to the generic member-summary contract.
         */
            $profile['matchPercentage'] =
                isset(
                    $row['match_percentage']
                )
                && is_numeric(
                    $row['match_percentage']
                )
                ? max(
                    0,
                    min(
                        100,
                        (int)
                        $row['match_percentage']
                    )
                )
                : null;

            $result[] =
                $profile;
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
}
