<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

/**
 * Supplies dashboard-specific member presentation data.
 *
 * Profile completion, profile image and profile-section actions are supplied
 * by MemberProfileSummaryService. This service currently retains placeholder
 * datasets only for account-plan and matchmaking features that have not yet
 * been connected to their final repositories.
 */
final class MemberDashboardDataService
{
    /**
     * Return dashboard-specific datasets for a member.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(int $userId): array
    {
        /*
         * Keep the user ID in the method signature because these placeholder
         * methods will later be replaced with member-specific repositories.
         */
        unset($userId);

        return [
            'accountPlan' => [
                'name' => 'Free account',
                'code' => 'FREE',
            ],

            'dailyRecommendations' =>
            $this->getDailyRecommendations(),

            'allMatches' =>
            $this->getAllMatches(),

            'newMatches' =>
            $this->getNewMatches(),

            'profileVisitors' =>
            $this->getProfileVisitors(),

            'shortlistedProfiles' =>
            $this->getShortlistedProfiles(),

            'shortlistedByProfiles' =>
            $this->getShortlistedByProfiles(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDailyRecommendations(): array
    {
        return [
            $this->profile(
                'SAK10021',
                'Simran Kaur',
                28,
                '5 ft 5 in',
                null
            ),

            $this->profile(
                'SAK10034',
                'Harleen Kaur',
                27,
                '5 ft 4 in',
                null
            ),

            $this->profile(
                'SAK10046',
                'Navneet Kaur',
                29,
                '5 ft 6 in',
                null
            ),

            $this->profile(
                'SAK10061',
                'Jasleen Kaur',
                26,
                '5 ft 3 in',
                null
            ),

            $this->profile(
                'SAK10075',
                'Gurleen Kaur',
                30,
                '5 ft 7 in',
                null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllMatches(): array
    {
        return [
            $this->profile(
                'SAK10104',
                'Manpreet Kaur',
                28,
                '5 ft 4 in',
                null
            ),

            $this->profile(
                'SAK10117',
                'Amandeep Kaur',
                29,
                '5 ft 5 in',
                null
            ),

            $this->profile(
                'SAK10125',
                'Ravneet Kaur',
                27,
                '5 ft 3 in',
                null
            ),

            $this->profile(
                'SAK10139',
                'Prabhjot Kaur',
                30,
                '5 ft 6 in',
                null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNewMatches(): array
    {
        return [
            $this->profile(
                'SAK10202',
                'Mehar Kaur',
                27,
                '5 ft 5 in',
                null
            ),

            $this->profile(
                'SAK10218',
                'Sukhmani Kaur',
                29,
                '5 ft 4 in',
                null
            ),

            $this->profile(
                'SAK10231',
                'Kirandeep Kaur',
                28,
                '5 ft 6 in',
                null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProfileVisitors(): array
    {
        return [
            $this->profile(
                'SAK10310',
                'Amrit Kaur',
                29,
                '5 ft 5 in',
                null
            ),

            $this->profile(
                'SAK10325',
                'Rajveer Kaur',
                27,
                '5 ft 4 in',
                null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getShortlistedProfiles(): array
    {
        return [
            $this->profile(
                'SAK10402',
                'Japneet Kaur',
                28,
                '5 ft 6 in',
                null
            ),

            $this->profile(
                'SAK10419',
                'Eknoor Kaur',
                27,
                '5 ft 4 in',
                null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getShortlistedByProfiles(): array
    {
        return [
            $this->profile(
                'SAK10507',
                'Noor Kaur',
                29,
                '5 ft 5 in',
                null
            ),

            $this->profile(
                'SAK10528',
                'Sehaj Kaur',
                28,
                '5 ft 3 in',
                null
            ),
        ];
    }

    /**
     * Build a consistent placeholder profile record.
     *
     * @return array<string, mixed>
     */
    private function profile(
        string $referenceId,
        string $name,
        int $age,
        string $height,
        ?string $image
    ): array {
        return [
            'referenceId' => $referenceId,

            'name' => $name,

            'age' => $age,

            'height' => $height,

            'image' => $image,

            'profileUrl' => '#',
        ];
    }
}
