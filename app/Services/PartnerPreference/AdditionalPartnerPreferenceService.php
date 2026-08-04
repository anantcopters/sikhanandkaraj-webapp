<?php

declare(strict_types=1);

namespace App\Services\PartnerPreference;

use App\Models\MemberPartnerLocationPreferenceModel;
use App\Models\MemberPartnerProfessionalPreferenceModel;
use App\Models\MemberPartnerReligiousPreferenceModel;
use App\Models\MemberPartnerSpecialRequestModel;
use App\Models\PartnerPreferenceSelectionModel;
use App\Models\UserModel;
use App\Services\Profile\ProfileMasterDataService;
use App\Support\BooleanValue;
use App\Support\PartnerPreference\AdditionalPreferenceItem;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Reads and saves Religious, Professional, Location and Special Request
 * partner preferences.
 */
final class AdditionalPartnerPreferenceService
{
    /**
     * Supported professional employment values.
     */
    private const EMPLOYMENT_TYPES = [
        'GOVERNMENT_PSU',
        'PRIVATE',
        'BUSINESS',
        'DEFENSE',
        'SELF_EMPLOYED',
        'NOT_WORKING',
    ];

    public function __construct(
        private readonly UserModel $userModel,

        private readonly MemberPartnerReligiousPreferenceModel
        $religiousModel,

        private readonly MemberPartnerProfessionalPreferenceModel
        $professionalModel,

        private readonly MemberPartnerLocationPreferenceModel
        $locationModel,

        private readonly MemberPartnerSpecialRequestModel
        $specialRequestModel,

        private readonly PartnerPreferenceSelectionModel
        $communitySelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $educationSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $employmentSelectionModel,

        private readonly PartnerPreferenceSelectionModel
        $occupationSelectionModel,

        private readonly ProfileMasterDataService
        $masterDataService,

        private readonly BaseConnection $database
    ) {}

