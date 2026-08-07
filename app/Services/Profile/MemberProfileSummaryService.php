<?php

declare(strict_types=1);

namespace App\Services\Profile;

/**
 * Builds the authenticated member's complete profile-summary dataset.
 *
 * This service is the single orchestration point used by:
 *
 * - The Complete Your Profile page.
 * - The member dashboard profile-completion card.
 *
 * Individual profile services remain responsible for retrieving and
 * calculating their respective section data.
 */
final class MemberProfileSummaryService
{
    public function __construct(
        private readonly BasicDetailsService $basicDetailsService,
        private readonly EducationProfessionService $educationProfessionService,
        private readonly FamilyDetailsService $familyDetailsService,
        private readonly LifestyleService $lifestyleService,
        private readonly AboutMeService $aboutMeService,
        private readonly MemberPhotoService $memberPhotoService,
        private readonly MemberPhotoUrlService $memberPhotoUrlService,
        private readonly ProfileCompletionService $profileCompletionService
    ) {}

    /**
     * Return the complete profile-summary dataset for a member.
     *
     * By default the member's approved primary medium image is resolved because
     * this service is primarily used by owner-facing Profile and Dashboard pages.
     *
     * Another-member flows must pass $resolveProfileImage = false. Those flows
     * have a different authorization context and must resolve the image only after
     * relationship/block/photo-visibility authorization has succeeded.
     *
     * @return array<string, mixed>
     */
    public function getForUser(
        int $userId,
        bool $resolveProfileImage = true
    ): array {
        $basicProfile = $this
            ->basicDetailsService
            ->getForUser(
                $userId
            );

        $educationProfile = $this
            ->educationProfessionService
            ->getForUser(
                $userId
            );

        $familyProfile = $this
            ->familyDetailsService
            ->getForUser(
                $userId
            );

        $lifestyleProfile = $this
            ->lifestyleService
            ->getForUser(
                $userId
            );

        $aboutMeProfile = $this
            ->aboutMeService
            ->getForUser(
                $userId
            );

        $photoSummary = $this
            ->memberPhotoService
            ->getPhotoSummary(
                $userId
            );

        /*
     * Overall completion continues to use the existing central
     * ProfileCompletionService.
     */
        $profileCompletion = $this
            ->profileCompletionService
            ->calculate(
                $basicProfile['completion'],

                $educationProfile['completion'],

                $familyProfile['completion'],

                $lifestyleProfile['completion'],

                $aboutMeProfile['completion'],

                (
                    $photoSummary['hasUploadedPhoto']
                    ?? false
                ) === true
            );

        /*
     * Owner-oriented callers resolve the approved primary medium image
     * exactly as they do today.
     *
     * Another-member callers explicitly disable this because they need
     * viewer-specific visibility authorization before a signed URL can
     * be generated.
     */
        $profileImage = '';

        if ($resolveProfileImage) {
            $profileImage = $this
                ->memberPhotoUrlService
                ->getApprovedPrimaryUrl(
                    $userId,
                    'medium'
                );
        }

        $profileSections = $this
            ->buildProfileSections(
                $basicProfile['completion'],

                $educationProfile['completion'],

                $familyProfile['completion'],

                $lifestyleProfile['completion'],

                $aboutMeProfile['completion'],

                (
                    $photoSummary['hasUploadedPhoto']
                    ?? false
                ) === true
            );

        return [
            'user' =>
            $basicProfile['user'],

            'basicDetails' =>
            $basicProfile['basicDetails'],

            'basicDetailsCompletion' =>
            $basicProfile['completion'],

            'educationProfession' =>
            $educationProfile['educationProfession'],

            'educationProfessionCompletion' =>
            $educationProfile['completion'],

            'familyDetails' =>
            $familyProfile['familyDetails'],

            'familyDetailsCompletion' =>
            $familyProfile['completion'],

            'lifestyleDetails' =>
            $lifestyleProfile['selectedDetails'],

            'lifestyleCompletion' =>
            $lifestyleProfile['completion'],

            'aboutMe' =>
            $aboutMeProfile['aboutMe'],

            'aboutMeCompletion' =>
            $aboutMeProfile['completion'],

            'photoSummary' =>
            $photoSummary,

            'profileImage' =>
            $profileImage,

            'profileCompletion' =>
            $profileCompletion,

            'overallProfileSummary' =>
            $this
                ->buildOverallProfileSummary(
                    $profileCompletion,
                    $profileImage,
                    $photoSummary
                ),

            'profileSections' =>
            $profileSections,

            'nextProfileSection' =>
            $this
                ->resolveNextProfileSection(
                    $profileSections
                ),
        ];
    }

