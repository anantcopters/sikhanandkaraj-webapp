<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminMemberActivityModel;
use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use App\Services\Matchmaking\MemberMatchScoreService;
use App\Services\Matchmaking\MemberProfilePresentationService;
use App\Services\Matchmaking\PartnerPreferenceMatchService;
use App\Services\Matchmaking\PartnerPreferencePresentationService;
use App\Services\Profile\MemberPhotoUrlService;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;

final class AdminMemberActivityService
{
    public const INTEREST_RECEIVED =
    'interest-received';

    public const INTEREST_SENT =
    'interest-sent';

    public const PROFILES_SHORTLISTED =
    'profiles-shortlisted';

    public const SHORTLISTED_BY =
    'shortlisted-by';

    public const INTERESTS_ACCEPTED =
    'interests-accepted';

    public const INTERESTS_REJECTED =
    'interests-rejected';

    public const PROFILES_VIEWED =
    'profiles-viewed';

    public const PROFILE_VIEWED_BY =
    'profile-viewed-by';

    public const VIDEOS_WATCHED =
    'videos-watched';

    public const VIDEO_VIEWED_BY =
    'video-viewed-by';

    public const MUTUAL_INTERESTS =
    'mutual-interests';

    public const PROFILES_BLOCKED =
    'profiles-blocked';

    public const BLOCKED_BY =
    'blocked-by';

    private const PAGER_GROUP =
    'adminMemberActivity';

    /**
     * @var array<string, array{
     *     label:string,
     *     description:string,
     *     icon:string
     * }>
     */
    private const ACTIVITIES = [
        self::INTEREST_RECEIVED => [
            'label' =>
            'Interest Received',

            'description' =>
            'Members who sent Interest to this member.',

            'icon' =>
            'ri-mail-download-line',
        ],

        self::INTEREST_SENT => [
            'label' =>
            'Interest Sent',

            'description' =>
            'Members to whom this member sent Interest.',

            'icon' =>
            'ri-send-plane-line',
        ],

        self::PROFILES_SHORTLISTED => [
            'label' =>
            'Profiles Shortlisted',

            'description' =>
            'Profiles shortlisted by this member.',

            'icon' =>
            'ri-bookmark-line',
        ],

        self::SHORTLISTED_BY => [
            'label' =>
            'Shortlisted By',

            'description' =>
            'Members who shortlisted this profile.',

            'icon' =>
            'ri-bookmark-3-line',
        ],

        self::INTERESTS_ACCEPTED => [
            'label' =>
            'Interests Accepted',

            'description' =>
            'Incoming Interests accepted by this member.',

            'icon' =>
            'ri-checkbox-circle-line',
        ],

        self::INTERESTS_REJECTED => [
            'label' =>
            'Interests Rejected',

            'description' =>
            'Incoming Interests rejected by this member.',

            'icon' =>
            'ri-close-circle-line',
        ],

        self::PROFILES_VIEWED => [
            'label' =>
            'Profiles Viewed',

            'description' =>
            'Unique profiles viewed by this member.',

            'icon' =>
            'ri-eye-line',
        ],

        self::PROFILE_VIEWED_BY => [
            'label' =>
            'Profile Viewed By',

            'description' =>
            'Unique members who viewed this profile.',

            'icon' =>
            'ri-user-follow-line',
        ],

        self::VIDEOS_WATCHED => [
            'label' =>
            'Videos Watched',

            'description' =>
            'Members whose Live Introduction was watched by this member.',

            'icon' =>
            'ri-video-line',
        ],

        self::VIDEO_VIEWED_BY => [
            'label' =>
            'Video Viewed By',

            'description' =>
            'Members who watched this member\'s Live Introduction.',

            'icon' =>
            'ri-movie-line',
        ],

        self::MUTUAL_INTERESTS => [
            'label' =>
            'Mutual Interests',

            'description' =>
            'Members with an accepted Interest relationship.',

            'icon' =>
            'ri-heart-2-line',
        ],

        self::PROFILES_BLOCKED => [
            'label' =>
            'Profiles Blocked',

            'description' =>
            'Members blocked by this member.',

            'icon' =>
            'ri-forbid-line',
        ],

        self::BLOCKED_BY => [
            'label' =>
            'Blocked By',

            'description' =>
            'Members who blocked this profile.',

            'icon' =>
            'ri-user-forbid-line',
        ],
    ];

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly AdminMemberActivityModel
        $activityModel,

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