    /**
     * Return overview sections.
     *
     * @return list<array<string, mixed>>
     */
    public function getSummarySections(
        int $userId
    ): array {
        $this->assertUserExists($userId);

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $religious = $this
            ->religiousModel
            ->findForUser($userId);

        $professional = $this
            ->professionalModel
            ->findForUser($userId);

        $location = $this
            ->locationModel
            ->findForUser($userId);

        $specialRequest = $this
            ->specialRequestModel
            ->findForUser($userId);

        $communityIds = $this->selectedValues(
            $religious,
            $this->communitySelectionModel
        );

        $educationIds = $this->selectedValues(
            $professional,
            $this->educationSelectionModel
        );

        $employmentValues = $this->selectedValues(
            $professional,
            $this->employmentSelectionModel
        );

        $occupationIds = $this->selectedValues(
            $professional,
            $this->occupationSelectionModel
        );

        $religiousItems = [
            $this->summaryItem(
                AdditionalPreferenceItem::COMMUNITY,
                $communityIds !== [],
                $this->masterLabels(
                    $communityIds,
                    $masterData['communities']
                ),
                $religious['community_match_mode'] ?? false
            ),
        ];

        $professionalItems = [
            $this->summaryItem(
                AdditionalPreferenceItem::EDUCATION,
                $educationIds !== [],
                $this->masterLabels(
                    $educationIds,
                    $masterData['educations']
                ),
                $professional['education_match_mode'] ?? false
            ),

            $this->summaryItem(
                AdditionalPreferenceItem::EMPLOYED_IN,
                $employmentValues !== [],
                $this->employmentLabels(
                    $employmentValues,
                    $masterData['employmentTypes']
                ),
                $professional['employed_in_match_mode'] ?? false
            ),

            $this->summaryItem(
                AdditionalPreferenceItem::OCCUPATION,
                $occupationIds !== [],
                $this->masterLabels(
                    $occupationIds,
                    $masterData['occupations']
                ),
                $professional['occupation_match_mode'] ?? false
            ),

            $this->summaryItem(
                AdditionalPreferenceItem::ANNUAL_INCOME,
                isset(
                    $professional['annual_income_from_id'],
                    $professional['annual_income_to_id']
                ),
                $this->incomeRangeLabel(
                    $professional['annual_income_from_id'] ?? null,
                    $professional['annual_income_to_id'] ?? null,
                    $masterData['annualIncomes']
                ),
                $professional['annual_income_match_mode'] ?? false
            ),
        ];

        $locationItems = [
            $this->summaryItem(
                AdditionalPreferenceItem::LOCATION,
                is_array($location),
                is_array($location)
                    ? trim(
                        (string) (
                            $location['city_name'] ?? ''
                        )
                    )
                    . ', '
                    . trim(
                        (string) (
                            $location['state_name'] ?? ''
                        )
                    )
                    : null,
                $location['location_match_mode'] ?? false
            ),
        ];

        $specialItems = [
            [
                'key' =>
                AdditionalPreferenceItem::SPECIAL_REQUEST,

                'title' =>
                AdditionalPreferenceItem::title(
                    AdditionalPreferenceItem::SPECIAL_REQUEST
                ),

                'value' =>
                trim(
                    (string) (
                        $specialRequest['request_text']
                        ?? ''
                    )
                ) !== ''
                    ? (string) $specialRequest['request_text']
                    : 'Not added',

                'isCompleted' =>
                is_array($specialRequest)
                    && trim(
                        (string) (
                            $specialRequest['request_text']
                            ?? ''
                        )
                    ) !== '',

                'isCompulsory' => false,
            ],
        ];

        return [
            $this->section(
                'religious',
                'Religious',
                'Community and religious preferences.',
                'ri-group-line text-primary',
                $religiousItems
            ),

            $this->section(
                'professional',
                'Professional Preference',
                'Education, occupation, employment and income preferences.',
                'ri-briefcase-4-line text-primary',
                $professionalItems
            ),

            $this->section(
                'location',
                'Location',
                'Preferred state and city.',
                'ri-map-pin-line text-primary',
                $locationItems
            ),

            $this->section(
                'special-request',
                'Any Special Request',
                'Add any additional partner expectations.',
                'ri-chat-heart-line text-primary',
                $specialItems
            ),
        ];
    }

