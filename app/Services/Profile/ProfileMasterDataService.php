<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MasterCityModel;
use App\Models\MasterCountryModel;
use App\Models\MasterHeightModel;
use App\Models\MasterMaritalStatusModel;
use App\Models\MasterMotherTongueModel;
use App\Models\MasterStateModel;
use App\Models\MasterAnnualIncomeModel;
use App\Models\MasterEducationModel;
use App\Models\MasterOccupationModel;
use App\Models\MasterFamilyOccupationModel;
use App\Models\MasterFamilyStatusModel;
use App\Models\MasterFamilyTypeModel;
use App\Models\MasterFamilyValueModel;
use App\Models\MasterSikhCommunityModel;
use App\Models\AbstractActiveMasterModel;
use App\Models\MasterDrinkingHabitModel;
use App\Models\MasterEatingHabitModel;
use App\Models\MasterPhysicalStatusModel;
use DomainException;

/**
 * Supplies active profile master data and validates relationships.
 */
final class ProfileMasterDataService
{
    public function __construct(
        private readonly MasterMaritalStatusModel $maritalStatusModel,
        private readonly MasterHeightModel $heightModel,
        private readonly MasterMotherTongueModel $motherTongueModel,
        private readonly MasterCountryModel $countryModel,
        private readonly MasterStateModel $stateModel,
        private readonly MasterCityModel $cityModel,
        private readonly MasterEducationModel $educationModel,
        private readonly MasterOccupationModel $occupationModel,
        private readonly MasterAnnualIncomeModel $annualIncomeModel,
        private readonly MasterFamilyOccupationModel $familyOccupationModel,
        private readonly MasterFamilyValueModel $familyValueModel,
        private readonly MasterFamilyTypeModel $familyTypeModel,
        private readonly MasterFamilyStatusModel $familyStatusModel,
        private readonly MasterSikhCommunityModel $communityModel,
        private readonly MasterDrinkingHabitModel $drinkingHabitModel,
        private readonly MasterEatingHabitModel $eatingHabitModel,
        private readonly MasterPhysicalStatusModel $physicalStatusModel
    ) {}

    /**
     * Return the master data required by Basic Details.
     *
     * @return array<string, mixed>
     */
    public function basicDetailsOptions(
        ?int $selectedStateId = null
    ): array {
        $india = $this->countryModel->findIndia();

        if (!is_array($india)) {
            throw new DomainException(
                'India master data is not configured.'
            );
        }

        return [
            'country' => $india,

            'maritalStatuses' =>
            $this->maritalStatusModel
                ->activeOptions(),

            'heights' =>
            $this->heightModel
                ->activeOptions(),

            'motherTongues' =>
            $this->motherTongueModel
                ->activeOptions(),

            'drinkingHabits' =>
            $this->drinkingHabitModel
                ->activeOptions(),

            'eatingHabits' =>
            $this->eatingHabitModel
                ->activeOptions(),

            'physicalStatuses' =>
            $this->physicalStatusModel
                ->activeOptions(),

            'states' =>
            $this->stateModel->activeForCountry(
                (int) $india['id']
            ),

            'cities' =>
            $selectedStateId !== null
                ? $this->cityModel->activeForState(
                    $selectedStateId
                )
                : [],
        ];
    }

    /**
     * Verify optional Basic Details master-data selections.
     *
     * Optional selections may be NULL. When an ID is supplied, it must refer
     * to an active master record. This prevents inactive or fabricated IDs
     * from being persisted through request manipulation.
     */
    public function assertValidOptionalBasicSelections(
        ?int $drinkingHabitId,
        ?int $eatingHabitId,
        ?int $physicalStatusId
    ): void {
        if ($drinkingHabitId !== null) {
            $this->assertActiveMaster(
                $this->drinkingHabitModel,
                $drinkingHabitId,
                'Please select a valid drinking habit.'
            );
        }

        if ($eatingHabitId !== null) {
            $this->assertActiveMaster(
                $this->eatingHabitModel,
                $eatingHabitId,
                'Please select a valid eating habit.'
            );
        }

        if ($physicalStatusId !== null) {
            $this->assertActiveMaster(
                $this->physicalStatusModel,
                $physicalStatusId,
                'Please select a valid physical status.'
            );
        }
    }

