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
        private readonly UserModel $userModel,
        private readonly MemberAadhaarSubmissionModel $submissionModel,
        private readonly S3Service $s3Service,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MemberPhotoUrlService $photoUrlService,
        private readonly AdminAuditService $auditService,
        private readonly BaseConnection $database,
        private readonly MemberMedia $mediaConfig
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
     * Validate actual content, upload privately to S3, then persist history.
     *
     * S3 is called before the DB transaction. If the DB write loses a race or
     * fails, the just-uploaded object is deleted as compensation.
     *
     * @return array{status:string}
     */
    public function upload(int $memberId, UploadedFile $file): array
    {
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
     * Return the searchable administrator queue.
     *
     * @return array{members:list<array<string,mixed>>,pager:\CodeIgniter\Pager\Pager,search:string}
     */
    public function pendingPage(string $search, int $perPage): array
    {
        $normalizedSearch = mb_substr(trim($search), 0, 100);
        $this->submissionModel->preparePendingListing($normalizedSearch);
        $members = $this->submissionModel->paginate(
            max(5, min($perPage, 50)),
            'pendingAadhaarMembers'
        );

        return [
            'members' => is_array($members) ? $members : [],
            'pager' => $this->submissionModel->pager,
            'search' => $normalizedSearch,
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
            (string) ($submission['object_key'] ?? '')
        );

        if ($objectKey === '') {
            throw new RuntimeException(
                'The Aadhaar document is unavailable.'
            );
        }

        return $this->cloudFrontService->signedUrl(
            $objectKey,
            $this->mediaConfig->profileUrlTtlSeconds
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
     * Audit after the business transaction commits.
     *
     * Do not place Aadhaar name, DOB, document key or signed URL
     * in the administrator audit payload.
     */
        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::MEMBER_AADHAAR_APPROVED,

                actorAdminId: $adminId,

                targetType: 'MEMBER',

                targetId: (int) $submission['member_id'],

                targetLabel: (string) (
                    $submission['profile_ref_number']
                    ?? ''
                ),

                description: 'Administrator approved a member Aadhaar submission.',

                afterData: [
                    'aadhaar_status' =>
                    MemberAadhaarSubmissionModel
                    ::STATUS_APPROVED,
                ],

                metadata: [
                    'submission_id' =>
                    (int) $submission['id'],
                ]
            )
        );
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

        $this->auditService->record(
            new AdminAuditEvent(
                action: AdminAuditAction
                ::MEMBER_AADHAAR_REJECTED,

                actorAdminId: $adminId,

                targetType: 'MEMBER',

                targetId: (int) $submission['member_id'],

                targetLabel: (string) (
                    $submission['profile_ref_number']
                    ?? ''
                ),

                description: 'Administrator rejected a member Aadhaar submission.',

                afterData: [
                    'aadhaar_status' =>
                    MemberAadhaarSubmissionModel
                    ::STATUS_REJECTED,
                ],

                metadata: [
                    'submission_id' =>
                    (int) $submission['id'],
                ]
            )
        );
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
