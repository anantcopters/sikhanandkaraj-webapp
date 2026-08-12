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

        /*
        * UPI ID is compulsory for every SAK Volunteer.
        *
        * FieldOfficerValidation is the primary validation layer, but the service
        * also enforces the business invariant so direct service callers cannot
        * create a Volunteer without a UPI ID.
        */
        $upiId =
            trim(
                (string) (
                    $input['upi_id']
                    ?? ''
                )
            );

        if ($upiId === '') {
            throw new RuntimeException(
                'UPI ID is required.'
            );
        }

        if (
            $this->fieldOfficerModel
            ->upiExists(
                $upiId
            )
        ) {
            throw new RuntimeException(
                'A SAK Volunteer with this UPI ID already exists.'
            );
        }

        foreach (
            [
                'aadhaar_document' =>
                'Aadhaar Card',

                'pan_document' =>
                'PAN Card',

                'cancelled_cheque_document' =>
                'Cancelled cheque copy',
            ]
            as $documentField => $documentLabel
        ) {
            if (
                trim(
                    (string) (
                        $input[$documentField]
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    $documentLabel
                        . ' is required.'
                );
            }
        }

        /*
        * Admin-created Volunteers are approved at creation and UPI is mandatory,
        * therefore a successful Admin creation starts ACTIVE.
        */
        $initialStatus =
            FieldOfficerModel::STATUS_ACTIVE;

        $activatedAt =
            date(
                'Y-m-d H:i:s'
            );

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

                        'aadhaar_document' =>
                        trim(
                            (string) (
                                $input['aadhaar_document']
                                ?? ''
                            )
                        ),

                        'pan_document' =>
                        trim(
                            (string) (
                                $input['pan_document']
                                ?? ''
                            )
                        ),

                        'cancelled_cheque_document' =>
                        trim(
                            (string) (
                                $input['cancelled_cheque_document']
                                ?? ''
                            )
                        ),
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
                    true,

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

                    description: 'SAK Volunteer was created in active status.',

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
     * Update editable SAK Volunteer details.
     *
     * Pending or rejected self-registrations must remain
     * INACTIVE. Only an approved record may participate in
     * the normal UPI-driven activation workflow.
     *
     * @param array<string, mixed> $input
     */
    public function update(
        int $fieldOfficerId,
        array $input,
        int $updatedBy
    ): void {
        $existing =
            $this->findForEdit(
                $fieldOfficerId
            );

        if ($updatedBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator '
                    . 'could not be identified.'
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

        $panNumber = strtoupper(
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
                'Another SAK Volunteer already '
                    . 'uses this Aadhaar number.'
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
                'Another SAK Volunteer already '
                    . 'uses this PAN number.'
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

        $address =
            $this->nullableText(
                $input['address']
                    ?? null
            );

        /*
        * UPI is an invariant for SAK Volunteers and cannot be removed.
        */
        $upiId =
            trim(
                (string) (
                    $input['upi_id']
                    ?? ''
                )
            );

        if ($upiId === '') {
            throw new RuntimeException(
                'UPI ID is required.'
            );
        }

        if (
            $this->fieldOfficerModel
            ->upiExists(
                $upiId,
                $fieldOfficerId
            )
        ) {
            throw new RuntimeException(
                'Another SAK Volunteer already '
                    . 'uses this UPI ID.'
            );
        }

        $existingStatus = strtoupper(
            trim(
                (string) (
                    $existing['account_status']
                    ?? FieldOfficerModel
                    ::STATUS_INACTIVE
                )
            )
        );

        $registrationSource = strtoupper(
            trim(
                (string) (
                    $existing['registration_source']
                    ?? FieldOfficerModel
                    ::REGISTRATION_SOURCE_ADMIN
                )
            )
        );

        $reviewStatus = strtoupper(
            trim(
                (string) (
                    $existing['review_status']
                    ?? FieldOfficerModel
                    ::REVIEW_STATUS_APPROVED
                )
            )
        );

        $isSelfRegistration =
            $registrationSource
            === FieldOfficerModel
            ::REGISTRATION_SOURCE_SELF;

        $reviewAllowsActivation =
            !$isSelfRegistration
            || $reviewStatus
            === FieldOfficerModel
            ::REVIEW_STATUS_APPROVED;

        /*
        * UPI presence no longer controls activation because UPI is mandatory.
        *
        * A pending/rejected self-registration must still remain inactive.
        * Admin-created or approved self-registered Volunteers may be active.
        */
        $newStatus =
            $reviewAllowsActivation
            ? FieldOfficerModel::STATUS_ACTIVE
            : FieldOfficerModel::STATUS_INACTIVE;

        $statusChanged =
            $existingStatus
            !== $newStatus;

        $activatedAt =
            $existing['activated_at']
            ?? null;

        $deactivatedAt =
            $existing['deactivated_at']
            ?? null;

        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel
            ::STATUS_ACTIVE
        ) {
            $activatedAt =
                date(
                    'Y-m-d H:i:s'
                );

            $deactivatedAt =
                null;
        }

        if (
            $statusChanged
            && $newStatus
            === FieldOfficerModel
            ::STATUS_INACTIVE
        ) {
            $deactivatedAt =
                date(
                    'Y-m-d H:i:s'
                );
        }

        $beforeData = [
            'country_id' =>
            (int) (
                $existing['country_id']
                ?? 0
            ),

            'state_id' =>
            (int) (
                $existing['state_id']
                ?? 0
            ),

            'city_id' =>
            (int) (
                $existing['city_id']
                ?? 0
            ),

            'address' =>
            $this->nullableText(
                $existing['address']
                    ?? null
            ),

            'upi_id_present' =>
            $this->hasUpiId(
                $existing
            ),

            'account_status' =>
            $existingStatus,

            'activated_at' =>
            $existing['activated_at']
                ?? null,

            'deactivated_at' =>
            $existing['deactivated_at']
                ?? null,
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
            true,

            'account_status' =>
            $newStatus,

            'activated_at' =>
            $activatedAt,

            'deactivated_at' =>
            $deactivatedAt,
        ];

        $this->database
            ->transBegin();

        try {
            $updated =
                $this->fieldOfficerModel
                ->update(
                    $fieldOfficerId,
                    [
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
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'The SAK Volunteer could '
                        . 'not be updated.'
                );
            }

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The SAK Volunteer update '
                        . 'transaction failed.'
                );
            }

            $this->database
                ->transCommit();
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }

        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_UPDATED,

                targetType: 'FIELD_OFFICER',

                targetId: $fieldOfficerId,

                targetLabel: (string) (
                    $existing['officer_code']
                    ?? ''
                ),

                description: 'SAK Volunteer details were updated.',

                beforeData: $beforeData,

                afterData: $afterData
            )
        );

        /*
     * Pending/rejected registrations are deliberately
     * excluded from status transition auditing because
     * editing them cannot activate them.
     */
        if (
            $reviewAllowsActivation
            && $statusChanged
            && $newStatus
            === FieldOfficerModel
            ::STATUS_ACTIVE
        ) {
            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_ACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: (string) (
                        $existing['officer_code']
                        ?? ''
                    ),

                    description: 'SAK Volunteer was automatically '
                        . 'activated after a valid UPI ID '
                        . 'was added.',

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
            === FieldOfficerModel
            ::STATUS_INACTIVE
        ) {
            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_DEACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: (string) (
                        $existing['officer_code']
                        ?? ''
                    ),

                    description: 'SAK Volunteer became inactive '
                        . 'because the UPI ID was removed.',
                )
            );
        }
    }

    /**
     * Activate an approved SAK Volunteer.
     */
    public function activate(
        int $fieldOfficerId,
        int $updatedBy
    ): void {
        $existing =
            $this->findForEdit(
                $fieldOfficerId
            );

        if ($updatedBy <= 0) {
            throw new RuntimeException(
                'The logged-in administrator '
                    . 'could not be identified.'
            );
        }

        $registrationSource = strtoupper(
            trim(
                (string) (
                    $existing['registration_source']
                    ?? FieldOfficerModel
                    ::REGISTRATION_SOURCE_ADMIN
                )
            )
        );

        $reviewStatus = strtoupper(
            trim(
                (string) (
                    $existing['review_status']
                    ?? FieldOfficerModel
                    ::REVIEW_STATUS_APPROVED
                )
            )
        );

        if (
            $registrationSource
            === FieldOfficerModel
            ::REGISTRATION_SOURCE_SELF
            && $reviewStatus
            !== FieldOfficerModel
            ::REVIEW_STATUS_APPROVED
        ) {
            throw new RuntimeException(
                'This SAK Volunteer registration '
                    . 'must be approved before activation.'
            );
        }

        if (
            (string) (
                $existing['account_status']
                ?? ''
            )
            === FieldOfficerModel
            ::STATUS_ACTIVE
        ) {
            throw new RuntimeException(
                'The SAK Volunteer is already active.'
            );
        }

        if (
            !$this->hasUpiId(
                $existing
            )
        ) {
            throw new RuntimeException(
                'The SAK Volunteer cannot be activated '
                    . 'because a UPI ID is not present. '
                    . 'Edit the SAK Volunteer, add a valid '
                    . 'UPI ID and try again.'
            );
        }

        $activatedAt =
            date(
                'Y-m-d H:i:s'
            );

        $updated =
            $this->fieldOfficerModel
            ->update(
                $fieldOfficerId,
                [
                    'account_status' =>
                    FieldOfficerModel
                    ::STATUS_ACTIVE,

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
                'The SAK Volunteer could '
                    . 'not be activated.'
            );
        }

        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_ACTIVATED,

                targetType: 'FIELD_OFFICER',

                targetId: $fieldOfficerId,

                targetLabel: (string) (
                    $existing['officer_code']
                    ?? ''
                ),

                description: 'SAK Volunteer was activated.'
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
     * Register a SAK Volunteer from the public
     * self-registration form.
     *
     * Self-registration differs from administrator creation:
     *
     * - account always begins INACTIVE;
     * - review status always begins PENDING;
     * - UPI does NOT activate the account;
     * - login becomes available only after administrator approval.
     *
     * @param array<string, mixed> $input
     *
     * @return array{
     *     fieldOfficerId: int,
     *     officerCode: string
     * }
     */
    public function register(
        array $input,
        int $createdBy
    ): array {
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

        foreach (
            [
                'aadhaar_document' =>
                'Aadhaar Card',

                'pan_document' =>
                'PAN Card',

                'cancelled_cheque_document' =>
                'Cancelled cheque copy',
            ]
            as $documentField => $documentLabel
        ) {
            if (
                trim(
                    (string) (
                        $input[$documentField]
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    $documentLabel
                        . ' is required.'
                );
            }
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

                        'aadhaar_document' =>
                        trim(
                            (string) (
                                $input['aadhaar_document']
                                ?? ''
                            )
                        ),

                        'pan_document' =>
                        trim(
                            (string) (
                                $input['pan_document']
                                ?? ''
                            )
                        ),

                        'cancelled_cheque_document' =>
                        trim(
                            (string) (
                                $input['cancelled_cheque_document']
                                ?? ''
                            )
                        ),
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
                    true,
                ]
            )
        );

        return [
            'fieldOfficerId' =>
            (int) $inserted,

            'officerCode' =>
            $officerCode,
        ];
    }

    /**
     * Approve one pending SAK Volunteer self-registration.
     *
     * Approval and activation are related but separate:
     *
     * - UPI present  -> APPROVED + ACTIVE
     * - UPI absent   -> APPROVED + INACTIVE
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
                'Invalid SAK Volunteer '
                    . 'approval request.'
            );
        }

        $existing =
            $this->findForEdit(
                $fieldOfficerId
            );

        if (
            strtoupper(
                trim(
                    (string) (
                        $existing['registration_source']
                        ?? ''
                    )
                )
            )
            !== FieldOfficerModel
            ::REGISTRATION_SOURCE_SELF
            ||
            strtoupper(
                trim(
                    (string) (
                        $existing['review_status']
                        ?? ''
                    )
                )
            )
            !== FieldOfficerModel
            ::REVIEW_STATUS_PENDING
        ) {
            throw new RuntimeException(
                'Only a pending SAK Volunteer '
                    . 'registration may be approved.'
            );
        }

        foreach (
            [
                'aadhaar_document' =>
                'Aadhaar Card',

                'pan_document' =>
                'PAN Card',

                'cancelled_cheque_document' =>
                'Cancelled cheque copy',
            ]
            as $column => $label
        ) {
            if (
                trim(
                    (string) (
                        $existing[$column]
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    $label
                        . ' must be uploaded before the SAK Volunteer can be approved.'
                );
            }
        }

        $hasUpiId =
            $this->hasUpiId(
                $existing
            );

        $now =
            date(
                'Y-m-d H:i:s'
            );

        $accountStatus =
            $hasUpiId
            ? FieldOfficerModel
            ::STATUS_ACTIVE
            : FieldOfficerModel
            ::STATUS_INACTIVE;

        $activatedAt =
            $hasUpiId
            ? $now
            : null;

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
                    $accountStatus,

                    'activated_at' =>
                    $activatedAt,

                    'deactivated_at' =>
                    null,

                    'updated_by' =>
                    $reviewedBy,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The SAK Volunteer could '
                    . 'not be approved.'
            );
        }

        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::FIELD_OFFICER_UPDATED,

                targetType: 'FIELD_OFFICER',

                targetId: $fieldOfficerId,

                targetLabel: (string) (
                    $existing['officer_code']
                    ?? ''
                ),

                description: $hasUpiId
                    ? 'SAK Volunteer registration was '
                    . 'approved and activated.'
                    : 'SAK Volunteer registration was '
                    . 'approved in inactive status '
                    . 'because no UPI ID is present.',

                afterData: [
                    'registration_source' =>
                    FieldOfficerModel
                    ::REGISTRATION_SOURCE_SELF,

                    'review_status' =>
                    FieldOfficerModel
                    ::REVIEW_STATUS_APPROVED,

                    'account_status' =>
                    $accountStatus,

                    'upi_id_present' =>
                    $hasUpiId,
                ]
            )
        );

        if ($hasUpiId) {
            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction
                    ::FIELD_OFFICER_ACTIVATED,

                    targetType: 'FIELD_OFFICER',

                    targetId: $fieldOfficerId,

                    targetLabel: (string) (
                        $existing['officer_code']
                        ?? ''
                    ),

                    description: 'SAK Volunteer was activated '
                        . 'during registration approval.',

                    metadata: [
                        'activation_source' =>
                        'SELF_REGISTRATION_APPROVAL',
                    ]
                )
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
