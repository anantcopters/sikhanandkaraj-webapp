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
        private readonly MasterAnnualIncomeModel $annualIncomeModel
    ) {}

    /**
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
            $this->maritalStatusModel->activeOptions(),

            'heights' =>
            $this->heightModel->activeOptions(),

            'motherTongues' =>
            $this->motherTongueModel->activeOptions(),

            'states' =>
            $this->stateModel->activeForCountry(
                (int) $india['id']
            ),

            'cities' => $selectedStateId !== null
                ? $this->cityModel->activeForState(
                    $selectedStateId
                )
                : [],
        ];
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
     * @return array<string, mixed>
     */
    public function educationProfessionOptions(): array
    {
        return [
            'educations' =>
            $this->educationModel->activeOptions(),

            'occupations' =>
            $this->occupationModel->activeOptions(),

            'annualIncomes' =>
            $this->annualIncomeModel->activeOptions(),

            /*
         * Keep enum values centralized here rather than duplicating
         * them in the view and service.
         */
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
            ->where('id', $educationId)
            ->where('is_active', true)
            ->first();

        if (!is_array($education)) {
            throw new \DomainException(
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
}
