<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberInterestModel;
use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use App\Services\Profile\LifestyleService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\ProfileMasterDataService;
use App\Validation\RegisterFreeValidation;
use DomainException;

final class MemberSearchService
{
    public const PER_PAGE = 10;

    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberMatchCandidateModel $candidateModel,
        private readonly MemberInterestModel $interestModel,
        private readonly MemberPhotoUrlService $photoUrlService,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly LifestyleService $lifestyleService
    ) {}

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function search(
        int $viewerUserId,
        array $input
    ): array {
        $viewer = $this->userModel
            ->find($viewerUserId);

        if (!is_array($viewer)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $mode = $this->mode(
            $input['mode'] ?? null
        );

        $sort = $this->sort(
            $input['sort'] ?? null
        );

        $page = max(
            1,
            (int) (
                $input['page']
                ?? 1
            )
        );

        /*
        * Resolve selected states first because Advanced Search City master data
        * depends on them.
        */
        $requestedStateIds =
            $this->positiveIds(
                $input['state_ids']
                    ?? []
            );

        $masterData =
            $this->searchMasterData(
                $requestedStateIds
            );

        $filters =
            $this->normaliseFilters(
                $mode,
                $input,
                $masterData
            );

        $results =
            $this->candidateModel
            ->searchCandidates(
                viewerUserId: $viewerUserId,

                viewerGender: (string) (
                    $viewer['gender']
                    ?? ''
                ),

                filters: $filters,

                page: $page,

                perPage: self::PER_PAGE,

                sort: $sort
            );

        $profiles =
            $this->presentationProfiles(
                $viewerUserId,
                $results['rows']
            );

        $total =
            max(
                0,
                (int) (
                    $results['total']
                    ?? 0
                )
            );

        $totalPages =
            max(
                1,
                (int) ceil(
                    $total
                        / self::PER_PAGE
                )
            );

        return [
            'mode' =>
            $mode,

            'sort' =>
            $sort,

            'page' =>
            max(
                1,
                (int) (
                    $results['page']
                    ?? $page
                )
            ),

            'perPage' =>
            self::PER_PAGE,

            'total' =>
            $total,

            'totalPages' =>
            $totalPages,

            'profiles' =>
            $profiles,

            'filters' =>
            $filters,

            'masterData' =>
            $masterData,
        ];
    }

    /**
     * Resolve a profile-reference search.
     *
     * The result still passes through MemberMatchCandidateModel so inactive,
     * blocked and otherwise ineligible members cannot be opened by typing a
     * reference directly.
     */
    public function profileByReference(
        int $viewerUserId,
        string $profileReference
    ): ?array {
        $profileReference =
            mb_strtoupper(
                trim(
                    $profileReference
                )
            );

        if (
            $profileReference === ''
            || mb_strlen(
                $profileReference
            ) > 50
        ) {
            return null;
        }

        $viewer = $this->userModel
            ->find(
                $viewerUserId
            );

        if (!is_array($viewer)) {
            return null;
        }

        $target = $this->userModel
            ->where(
                'profile_ref_number',
                $profileReference
            )
            ->first();

        if (!is_array($target)) {
            return null;
        }

        $targetId = max(
            0,
            (int) (
                $target['id']
                ?? 0
            )
        );

        if ($targetId <= 0) {
            return null;
        }

        $visible =
            $this->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                (string) (
                    $viewer['gender']
                    ?? ''
                ),
                [
                    $targetId,
                ]
            );

        if ($visible === []) {
            return null;
        }

        return $visible[0];
    }

    /**
     * Return active master data required by member Search.
     *
     * @param list<int> $selectedStateIds
     *
     * @return array<string, mixed>
     */
    private function searchMasterData(
        array $selectedStateIds = []
    ): array {
        $basic =
            $this->masterDataService
            ->basicDetailsOptions();

        $additional =
            $this->masterDataService
            ->additionalPartnerPreferenceOptions();

        $lifestyle =
            $this->lifestyleService
            ->activeOptions();

        /*
     * Advanced Search may select multiple states, unlike Profile Edit.
     * Load active cities across all selected states so submitted city values
     * remain visible after Search, sorting and pagination.
     */
        $cities =
            $selectedStateIds !== []
            ? $this->masterDataService
            ->citiesForStates(
                $selectedStateIds
            )
            : [];

        return array_merge(
            $basic,
            $additional,
            [
                'cities' =>
                $cities,

                'lifestyleCategories' =>
                $lifestyle['categories']
                    ?? [],

                'lifestyleOptionsByCategory' =>
                $lifestyle['optionsByCategory']
                    ?? [],

                /*
             * Existing registration profile ownership values are reused.
             * No duplicate Search master is introduced.
             */
                'profileManagedBy' => [
                    [
                        'value' =>
                        'self',

                        'label' =>
                        'Self',
                    ],
                    [
                        'value' =>
                        'son',

                        'label' =>
                        'Son',
                    ],
                    [
                        'value' =>
                        'daughter',

                        'label' =>
                        'Daughter',
                    ],
                    [
                        'value' =>
                        'brother',

                        'label' =>
                        'Brother',
                    ],
                    [
                        'value' =>
                        'sister',

                        'label' =>
                        'Sister',
                    ],
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $masterData
     *
     * @return array<string, mixed>
     */
    private function normaliseFilters(
        string $mode,
        array $input,
        array $masterData
    ): array {
        $ageMin =
            $this->nullablePositiveInt(
                $input['age_min']
                    ?? null
            );

        $ageMax =
            $this->nullablePositiveInt(
                $input['age_max']
                    ?? null
            );

        if (
            $ageMin !== null
            && $ageMax !== null
            && $ageMin > $ageMax
        ) {
            throw new DomainException(
                'Minimum age cannot be greater than maximum age.'
            );
        }

        $heightMinId =
            $this->nullablePositiveInt(
                $input['height_min_id']
                    ?? null
            );

        $heightMaxId =
            $this->nullablePositiveInt(
                $input['height_max_id']
                    ?? null
            );

        $heightMinCm =
            $this->heightCm(
                $heightMinId,
                $masterData['heights']
                    ?? []
            );

        $heightMaxCm =
            $this->heightCm(
                $heightMaxId,
                $masterData['heights']
                    ?? []
            );

        if (
            $heightMinCm !== null
            && $heightMaxCm !== null
            && $heightMinCm > $heightMaxCm
        ) {
            throw new DomainException(
                'Minimum height cannot be greater than maximum height.'
            );
        }

        $filters = [
            'mode' =>
            $mode,

            'age_min' =>
            $ageMin,

            'age_max' =>
            $ageMax,

            'height_min_id' =>
            $heightMinId,

            'height_max_id' =>
            $heightMaxId,

            'height_min_cm' =>
            $heightMinCm,

            'height_max_cm' =>
            $heightMaxCm,

            'marital_status_ids' =>
            $this->validatedMasterIds(
                $input['marital_status_ids']
                    ?? [],
                $masterData['maritalStatuses']
                    ?? []
            ),

            'state_ids' =>
            $this->validatedMasterIds(
                $input['state_ids']
                    ?? [],
                $masterData['states']
                    ?? []
            ),

            'photo_visibility' =>
            $this->photoVisibility(
                $input['photo_visibility']
                    ?? []
            ),
        ];

        if ($mode !== 'advanced') {
            return $filters;
        }

        $filters['community_ids'] =
            $this->validatedMasterIds(
                $input['community_ids']
                    ?? [],
                $masterData['communities']
                    ?? []
            );

        /*
        * Validate cities against active city master data belonging to the selected
        * states. A browser-supplied positive numeric ID is not trusted by itself.
        */
        $selectedStateIds =
            $filters['state_ids'];

        $availableCities =
            $selectedStateIds !== []
            ? $this->masterDataService
            ->citiesForStates(
                $selectedStateIds
            )
            : [];

        $filters['city_ids'] =
            $this->validatedMasterIds(
                $input['city_ids']
                    ?? [],
                $availableCities
            );

        $filters['managed_by'] =
            $this->allowedStrings(
                $input['managed_by']
                    ?? [],
                RegisterFreeValidation
                ::PROFILE_TYPES
            );

        $filters['education_ids'] =
            $this->validatedMasterIds(
                $input['education_ids']
                    ?? [],
                $masterData['educations']
                    ?? []
            );

        $filters['occupation_ids'] =
            $this->validatedMasterIds(
                $input['occupation_ids']
                    ?? [],
                $masterData['occupations']
                    ?? []
            );

        $employmentValues = [];

        foreach (
            $masterData['employmentTypes']
                ?? []
            as $employment
        ) {
            if (!is_array($employment)) {
                continue;
            }

            $value = trim(
                (string) (
                    $employment['value']
                    ?? ''
                )
            );

            if ($value !== '') {
                $employmentValues[] =
                    $value;
            }
        }

        $filters['employed_in'] =
            $this->allowedStrings(
                $input['employed_in']
                    ?? [],
                $employmentValues
            );

        $incomeFrom =
            $this->nullablePositiveInt(
                $input['annual_income_from_id']
                    ?? null
            );

        $incomeTo =
            $this->nullablePositiveInt(
                $input['annual_income_to_id']
                    ?? null
            );

        $filters['annual_income_from_id'] =
            $incomeFrom;

        $filters['annual_income_to_id'] =
            $incomeTo;

        $filters['annual_income_ids'] =
            $this->incomeRangeIds(
                $incomeFrom,
                $incomeTo,
                $masterData['annualIncomes']
                    ?? []
            );

        /*
        * Flatten currently active Lifestyle options before validating submitted
        * option IDs.
        */
        $activeLifestyleOptions = [];

        foreach (
            $masterData['lifestyleOptionsByCategory']
                ?? []
            as $categoryOptions
        ) {
            if (!is_array($categoryOptions)) {
                continue;
            }

            foreach (
                $categoryOptions
                as $option
            ) {
                if (is_array($option)) {
                    $activeLifestyleOptions[] =
                        $option;
                }
            }
        }

        $filters['lifestyle_option_ids'] =
            $this->validatedMasterIds(
                $input['lifestyle_option_ids']
                    ?? [],
                $activeLifestyleOptions
            );

        return $filters;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function presentationProfiles(
        int $viewerUserId,
        array $rows
    ): array {
        $profiles = [];

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

            if ($memberId <= 0) {
                continue;
            }

            $reference = trim(
                (string) (
                    $row['profile_ref_number']
                    ?? ''
                )
            );

            if ($reference === '') {
                continue;
            }

            $hasInterest =
                $this->interestModel
                ->existsBetween(
                    $viewerUserId,
                    $memberId
                );

            $image =
                $this->photoUrlService
                ->getApprovedPrimaryUrlForViewer(
                    memberId: $memberId,

                    viewerUserId: $viewerUserId,

                    hasInterestRelationship: $hasInterest,

                    variant: 'thumbnail'
                );

            $profiles[] = [
                'referenceId' =>
                $reference,

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

                'height' =>
                trim(
                    (string) (
                        $row['height_name']
                        ?? ''
                    )
                ),

                'city' =>
                trim(
                    (string) (
                        $row['city_name']
                        ?? ''
                    )
                ),

                'state' =>
                trim(
                    (string) (
                        $row['state_name']
                        ?? ''
                    )
                ),

                'maritalStatus' =>
                trim(
                    (string) (
                        $row['marital_status_name']
                        ?? ''
                    )
                ),

                'image' =>
                $image,

                'profileUrl' =>
                route_to(
                    'web.members.view',
                    $reference
                ),
            ];
        }

        return $profiles;
    }

    private function mode(
        mixed $value
    ): string {
        $value = mb_strtolower(
            trim(
                (string) $value
            )
        );

        return $value === 'advanced'
            ? 'advanced'
            : 'basic';
    }

    private function sort(
        mixed $value
    ): string {
        $value = mb_strtolower(
            trim(
                (string) $value
            )
        );

        return in_array(
            $value,
            [
                'default',
                'latest',
                'oldest',
                'last_login',
            ],
            true
        )
            ? $value
            : 'default';
    }

    private function nullablePositiveInt(
        mixed $value
    ): ?int {
        $value = trim(
            (string) $value
        );

        if (
            $value === ''
            || !ctype_digit($value)
        ) {
            return null;
        }

        $number = (int) $value;

        return $number > 0
            ? $number
            : null;
    }

    /**
     * @param mixed $values
     *
     * @return list<int>
     */
    private function positiveIds(
        mixed $values
    ): array {
        if (!is_array($values)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $values
                    ),
                    static fn(
                        int $value
                    ): bool =>
                    $value > 0
                )
            )
        );
    }

    /**
     * @param list<array<string, mixed>> $masterRows
     *
     * @return list<int>
     */
    private function validatedMasterIds(
        mixed $values,
        array $masterRows
    ): array {
        $ids =
            $this->positiveIds(
                $values
            );

        if ($ids === []) {
            return [];
        }

        $allowed = [];

        foreach ($masterRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) (
                $row['id']
                ?? 0
            );

            if ($id > 0) {
                $allowed[$id] =
                    true;
            }
        }

        foreach ($ids as $id) {
            if (!isset($allowed[$id])) {
                throw new DomainException(
                    'One or more selected search values are invalid.'
                );
            }
        }

        return $ids;
    }

    /**
     * @param list<string> $allowed
     *
     * @return list<string>
     */
    private function allowedStrings(
        mixed $values,
        array $allowed
    ): array {
        if (!is_array($values)) {
            return [];
        }

        $result = [];

        foreach ($values as $value) {
            $value = trim(
                (string) $value
            );

            if (
                $value !== ''
                && in_array(
                    $value,
                    $allowed,
                    true
                )
            ) {
                $result[] =
                    $value;
            }
        }

        return array_values(
            array_unique(
                $result
            )
        );
    }

    /**
     * @return list<string>
     */
    private function photoVisibility(
        mixed $values
    ): array {
        return $this->allowedStrings(
            $values,
            [
                'PUBLIC',
                'INTERESTED_MEMBERS',
            ]
        );
    }

    /**
     * @param list<array<string, mixed>> $heights
     */
    private function heightCm(
        ?int $heightId,
        array $heights
    ): ?int {
        if ($heightId === null) {
            return null;
        }

        foreach ($heights as $height) {
            if (
                is_array($height)
                && (int) (
                    $height['id']
                    ?? 0
                ) === $heightId
            ) {
                return (int) (
                    $height['height_cm']
                    ?? 0
                );
            }
        }

        throw new DomainException(
            'Please select a valid height.'
        );
    }

    /**
     * @param list<array<string, mixed>> $incomes
     *
     * @return list<int>
     */
    private function incomeRangeIds(
        ?int $fromId,
        ?int $toId,
        array $incomes
    ): array {
        if (
            $fromId === null
            && $toId === null
        ) {
            return [];
        }

        $ordered = [];

        foreach ($incomes as $income) {
            if (!is_array($income)) {
                continue;
            }

            $id = max(
                0,
                (int) (
                    $income['id']
                    ?? 0
                )
            );

            if ($id > 0) {
                $ordered[] = $id;
            }
        }

        $fromIndex =
            $fromId !== null
            ? array_search(
                $fromId,
                $ordered,
                true
            )
            : 0;

        $toIndex =
            $toId !== null
            ? array_search(
                $toId,
                $ordered,
                true
            )
            : count($ordered) - 1;

        if (
            $fromIndex === false
            || $toIndex === false
        ) {
            throw new DomainException(
                'Please select a valid annual income range.'
            );
        }

        if ($fromIndex > $toIndex) {
            throw new DomainException(
                'Minimum annual income cannot exceed maximum annual income.'
            );
        }

        return array_slice(
            $ordered,
            $fromIndex,
            $toIndex
                - $fromIndex
                + 1
        );
    }

    private function age(
        mixed $date
    ): ?int {
        $date = trim(
            (string) $date
        );

        if ($date === '') {
            return null;
        }

        try {
            return (
                new \DateTimeImmutable(
                    $date
                )
            )->diff(
                new \DateTimeImmutable(
                    'today'
                )
            )->y;
        } catch (\Throwable) {
            return null;
        }
    }
}