    /**
     * Return active master data required by Basic Partner Preference.
     *
     * Existing profile master-model methods are reused so that partner
     * preferences and member profile details use the same active options.
     *
     * @return array{
     *     maritalStatuses: array<int, array<string, mixed>>,
     *     heights: array<int, array<string, mixed>>,
     *     motherTongues: array<int, array<string, mixed>>,
     *     drinkingHabits: array<int, array<string, mixed>>,
     *     eatingHabits: array<int, array<string, mixed>>,
     *     physicalStatuses: array<int, array<string, mixed>>
     * }
     */
    public function partnerBasicPreferenceOptions(): array
    {
        return [
            'maritalStatuses' =>
            $this->maritalStatusModel
                ->activeOptions(),

            'heights' =>
            $this->heightModel
                ->activeOptions(),

            'motherTongues' =>
            $this->motherTongueModel
                ->activeOptions(),

            'drinkingHabits' =>
            $this->drinkingHabitModel
                ->activeOptions(),

            'eatingHabits' =>
            $this->eatingHabitModel
                ->activeOptions(),

            'physicalStatuses' =>
            $this->physicalStatusModel
                ->activeOptions(),
        ];
    }

    /**
     * Return active master values used by partner preferences.
     *
     * Flat Education and Occupation collections are retained for:
     *
     * - server-side submitted-ID validation;
     * - summary label resolution;
     * - backward-compatible consumers.
     *
     * Grouped collections are supplied separately for searchable
     * category-based multi-select rendering.
     *
     * @return array<string, mixed>
     */
    public function additionalPartnerPreferenceOptions(): array
    {
        $india = $this->countryModel->findIndia();

        if (!is_array($india)) {
            throw new DomainException(
                'India master data is not configured.'
            );
        }

        return [
            'communities' =>
            $this->communityModel
                ->activeOptions(),

            /*
         * Flat education collection.
         *
         * AdditionalPartnerPreferenceService uses this collection
         * to validate selected IDs and resolve summary labels.
         */
            'educations' =>
            $this->educationModel
                ->activeOptions(),

            /*
         * Grouped collection used only by the Partner Preference UI.
         */
            'educationGroups' =>
            $this->educationModel
                ->activeGroupedOptions(),

            'employmentTypes' => [
                [
                    'value' => 'GOVERNMENT_PSU',
                    'label' => 'Government / PSU',
                ],
                [
                    'value' => 'PRIVATE',
                    'label' => 'Private',
                ],
                [
                    'value' => 'BUSINESS',
                    'label' => 'Business',
                ],
                [
                    'value' => 'DEFENSE',
                    'label' => 'Defense',
                ],
                [
                    'value' => 'SELF_EMPLOYED',
                    'label' => 'Self Employed',
                ],
                [
                    'value' => 'NOT_WORKING',
                    'label' => 'Not Working',
                ],
            ],

            /*
         * Keep flat occupations because the service validates
         * submitted occupation IDs against this collection and
         * uses it for summary labels.
         */
            'occupations' =>
            $this->occupationModel
                ->activeOptions(),

            /*
         * Grouped collection for category-based UI rendering.
         */
            'occupationGroups' =>
            $this->occupationModel
                ->activeGroupedOptions(),

            'annualIncomes' =>
            $this->annualIncomeModel
                ->activeOptions(),

            'country' =>
            $india,

            'states' =>
            $this->stateModel
                ->activeForCountry(
                    (int) $india['id']
                ),
        ];
    }

