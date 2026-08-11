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
 * Handles SAK Volunteer business rules and persistence.
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
                'Invalid SAK Volunteer.'
            );
        }

        $record = $this->fieldOfficerModel
            ->findActiveRecord($fieldOfficerId);

        if ($record === null) {
            throw new RuntimeException(
                'SAK Volunteer was not found.'
            );
        }

        return $record;
    }

    /**
     * Create a SAK Volunteer.
     *
     * A valid UPI ID makes the SAK Volunteer active immediately.
     * Without a UPI ID, the SAK Volunteer starts inactive.
     *
     * Business persistence and audit persistence are deliberately
     * separated. Once the SAK Volunteer transaction commits,
     * an audit failure must never make the caller believe that
     * SAK Volunteer creation failed.
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
                'A SAK Volunteer with this mobile number already exists.'
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
                'A SAK Volunteer with this Aadhaar number already exists.'
            );
        }

        if (
            $this->fieldOfficerModel
            ->panExists(
                $panNumber
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this PAN number already exists.'
            );
        }

        $countryId = (int) (
            $input['country_id']
            ?? 0
        );

        $stateId = (int) (
            $input['state_id']
            ?? 0
        );

        $cityId = (int) (
            $input['city_id']
            ?? 0
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

        if (
            $upiId !== null
            && $this->fieldOfficerModel
            ->upiExists(
                $upiId
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this UPI ID already exists.'
            );
        }

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

        /*
     * Generate the code before beginning the transaction.
     *
     * Code generation performs existence checks and does
     * not itself need to be part of this transaction.
     */
        $officerCode =
            $this->generateOfficerCode();

        /*
     * ------------------------------------------------------
     * BUSINESS TRANSACTION
     * ------------------------------------------------------
     *
     * Only SAK Volunteer persistence belongs inside this
     * transaction.
     */
        $this->database->transBegin();

        try {
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
                    'The SAK Volunteer could not be created.'
                );
            }

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The SAK Volunteer transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            /*
         * Rollback belongs only to the database persistence
         * scope. Nothing after this block is allowed to cause
         * a rollback attempt.
         */
            if (
                $this->database
                ->transStatus()
                !== false
            ) {
                $this->database
                    ->transRollback();
            } else {
                /*
             * Even when transStatus() is false, explicitly
             * close the failed transaction.
             */
                $this->database
                    ->transRollback();
            }

            if (
                $this->isUniqueMobileViolation(
                    $exception
                )
            ) {
                throw new RuntimeException(
                    'A SAK Volunteer with this mobile number already exists.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        /*
     * At this point the SAK Volunteer exists permanently.
     *
     * From here onward, nothing should cause create() to
     * report that SAK Volunteer creation failed.
     */
        $fieldOfficerId =
            (int) $inserted;

        /*
     * ------------------------------------------------------
     * AUDIT — NON-BLOCKING AFTER COMMIT
     * ------------------------------------------------------
     */
        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_CREATED,

                targetType: 'FIELD_OFFICER',

                targetId: $fieldOfficerId,

                targetLabel: $officerCode,

                description: $initialStatus
                    === FieldOfficerModel::STATUS_ACTIVE
                    ? 'SAK Volunteer was created in active status because a UPI ID was provided.'
                    : 'SAK Volunteer was created in inactive status because no UPI ID was provided.',

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
                 * Aadhaar and PAN are sensitive identity
                 * information and must not be written to
                 * audit logs.
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

        /*
     * Record a separate activation event when creation
     * itself activates the SAK Volunteer.
     *
     * This audit is also intentionally non-blocking.
     */
        if (
            $initialStatus
            === FieldOfficerModel::STATUS_ACTIVE
        ) {
            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_ACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: $officerCode,

                    description: 'SAK Volunteer was activated during creation because a valid UPI ID was supplied.',

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
    }

    /**
     * Record an administrator audit event without allowing an
     * audit persistence failure to invalidate an already completed
     * business operation.
     */
    private function recordAuditSafely(
        AdminAuditEvent $event
    ): void {
        try {
            $this->auditService->record(
                $event
            );
        } catch (Throwable $exception) {
            /*
         * Audit failure must be visible operationally,
         * but must not change the result of an already
         * committed SAK Volunteer operation.
         */
            log_message(
                'error',
                'SAK Volunteer audit event could not be recorded: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Update the editable SAK Volunteer details.
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
                $aadhaarNumber,
                $fieldOfficerId
            )
        ) {
            throw new RuntimeException(
                'Another SAK Volunteer already uses this Aadhaar number.'
            );
        }

        if (
            $this->fieldOfficerModel
            ->panExists(
                $panNumber,
                $fieldOfficerId
            )
        ) {
            throw new RuntimeException(
                'Another SAK Volunteer already uses this PAN number.'
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

        if (
            $upiId !== null
            && $this->fieldOfficerModel
            ->upiExists(
                $upiId,
                $fieldOfficerId
            )
        ) {
            throw new RuntimeException(
                'Another SAK Volunteer already uses this UPI ID.'
            );
        }

        $existingStatus = (string) (
            $existing['account_status']
            ?? FieldOfficerModel::STATUS_INACTIVE
        );

        /*
     * SAK Volunteer status is derived from UPI availability.
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
                    'The SAK Volunteer could not be updated.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The SAK Volunteer update transaction failed.'
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

                description: 'SAK Volunteer details were updated.',

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

                    description: 'SAK Volunteer was automatically activated after a valid UPI ID was added.',

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

                    description: 'SAK Volunteer was automatically deactivated because the UPI ID was removed.',

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
     * Activate a SAK Volunteer.
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
                'The SAK Volunteer is already active.'
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
                    description: 'SAK Volunteer activation was denied because UPI ID was not present.',
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
                'The SAK Volunteer cannot be activated because a UPI ID is not present. Edit the SAK Volunteer, add a valid UPI ID and try again.'
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
                'The SAK Volunteer could not be activated.'
            );
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction::FIELD_OFFICER_ACTIVATED,
                targetType: 'FIELD_OFFICER',
                targetId: $fieldOfficerId,
                targetLabel: (string) $existing['officer_code'],
                description: 'SAK Volunteer was activated.',
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
     * Deactivate a SAK Volunteer.
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
                'The SAK Volunteer is already inactive.'
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
                'The SAK Volunteer could not be deactivated.'
            );
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_DEACTIVATED,
                targetType: 'FIELD_OFFICER',
                targetId: $fieldOfficerId,
                targetLabel: (string) $existing['officer_code'],
                description: 'SAK Volunteer was deactivated.',
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
     * Register a SAK Volunteer from the public self-registration form.
     *
     * Self-registration differs from administrator creation:
     *
     * - account always begins INACTIVE;
     * - review status always begins PENDING;
     * - UPI does NOT activate the account;
     * - login becomes available only after administrator approval.
     *
     * @param array<string, mixed> $input
     */
    public function register(
        array $input,
        int $createdBy
    ): int {
        if ($createdBy <= 0) {
            throw new RuntimeException(
                'The registration owner could not be identified.'
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
                'A SAK Volunteer with this mobile number already exists.'
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

        if (
            $this->fieldOfficerModel
            ->aadhaarExists(
                $aadhaarNumber
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this Aadhaar number already exists.'
            );
        }

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
            ->panExists(
                $panNumber
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this PAN number already exists.'
            );
        }

        $countryId = max(
            0,
            (int) (
                $input['country_id']
                ?? 0
            )
        );

        $stateId = max(
            0,
            (int) (
                $input['state_id']
                ?? 0
            )
        );

        $cityId = max(
            0,
            (int) (
                $input['city_id']
                ?? 0
            )
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

        if (
            $upiId !== null
            && $this->fieldOfficerModel
            ->upiExists(
                $upiId
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this UPI ID already exists.'
            );
        }

        /*
     * Generate exactly the same immutable volunteer code
     * used by administrator creation.
     */
        $officerCode =
            $this->generateOfficerCode();

        $this->database->transBegin();

        try {
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

                        /*
                         * Public registration never activates
                         * the applicant directly.
                         */
                        'account_status' =>
                        FieldOfficerModel
                        ::STATUS_INACTIVE,

                        'activated_at' =>
                        null,

                        'deactivated_at' =>
                        null,

                        /*
                         * This value is the configured
                         * Super Admin system owner.
                         */
                        'created_by' =>
                        $createdBy,

                        'registration_source' =>
                        FieldOfficerModel
                        ::REGISTRATION_SOURCE_SELF,

                        'review_status' =>
                        FieldOfficerModel
                        ::REVIEW_STATUS_PENDING,

                        'reviewed_by' =>
                        null,

                        'reviewed_at' =>
                        null,

                        'rejection_reason' =>
                        null,
                    ],
                    true
                );

            if (!is_numeric($inserted)) {
                throw new RuntimeException(
                    'The SAK Volunteer registration '
                        . 'could not be saved.'
                );
            }

            if (
                $this->database
                ->transStatus() === false
            ) {
                throw new RuntimeException(
                    'The SAK Volunteer registration '
                        . 'transaction failed.'
                );
            }

            $this->database
                ->transCommit();
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            if (
                $this->isUniqueMobileViolation(
                    $exception
                )
            ) {
                throw new RuntimeException(
                    'A SAK Volunteer with this '
                        . 'mobile number already exists.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        /*
     * Audit stays outside the business transaction,
     * following the existing service rule.
     */
        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_CREATED,

                targetType: 'FIELD_OFFICER',

                targetId: (int) $inserted,

                targetLabel: $officerCode,

                description: 'SAK Volunteer submitted '
                    . 'a self-registration application.',

                afterData: [
                    'officer_code' =>
                    $officerCode,

                    'mobile_number' =>
                    $this->maskMobile(
                        $mobileNumber
                    ),

                    'registration_source' =>
                    FieldOfficerModel
                    ::REGISTRATION_SOURCE_SELF,

                    'review_status' =>
                    FieldOfficerModel
                    ::REVIEW_STATUS_PENDING,

                    'account_status' =>
                    FieldOfficerModel
                    ::STATUS_INACTIVE,

                    'upi_id_present' =>
                    $upiId !== null,
                ]
            )
        );

        return (int) $inserted;
    }

    /**
     * Approve one pending self-registration.
     */
    public function approveRegistration(
        int $fieldOfficerId,
        int $reviewedBy
    ): void {
        if (
            $fieldOfficerId <= 0
            || $reviewedBy <= 0
        ) {
            throw new RuntimeException(
                'Invalid SAK Volunteer approval request.'
            );
        }

        $existing =
            $this->findForEdit(
                $fieldOfficerId
            );

        if (
            (string) (
                $existing['registration_source']
                ?? ''
            )
            !== FieldOfficerModel
            ::REGISTRATION_SOURCE_SELF
            ||
            (string) (
                $existing['review_status']
                ?? ''
            )
            !== FieldOfficerModel
            ::REVIEW_STATUS_PENDING
        ) {
            throw new RuntimeException(
                'Only a pending SAK Volunteer '
                    . 'registration may be approved.'
            );
        }

        /*
     * Existing business rule:
     * an ACTIVE SAK Volunteer requires UPI.
     */
        $upiId = trim(
            (string) (
                $existing['upi_id']
                ?? ''
            )
        );

        if ($upiId === '') {
            throw new RuntimeException(
                'Add a valid UPI ID before approving '
                    . 'this SAK Volunteer.'
            );
        }

        $now =
            date('Y-m-d H:i:s');

        $updated =
            $this->fieldOfficerModel
            ->update(
                $fieldOfficerId,
                [
                    'review_status' =>
                    FieldOfficerModel
                    ::REVIEW_STATUS_APPROVED,

                    'reviewed_by' =>
                    $reviewedBy,

                    'reviewed_at' =>
                    $now,

                    'rejection_reason' =>
                    null,

                    'account_status' =>
                    FieldOfficerModel
                    ::STATUS_ACTIVE,

                    'activated_at' =>
                    $now,

                    'deactivated_at' =>
                    null,

                    'updated_by' =>
                    $reviewedBy,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The SAK Volunteer could not be approved.'
            );
        }
    }


    /**
     * Reject one pending self-registration.
     */
    public function rejectRegistration(
        int $fieldOfficerId,
        int $reviewedBy
    ): void {
        if (
            $fieldOfficerId <= 0
            || $reviewedBy <= 0
        ) {
            throw new RuntimeException(
                'Invalid SAK Volunteer rejection request.'
            );
        }

        $existing =
            $this->findForEdit(
                $fieldOfficerId
            );

        if (
            (string) (
                $existing['registration_source']
                ?? ''
            )
            !== FieldOfficerModel
            ::REGISTRATION_SOURCE_SELF
            ||
            (string) (
                $existing['review_status']
                ?? ''
            )
            !== FieldOfficerModel
            ::REVIEW_STATUS_PENDING
        ) {
            throw new RuntimeException(
                'Only a pending SAK Volunteer '
                    . 'registration may be rejected.'
            );
        }

        $updated =
            $this->fieldOfficerModel
            ->update(
                $fieldOfficerId,
                [
                    'review_status' =>
                    FieldOfficerModel
                    ::REVIEW_STATUS_REJECTED,

                    'reviewed_by' =>
                    $reviewedBy,

                    'reviewed_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),

                    'account_status' =>
                    FieldOfficerModel
                    ::STATUS_INACTIVE,

                    'activated_at' =>
                    null,

                    'updated_by' =>
                    $reviewedBy,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The SAK Volunteer could not be rejected.'
            );
        }
    }

    /**
     * Generate a cryptographically secure random SAK Volunteer code.
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
            'A unique SAK Volunteer code could not be generated. Please try again.'
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
