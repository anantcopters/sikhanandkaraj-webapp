<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use App\Services\Matchmaking\MemberMatchScoreService;
use App\Services\Matchmaking\MemberProfilePresentationService;
use App\Services\Matchmaking\PartnerPreferenceMatchService;
use App\Services\Profile\MemberPhotoUrlService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Matchmaking;

/**
 * Builds the administrator-only Match listing for one member.
 *
 * IMPORTANT:
 *
 * This service does not implement a second matchmaking algorithm.
 *
 * Existing production authorities remain responsible for:
 *
 * - candidate eligibility;
 * - blocking;
 * - account visibility;
 * - Partner Preference matching;
 * - compulsory preference rules;
 * - minimum Match percentage;
 * - final Match Score;
 * - common card presentation.
 */
final class AdminMemberMatchesService
{
    public const SORT_MATCH_SCORE =
    'match_score';

    public const SORT_PARTNER_PREFERENCE =
    'partner_preference';

    private const PAGER_GROUP =
    'adminMemberMatches';

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly PartnerPreferenceMatchService
        $partnerPreferenceMatchService,

        private readonly MemberMatchScoreService
        $matchScoreService,

        private readonly MemberProfilePresentationService
        $profilePresentationService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly Matchmaking
        $configuration
    ) {}

    /**
     * Return the administrator Match page contract.
     *
     * @return array<string, mixed>
     */
    public function paginatedMatches(
        int $memberUserId,
        string $search,
        string $sort,
        int $page,
        int $perPage = 9
    ): array {
        if ($memberUserId <= 0) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $member = $this->userModel
            ->findForAdmin(
                $memberUserId
            );

        if (!is_array($member)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $normalizedSearch =
            $this->normalizeSearch(
                $search
            );

        $normalizedSort =
            $this->normalizeSort(
                $sort
            );

        $safePage = max(
            1,
            $page
        );

        /*
         * Requirement is a 3 x 3 card page.
         */
        $safePerPage = 9;

        $viewerGender = trim(
            (string) (
                $member['gender']
                ?? ''
            )
        );

        /*
         * Reuse exactly the same candidate eligibility projection used by
         * Dashboard and Search.
         */
        $candidateRows =
            $this->candidateModel
            ->eligibleCandidates(
                $memberUserId,
                $viewerGender
            );

        /*
         * Reuse the production Partner Preference algorithm.
         *
         * This also keeps compulsory-preference behaviour aligned with
         * member-facing matchmaking.
         */
        $preferenceScored =
            $this->partnerPreferenceMatchService
            ->scoreCandidates(
                $memberUserId,
                $candidateRows
            );

        $minimumPercentage =
            $this->configuration
            ->minimumMatchPercentage;

        /*
         * This is the existing All Matches qualification rule:
         *
         * - at least one configured preference;
         * - compulsory preference checks have already been applied;
         * - percentage >= configured minimum.
         */
        $qualified = array_values(
            array_filter(
                $preferenceScored,
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
                        (float) (
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
        * Score once here because Partner Preference ordering still needs the
        * final Match Score as its deterministic secondary order.
        */
        $scored =
            $this->matchScoreService
            ->scoreCandidates(
                $qualified
            );

        /*
         * Admin search is intentionally restricted to the already-qualified
         * Match pool.
         *
         * Searching cannot make an otherwise ineligible profile appear.
         */
        if ($normalizedSearch !== '') {
            $searchNeedle =
                mb_strtolower(
                    $normalizedSearch
                );

            $scored = array_values(
                array_filter(
                    $scored,
                    static function (
                        array $candidate
                    ) use (
                        $searchNeedle
                    ): bool {
                        $reference =
                            mb_strtolower(
                                trim(
                                    (string) (
                                        $candidate['profile_ref_number']
                                        ?? ''
                                    )
                                )
                            );

                        $name =
                            mb_strtolower(
                                trim(
                                    (string) (
                                        $candidate['full_name']
                                        ?? ''
                                    )
                                )
                            );

                        return str_contains(
                            $reference,
                            $searchNeedle
                        )
                            || str_contains(
                                $name,
                                $searchNeedle
                            );
                    }
                )
            );
        }

        /*
         * The same candidate pool supports both requested orderings.
         */
        if (
            $normalizedSort
            === self::SORT_PARTNER_PREFERENCE
        ) {
            $this->sortByPartnerPreference(
                $scored
            );
        } else {

            /*
            * rankCandidates() is the canonical ranking authority.
            *
            * Use the qualified collection here because it attaches the score and
            * applies all established Match Score tie-break rules in one operation.
            */
            $scored =
                $this->matchScoreService
                ->rankCandidates(
                    $qualified
                );
            /*
             * rankCandidates() scores the collection itself.
             * Apply the Admin search afterwards when Match Score is selected.
             */
            if ($normalizedSearch !== '') {
                $searchNeedle =
                    mb_strtolower(
                        $normalizedSearch
                    );

                $scored = array_values(
                    array_filter(
                        $scored,
                        static function (
                            array $candidate
                        ) use (
                            $searchNeedle
                        ): bool {
                            $reference =
                                mb_strtolower(
                                    trim(
                                        (string) (
                                            $candidate['profile_ref_number']
                                            ?? ''
                                        )
                                    )
                                );

                            $name =
                                mb_strtolower(
                                    trim(
                                        (string) (
                                            $candidate['full_name']
                                            ?? ''
                                        )
                                    )
                                );

                            return str_contains(
                                $reference,
                                $searchNeedle
                            )
                                || str_contains(
                                    $name,
                                    $searchNeedle
                                );
                        }
                    )
                );
            }
        }

        $total = count(
            $scored
        );

        $totalPages = max(
            1,
            (int) ceil(
                $total
                    / $safePerPage
            )
        );

        $safePage = min(
            $safePage,
            $totalPages
        );

        $offset =
            ($safePage - 1)
            * $safePerPage;

        $pageRows = array_slice(
            $scored,
            $offset,
            $safePerPage
        );

        $profiles = [];

        /*
         * Only the current nine-card page requires signed photographs.
         *
         * Admin uses the existing internal/Admin photo flow, therefore
         * member-facing PUBLIC/INTERESTED_MEMBERS restrictions are not
         * incorrectly applied to administrator review.
         */
        foreach ($pageRows as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $candidateId = max(
                0,
                (int) (
                    $candidate['id']
                    ?? 0
                )
            );

            if ($candidateId <= 0) {
                continue;
            }

            $image =
                $this->photoUrlService
                ->getApprovedPrimaryUrl(
                    $candidateId,
                    'thumbnail'
                );

            /*
             * Reuse the common ProfileCard presentation contract.
             *
             * Supplying resolvedImage prevents another photo lookup.
             */
            $profile =
                $this->profilePresentationService
                ->summary(
                    viewerUserId: $memberUserId,

                    member: $candidate,

                    hasInterestRelationship: false,

                    resolvedImage: $image
                );

            if (!is_array($profile)) {
                continue;
            }

            /*
             * Admin may see the complete member name.
             *
             * MemberProfilePresentationService intentionally masks another
             * female member's name for normal member-facing screens.
             */
            $fullName = trim(
                (string) (
                    $candidate['full_name']
                    ?? ''
                )
            );

            if ($fullName !== '') {
                $profile['name'] =
                    $fullName;
            }

            /*
             * Admin View Profile must use the Admin route and must not pass
             * through member membership/profile-access policies.
             */
            $profile['profileUrl'] =
                route_to(
                    'admin.members.view',
                    $candidateId
                );

            $profile['partnerPreferencePercentage'] = max(
                0.0,
                min(
                    100.0,
                    (float) (
                        $candidate['match_percentage']
                        ?? 0
                    )
                )
            );

            $profile['matchScore'] =
                max(
                    0.0,
                    min(
                        100.0,
                        (float) (
                            $candidate['match_score']
                            ?? 0
                        )
                    )
                );

            $profiles[] =
                $profile;
        }

        /*
         * Populate the standard CI Pager group so the existing reusable
         * Components/Pagination.php can be used unchanged.
         */
        $pager = service(
            'pager'
        );

        $pager->makeLinks(
            $safePage,
            $safePerPage,
            $total,
            'default_full',
            0,
            self::PAGER_GROUP
        );

        return [
            'member' =>
            $member,

            'profiles' =>
            $profiles,

            'pager' =>
            $pager,

            'pagerGroup' =>
            self::PAGER_GROUP,

            'page' =>
            $safePage,

            'perPage' =>
            $safePerPage,

            'total' =>
            $total,

            'search' =>
            $normalizedSearch,

            'sort' =>
            $normalizedSort,

            'minimumMatchPercentage' =>
            $minimumPercentage,
        ];
    }

    private function normalizeSearch(
        string $search
    ): string {
        $normalized =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $search
                )
            ) ?? '';

        return mb_substr(
            $normalized,
            0,
            100
        );
    }

    private function normalizeSort(
        string $sort
    ): string {
        $normalized =
            mb_strtolower(
                trim(
                    $sort
                )
            );

        return in_array(
            $normalized,
            [
                self::SORT_MATCH_SCORE,
                self::SORT_PARTNER_PREFERENCE,
            ],
            true
        )
            ? $normalized
            : self::SORT_MATCH_SCORE;
    }

    /**
     * Sort qualified candidates by Partner Preference percentage.
     *
     * Final Match Score remains the deterministic secondary order.
     *
     * @param list<array<string, mixed>> $candidates
     */
    private function sortByPartnerPreference(
        array &$candidates
    ): void {
        usort(
            $candidates,
            static function (
                array $left,
                array $right
            ): int {
                $comparison =
                    ((float) (
                        $right['match_percentage']
                        ?? 0
                    ))
                    <=>
                    ((float) (
                        $left['match_percentage']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison =
                    ((float) (
                        $right['match_score']
                        ?? 0
                    ))
                    <=>
                    ((float) (
                        $left['match_score']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                return ((int) (
                    $right['id']
                    ?? 0
                ))
                    <=>
                    ((int) (
                        $left['id']
                        ?? 0
                    ));
            }
        );
    }
}
