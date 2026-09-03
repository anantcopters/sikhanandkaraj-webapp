<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberAadhaarSubmissionModel;
use App\Models\UserModel;
use App\Services\Admin\Audit\AdminAuditAction;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Aws\CloudFrontService;
use App\Services\Aws\S3Service;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use App\Services\Membership\MembershipEntitlementService;
use App\Services\Communication\CommunicationEventRegistry;
use App\Services\Notification\MemberNotificationService;
use App\Services\Email\MemberEmailService;
use Config\MemberMedia;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Owns member Aadhaar upload, history and administrator-review transitions.
 */
final class MemberAadhaarService
{
    private const MAXIMUM_FILE_SIZE_BYTES = 1048576;

    /** @var array<string, string> */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberAadhaarSubmissionModel
        $submissionModel,

        private readonly S3Service
        $s3Service,

        private readonly CloudFrontService
        $cloudFrontService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly AdminAuditService
        $auditService,

        private readonly MemberEmailService
        $memberEmailService,

        private readonly BaseConnection
        $database,

        private readonly MemberMedia
        $mediaConfig,

        /*
        * MembershipEntitlementService is the single authority for deciding
        * whether a member may use Aadhaar verification.
        *
        * Do not derive paid/free state from UserModel or plan codes here.
        */
        private readonly MembershipEntitlementService
        $membershipEntitlementService,