        private readonly PartnerPreferencePresentationService
        $partnerPreferencePresentationService
    ) {}

    /**
     * @return array<string, int>
     */
    public function countsForMember(
        int $memberUserId
    ): array {
        $this->member(
            $memberUserId
        );

        $counts = [];

        foreach (
            array_keys(
                self::ACTIVITIES
            ) as $activityType
        ) {
            $counts[$activityType] =
                count(
                    $this->activityModel
                        ->memberIds(
                            $memberUserId,
                            $activityType
                        )
                );
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    public function paginatedActivity(
        int $memberUserId,
        string $activityType,
        string $search,
        int $page,
        int $perPage = 9
    ): array {
        $member = $this->member(
            $memberUserId
        );

        $activity =
            $this->activityDefinition(
                $activityType
            );

        $memberIds =
            $this->activityModel
            ->memberIds(
                $memberUserId,
                $activityType
            );

        $candidates =
            $this->candidateModel
            ->adminCandidatesByIds(
                $memberUserId,
                $memberIds
            );

        /*
         * Calculate Partner Preference using the same authority used by
         * Dashboard, Search and Admin Matches.
         */
        $candidates =
            $this->partnerPreferenceMatchService
            ->scoreCandidates(
                $memberUserId,
                $candidates
            );

        /*
         * Match Score uses the existing production scoring authority.
         */
        $candidates =
            $this->matchScoreService
            ->scoreCandidates(
                $candidates
            );

        $normalizedSearch =
            preg_replace(
                '/\s+/u',
                ' ',
                trim($search)
            ) ?? '';

        $normalizedSearch =
            mb_substr(
                $normalizedSearch,
                0,
                100
            );

        if ($normalizedSearch !== '') {
            $needle =
                mb_strtolower(
                    $normalizedSearch
                );

            $candidates =
                array_values(
                    array_filter(
                        $candidates,
                        static function (
                            array $candidate
                        ) use (
                            $needle
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
                                $needle
                            )
                                || str_contains(
                                    $name,
                                    $needle
                                );
                        }
                    )
                );
        }

        $safePerPage = 9;

        $total = count(
            $candidates
        );

        $totalPages = max(
            1,
            (int) ceil(
                $total
                    / $safePerPage
            )
        );

        $safePage = min(
            max(
                1,
                $page
            ),
            $totalPages
        );

        $pageRows =
            array_slice(
                $candidates,
                ($safePage - 1)
                    * $safePerPage,
                $safePerPage
            );

        $profiles = [];

        foreach ($pageRows as $candidate) {
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
             * Admin sees the actual stored member name.
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

            $profile['profileUrl'] =
                route_to(
                    'admin.members.view',
                    $candidateId
                );

            $partnerPercentage =
                max(
                    0,
                    min(
                        100,
                        (int) round(
                            (float) (
                                $candidate['match_percentage']
                                ?? 0
                            )
                        )
                    )
                );

            $matched =
                max(
                    0,
                    (int) (
                        $candidate['matched_preferences']
                        ?? 0
                    )
                );

            $preferenceTotal =
                max(
                    0,
                    (int) (
                        $candidate['total_preferences']
                        ?? 0
                    )
                );

            $criteria =
                isset(
                    $candidate['match_criteria']
                )
                && is_array(
                    $candidate['match_criteria']
                )
                ? $candidate['match_criteria']
                : [];

            $profile['partnerPreferencePercentage'] =
                $partnerPercentage;

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

            $profile['matchScoreComponents'] =
                isset(
                    $candidate['match_score_components']
                )
                && is_array(
                    $candidate['match_score_components']
                )
                ? $candidate['match_score_components']
                : [];

            $profile['matchScoreContributions'] =
                isset(
                    $candidate['match_score_contributions']
                )
                && is_array(
                    $candidate['match_score_contributions']
                )
                ? $candidate['match_score_contributions']
                : [];

            $profile['partnerPreferenceMatch'] = [
                'percentage' =>
                $partnerPercentage,

                'matched' =>
                $matched,

                'total' =>
                $preferenceTotal,

                'unmatched' =>
                max(
                    0,
                    $preferenceTotal
                        - $matched
                ),

                'criteria' =>
                $criteria,

                'displayItems' =>
                $this
                    ->partnerPreferencePresentationService
                    ->displayItems(
                        $memberUserId,
                        $criteria
                    ),
            ];

            $profiles[] =
                $profile;
        }

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

            'activityType' =>
            $activityType,

            'activity' =>
            $activity,

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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function member(
        int $memberUserId
    ): array {
        $member =
            $this->userModel
            ->findForAdmin(
                $memberUserId
            );

        if (!is_array($member)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $member;
    }

    /**
     * @return array{
     *     label:string,
     *     description:string,
     *     icon:string
     * }
     */
    private function activityDefinition(
        string $activityType
    ): array {
        if (
            !isset(
                self::ACTIVITIES[$activityType]
            )
        ) {
            throw new DomainException(
                'Invalid member activity.'
            );
        }

        return self::ACTIVITIES[$activityType];
    }
}