    /**
     * Build presentation-ready profile section records.
     *
     * @param array<string, mixed> $basicCompletion
     * @param array<string, mixed> $educationCompletion
     * @param array<string, mixed> $familyCompletion
     * @param array<string, mixed> $lifestyleCompletion
     * @param array<string, mixed> $aboutMeCompletion
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildProfileSections(
        array $basicCompletion,
        array $educationCompletion,
        array $familyCompletion,
        array $lifestyleCompletion,
        array $aboutMeCompletion,
        bool $hasUploadedPhoto
    ): array {
        return [
            $this->buildProfileSection(
                title: 'Basic Details',
                description: 'Review your personal and basic profile information.',
                icon: 'ri-user-line',
                routeName: 'web.profile.basic-details',
                completion: $basicCompletion,
                cardClass: 'bg-primary-subtle shadow-none bg-opacity-10',
                journeyOrder: 1
            ),

            $this->buildProfileSection(
                title: 'Education & Profession',
                description: 'Complete your education and career information.',
                icon: 'ri-graduation-cap-line',
                routeName: 'web.profile.education-profession',
                completion: $educationCompletion,
                cardClass: 'bg-success-subtle shadow-none bg-opacity-10',
                journeyOrder: 2
            ),

            $this->buildProfileSection(
                title: 'Family Details',
                description: 'Tell prospective matches more about your family.',
                icon: 'ri-group-line',
                routeName: 'web.profile.family-details',
                completion: $familyCompletion,
                cardClass: 'bg-secondary-subtle shadow-none bg-opacity-10',
                journeyOrder: 3
            ),

            $this->buildProfileSection(
                title: 'Lifestyle',
                description: 'Complete your lifestyle choices and preferences.',
                icon: 'ri-heart-pulse-line',
                routeName: 'web.profile.lifestyle',
                completion: $lifestyleCompletion,
                cardClass: 'bg-warning-subtle shadow-none bg-opacity-10',
                journeyOrder: 4
            ),

            $this->buildProfileSection(
                title: 'About Me',
                description: 'Introduce yourself to prospective matches.',
                icon: 'ri-file-user-line',
                routeName: 'web.profile.about-me',
                completion: $aboutMeCompletion,
                cardClass: 'bg-info-subtle shadow-none bg-opacity-10',
                journeyOrder: 5
            ),

            [
                'title' => 'Profile Photos',

                'description' => $hasUploadedPhoto
                    ? 'Your profile photo has been added. You can review or update it.'
                    : 'Add a profile photo to improve visibility and member interest.',

                'icon' => 'ri-camera-line',

                'route' => 'web.profile.photos',

                'url' => route_to(
                    'web.profile.photos'
                ),

                'percentage' =>
                $hasUploadedPhoto ? 100 : 0,

                'isComplete' =>
                $hasUploadedPhoto,

                'statusLabel' =>
                $hasUploadedPhoto
                    ? 'Completed'
                    : 'Pending',

                'class' =>
                'bg-danger-subtle shadow-none bg-opacity-10',

                /*
                 * Photos count towards overall completion but remain outside
                 * the guided profile-section journey.
                 */
                'includeInJourney' => false,

