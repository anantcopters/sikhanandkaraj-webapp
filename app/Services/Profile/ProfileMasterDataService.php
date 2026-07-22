<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MasterCityModel;
use App\Models\MasterCountryModel;
use App\Models\MasterHeightModel;
use App\Models\MasterMaritalStatusModel;
use App\Models\MasterMotherTongueModel;
use App\Models\MasterStateModel;
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
        private readonly MasterCityModel $cityModel
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
}