        private readonly MemberNotificationService
        $memberNotificationService,
    ) {}

    /**
     * Return dashboard state without exposing document storage details.
     *
     * @return array{status:string,rejectionReason:string}
     */
    public function dashboardState(int $memberId): array
    {
        $latest = $this->submissionModel->latestForMember($memberId);
        $status = is_array($latest)
            ? (string) ($latest['status'] ?? '')
            : 'NOT_ADDED';

        if ($status === 'NOT_ADDED') {
            $member = $this->userModel->find($memberId);

            if (
                is_array($member)
                && \App\Support\BooleanValue::fromDatabase(
                    $member['is_aadhaar_verified'] ?? false
                )
            ) {
                $status = MemberAadhaarSubmissionModel::STATUS_APPROVED;
            }
        }

        return [
            'status' => $status,
            'rejectionReason' => is_array($latest)
                ? trim((string) ($latest['rejection_reason'] ?? ''))
                : '',
        ];
    }

    /**
     * Return member-facing Aadhaar verification state.
     *
     * Document storage details, checksum, administrator IDs and
     * signed URLs must never be exposed to the member.
     *
     * @return array{
     *     status:string,
     *     rejectionReason:string,
     *     latest:array<string,mixed>|null,
     *     history:list<array<string,mixed>>,
     *     canUpload:bool
     * }
     */
    public function settingsForMember(
        int $memberId
    ): array {
        if ($memberId <= 0) {
            return [
                'status' =>
                'NOT_ADDED',

                'rejectionReason' =>
                '',

                'latest' =>
                null,

                'history' =>
                [],

                /*
                * Invalid/nonexistent members never receive an entitlement.
                */
                'hasAadhaarEntitlement' =>
                false,

                'canUpload' =>
                false,
            ];
        }

        $member = $this
            ->userModel
            ->find(
                $memberId
            );

        if (
            !is_array($member)
            || ($member['deleted_at'] ?? null) !== null
        ) {
            return [
                'status' =>
                'NOT_ADDED',

                'rejectionReason' =>
                '',

                'latest' =>
                null,

                'history' =>
                [],

                /*
                * Invalid/nonexistent members never receive an entitlement.
                */
                'hasAadhaarEntitlement' =>
                false,

                'canUpload' =>
                false,
            ];
        }

        $latest = $this
            ->submissionModel
            ->latestForMember(
                $memberId
            );

        $status = is_array($latest)
            ? mb_strtoupper(
                trim(
                    (string) (
                        $latest['status']
                        ?? ''
                    )
                )
            )
            : 'NOT_ADDED';

        /*
        * Support members verified before immutable Aadhaar
        * submission history was introduced.
        */
        $isLegacyVerified =
            \App\Support\BooleanValue::fromDatabase(
                $member['is_aadhaar_verified']
                    ?? false
            );

        if (
            $status === 'NOT_ADDED'
            && $isLegacyVerified
        ) {
            $status =
                MemberAadhaarSubmissionModel
                ::STATUS_APPROVED;
        }

        if (
            !in_array(
                $status,
                [
                    MemberAadhaarSubmissionModel
                    ::STATUS_UNDER_REVIEW,

                    MemberAadhaarSubmissionModel
                    ::STATUS_APPROVED,

                    MemberAadhaarSubmissionModel
                    ::STATUS_REJECTED,

                    'NOT_ADDED',
                ],
                true
            )
        ) {
            $status = 'NOT_ADDED';
        }

        $rejectionReason =
            is_array($latest)
            ? trim(
                (string) (
                    $latest['rejection_reason']
                    ?? ''
                )
            )
            : '';

        /*
        * Aadhaar Verification is available to both Free and Paid members.
        *
        * Keep capability resolution centralized through
        * MembershipEntitlementService rather than making this service infer
        * membership/account type directly.
        *
        * The entitlement result determines whether the product capability is
        * available. The current Aadhaar workflow state independently determines
        * whether another document may actually be submitted.
        */
        $hasAadhaarEntitlement =
            $this->membershipEntitlementService
            ->canUseAadhaar(
                $memberId
            );

        /*
        * A member may upload only when BOTH conditions are satisfied:
        *
        * 1. Aadhaar Verification is available to the authenticated member; and
        * 2. the current Aadhaar workflow state permits another submission.
        *
        * Free and Paid members follow exactly the same Aadhaar workflow:
        *
        * NOT_ADDED     -> upload allowed
        * REJECTED      -> re-upload allowed
        * UNDER_REVIEW  -> upload blocked
        * APPROVED      -> upload blocked
        *
        * upload() repeats the capability check server-side and remains the
        * authoritative security boundary for direct POST requests.
        */
        $canUpload =
            $hasAadhaarEntitlement
            && in_array(
                $status,
                [
                    'NOT_ADDED',
                    MemberAadhaarSubmissionModel
                    ::STATUS_REJECTED,
                ],
                true
            );

        return [
            'status' =>
            $status,

            'rejectionReason' =>
            $rejectionReason,

            /*
            * Only member-safe fields from the latest submission.
            */
            'latest' =>
            is_array($latest)
                ? [
                    'status' =>
                    $status,

                    'uploaded_at' =>
                    $latest['uploaded_at']
                        ?? null,

                    'reviewed_at' =>
                    $latest['reviewed_at']
                        ?? null,

                    'rejection_reason' =>
                    $rejectionReason,
                ]
                : null,

            /*
            * historyForMember() already deliberately excludes
            * object_key, checksum and upload reference.
            */
            'history' =>
            $this
                ->submissionModel
                ->historyForMember(
                    $memberId
                ),

            /*
            * Presentation may use this value to display the membership lock.
            *
            * The View must not calculate membership state itself.
            */
            'hasAadhaarEntitlement' =>
            $hasAadhaarEntitlement,

            'canUpload' =>
            $canUpload,
        ];
    }

    /**
     * Validate actual content, upload privately to S3, then persist history.
     *
     * S3 is called before the DB transaction. If the DB write loses a race or
     * fails, the just-uploaded object is deleted as compensation.
     *
     * @return array{status:string}
     */
    public function upload(int $memberId, UploadedFile $file): array
    {
        /*
        * SECURITY BOUNDARY
        * --------------------------------------------------------------------------
        *
        * Aadhaar Verification is available to both Free and Paid members.
        *
        * MembershipEntitlementService remains the central authority for product
        * capabilities. This service must not independently infer account type or
        * bypass that capability boundary.
        *
        * This check MUST occur before:
        *
        * - inspecting the uploaded document;
        * - calculating checksums;
        * - generating S3 paths;
        * - uploading anything to S3;
        * - writing submission history.
        *
        * The check is intentionally repeated server-side even though the Account
        * Settings UI also uses the resolved Aadhaar state. UI visibility is never
        * treated as authorization.
        */
        if (
            !$this->membershipEntitlementService
                ->canUseAadhaar(
                    $memberId
                )
        ) {
            throw new DomainException(
                'Aadhaar Verification is not available '
                    . 'for this account.'
            );
        }

        $member = $this->userModel->find($memberId);

        if (
            !is_array($member)
            || ($member['deleted_at'] ?? null) !== null
        ) {
            throw new DomainException('The member account could not be found.');
        }

        $latest = $this->submissionModel->latestForMember($memberId);
        $latestStatus = is_array($latest)
            ? strtoupper(trim((string) ($latest['status'] ?? '')))
            : '';

        if ($latestStatus === MemberAadhaarSubmissionModel::STATUS_UNDER_REVIEW) {
            throw new DomainException('Your Aadhaar document is already under review.');
        }

        if (
            $latestStatus === MemberAadhaarSubmissionModel::STATUS_APPROVED
            || \App\Support\BooleanValue::fromDatabase(
                $member['is_aadhaar_verified'] ?? false
            )
        ) {
            throw new DomainException('Your Aadhaar is already approved.');
        }

        $fileDetails = $this->inspectUploadedFile($file);
        $profileReference = trim((string) ($member['profile_ref_number'] ?? ''));

        if (preg_match('/^SAK[0-9]{7}$/', $profileReference) !== 1) {
            throw new RuntimeException('The member reference is unavailable.');
        }

        $uploadReference = bin2hex(random_bytes(16));
        $objectKey = 'members/aadhaar-documents/'
            . $profileReference
            . '/'
            . $uploadReference
            . '.'
            . $fileDetails['extension'];

        $this->s3Service->upload(
            $fileDetails['path'],
            $objectKey,
            $fileDetails['mimeType'],
            [
                'document-type' => 'aadhaar',
                'member-reference' => $profileReference,
                'upload-reference' => $uploadReference,
            ]
        );

        try {
            $checksum = hash_file('sha256', $fileDetails['path']);

            if (!is_string($checksum) || $checksum === '') {
                throw new RuntimeException('The Aadhaar document checksum could not be calculated.');
            }

            $inserted = $this->submissionModel->insert([
                'upload_reference' => $uploadReference,
                'member_id' => $memberId,
                'object_key' => $objectKey,
                'mime_type' => $fileDetails['mimeType'],
                'file_extension' => $fileDetails['extension'],
                'file_size_bytes' => $fileDetails['size'],
                'checksum_sha256' => $checksum,
                'status' => MemberAadhaarSubmissionModel::STATUS_UNDER_REVIEW,
                'uploaded_at' => gmdate('Y-m-d H:i:s') . '+00:00',
            ], true);

            if (!is_numeric($inserted)) {
                throw new RuntimeException('The Aadhaar upload history could not be saved.');
            }
        } catch (Throwable $exception) {
            $this->s3Service->delete($objectKey);

            if ($this->isUniqueViolation($exception)) {
                throw new DomainException(
                    'Your Aadhaar document is already under review.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        return ['status' => MemberAadhaarSubmissionModel::STATUS_UNDER_REVIEW];
    }

    /**
     * Return the searchable and filterable administrator
     * Aadhaar submission listing.
     *
     * @return array{
     *     members:list<array<string,mixed>>,
     *     pager:\CodeIgniter\Pager\Pager,
     *     search:string,
     *     status:string
     * }
     */
    public function adminPage(
        string $search,
        string $status,
        int $perPage
    ): array {
        $normalizedSearch =
            mb_substr(
                trim($search),
                0,
                100
            );

        $normalizedStatus =
            mb_strtoupper(
                trim($status)
            );

        $allowedStatuses = [
            'ALL',
            MemberAadhaarSubmissionModel
            ::STATUS_UNDER_REVIEW,

            MemberAadhaarSubmissionModel
            ::STATUS_APPROVED,

            MemberAadhaarSubmissionModel
            ::STATUS_REJECTED,
        ];

        if (
            !in_array(
                $normalizedStatus,
                $allowedStatuses,
                true
            )
        ) {
            $normalizedStatus =
                MemberAadhaarSubmissionModel
                ::STATUS_UNDER_REVIEW;
        }

        $this->submissionModel
            ->prepareAdminListing(
                $normalizedSearch,
                $normalizedStatus
            );

        $members =
            $this->submissionModel
            ->paginate(
                max(
                    5,
                    min(
                        $perPage,
                        50
                    )
                ),
                'pendingAadhaarMembers'
            );

        return [
            'members' =>
            is_array($members)
                ? $members
                : [],

            'pager' =>
            $this->submissionModel
                ->pager,

            'search' =>
            $normalizedSearch,

            'status' =>
            $normalizedStatus,
        ];
    }

    /**
     * Return one administrator-authorized review contract.
     *
     * @return array<string, mixed>
     */
    public function review(string $profileReference): array
    {
        $submission = $this->submissionModel
            ->pendingReviewByProfileReference(
                $profileReference
            );

        if (!is_array($submission)) {
            throw new DomainException(
                'The pending Aadhaar submission was not found.'
            );
        }

        $memberId = (int) (
            $submission['member_id'] ?? 0
        );

        if ($memberId <= 0) {
            throw new RuntimeException(
                'The Aadhaar submission is incomplete.'
            );
        }

        return [
            'submission' => $submission,

            'photos' =>
            $this->photoUrlService
                ->getAdminThumbnailPhotos($memberId),

            'history' =>
            $this->submissionModel
                ->historyForMember($memberId),
        ];
    }

    /**
     * Return a short-lived private URL for downloading one pending
     * Aadhaar document.
     *
     * Aadhaar is sensitive identity documentation, therefore its
     * signed URL lifetime is deliberately independent from profile
     * media configuration.
     */
    public function documentDownloadUrl(
        string $profileReference
    ): string {
        $submission = $this->submissionModel
            ->pendingReviewByProfileReference(
                $profileReference
            );

        if (!is_array($submission)) {
            throw new DomainException(
                'The pending Aadhaar submission was not found.'
            );
        }

        $objectKey = trim(
            (string) (
                $submission['object_key']
                ?? ''
            )
        );

        if ($objectKey === '') {
            throw new RuntimeException(
                'The Aadhaar document is unavailable.'
            );
        }

        return $this->cloudFrontService
            ->signedUrl(
                $objectKey,
                $this->mediaConfig
                    ->privateDocumentUrlTtlSeconds
            );
    }

    /**
     * Approve the current pending submission and store separate
     * Aadhaar verification data.
     *
     * This method must never update:
     *
     * - users.full_name
     * - member_basic_details.date_of_birth
     *
     * Therefore Aadhaar verification cannot change the member's
     * displayed name, age, Search results, Matches or partner
     * preference calculations.
     */
    public function approve(
        string $profileReference,
        int $adminId,
        string $aadhaarName,
        string $aadhaarDateOfBirth
    ): void {
        $normalizedName = preg_replace(
            '/\s+/u',
            ' ',
            trim($aadhaarName)
        ) ?? '';

        $reviewedAt =
            gmdate('Y-m-d H:i:s')
            . '+00:00';

        /**
         * @var array<string, mixed>|null $submission
         */
        $submission = null;

        $this->database->transBegin();

        try {
            /*
         * Lock the current pending submission.
         *
         * This prevents simultaneous Approve/Reject requests from
         * applying conflicting decisions.
         */
            $submission = $this
                ->submissionModel
                ->lockPendingByProfileReference(
                    $profileReference
                );

            if (!is_array($submission)) {
                throw new DomainException(
                    'This Aadhaar submission is no longer under review.'
                );
            }

            $memberId = (int) (
                $submission['member_id']
                ?? 0
            );

            if (
                $memberId <= 0
                || $adminId <= 0
            ) {
                throw new DomainException(
                    'A valid member and administrator are required.'
                );
            }

            /*
         * Update only the existing verification-summary columns.
         *
         * Do not update full_name or Basic Details DOB.
         */
            $userUpdated = $this
                ->userModel
                ->update(
                    $memberId,
                    [
                        'is_aadhaar_verified' =>
                        true,

                        'aadhaar_verified_at' =>
                        $reviewedAt,
                    ]
                );

            if ($userUpdated === false) {
                throw new RuntimeException(
                    'The member Aadhaar status could not be updated.'
                );
            }

            /*
         * Aadhaar name and Aadhaar DOB are stored on the immutable
         * verification submission, not on the matrimonial profile.
         */
            $submissionUpdated = $this
                ->submissionModel
                ->where(
                    'id',
                    (int) $submission['id']
                )
                ->where(
                    'status',
                    MemberAadhaarSubmissionModel
                    ::STATUS_UNDER_REVIEW
                )
                ->set([
                    'status' =>
                    MemberAadhaarSubmissionModel
                    ::STATUS_APPROVED,

                    'aadhaar_name' =>
                    $normalizedName,

                    'aadhaar_date_of_birth' =>
                    $aadhaarDateOfBirth,

                    'reviewed_by_admin_id' =>
                    $adminId,

                    'reviewed_at' =>
                    $reviewedAt,

                    'rejection_reason' =>
                    null,

                    'updated_at' =>
                    $reviewedAt,
                ])
                ->update();

            if (
                $submissionUpdated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'This Aadhaar submission is no longer under review.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The Aadhaar approval transaction failed.'
                );
            }



            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        /*
        * Moderation is already committed.
        *
        * Application notification and email are independent
        * downstream communication channels.
        */
        try {
            $this->memberNotificationService
                ->create([
                    'recipientUserId' =>
                    $memberId,

                    'type' =>
                    CommunicationEventRegistry
                    ::AADHAAR_APPROVED,

                    'title' =>
                    'Aadhaar Approved',

                    'message' =>
                    'Your Aadhaar verification has been approved.',

                    'entityType' =>
                    'AADHAAR_SUBMISSION',

                    'entityId' =>
                    (int) $submission['id'],

                    'targetUrl' =>
                    route_to(
                        'web.account.settings.section',
                        'aadhaar-verification'
                    ),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Aadhaar approval notification failed for '
                    . 'member {memberId}, submission {submissionId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'submissionId' =>
                    (int) $submission['id'],

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }

        try {
            $member =
                $this->userModel
                ->find(
                    $memberId
                );

            $this->memberEmailService
                ->queueAadhaarApproved(
                    recipientUserId: $memberId,

                    recipientName: is_array($member)
                        ? trim(
                            (string) (
                                $member['full_name']
                                ?? ''
                            )
                        )
                        : '',

                    submissionId: (int) $submission['id']
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Aadhaar approval email queue failed for '
                    . 'member {memberId}, submission {submissionId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'submissionId' =>
                    (int) $submission['id'],

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * Reject the current pending Aadhaar submission.
     *
     * A rejected submission does not store approved Aadhaar
     * name or Aadhaar DOB.
     */
    public function reject(
        string $profileReference,
        int $adminId,
        string $reason
    ): void {
        $normalizedReason = preg_replace(
            '/\s+/u',
            ' ',
            trim($reason)
        ) ?? '';

        $reviewedAt =
            gmdate('Y-m-d H:i:s')
            . '+00:00';

        /**
         * @var array<string, mixed>|null $submission
         */
        $submission = null;

        $this->database->transBegin();

        try {
            $submission = $this
                ->submissionModel
                ->lockPendingByProfileReference(
                    $profileReference
                );

            if (!is_array($submission)) {
                throw new DomainException(
                    'This Aadhaar submission is no longer under review.'
                );
            }

            $memberId = (int) (
                $submission['member_id']
                ?? 0
            );

            if (
                $memberId <= 0
                || $adminId <= 0
            ) {
                throw new DomainException(
                    'A valid member and administrator are required.'
                );
            }

            $submissionUpdated = $this
                ->submissionModel
                ->where(
                    'id',
                    (int) $submission['id']
                )
                ->where(
                    'status',
                    MemberAadhaarSubmissionModel
                    ::STATUS_UNDER_REVIEW
                )
                ->set([
                    'status' =>
                    MemberAadhaarSubmissionModel
                    ::STATUS_REJECTED,

                    /*
                 * Rejected documents do not contain approved
                 * verification identity values.
                 */
                    'aadhaar_name' =>
                    null,

                    'aadhaar_date_of_birth' =>
                    null,

                    'reviewed_by_admin_id' =>
                    $adminId,

                    'reviewed_at' =>
                    $reviewedAt,

                    'rejection_reason' =>
                    $normalizedReason,

                    'updated_at' =>
                    $reviewedAt,
                ])
                ->update();

            if (
                $submissionUpdated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'This Aadhaar submission is no longer under review.'
                );
            }

            $userUpdated = $this
                ->userModel
                ->update(
                    $memberId,
                    [
                        'is_aadhaar_verified' =>
                        false,

                        'aadhaar_verified_at' =>
                        null,
                    ]
                );

            if ($userUpdated === false) {
                throw new RuntimeException(
                    'The member Aadhaar status could not be updated.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The Aadhaar rejection transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        /*
        * Moderation is already committed.
        *
        * In-app notification failure must not prevent the
        * rejection email, and vice versa.
        */
        try {
            $this->memberNotificationService
                ->create([
                    'recipientUserId' =>
                    $memberId,

                    'type' =>
                    CommunicationEventRegistry
                    ::AADHAAR_REJECTED,

                    'title' =>
                    'Aadhaar Rejected',

                    'message' =>
                    'Your Aadhaar verification was not approved. '
                        . 'Reason: '
                        . $reason,

                    'entityType' =>
                    'AADHAAR_SUBMISSION',

                    'entityId' =>
                    (int) $submission['id'],

                    'targetUrl' =>
                    route_to(
                        'web.account.settings.section',
                        'aadhaar-verification'
                    ),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Aadhaar rejection notification failed for '
                    . 'member {memberId}, submission {submissionId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'submissionId' =>
                    (int) $submission['id'],

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }

        try {
            $member =
                $this->userModel
                ->find(
                    $memberId
                );

            $this->memberEmailService
                ->queueAadhaarRejected(
                    recipientUserId: $memberId,

                    recipientName: is_array($member)
                        ? trim(
                            (string) (
                                $member['full_name']
                                ?? ''
                            )
                        )
                        : '',

                    submissionId: (int) $submission['id'],

                    reason: $reason
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Aadhaar rejection email queue failed for '
                    . 'member {memberId}, submission {submissionId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'submissionId' =>
                    (int) $submission['id'],

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * @return array{path:string,mimeType:string,extension:string,size:int}
     */
    private function inspectUploadedFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new DomainException('The uploaded Aadhaar document is invalid.');
        }

        $path = $file->getTempName();
        $size = $file->getSize();

        if (!is_file($path) || !is_readable($path) || $size < 1) {
            throw new DomainException('The uploaded Aadhaar document could not be read.');
        }

        if ($size >= self::MAXIMUM_FILE_SIZE_BYTES) {
            throw new DomainException('The Aadhaar document must be smaller than 1 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = strtolower(trim((string) $finfo->file($path)));
        $extension = self::ALLOWED_MIME_TYPES[$mimeType] ?? '';

        if ($extension === '') {
            throw new DomainException('Only JPG, JPEG, PNG or PDF files are allowed.');
        }

        if (str_starts_with($mimeType, 'image/')) {
            $imageDetails = getimagesize($path);

            if (!is_array($imageDetails) || strtolower((string) ($imageDetails['mime'] ?? '')) !== $mimeType) {
                throw new DomainException('The selected image is not a valid Aadhaar document image.');
            }
        } elseif (!$this->hasValidPdfSignature($path)) {
            throw new DomainException('The selected PDF is not a valid PDF document.');
        }

        return [
            'path' => $path,
            'mimeType' => $mimeType,
            'extension' => $extension,
            'size' => $size,
        ];
    }

    private function hasValidPdfSignature(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $header = fread($handle, 5);
            fseek($handle, max(0, filesize($path) - 1024));
            $tail = stream_get_contents($handle);

            return $header === '%PDF-'
                && is_string($tail)
                && str_contains($tail, '%%EOF');
        } finally {
            fclose($handle);
        }
    }

    private function isUniqueViolation(Throwable $exception): bool
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $message = mb_strtolower($current->getMessage());

            if (
                (string) $current->getCode() === '23505'
                || str_contains($message, '23505')
                || str_contains($message, 'uq_member_aadhaar_one_pending')
            ) {
                return true;
            }
        }

        return false;
    }
}