                'journeyOrder' => 6,
            ],
        ];
    }

    /**
     * Build one standard profile-section record.
     *
     * @param array<string, mixed> $completion
     *
     * @return array<string, mixed>
     */
    private function buildProfileSection(
        string $title,
        string $description,
        string $icon,
        string $routeName,
        array $completion,
        string $cardClass,
        int $journeyOrder
    ): array {
        $percentage = $this->normalisePercentage(
            $completion['percentage'] ?? 0
        );

        $isComplete = $percentage === 100;

        return [
            'title' => $title,

            'description' => $isComplete
                ? $title . ' is complete. You can review or update it.'
                : $description,

            'icon' => $icon,

            'route' => $routeName,

            'url' => route_to($routeName),

            'percentage' => $percentage,

            'isComplete' => $isComplete,

            'statusLabel' => $isComplete
                ? 'Completed'
                : 'Pending',

            'class' => $cardClass,

            'includeInJourney' => true,

            'journeyOrder' => $journeyOrder,
        ];
    }

    /**
     * Resolve the first incomplete guided profile section.
     *
     * Profile photos intentionally remain excluded from the guided journey.
     *
     * @param array<int, array<string, mixed>> $profileSections
     *
     * @return array<string, string>|null
     */
    private function resolveNextProfileSection(
        array $profileSections
    ): ?array {
        foreach ($profileSections as $section) {
            $includeInJourney = (
                $section['includeInJourney']
                ?? false
            ) === true;

            $isComplete = (
                $section['isComplete']
                ?? false
            ) === true;

            if (
                !$includeInJourney
                || $isComplete
            ) {
                continue;
            }

            return [
                'title' => (string) (
                    $section['title']
                    ?? 'Profile Details'
                ),

                'route' => (string) (
                    $section['route']
                    ?? 'web.profile.edit'
                ),
            ];
        }

        return null;
    }

    /**
     * Build overall presentation-ready profile summary values.
     *
     * @param array<string, mixed> $profileCompletion
     * @param array{
     *     uploadedCount?:int,
     *     approvedCount?:int,
     *     hasUploadedPhoto?:bool
     * } $photoSummary
     *
     * @return array<string, mixed>
     */
    private function buildOverallProfileSummary(
        array $profileCompletion,
        string $profileImage,
        array $photoSummary
    ): array {
        $percentage = $this->normalisePercentage(
            $profileCompletion['percentage'] ?? 0
        );

        $completedSteps = max(
            0,
            (int) (
                $profileCompletion['completedSteps']
                ?? 0
            )
        );

        $totalSteps = max(
            0,
            (int) (
                $profileCompletion['totalSteps']
                ?? 0
            )
        );

        $pendingSections = max(
            0,
            $totalSteps - $completedSteps
        );

        if ($percentage >= 80) {
            $visibilityLabel = 'High';
            $visibilityClass = 'success';
        } elseif ($percentage >= 50) {
            $visibilityLabel = 'Medium';
            $visibilityClass = 'warning';
        } else {
            $visibilityLabel = 'Low';
            $visibilityClass = 'danger';
        }

        return [
            'percentage' => $percentage,

            'completedSteps' => $completedSteps,

            'totalSteps' => $totalSteps,

            'pendingSections' => $pendingSections,

            'hasUploadedPhoto' => (
                $photoSummary['hasUploadedPhoto']
                ?? false
            ) === true,

            'uploadedPhotoCount' => max(
                0,
                (int) (
                    $photoSummary['uploadedCount']
                    ?? 0
                )
            ),

            'approvedPhotoCount' => max(
                0,
                (int) (
                    $photoSummary['approvedCount']
                    ?? 0
                )
            ),

            'hasProfilePhoto' =>
            $profileImage !== '',

            'profilePhotoUrl' =>
            $profileImage,

            'visibilityLabel' =>
            $visibilityLabel,

            'visibilityClass' =>
            $visibilityClass,
        ];
    }

    /**
     * Keep a percentage inside the supported 0-100 range.
     */
    private function normalisePercentage(
        mixed $percentage
    ): int {
        return max(
            0,
            min(
                100,
                (int) $percentage
            )
        );
    }
}