    /**
     * Return one edit-page payload.
     *
     * @return array<string, mixed>
     */
    public function getItemForUser(
        int $userId,
        string $item
    ): array {
        $this->assertSupportedItem($item);
        $this->assertUserExists($userId);

        $religious = $this
            ->religiousModel
            ->findForUser($userId);

        $professional = $this
            ->professionalModel
            ->findForUser($userId);

        $location = $this
            ->locationModel
            ->findForUser($userId);

        $specialRequest = $this
            ->specialRequestModel
            ->findForUser($userId);

        $selectedStateId = is_array($location)
            && is_numeric(
                $location['state_id'] ?? null
            )
            ? (int) $location['state_id']
            : null;

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $masterData['cities'] =
            $selectedStateId !== null
            ? $this->masterDataService
            ->citiesForState($selectedStateId)
            : [];

        return [
            'item' => $item,

            'itemTitle' =>
            AdditionalPreferenceItem::title($item),

            'sectionKey' =>
            AdditionalPreferenceItem::section($item),

            'religiousPreference' =>
            $religious,

            'professionalPreference' =>
            $professional,

            'locationPreference' =>
            $location,

            'specialRequest' =>
            $specialRequest,

            'selectedValues' => [
                'communities' =>
                $this->selectedValues(
                    $religious,
                    $this->communitySelectionModel
                ),

                'educations' =>
                $this->selectedValues(
                    $professional,
                    $this->educationSelectionModel
                ),

                'employmentTypes' =>
                $this->selectedValues(
                    $professional,
                    $this->employmentSelectionModel
                ),

                'occupations' =>
                $this->selectedValues(
                    $professional,
                    $this->occupationSelectionModel
                ),
            ],

            'masterData' => $masterData,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveItem(
        int $userId,
        string $item,
        array $data
    ): void {
        $this->assertSupportedItem($item);
        $this->assertUserExists($userId);

        $this->database->transException(true);
        $this->database->transStart();

        try {
            match ($item) {
                AdditionalPreferenceItem::COMMUNITY =>
                $this->saveCommunity(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::EDUCATION =>
                $this->saveEducation(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::EMPLOYED_IN =>
                $this->saveEmployment(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::OCCUPATION =>
                $this->saveOccupation(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::ANNUAL_INCOME =>
                $this->saveAnnualIncome(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::LOCATION =>
                $this->saveLocation(
                    $userId,
                    $data
                ),

                AdditionalPreferenceItem::SPECIAL_REQUEST =>
                $this->saveSpecialRequest(
                    $userId,
                    $data
                ),

                default =>
                throw new DomainException(
                    'The selected partner preference is invalid.'
                ),
            };

            $this->database->transComplete();

            if ($this->database->transStatus() === false) {
                throw new RuntimeException(
                    'The partner preference could not be saved.'
                );
            }
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveCommunity(
        int $userId,
        array $data
    ): void {
        $ids = $this->normalizeIntegerIds(
            $data['community_ids'] ?? []
        );

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $this->assertMasterIds(
            $ids,
            $masterData['communities']
        );

        $parentId = $this->ensureParent(
            $this->religiousModel,
            $userId
        );

        $this->assertSaved(
            $this->religiousModel->update(
                $parentId,
                [
                    'community_match_mode' =>
                    $this->strictMode($data),
                ]
            )
        );

        $this->assertSaved(
            $this->communitySelectionModel
                ->replaceSelections(
                    $parentId,
                    $ids
                )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveEducation(
        int $userId,
        array $data
    ): void {
        $ids = $this->normalizeIntegerIds(
            $data['education_ids'] ?? []
        );

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $this->assertMasterIds(
            $ids,
            $masterData['educations']
        );

        $parentId = $this->ensureParent(
            $this->professionalModel,
            $userId
        );

        $this->assertSaved(
            $this->professionalModel->update(
                $parentId,
                [
                    'education_match_mode' =>
                    $this->strictMode($data),
                ]
            )
        );

        $this->assertSaved(
            $this->educationSelectionModel
                ->replaceSelections(
                    $parentId,
                    $ids
                )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveEmployment(
        int $userId,
        array $data
    ): void {
        $values = array_values(
            array_unique(
                array_map(
                    static fn(mixed $value): string =>
                    strtoupper(
                        trim((string) $value)
                    ),
                    is_array(
                        $data['employed_in_values']
                            ?? null
                    )
                        ? $data['employed_in_values']
                        : []
                )
            )
        );

        if (
            $values === []
            || array_diff(
                $values,
                self::EMPLOYMENT_TYPES
            ) !== []
        ) {
            throw new DomainException(
                'Please select valid employment types.'
            );
        }

        $parentId = $this->ensureParent(
            $this->professionalModel,
            $userId
        );

        $this->assertSaved(
            $this->professionalModel->update(
                $parentId,
                [
                    'employed_in_match_mode' =>
                    $this->strictMode($data),
                ]
            )
        );

        $this->assertSaved(
            $this->employmentSelectionModel
                ->replaceSelections(
                    $parentId,
                    $values
                )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveOccupation(
        int $userId,
        array $data
    ): void {
        $ids = $this->normalizeIntegerIds(
            $data['occupation_ids'] ?? []
        );

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $this->assertMasterIds(
            $ids,
            $masterData['occupations']
        );

        $parentId = $this->ensureParent(
            $this->professionalModel,
            $userId
        );

        $this->assertSaved(
            $this->professionalModel->update(
                $parentId,
                [
                    'occupation_match_mode' =>
                    $this->strictMode($data),
                ]
            )
        );

        $this->assertSaved(
            $this->occupationSelectionModel
                ->replaceSelections(
                    $parentId,
                    $ids
                )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveAnnualIncome(
        int $userId,
        array $data
    ): void {
        $fromId = (int) (
            $data['annual_income_from_id'] ?? 0
        );

        $toId = (int) (
            $data['annual_income_to_id'] ?? 0
        );

        $masterData = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions();

        $this->assertMasterIds(
            [$fromId, $toId],
            $masterData['annualIncomes']
        );

        $fromRow = $this->masterRow(
            $fromId,
            $masterData['annualIncomes']
        );

        $toRow = $this->masterRow(
            $toId,
            $masterData['annualIncomes']
        );

        if (
            (int) ($fromRow['min_amount'] ?? 0)
            > (int) ($toRow['min_amount'] ?? 0)
        ) {
            throw new DomainException(
                'Minimum annual income cannot exceed maximum annual income.'
            );
        }

        $parentId = $this->ensureParent(
            $this->professionalModel,
            $userId
        );

        $this->assertSaved(
            $this->professionalModel->update(
                $parentId,
                [
                    'annual_income_from_id' =>
                    $fromId,

                    'annual_income_to_id' =>
                    $toId,

                    'annual_income_match_mode' =>
                    $this->strictMode($data),
                ]
            )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveLocation(
        int $userId,
        array $data
    ): void {
        $stateId = (int) (
            $data['state_id'] ?? 0
        );

        $cityId = (int) (
            $data['city_id'] ?? 0
        );

        $states = $this
            ->masterDataService
            ->additionalPartnerPreferenceOptions()['states'];

        $this->assertMasterIds(
            [$stateId],
            $states
        );

        $cities = $this
            ->masterDataService
            ->citiesForState($stateId);

        $this->assertMasterIds(
            [$cityId],
            $cities
        );

        $existing = $this
            ->locationModel
            ->findForUser($userId);

        $payload = [
            'user_id' => $userId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'location_match_mode' =>
            $this->strictMode($data),
        ];

        $saved = is_array($existing)
            ? $this->locationModel->update(
                (int) $existing['id'],
                $payload
            )
            : $this->locationModel->insert(
                $payload,
                false
            );

        $this->assertSaved($saved);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveSpecialRequest(
        int $userId,
        array $data
    ): void {
        $requestText = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) (
                    $data['request_text'] ?? ''
                )
            )
        ) ?? '';

        if (
            mb_strlen($requestText) < 10
            || mb_strlen($requestText) > 1000
        ) {
            throw new DomainException(
                'Special request must contain between 10 and 1000 characters.'
            );
        }

        $existing = $this
            ->specialRequestModel
            ->findForUser($userId);

        $payload = [
            'user_id' => $userId,
            'request_text' => $requestText,
        ];

        $saved = is_array($existing)
            ? $this->specialRequestModel->update(
                (int) $existing['id'],
                $payload
            )
            : $this->specialRequestModel->insert(
                $payload,
                false
            );

        $this->assertSaved($saved);
    }

    /**
     * @param \CodeIgniter\Model $model
     */
    private function ensureParent(
        \CodeIgniter\Model $model,
        int $userId
    ): int {
        $existing = $model
            ->where('user_id', $userId)
            ->first();

        if (is_array($existing)) {
            return (int) $existing['id'];
        }

        $insertId = $model->insert(
            [
                'user_id' => $userId,
            ],
            true
        );

        if (!is_numeric($insertId)) {
            throw new RuntimeException(
                'The partner preference could not be created.'
            );
        }

        return (int) $insertId;
    }

    /**
     * @param array<string, mixed>|null $parent
     *
     * @return list<int|string>
     */
    private function selectedValues(
        ?array $parent,
        PartnerPreferenceSelectionModel $model
    ): array {
        if (!is_array($parent)) {
            return [];
        }

        return $model->selectedValues(
            (int) $parent['id']
        );
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function section(
        string $key,
        string $title,
        string $description,
        string $icon,
        array $items
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,

            'isCompleted' =>
            count($items) > 0
                && count(
                    array_filter(
                        $items,
                        static fn(array $item): bool => ($item['isCompleted'] ?? false)
                            === true
                    )
                ) === count($items),

            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryItem(
        string $item,
        bool $completed,
        ?string $value,
        mixed $strict
    ): array {
        return [
            'key' => $item,

            'title' =>
            AdditionalPreferenceItem::title(
                $item
            ),

            'value' =>
            $value !== null
                && trim($value) !== ''
                ? $value
                : 'Not added',

            'isCompleted' => $completed,

            'isCompulsory' =>
            BooleanValue::fromDatabase(
                $strict
            ),
        ];
    }

    /**
     * @param list<int|string>           $ids
     * @param list<array<string, mixed>> $rows
     */
    private function masterLabels(
        array $ids,
        array $rows
    ): ?string {
        $labels = [];

        foreach ($rows as $row) {
            if (
                !in_array(
                    (int) ($row['id'] ?? 0),
                    array_map('intval', $ids),
                    true
                )
            ) {
                continue;
            }

            $label = trim(
                (string) (
                    $row['name']
                    ?? $row['display_name']
                    ?? ''
                )
            );

            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return $labels !== []
            ? implode(', ', $labels)
            : null;
    }

    /**
     * @param list<int|string>           $values
     * @param list<array<string, string>> $options
     */
    private function employmentLabels(
        array $values,
        array $options
    ): ?string {
        $labels = [];

        foreach ($options as $option) {
            if (
                in_array(
                    $option['value'],
                    $values,
                    true
                )
            ) {
                $labels[] = $option['label'];
            }
        }

        return $labels !== []
            ? implode(', ', $labels)
            : null;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function incomeRangeLabel(
        mixed $fromId,
        mixed $toId,
        array $rows
    ): ?string {
        if (
            !is_numeric($fromId)
            || !is_numeric($toId)
        ) {
            return null;
        }

        $from = $this->masterRow(
            (int) $fromId,
            $rows
        );

        $to = $this->masterRow(
            (int) $toId,
            $rows
        );

        $fromLabel = trim(
            (string) (
                $from['display_name']
                ?? $from['name']
                ?? ''
            )
        );

        $toLabel = trim(
            (string) (
                $to['display_name']
                ?? $to['name']
                ?? ''
            )
        );

        return $fromLabel !== ''
            && $toLabel !== ''
            ? $fromLabel . ' to ' . $toLabel
            : null;
    }

    /**
     * @param list<int>                  $submittedIds
     * @param list<array<string, mixed>> $rows
     */
    private function assertMasterIds(
        array $submittedIds,
        array $rows
    ): void {
        $validIds = array_map(
            static fn(array $row): int =>
            (int) ($row['id'] ?? 0),
            $rows
        );

        if (
            $submittedIds === []
            || array_diff(
                $submittedIds,
                $validIds
            ) !== []
        ) {
            throw new DomainException(
                'One or more selected values are invalid.'
            );
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function masterRow(
        int $id,
        array $rows
    ): array {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        throw new DomainException(
            'The selected master value is invalid.'
        );
    }

    /**
     * @param mixed $values
     *
     * @return list<int>
     */
    private function normalizeIntegerIds(
        mixed $values
    ): array {
        if (!is_array($values)) {
            return [];
        }

        $ids = [];

        foreach ($values as $value) {
            $value = trim((string) $value);

            if (
                ctype_digit($value)
                && (int) $value > 0
            ) {
                $ids[] = (int) $value;
            }
        }

        return array_values(
            array_unique($ids)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function strictMode(array $data): bool
    {
        return BooleanValue::fromDatabase(
            $data['is_compulsory'] ?? false
        );
    }

    private function assertSaved(mixed $saved): void
    {
        if ($saved === false) {
            throw new RuntimeException(
                'The partner preference could not be saved.'
            );
        }
    }

    private function assertSupportedItem(
        string $item
    ): void {
        if (
            !AdditionalPreferenceItem::isValid(
                $item
            )
        ) {
            throw new DomainException(
                'The selected partner preference is invalid.'
            );
        }
    }

    private function assertUserExists(
        int $userId
    ): void {
        if (!is_array(
            $this->userModel->find($userId)
        )) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }
    }
}
