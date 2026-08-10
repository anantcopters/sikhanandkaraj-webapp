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

    private const CODE_GENERATION_ATTEMPTS = 10;

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
     * Create a Field Officer.
     *
     * A valid UPI ID makes the Field Officer active immediately.
     * Without a UPI ID, the Field Officer starts inactive.
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

        $mobileNumber =
            $this->normalizeMobileNumber(
                (string) (
                    $input['mobile_number']
                    ?? ''
                )
            );

        if (
            $this->fieldOfficerModel
            ->mobileExists(
                $mobileNumber
            )
        ) {
            throw new RuntimeException(
                'A Field Officer with this mobile number already exists.'
            );
        }

        $aadhaarNumber =
            preg_replace(
                '/\D+/',
                '',
                (string) (
                    $input['aadhaar_number']
                    ?? ''
                )
            ) ?? '';

        $panNumber =
            strtoupper(
                trim(
                    (string) (
                        $input['pan_number']
                        ?? ''
                    )
                )
            );

        if (
            $this->fieldOfficerModel
            ->aadhaarExists(
                $aadhaarNumber
            )
        ) {
            throw new RuntimeException(
                'A Field Officer with this Aadhaar number already exists.'
            );
        }

        if (
            $this->fieldOfficerModel
            ->panExists(
                $panNumber
            )
        ) {
            throw new RuntimeException(
                'A Field Officer with this PAN number already exists.'
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

        $upiId =
            $this->nullableText(
                $input['upi_id']
                    ?? null
            );

        /*
     * UPI validation has already run through
     * FieldOfficerValidation.
     */
        $initialStatus =
            $upiId !== null
            ? FieldOfficerModel::STATUS_ACTIVE
            : FieldOfficerModel::STATUS_INACTIVE;

        $activatedAt =
            $initialStatus
            === FieldOfficerModel::STATUS_ACTIVE
            ? date('Y-m-d H:i:s')
            : null;

        $this->database->transBegin();

        try {
            $officerCode =
                $this->generateOfficerCode();

            $inserted =
                $this->fieldOfficerModel
                ->insert(
                    [
                        'officer_code' =>
                        $officerCode,

                        'full_name' =>
                        trim(
                            (string) (
                                $input['full_name']
                                ?? ''
                            )
                        ),

                        'mobile_number' =>
                        $mobileNumber,

                        'aadhaar_number' =>
                        $aadhaarNumber,

                        'pan_number' =>
                        $panNumber,

                        'country_id' =>
                        $countryId,

                        'state_id' =>
                        $stateId,

                        'city_id' =>
                        $cityId,

                        'address' =>
                        $this->nullableText(
                            $input['address']
                                ?? null
                        ),

                        'upi_id' =>
                        $upiId,

                        'account_status' =>
                        $initialStatus,

                        'activated_at' =>
                        $activatedAt,

                        'deactivated_at' =>
                        null,

                        'created_by' =>
                        $createdBy,
                    ],
                    true
                );

            if ($inserted === false) {
                throw new RuntimeException(
                    'The Field Officer could not be created.'
                );
            }

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The Field Officer transaction failed.'
                );
            }

            $this->database->transCommit();

            $fieldOfficerId =
                (int) $inserted;

            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_CREATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: $officerCode,

                    description: $initialStatus
                        === FieldOfficerModel
                        ::STATUS_ACTIVE
                        ? 'Field Officer was created in active status because a UPI ID was provided.'
                        : 'Field Officer was created in inactive status because no UPI ID was provided.',

                    afterData: [
                        'officer_code' =>
                        $officerCode,

                        'full_name' =>
                        trim(
                            (string) (
                                $input['full_name']
                                ?? ''
                            )
                        ),

                        'mobile_number' =>
                        $this->maskMobile(
                            $mobileNumber
                        ),

                        /*
                     * Do not put Aadhaar/PAN values
                     * into audit logs.
                     */
                        'aadhaar_present' =>
                        true,

                        'pan_present' =>
                        true,

                        'country_id' =>
                        $countryId,

                        'state_id' =>
                        $stateId,

                        'city_id' =>
                        $cityId,

                        'upi_id_present' =>
                        $upiId !== null,

                        'account_status' =>
                        $initialStatus,

                        'activated_at' =>
                        $activatedAt,
                    ]
                )
            );

            if (
                $initialStatus
                === FieldOfficerModel::STATUS_ACTIVE
            ) {
                $this->auditService->record(
                    new AdminAuditEvent(
                        action: AdminAuditAction
                        ::FIELD_OFFICER_ACTIVATED,

                        targetType: 'FIELD_OFFICER',

                        targetId: $fieldOfficerId,

                        targetLabel: $officerCode,

                        description: 'Field Officer was activated during creation because a valid UPI ID was supplied.',

                        beforeData: [
                            'account_status' =>
                            null,

                            'upi_id_present' =>
                            true,
                        ],

                        afterData: [
                            'account_status' =>
                            FieldOfficerModel
                            ::STATUS_ACTIVE,

                            'activated_at' =>
                            $activatedAt,

                            'upi_id_present' =>
                            true,
                        ],

                        metadata: [
                            'activation_source' =>
                            'FIELD_OFFICER_CREATION',
                        ]
                    )
                );
            }

            return $fieldOfficerId;
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

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
     * Update the editable Field Officer details.
     *
     * Account status is automatically synchronized with UPI availability:
     *
     * - UPI present: ACTIVE
     * - UPI absent: INACTIVE
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

        $address = $this->nullableText(
            $input['address'] ?? null
        );

        $upiId = $this->nullableText(
            $input['upi_id'] ?? null
        );

        $existingStatus = (string) (
            $existing['account_status']
            ?? FieldOfficerModel::STATUS_INACTIVE
        );

        /*
     * Field Officer status is derived from UPI availability.
     * This keeps activation rules consistent in create and edit flows.
     */
        $newStatus = $upiId !== null
            ? FieldOfficerModel::STATUS_ACTIVE
            : FieldOfficerModel::STATUS_INACTIVE;

        $statusChanged =
            $existingStatus !== $newStatus;

        $activatedAt =
            $existing['activated_at'] ?? null;

        $deactivatedAt =
            $existing['deactivated_at'] ?? null;

        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel::STATUS_ACTIVE
        ) {
            $activatedAt = date('Y-m-d H:i:s');
            $deactivatedAt = null;
        }

        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel::STATUS_INACTIVE
        ) {
            $deactivatedAt = date('Y-m-d H:i:s');
        }

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
            $existingStatus,

            'activated_at' =>
            $existing['activated_at'] ?? null,

            'deactivated_at' =>
            $existing['deactivated_at'] ?? null,
        ];

        $afterData = [
            'country_id' =>
            $countryId,

            'state_id' =>
            $stateId,

            'city_id' =>
            $cityId,

            'address' =>
            $address,

            'upi_id_present' =>
            $upiId !== null,

            'account_status' =>
            $newStatus,

            'activated_at' =>
            $activatedAt,

            'deactivated_at' =>
            $deactivatedAt,
        ];

        $this->database->transBegin();

        try {
            $updated = $this
                ->fieldOfficerModel
                ->update(
                    (int) $existing['id'],
                    [
                        'country_id' =>
                        $countryId,

                        'state_id' =>
                        $stateId,

                        'city_id' =>
                        $cityId,

                        'address' =>
                        $address,

                        'upi_id' =>
                        $upiId,

                        'account_status' =>
                        $newStatus,

                        'activated_at' =>
                        $activatedAt,

                        'deactivated_at' =>
                        $deactivatedAt,

                        'updated_by' =>
                        $updatedBy,
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'The Field Officer could not be updated.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The Field Officer update transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_UPDATED,

                targetType: 'FIELD_OFFICER',

                targetId: $fieldOfficerId,

                targetLabel: (string) $existing['officer_code'],

                description: 'Field Officer details were updated.',

                beforeData: $beforeData,

                afterData: $afterData
            )
        );

        /*
     * Record the status transition separately so activation and
     * deactivation reports remain complete.
     */
        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel::STATUS_ACTIVE
        ) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_ACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: (string) $existing['officer_code'],

                    description: 'Field Officer was automatically activated after a valid UPI ID was added.',

                    beforeData: [
                        'account_status' =>
                        $existingStatus,

                        'upi_id_present' =>
                        false,

                        'activated_at' =>
                        $existing['activated_at']
                            ?? null,
                    ],

                    afterData: [
                        'account_status' =>
                        FieldOfficerModel
                        ::STATUS_ACTIVE,

                        'upi_id_present' =>
                        true,

                        'activated_at' =>
                        $activatedAt,

                        'deactivated_at' =>
                        null,
                    ],

                    metadata: [
                        'activation_source' =>
                        'FIELD_OFFICER_UPDATE',
                    ]
                )
            );
        }

        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel::STATUS_INACTIVE
        ) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_DEACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: (string) $existing['officer_code'],

                    description: 'Field Officer was automatically deactivated because the UPI ID was removed.',

                    beforeData: [
                        'account_status' =>
                        $existingStatus,

                        'upi_id_present' =>
                        true,

                        'activated_at' =>
                        $existing['activated_at']
                            ?? null,

                        'deactivated_at' =>
                        $existing['deactivated_at']
                            ?? null,
                    ],

                    afterData: [
                        'account_status' =>
                        FieldOfficerModel
                        ::STATUS_INACTIVE,

                        'upi_id_present' =>
                        false,

                        'activated_at' =>
                        $activatedAt,

                        'deactivated_at' =>
                        $deactivatedAt,
                    ],

                    metadata: [
                        'deactivation_source' =>
                        'UPI_REMOVED_DURING_UPDATE',
                    ]
                )
            );
        }
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

    /**
     * Generate a cryptographically secure random Field Officer code.
     *
     * The database unique constraint remains the final safeguard against
     * an unlikely collision.
     */
    private function generateOfficerCode(): string
    {
        $minimum = 10 ** (
            self::CODE_DIGITS - 1
        );

        $maximum = (
            10 ** self::CODE_DIGITS
        ) - 1;

        for (
            $attempt = 1;
            $attempt <= self::CODE_GENERATION_ATTEMPTS;
            $attempt++
        ) {
            $officerCode =
                self::CODE_PREFIX
                . (string) random_int(
                    $minimum,
                    $maximum
                );

            if (
                !$this->fieldOfficerModel
                    ->officerCodeExists(
                        $officerCode
                    )
            ) {
                return $officerCode;
            }
        }

        throw new RuntimeException(
            'A unique Field Officer code could not be generated. Please try again.'
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
