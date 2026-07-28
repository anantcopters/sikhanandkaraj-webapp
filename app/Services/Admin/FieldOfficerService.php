<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\FieldOfficerModel;
use App\Services\Admin\Audit\AdminAuditAction;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Profile\ProfileMasterDataService;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Handles Field Officer business rules and persistence.
 */
final class FieldOfficerService
{
    private const CODE_PREFIX = 'FOSAK';

    private const CODE_DIGITS = 6;

    public function __construct(
        private readonly FieldOfficerModel $fieldOfficerModel,
        private readonly ProfileMasterDataService $masterDataService,
        private readonly AdminAuditService $auditService,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listFieldOfficers(): array
    {
        return $this->fieldOfficerModel
            ->listWithLocation();
    }

    /**
     * @return array<string, mixed>
     */
    public function findForEdit(
        int $fieldOfficerId
    ): array {
        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'Invalid Field Officer.'
            );
        }

        $record = $this->fieldOfficerModel
            ->findActiveRecord($fieldOfficerId);

        if ($record === null) {
            throw new RuntimeException(
                'Field Officer was not found.'
            );
        }

        return $record;
    }

    /**
     * Create a Field Officer as INACTIVE.
     *
     * @param array<string, mixed> $input
     */
    public function create(
        array $input,
        int $createdBy
    ): int {
        if ($createdBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator could not be identified.'
            );
        }

        $mobileNumber = $this->normalizeMobileNumber(
            (string) ($input['mobile_number'] ?? '')
        );

        if (
            $this->fieldOfficerModel
            ->mobileExists($mobileNumber)
        ) {
            throw new RuntimeException(
                'A Field Officer with this mobile number already exists.'
            );
        }

        $countryId = (int) (
            $input['country_id'] ?? 0
        );

        $stateId = (int) (
            $input['state_id'] ?? 0
        );

        $cityId = (int) (
            $input['city_id'] ?? 0
        );

        $this->assertValidLocation(
            $countryId,
            $stateId,
            $cityId
        );

        $upiId = $this->nullableText(
            $input['upi_id'] ?? null
        );

        $this->database->transBegin();

        try {
            $officerCode =
                $this->generateOfficerCode();

            $inserted = $this
                ->fieldOfficerModel
                ->insert([
                    'officer_code' =>
                    $officerCode,
                    'full_name' => trim(
                        (string) (
                            $input['full_name'] ?? ''
                        )
                    ),
                    'mobile_number' =>
                    $mobileNumber,
                    'country_id' =>
                    $countryId,
                    'state_id' =>
                    $stateId,
                    'city_id' =>
                    $cityId,
                    'address' =>
                    $this->nullableText(
                        $input['address'] ?? null
                    ),
                    'upi_id' =>
                    $upiId,
                    'account_status' =>
                    FieldOfficerModel::STATUS_INACTIVE,
                    'activated_at' =>
                    null,
                    'deactivated_at' =>
                    null,
                    'created_by' =>
                    $createdBy,
                ], true);

            if ($inserted === false) {
                throw new RuntimeException(
                    'The Field Officer could not be created.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The Field Officer transaction failed.'
                );
            }

            $this->database->transCommit();

            $fieldOfficerId = (int) $inserted;

            /*
             * Audit is recorded after commit. Audit failure must not
             * roll back an already completed business transaction.
             */
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction::FIELD_OFFICER_CREATED,
                    targetType: 'FIELD_OFFICER',
                    targetId: $fieldOfficerId,
                    targetLabel: $officerCode,
                    description: 'Field Officer was created in inactive status.',
                    afterData: [
                        'officer_code' =>
                        $officerCode,
                        'full_name' =>
                        trim(
                            (string) (
                                $input['full_name'] ?? ''
                            )
                        ),
                        'mobile_number' =>
                        $this->maskMobile(
                            $mobileNumber
                        ),
                        'country_id' =>
                        $countryId,
                        'state_id' =>
                        $stateId,
                        'city_id' =>
                        $cityId,
                        'upi_id_present' =>
                        $upiId !== null,
                        'account_status' =>
                        FieldOfficerModel::STATUS_INACTIVE,
                    ]
                )
            );

            return $fieldOfficerId;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            if (
                $this->isUniqueMobileViolation(
                    $exception
                )
            ) {
                throw new RuntimeException(
                    'A Field Officer with this mobile number already exists.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }
    }

    /**
     * Update only location, address and UPI ID.
     *
     * @param array<string, mixed> $input
     */
    public function update(
        int $fieldOfficerId,
        array $input,
        int $updatedBy
    ): void {
        $existing = $this->findForEdit(
            $fieldOfficerId
        );

        if ($updatedBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator could not be identified.'
            );
        }

        $countryId = (int) (
            $input['country_id'] ?? 0
        );

        $stateId = (int) (
            $input['state_id'] ?? 0
        );

        $cityId = (int) (
            $input['city_id'] ?? 0
        );

        $this->assertValidLocation(
            $countryId,
            $stateId,
            $cityId
        );

        $upiId = $this->nullableText(
            $input['upi_id'] ?? null
        );

        $beforeData = [
            'country_id' =>
            (int) $existing['country_id'],
            'state_id' =>
            (int) $existing['state_id'],
            'city_id' =>
            (int) $existing['city_id'],
            'address' =>
            $this->nullableText(
                $existing['address'] ?? null
            ),
            'upi_id_present' =>
            $this->hasUpiId($existing),
            'account_status' =>
            (string) $existing['account_status'],
        ];

        $afterData = [
            'country_id' =>
            $countryId,
            'state_id' =>
            $stateId,
            'city_id' =>
            $cityId,
            'address' =>
            $this->nullableText(
                $input['address'] ?? null
            ),
            'upi_id_present' =>
            $upiId !== null,
            'account_status' =>
            (string) $existing['account_status'],
        ];

        $removingRequiredUpi =
            (string) $existing['account_status']
            === FieldOfficerModel::STATUS_ACTIVE
            && $upiId === null;

        $updateData = [
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'address' => $afterData['address'],
            'upi_id' => $upiId,
            'updated_by' => $updatedBy,
        ];

        if ($removingRequiredUpi) {
            $updateData['account_status'] =
                FieldOfficerModel::STATUS_INACTIVE;

            $updateData['deactivated_at'] =
                date('Y-m-d H:i:s');

            $afterData['account_status'] =
                FieldOfficerModel::STATUS_INACTIVE;
        }

        $updated = $this
            ->fieldOfficerModel
            ->update(
                (int) $existing['id'],
                $updateData
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The Field Officer could not be updated.'
            );
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction::FIELD_OFFICER_UPDATED,
                targetType: 'FIELD_OFFICER',
                targetId: $fieldOfficerId,
                targetLabel: (string) $existing['officer_code'],
                description: 'Field Officer details were updated.',
                beforeData: $beforeData,
                afterData: $afterData
            )
        );
    }

    /**
     * Activate a Field Officer.
     *
     * An officer may be activated only when a UPI ID exists.
     */
    public function activate(
        int $fieldOfficerId,
        int $updatedBy
    ): void {
        $existing = $this->findForEdit(
            $fieldOfficerId
        );

        if ($updatedBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator could not be identified.'
            );
        }

        if (
            (string) $existing['account_status']
            === FieldOfficerModel::STATUS_ACTIVE
        ) {
            throw new RuntimeException(
                'The Field Officer is already active.'
            );
        }

        if (!$this->hasUpiId($existing)) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_ACTIVATION_DENIED,
                    outcome: 'DENIED',
                    targetType: 'FIELD_OFFICER',
                    targetId: $fieldOfficerId,
                    targetLabel: (string) $existing['officer_code'],
                    description: 'Field Officer activation was denied because UPI ID was not present.',
                    beforeData: [
                        'account_status' =>
                        (string) $existing['account_status'],
                        'upi_id_present' =>
                        false,
                    ],
                    metadata: [
                        'reason' =>
                        'UPI_ID_REQUIRED',
                    ]
                )
            );

            throw new RuntimeException(
                'The Field Officer cannot be activated because a UPI ID is not present. Edit the Field Officer, add a valid UPI ID and try again.'
            );
        }

        $activatedAt =
            date('Y-m-d H:i:s');

        $updated = $this
            ->fieldOfficerModel
            ->update(
                $fieldOfficerId,
                [
                    'account_status' =>
                    FieldOfficerModel::STATUS_ACTIVE,
                    'activated_at' =>
                    $activatedAt,
                    'deactivated_at' =>
                    null,
                    'updated_by' =>
                    $updatedBy,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The Field Officer could not be activated.'
            );
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction::FIELD_OFFICER_ACTIVATED,
                targetType: 'FIELD_OFFICER',
                targetId: $fieldOfficerId,
                targetLabel: (string) $existing['officer_code'],
                description: 'Field Officer was activated.',
                beforeData: [
                    'account_status' =>
                    FieldOfficerModel::STATUS_INACTIVE,
                    'activated_at' =>
                    $existing['activated_at'] ?? null,
                    'deactivated_at' =>
                    $existing['deactivated_at'] ?? null,
                    'upi_id_present' =>
                    true,
                ],
                afterData: [
                    'account_status' =>
                    FieldOfficerModel::STATUS_ACTIVE,
                    'activated_at' =>
                    $activatedAt,
                    'deactivated_at' =>
                    null,
                    'upi_id_present' =>
                    true,
                ]
            )
        );
    }

    /**
     * Deactivate a Field Officer.
     */
    public function deactivate(
        int $fieldOfficerId,
        int $updatedBy
    ): void {
        $existing = $this->findForEdit(
            $fieldOfficerId
        );

        if ($updatedBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator could not be identified.'
            );
        }

        if (
            (string) $existing['account_status']
            === FieldOfficerModel::STATUS_INACTIVE
        ) {
            throw new RuntimeException(
                'The Field Officer is already inactive.'
            );
        }

        $deactivatedAt =
            date('Y-m-d H:i:s');

        $updated = $this
            ->fieldOfficerModel
            ->update(
                $fieldOfficerId,
                [
                    'account_status' =>
                    FieldOfficerModel::STATUS_INACTIVE,
                    'deactivated_at' =>
                    $deactivatedAt,
                    'updated_by' =>
                    $updatedBy,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The Field Officer could not be deactivated.'
            );
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_DEACTIVATED,
                targetType: 'FIELD_OFFICER',
                targetId: $fieldOfficerId,
                targetLabel: (string) $existing['officer_code'],
                description: 'Field Officer was deactivated.',
                beforeData: [
                    'account_status' =>
                    FieldOfficerModel::STATUS_ACTIVE,
                    'activated_at' =>
                    $existing['activated_at'] ?? null,
                    'deactivated_at' =>
                    $existing['deactivated_at'] ?? null,
                ],
                afterData: [
                    'account_status' =>
                    FieldOfficerModel::STATUS_INACTIVE,
                    'activated_at' =>
                    $existing['activated_at'] ?? null,
                    'deactivated_at' =>
                    $deactivatedAt,
                ]
            )
        );
    }

    private function generateOfficerCode(): string
    {
        $query = $this->database->query(
            "SELECT nextval(
                'field_officer_code_seq'
            ) AS number"
        );

        $row = $query->getRowArray();

        $number = (int) (
            $row['number'] ?? 0
        );

        if (
            $number <= 0
            || $number > 999999
        ) {
            throw new RuntimeException(
                'The Field Officer code range has been exhausted.'
            );
        }

        return self::CODE_PREFIX
            . str_pad(
                (string) $number,
                self::CODE_DIGITS,
                '0',
                STR_PAD_LEFT
            );
    }

    private function assertValidLocation(
        int $countryId,
        int $stateId,
        int $cityId
    ): void {
        if (
            !$this->masterDataService
                ->countryExists($countryId)
        ) {
            throw new RuntimeException(
                'The selected country is invalid or inactive.'
            );
        }

        if (
            !$this->masterDataService
                ->stateBelongsToCountry(
                    $stateId,
                    $countryId
                )
        ) {
            throw new RuntimeException(
                'The selected state does not belong to the selected country.'
            );
        }

        if (
            !$this->masterDataService
                ->cityBelongsToState(
                    $cityId,
                    $stateId
                )
        ) {
            throw new RuntimeException(
                'The selected city does not belong to the selected state.'
            );
        }
    }

    /**
     * @param array<string, mixed> $fieldOfficer
     */
    private function hasUpiId(
        array $fieldOfficer
    ): bool {
        return trim(
            (string) (
                $fieldOfficer['upi_id'] ?? ''
            )
        ) !== '';
    }

    private function normalizeMobileNumber(
        string $mobileNumber
    ): string {
        return preg_replace(
            '/\D+/',
            '',
            trim($mobileNumber)
        ) ?? '';
    }

    private function nullableText(
        mixed $value
    ): ?string {
        $normalized = trim(
            (string) $value
        );

        return $normalized !== ''
            ? $normalized
            : null;
    }

    private function maskMobile(
        string $mobileNumber
    ): string {
        $mobileNumber = trim(
            $mobileNumber
        );

        if (strlen($mobileNumber) <= 4) {
            return str_repeat(
                '*',
                strlen($mobileNumber)
            );
        }

        return str_repeat(
            '*',
            strlen($mobileNumber) - 4
        ) . substr($mobileNumber, -4);
    }

    private function isUniqueMobileViolation(
        Throwable $exception
    ): bool {
        $message = strtolower(
            $exception->getMessage()
        );

        return str_contains(
            $message,
            'uq_field_officers_mobile'
        ) || (
            str_contains(
                $message,
                'duplicate key'
            )
            && str_contains(
                $message,
                'mobile_number'
            )
        );
    }
}
