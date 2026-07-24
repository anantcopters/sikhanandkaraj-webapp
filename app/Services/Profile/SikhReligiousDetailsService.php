<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MasterBirthStarModel;
use App\Models\MasterCityModel;
use App\Models\MasterCountryModel;
use App\Models\MasterMoonSignModel;
use App\Models\MasterSikhCommunityModel;
use App\Models\MasterSikhSubcommunityModel;
use App\Models\MasterStateModel;
use App\Models\MemberSikhReligiousDetailModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Handles Sikh and Religious Details business rules.
 */
final class SikhReligiousDetailsService
{
    private const DOSH_VALUES = [
        'NO',
        'YES',
        'DONT_KNOW',
        'NOT_APPLICABLE',
    ];

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberSikhReligiousDetailModel $detailModel,
        private readonly MasterSikhCommunityModel $communityModel,
        private readonly MasterSikhSubcommunityModel $subcommunityModel,
        private readonly MasterMoonSignModel $moonSignModel,
        private readonly MasterBirthStarModel $birthStarModel,
        private readonly MasterCountryModel $countryModel,
        private readonly MasterStateModel $stateModel,
        private readonly MasterCityModel $cityModel,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getForUser(int $userId): array
    {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $details = $this->detailModel->findForUser($userId);

        $communityId = $this->existingInteger(
            $details['community_id'] ?? null
        );

        $stateId = $this->existingInteger(
            $details['birth_state_id'] ?? null
        );

        $india = $this->countryModel->findIndia();

        if (!is_array($india)) {
            throw new DomainException(
                'India master data is not configured.'
            );
        }

        return [
            'user' => $user,

            'sikhReligiousDetails' => $details,

            'masterData' => [
                'country' => $india,

                'communities' =>
                $this->communityModel->activeOptions(),

                'subcommunities' => $communityId !== null
                    ? $this->subcommunityModel
                    ->activeForCommunity($communityId)
                    : [],

                'moonSigns' =>
                $this->moonSignModel->activeOptions(),

                'birthStars' =>
                $this->birthStarModel->activeOptions(),

                'states' =>
                $this->stateModel->activeForCountry(
                    (int) $india['id']
                ),

                'cities' => $stateId !== null
                    ? $this->cityModel->activeForState(
                        $stateId
                    )
                    : [],

                'birthHours' => range(1, 12),

                'birthMinutes' => range(0, 59),

                'doshOptions' => [
                    [
                        'value' => 'NO',
                        'label' => 'No',
                    ],
                    [
                        'value' => 'YES',
                        'label' => 'Yes',
                    ],
                    [
                        'value' => 'DONT_KNOW',
                        'label' => "Don't know",
                    ],
                    [
                        'value' => 'NOT_APPLICABLE',
                        'label' => 'Not applicable',
                    ],
                ],
            ],

            'completion' =>
            $this->calculateCompletion($details),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(
        int $userId,
        array $data
    ): void {
        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $communityId = $this->requiredPositiveInteger(
            $data['community_id'] ?? null,
            'Please select your community.'
        );

        $subcommunityId = $this->requiredPositiveInteger(
            $data['subcommunity_id'] ?? null,
            'Please select your sub-community.'
        );

        $birthHour = $this->requiredBoundedInteger(
            $data['birth_hour'] ?? null,
            1,
            12,
            'Please select a valid birth hour.'
        );

        $birthMinute = $this->requiredBoundedInteger(
            $data['birth_minute'] ?? null,
            0,
            59,
            'Please select a valid birth minute.'
        );

        $birthMeridiem = strtoupper(
            trim((string) ($data['birth_meridiem'] ?? ''))
        );

        if (!in_array($birthMeridiem, ['AM', 'PM'], true)) {
            throw new DomainException(
                'Please select AM or PM.'
            );
        }

        if (
            $birthMeridiem !== null
            && !in_array(
                $birthMeridiem,
                ['AM', 'PM'],
                true
            )
        ) {
            throw new DomainException(
                'Please select AM or PM.'
            );
        }

        $countryId = $this->requiredPositiveInteger(
            $data['birth_country_id'] ?? null,
            'Please select a valid country of birth.'
        );

        $stateId = $this->requiredPositiveInteger(
            $data['birth_state_id'] ?? null,
            'Please select a valid state of birth.'
        );

        $cityId = $this->requiredPositiveInteger(
            $data['birth_city_id'] ?? null,
            'Please select a valid city of birth.'
        );

        $gotra = trim((string) ($data['gotra'] ?? ''));

        $gotra = $gotra !== ''
            ? $gotra
            : null;

        $moonSignId = $this->nullablePositiveInteger(
            $data['moon_sign_id'] ?? null,
            'Please select a valid moon sign.'
        );

        $birthStarId = $this->nullablePositiveInteger(
            $data['birth_star_id'] ?? null,
            'Please select a valid birth star.'
        );

        $hasDosh = strtoupper(
            trim((string) ($data['has_dosh'] ?? ''))
        );

        $hasDosh = $hasDosh !== ''
            ? $hasDosh
            : null;

        if (
            $hasDosh !== null
            && !in_array(
                $hasDosh,
                self::DOSH_VALUES,
                true
            )
        ) {
            throw new DomainException(
                'Please select a valid dosh option.'
            );
        }

        $this->assertValidMasters(
            $communityId,
            $subcommunityId,
            $countryId,
            $stateId,
            $cityId,
            $moonSignId,
            $birthStarId
        );

        $saveData = [
            'user_id' => $userId,
            'community_id' => $communityId,
            'subcommunity_id' => $subcommunityId,
            'birth_hour' => $birthHour,
            'birth_minute' => $birthMinute,
            'birth_meridiem' => $birthMeridiem,
            'birth_country_id' => $countryId,
            'birth_state_id' => $stateId,
            'birth_city_id' => $cityId,
            'gotra' => $gotra,
            'moon_sign_id' => $moonSignId,
            'birth_star_id' => $birthStarId,
            'has_dosh' => $hasDosh,
        ];

        $this->database->transException(true);
        $this->database->transStart();

        try {
            $existing = $this->detailModel
                ->findForUser($userId);

            $saved = is_array($existing)
                ? $this->detailModel->update(
                    (int) $existing['id'],
                    $saveData
                )
                : $this->detailModel->insert(
                    $saveData,
                    false
                );

            if ($saved === false) {
                throw new RuntimeException(
                    'Sikh and religious details could not be saved.'
                );
            }

            $this->database->transComplete();

            if ($this->database->transStatus() === false) {
                throw new RuntimeException(
                    'Sikh and religious details could not be saved.'
                );
            }
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function assertValidMasters(
        int $communityId,
        int $subcommunityId,
        int $countryId,
        int $stateId,
        int $cityId,
        ?int $moonSignId,
        ?int $birthStarId
    ): void {
        $india = $this->countryModel->findIndia();

        if (
            !is_array($india)
            || (int) $india['id'] !== $countryId
        ) {
            throw new DomainException(
                'Please select a valid country of birth.'
            );
        }

        $community = $this->communityModel
            ->where('id', $communityId)
            ->where('is_active', true)
            ->first();

        if (!is_array($community)) {
            throw new DomainException(
                'Please select a valid community.'
            );
        }

        if ($communityId === null) {
            throw new DomainException(
                'Please select a community before '
                    . 'selecting a sub-community.'
            );
        }

        $subcommunity = $this->subcommunityModel
            ->where('id', $subcommunityId)
            ->where('community_id', $communityId)
            ->where('is_active', true)
            ->first();

        if (!is_array($subcommunity)) {
            throw new DomainException(
                'Please select a valid sub-community '
                    . 'for the selected community.'
            );
        }

        $state = $this->stateModel
            ->where('id', $stateId)
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->first();

        if (!is_array($state)) {
            throw new DomainException(
                'Please select a valid state of birth.'
            );
        }

        $city = $this->cityModel
            ->where('id', $cityId)
            ->where('state_id', $stateId)
            ->where('is_active', true)
            ->first();

        if (!is_array($city)) {
            throw new DomainException(
                'Please select a valid city for '
                    . 'the selected birth state.'
            );
        }

        if ($moonSignId !== null) {
            $moonSign = $this->moonSignModel
                ->where('id', $moonSignId)
                ->where('is_active', true)
                ->first();

            if (!is_array($moonSign)) {
                throw new DomainException(
                    'Please select a valid moon sign.'
                );
            }
        }

        if ($birthStarId !== null) {
            $birthStar = $this->birthStarModel
                ->where('id', $birthStarId)
                ->where('is_active', true)
                ->first();

            if (!is_array($birthStar)) {
                throw new DomainException(
                    'Please select a valid birth star.'
                );
            }
        }
    }

    /**
     * Only birthplace is mandatory for section completion.
     *
     * @param array<string, mixed>|null $details
     *
     * @return array<string, int>
     */
    private function calculateCompletion(
        ?array $details
    ): array {
        $requiredValues = [
            $details['community_id'] ?? null,
            $details['subcommunity_id'] ?? null,
            $details['birth_hour'] ?? null,
            $details['birth_minute'] ?? null,
            $details['birth_meridiem'] ?? null,
            $details['birth_country_id'] ?? null,
            $details['birth_state_id'] ?? null,
            $details['birth_city_id'] ?? null,
        ];

        $completed = count(
            array_filter(
                $requiredValues,
                static function (mixed $value): bool {
                    if (is_int($value)) {
                        return $value >= 0;
                    }

                    return $value !== null
                        && trim((string) $value) !== '';
                }
            )
        );

        $total = count($requiredValues);

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0
                ? (int) round(
                    ($completed / $total) * 100
                )
                : 0,
        ];
    }

    private function existingInteger(mixed $value): ?int
    {
        return is_numeric($value)
            && (int) $value > 0
            ? (int) $value
            : null;
    }

    private function requiredPositiveInteger(
        mixed $value,
        string $message
    ): int {
        $normalized = trim((string) $value);

        if (
            !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }

    private function nullablePositiveInteger(
        mixed $value,
        string $message
    ): ?int {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (
            !ctype_digit($normalized)
            || (int) $normalized <= 0
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }

    private function requiredBoundedInteger(
        mixed $value,
        int $minimum,
        int $maximum,
        string $message
    ): int {
        $normalized = trim((string) $value);

        if (
            $normalized === ''
            || !ctype_digit($normalized)
            || (int) $normalized < $minimum
            || (int) $normalized > $maximum
        ) {
            throw new DomainException($message);
        }

        return (int) $normalized;
    }
}