    /**
     * Determine whether an active marital status represents Never Married.
     */
    public function isNeverMarried(
        int $maritalStatusId
    ): bool {
        if ($maritalStatusId <= 0) {
            throw new DomainException(
                'Please select a valid marital status.'
            );
        }

        $maritalStatus = $this->maritalStatusModel
            ->where(
                'id',
                $maritalStatusId
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!is_array($maritalStatus)) {
            throw new DomainException(
                'Please select a valid marital status.'
            );
        }

        $code = strtoupper(
            trim(
                (string) (
                    $maritalStatus['code']
                    ?? ''
                )
            )
        );

        if ($code !== '') {
            return $code === 'NEVER_MARRIED';
        }

        /*
        * Backward-compatible fallback for older marital-status master data
        * where only name may be available.
        */
        return strtoupper(
            trim(
                (string) (
                    $maritalStatus['name']
                    ?? ''
                )
            )
        ) === 'NEVER MARRIED';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function citiesForState(int $stateId): array
    {
        if ($stateId <= 0) {
            return [];
        }

        return $this->cityModel->activeForState($stateId);
    }

    /**
     * Return active cities for multiple states.
     *
     * @param list<int> $stateIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function citiesForStates(
        array $stateIds
    ): array {
        $normalizedStateIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $stateIds
                    ),
                    static fn(int $stateId): bool =>
                    $stateId > 0
                )
            )
        );

        if ($normalizedStateIds === []) {
            return [];
        }

        return $this
            ->cityModel
            ->activeForStates(
                $normalizedStateIds
            );
    }

    public function assertValidSelection(
        int $maritalStatusId,
        int $heightId,
        int $motherTongueId,
        int $countryId,
        int $stateId,
        int $cityId
    ): void {
        $india = $this->countryModel->findIndia();

        if (
            !is_array($india)
            || (int) $india['id'] !== $countryId
        ) {
            throw new DomainException(
                'Please select a valid country.'
            );
        }

        $maritalStatus = $this->maritalStatusModel
            ->where('id', $maritalStatusId)
            ->where('is_active', true)
            ->first();

        if (!is_array($maritalStatus)) {
            throw new DomainException(
                'Please select a valid marital status.'
            );
        }

        $height = $this->heightModel
            ->where('id', $heightId)
            ->where('is_active', true)
            ->first();

        if (!is_array($height)) {
            throw new DomainException(
                'Please select a valid height.'
            );
        }

        $motherTongue = $this->motherTongueModel
            ->where('id', $motherTongueId)
            ->where('is_active', true)
            ->first();

        if (!is_array($motherTongue)) {
            throw new DomainException(
                'Please select a valid mother tongue.'
            );
        }

        $state = $this->stateModel
            ->where('id', $stateId)
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->first();

        if (!is_array($state)) {
            throw new DomainException(
                'Please select a valid state.'
            );
        }

        $city = $this->cityModel
            ->where('id', $cityId)
            ->where('state_id', $stateId)
            ->where('is_active', true)
            ->first();

        if (!is_array($city)) {
            throw new DomainException(
                'Please select a valid city for the selected state.'
            );
        }
    }

    /**
     * Return master data required by Education & Profession.
     *
     * Flat collections remain available for backward compatibility.
     * Grouped collections are used by member and prelaunch selects.
     *
     * @return array<string, mixed>
     */
    public function educationProfessionOptions(): array
    {
        return [
            /*
            * Preserve the existing flat contract.
            */
            'educations' =>
            $this->educationModel
                ->activeOptions(),

            /*
            * Member and prelaunch forms use optgroups.
            */
            'educationGroups' =>
            $this->educationModel
                ->activeGroupedOptions(),

            'occupations' =>
            $this->occupationModel
                ->activeOptions(),

            'occupationGroups' =>
            $this->occupationModel
                ->activeGroupedOptions(),

            'annualIncomes' =>
            $this->annualIncomeModel
                ->activeOptions(),

            'employmentTypes' => [
                [
                    'value' => 'GOVERNMENT_PSU',
                    'label' => 'Government / PSU',
                ],
                [
                    'value' => 'PRIVATE',
                    'label' => 'Private',
                ],
                [
                    'value' => 'BUSINESS',
                    'label' => 'Business',
                ],
                [
                    'value' => 'DEFENSE',
                    'label' => 'Defense',
                ],
                [
                    'value' => 'SELF_EMPLOYED',
                    'label' => 'Self Employed',
                ],
                [
                    'value' => 'NOT_WORKING',
                    'label' => 'Not Working',
                ],
            ],
        ];
    }

    /**
     * Verify that submitted Education & Profession masters are active.
     */
    public function assertValidEducationProfessionSelection(
        int $educationId,
        int $occupationId,
        ?int $annualIncomeId
    ): void {
        $education = $this->educationModel
            ->select('master_educations.id')
            ->join(
                'master_education_categories',
                'master_education_categories.id = '
                    . 'master_educations.category_id',
                'inner'
            )
            ->where(
                'master_educations.id',
                $educationId
            )
            ->where(
                'master_educations.is_active',
                true
            )
            ->where(
                'master_education_categories.is_active',
                true
            )
            ->first();

        if (!is_array($education)) {
            throw new DomainException(
                'Please select a valid highest education.'
            );
        }

        $occupation = $this->occupationModel
            ->where('id', $occupationId)
            ->where('is_active', true)
            ->first();

        if (!is_array($occupation)) {
            throw new \DomainException(
                'Please select a valid occupation.'
            );
        }

        if ($annualIncomeId === null) {
            return;
        }

        $annualIncome = $this->annualIncomeModel
            ->where('id', $annualIncomeId)
            ->where('is_active', true)
            ->first();

        if (!is_array($annualIncome)) {
            throw new \DomainException(
                'Please select a valid annual income.'
            );
        }
    }

    /**
     * Return master values required by Family Details.
     *
     * @return array<string, mixed>
     */
    public function familyDetailsOptions(
        ?int $selectedStateId = null
    ): array {
        $india = $this->countryModel->findIndia();

        if (!is_array($india)) {
            throw new DomainException(
                'India master data is not configured.'
            );
        }

        return [
            'country' => $india,

            'states' =>
            $this->stateModel->activeForCountry(
                (int) $india['id']
            ),

            'cities' => $selectedStateId !== null
                ? $this->cityModel->activeForState(
                    $selectedStateId
                )
                : [],

            'familyOccupations' =>
            $this->familyOccupationModel->activeOptions(),

            'familyValues' =>
            $this->familyValueModel->activeOptions(),

            'familyTypes' =>
            $this->familyTypeModel->activeOptions(),

            'familyStatuses' =>
            $this->familyStatusModel->activeOptions(),

            'communities' =>
            $this->communityModel->activeOptions(),

            'siblingCounts' => range(0, 10),
        ];
    }

    /**
     * Verify all Family Details master-data relationships.
     *
     * Family value, family type and family status are optional. When supplied,
     * they must still reference active master records.
     */
    public function assertValidFamilySelection(
        ?int $familyValueId,
        ?int $familyTypeId,
        ?int $familyStatusId,
        int $communityId,
        ?int $fatherOccupationId,
        ?int $motherOccupationId,
        int $countryId,
        int $stateId,
        int $cityId
    ): void {
        if ($familyValueId !== null) {
            $this->assertActiveMaster(
                $this->familyValueModel,
                $familyValueId,
                'Please select a valid family value.'
            );
        }

        if ($familyTypeId !== null) {
            $this->assertActiveMaster(
                $this->familyTypeModel,
                $familyTypeId,
                'Please select a valid family type.'
            );
        }

        if ($familyStatusId !== null) {
            $this->assertActiveMaster(
                $this->familyStatusModel,
                $familyStatusId,
                'Please select a valid family status.'
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

        $india = $this->countryModel->findIndia();

        if (
            !is_array($india)
            || (int) $india['id'] !== $countryId
        ) {
            throw new DomainException(
                'Please select a valid country.'
            );
        }

        foreach (
            [
                "father's" => $fatherOccupationId,
                "mother's" => $motherOccupationId,
            ] as $parent => $occupationId
        ) {
            if ($occupationId === null) {
                continue;
            }

            $occupation = $this->familyOccupationModel
                ->where('id', $occupationId)
                ->where('is_active', true)
                ->first();

            if (!is_array($occupation)) {
                throw new DomainException(
                    sprintf(
                        'Please select a valid %s occupation.',
                        $parent
                    )
                );
            }
        }

        $state = $this->stateModel
            ->where('id', $stateId)
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->first();

        if (!is_array($state)) {
            throw new DomainException(
                'Please select a valid family state.'
            );
        }

        $city = $this->cityModel
            ->where('id', $cityId)
            ->where('state_id', $stateId)
            ->where('is_active', true)
            ->first();

        if (!is_array($city)) {
            throw new DomainException(
                'Please select a valid family city '
                    . 'for the selected state.'
            );
        }
    }

    /**
     * Validate a simple active master record.
     */
    private function assertActiveMaster(
        AbstractActiveMasterModel $model,
        int $id,
        string $message
    ): void {
        if (!is_array($model->findActive($id))) {
            throw new DomainException($message);
        }
    }

    /**
     * Check whether an active country exists.
     */
    public function countryExists(int $countryId): bool
    {
        if ($countryId <= 0) {
            return false;
        }

        return $this->countryModel
            ->where('id', $countryId)
            ->where('is_active', true)
            ->first() !== null;
    }

    /**
     * Check whether an active state belongs to an active country.
     */
    public function stateBelongsToCountry(
        int $stateId,
        int $countryId
    ): bool {
        if ($stateId <= 0 || $countryId <= 0) {
            return false;
        }

        return $this->stateModel
            ->where('id', $stateId)
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->first() !== null;
    }

    /**
     * Check whether an active city belongs to an active state.
     */
    public function cityBelongsToState(
        int $cityId,
        int $stateId
    ): bool {
        if ($cityId <= 0 || $stateId <= 0) {
            return false;
        }

        return $this->cityModel
            ->where('id', $cityId)
            ->where('state_id', $stateId)
            ->where('is_active', true)
            ->first() !== null;
    }

    /**
     * Return only basic master data required by the prelaunch form.
     *
     * @return array<string, mixed>
     */
    public function prelaunchBasicDetailsOptions(
        ?int $selectedStateId = null
    ): array {
        $india =
            $this->countryModel->findIndia();

        if (!is_array($india)) {
            throw new DomainException(
                'India master data is not configured.'
            );
        }

        return [
            'country' =>
            $india,

            'maritalStatuses' =>
            $this->maritalStatusModel
                ->activeOptions(),

            'heights' =>
            $this->heightModel
                ->activeOptions(),

            'states' =>
            $this->stateModel
                ->activeForCountry(
                    (int) $india['id']
                ),

            'cities' =>
            $selectedStateId !== null
                ? $this->cityModel
                ->activeForState(
                    $selectedStateId
                )
                : [],
        ];
    }

    /**
     * Return only family master data required by the prelaunch form.
     *
     * @return array<string, mixed>
     */
    public function prelaunchFamilyDetailsOptions(
        ?int $selectedCommunityId = null
    ): array {
        return [
            'communities' =>
            $this->communityModel
                ->activeOptions(),
        ];
    }
}
