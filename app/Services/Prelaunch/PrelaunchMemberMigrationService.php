<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\MemberPhotoModel;
use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Aws\AwsMediaService;
use App\Support\IndianMobileNormalizer;
use CodeIgniter\Database\BaseConnection;
use Config\Prelaunch;
use RuntimeException;
use Throwable;

/**
 * Migrates an administrator-approved prelaunch profile into the normal
 * member-account and profile tables.
 *
 * Important guarantees:
 *
 * - The prelaunch profile is migrated only once.
 * - At least one prelaunch photograph must already be approved.
 * - Mobile and optional email must not exist in the member contact table.
 * - Approved photographs pass through the existing S3 media pipeline.
 * - Database changes are atomic.
 * - S3 objects are removed when the database transaction fails.
 * - Migrated accounts are ACTIVE.
 * - Migrated mobile and optional email contacts are verified.
 */
final class PrelaunchMemberMigrationService
{
    /**
     * Maximum attempts when generating a unique member profile reference.
     */
    private const PROFILE_REFERENCE_ATTEMPTS = 20;

    /**
     * Number of days to retain the local prelaunch photographs after
     * successful migration.
     */
    private const LOCAL_PHOTO_RETENTION_DAYS = 7;

    public function __construct(
        private readonly PrelaunchProfileModel $prelaunchProfileModel,
        private readonly PrelaunchPhotoModel $prelaunchPhotoModel,
        private readonly UserModel $userModel,
        private readonly UserContactModel $userContactModel,
        private readonly MemberPhotoModel $memberPhotoModel,
        private readonly PrelaunchPhotoService $prelaunchPhotoService,
        private readonly AwsMediaService $awsMediaService,
        private readonly BaseConnection $database,
        private readonly Prelaunch $configuration
    ) {}

    /**
     * Migrate one approved prelaunch profile.
     *
     * @return array{
     *     memberId:int,
     *     profileReference:string,
     *     migratedPhotoCount:int
     * }
     */
    public function migrate(
        int $prelaunchProfileId,
        int $adminUserId
    ): array {
        if ($prelaunchProfileId <= 0) {
            throw new RuntimeException(
                'Invalid prelaunch profile ID.'
            );
        }

        if ($adminUserId <= 0) {
            throw new RuntimeException(
                'The reviewing administrator could not be identified.'
            );
        }

        /*
         * Fast idempotency check before beginning the transaction.
         *
         * The database unique index on users.prelaunch_profile_id remains
         * the final protection against concurrent duplicate migrations.
         */
        $existingMember = $this->userModel
            ->findByPrelaunchProfileId(
                $prelaunchProfileId
            );

        if ($existingMember !== null) {
            throw new RuntimeException(
                'This prelaunch profile has already been migrated.'
            );
        }

        /**
         * Database rollback cannot remove objects already uploaded to S3.
         * Keep every uploaded key so they can be removed if a later step fails.
         *
         * @var list<string> $uploadedObjectKeys
         */
        $uploadedObjectKeys = [];

        $this->database->transBegin();

        try {
            /*
             * Lock the source row so two administrators cannot migrate the
             * same prelaunch profile simultaneously.
             */
            $lockedProfile = $this->database
                ->query(
                    'SELECT * '
                        . 'FROM prelaunch_profiles '
                        . 'WHERE id = ? '
                        . 'AND deleted_at IS NULL '
                        . 'FOR UPDATE',
                    [
                        $prelaunchProfileId,
                    ]
                )
                ->getRowArray();

            if (!is_array($lockedProfile)) {
                throw new RuntimeException(
                    'The prelaunch profile was not found.'
                );
            }

            if (
                (string) (
                    $lockedProfile['status']
                    ?? ''
                )
                !== PrelaunchProfileModel::STATUS_DRAFT
            ) {
                throw new RuntimeException(
                    'Only DRAFT prelaunch profiles may be approved.'
                );
            }

            if (
                (int) (
                    $lockedProfile['migrated_user_id']
                    ?? 0
                ) > 0
            ) {
                throw new RuntimeException(
                    'This prelaunch profile has already been migrated.'
                );
            }

            $approvedPhotos = $this->prelaunchPhotoModel
                ->findApprovedByProfile(
                    $prelaunchProfileId
                );

            if ($approvedPhotos === []) {
                throw new RuntimeException(
                    'Approve at least one photograph before approving '
                        . 'the profile.'
                );
            }

            /*
             * Normalize contacts once and reuse exactly the same value for:
             *
             * - uniqueness lookup;
             * - contact_value;
             * - normalized_value.
             */
            $normalizedMobile = $this->normalizeMobile(
                (string) (
                    $lockedProfile['country_code']
                    ?? '+91'
                ),
                (string) (
                    $lockedProfile['mobile_number']
                    ?? ''
                )
            );

            $normalizedEmail = $this->normalizeEmail(
                $lockedProfile['email']
                    ?? null
            );

            $this->assertMemberContactsAvailable(
                $normalizedMobile,
                $normalizedEmail
            );

            $profileReference =
                $this->generateMemberReference();

            $memberId = $this->insertMemberAccount(
                $prelaunchProfileId,
                $profileReference,
                $lockedProfile
            );

            $verifiedAt = date(
                'Y-m-d H:i:s'
            );

            $this->insertMemberContacts(
                $memberId,
                $normalizedMobile,
                $normalizedEmail,
                $verifiedAt
            );

            $this->insertProfileDetails(
                $memberId,
                $lockedProfile
            );

            $migratedPhotoCount =
                $this->migrateApprovedPhotos(
                    memberId: $memberId,
                    adminUserId: $adminUserId,
                    approvedPhotos: $approvedPhotos,
                    uploadedObjectKeys: $uploadedObjectKeys
                );

            $now = date(
                'Y-m-d H:i:s'
            );

            $cleanupAfter = date(
                'Y-m-d H:i:s',
                strtotime(
                    sprintf(
                        '+%d days',
                        self::LOCAL_PHOTO_RETENTION_DAYS
                    )
                )
            );

            $profileUpdated =
                $this->prelaunchProfileModel->update(
                    $prelaunchProfileId,
                    [
                        'status' =>
                        PrelaunchProfileModel
                        ::STATUS_APPROVED,

                        'reviewed_by' =>
                        $adminUserId,

                        'reviewed_at' =>
                        $now,

                        'rejection_reason' =>
                        null,

                        'migrated_user_id' =>
                        $memberId,

                        'migrated_at' =>
                        $now,

                        'local_photos_cleanup_after' =>
                        $cleanupAfter,

                        'local_photos_cleaned_at' =>
                        null,

                        'migration_error' =>
                        null,
                    ]
                );

            if ($profileUpdated === false) {
                throw new RuntimeException(
                    'The prelaunch migration status could not be recorded.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The prelaunch profile migration transaction failed.'
                );
            }

            $this->database->transCommit();

            return [
                'memberId' =>
                $memberId,

                'profileReference' =>
                $profileReference,

                'migratedPhotoCount' =>
                $migratedPhotoCount,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            /*
             * Remove only the objects uploaded during this migration attempt.
             */
            if ($uploadedObjectKeys !== []) {
                try {
                    $this->awsMediaService
                        ->deleteObjectKeys(
                            array_values(
                                array_unique(
                                    $uploadedObjectKeys
                                )
                            )
                        );
                } catch (Throwable $cleanupException) {
                    log_message(
                        'critical',
                        'Unable to rollback S3 objects after failed '
                            . 'prelaunch migration. '
                            . 'Profile: {profileId}; '
                            . 'migration error: {migrationError}; '
                            . 'cleanup error: {cleanupError}.',
                        [
                            'profileId' =>
                            $prelaunchProfileId,

                            'migrationError' =>
                            $exception->getMessage(),

                            'cleanupError' =>
                            $cleanupException
                                ->getMessage(),
                        ]
                    );
                }
            }

            log_message(
                'error',
                'Prelaunch member migration failed. '
                    . 'Profile: {profileId}; '
                    . 'exception: {exception}; '
                    . 'reason: {message}; '
                    . 'file: {file}; '
                    . 'line: {line}.',
                [
                    'profileId' =>
                    $prelaunchProfileId,

                    'exception' =>
                    $exception::class,

                    'message' =>
                    $exception->getMessage(),

                    'file' =>
                    $exception->getFile(),

                    'line' =>
                    $exception->getLine(),
                ]
            );

            throw $exception;
        }
    }

    /**
     * Insert the member account.
     *
     * Migrated accounts are ACTIVE because the administrator has completed
     * the prelaunch review and the supplied contacts are treated as verified.
     *
     * Password remains NULL. The migrated member must use the applicable
     * password-creation or password-reset flow before password login.
     *
     * @param array<string, mixed> $profile
     */
    private function insertMemberAccount(
        int $prelaunchProfileId,
        string $profileReference,
        array $profile
    ): int {
        $memberId = $this->userModel->insert(
            [
                'prelaunch_profile_id' =>
                $prelaunchProfileId,

                'profile_ref_number' =>
                $profileReference,

                'profile_created_for' =>
                $this->resolveMemberProfileCreatedFor(
                    (string) (
                        $profile['profile_created_for']
                        ?? ''
                    )
                ),

                'gender' =>
                $this->resolveMemberGender(
                    (string) (
                        $profile['gender']
                        ?? ''
                    ),
                    (string) (
                        $profile['profile_created_for']
                        ?? ''
                    )
                ),

                'full_name' =>
                $this->requireText(
                    $profile['full_name']
                        ?? null,
                    'The prelaunch profile name is missing.'
                ),

                /*
                 * config password is stored
                 */
                'password_hash' =>
                $this->createMigratedMemberPasswordHash(),

                /*
                 * Business requirement:
                 *
                 * migrated prelaunch accounts are immediately active.
                 */
                'account_status' =>
                UserModel::STATUS_ACTIVE,
            ],
            true
        );

        if (!is_numeric($memberId)) {
            log_message(
                'error',
                'Prelaunch member-account insert failed. '
                    . 'Profile: {profileId}; '
                    . 'model errors: {errors}.',
                [
                    'profileId' =>
                    $prelaunchProfileId,

                    'errors' =>
                    json_encode(
                        $this->userModel->errors(),
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                    ),
                ]
            );

            throw new RuntimeException(
                'The member account could not be created.'
            );
        }

        return (int) $memberId;
    }

    /**
     * Create the secure password hash required for an ACTIVE migrated account.
     *
     * The plain password comes from environment configuration. It must never be
     * returned, logged, added to audit metadata, or stored outside password_hash.
     */
    private function createMigratedMemberPasswordHash(): string
    {
        $plainPassword = trim(
            $this->configuration
                ->migratedMemberDefaultPassword
        );

        if ($plainPassword === '') {
            throw new RuntimeException(
                'The default migrated-member password is not configured.'
            );
        }

        /*
     * Keep this aligned with the normal registration password requirements.
     */
        if (strlen($plainPassword) < 8) {
            throw new RuntimeException(
                'The configured migrated-member password must '
                    . 'contain at least 8 characters.'
            );
        }

        $passwordHash = password_hash(
            $plainPassword,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'The migrated-member password could not be secured.'
            );
        }

        return $passwordHash;
    }

    /**
     * Insert verified member contact rows.
     *
     * Email remains optional. When absent, only the verified mobile row is
     * created.
     */
    private function insertMemberContacts(
        int $memberId,
        string $normalizedMobile,
        ?string $normalizedEmail,
        string $verifiedAt
    ): void {
        $mobileContactId =
            $this->userContactModel->insert(
                [
                    'user_id' =>
                    $memberId,

                    'contact_type' =>
                    UserContactModel::TYPE_MOBILE,

                    /*
                     * Follow the normal registration format:
                     *
                     * +919876543210
                     */
                    'contact_value' =>
                    $normalizedMobile,

                    'normalized_value' =>
                    $normalizedMobile,

                    'is_primary' =>
                    true,

                    /*
                     * Migrated contacts are administrator-verified.
                     */
                    'is_verified' =>
                    true,

                    'verified_at' =>
                    $verifiedAt,
                ],
                true
            );

        if (!is_numeric($mobileContactId)) {
            throw new RuntimeException(
                'The verified member mobile contact '
                    . 'could not be created.'
            );
        }

        if ($normalizedEmail === null) {
            return;
        }

        $emailContactId =
            $this->userContactModel->insert(
                [
                    'user_id' =>
                    $memberId,

                    'contact_type' =>
                    UserContactModel::TYPE_EMAIL,

                    'contact_value' =>
                    $normalizedEmail,

                    'normalized_value' =>
                    $normalizedEmail,

                    'is_primary' =>
                    true,

                    /*
                     * Business requirement:
                     *
                     * an email present on an approved prelaunch profile is
                     * migrated as verified.
                     */
                    'is_verified' =>
                    true,

                    'verified_at' =>
                    $verifiedAt,
                ],
                true
            );

        if (!is_numeric($emailContactId)) {
            throw new RuntimeException(
                'The verified member email contact '
                    . 'could not be created.'
            );
        }
    }

    /**
     * Ensure contacts do not already belong to a member.
     */
    private function assertMemberContactsAvailable(
        string $normalizedMobile,
        ?string $normalizedEmail
    ): void {
        $existingMobile = $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $normalizedMobile
            );

        if ($existingMobile !== null) {
            throw new RuntimeException(
                'This mobile number already belongs to '
                    . 'an existing member.'
            );
        }

        if ($normalizedEmail === null) {
            return;
        }

        $existingEmail = $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_EMAIL,
                $normalizedEmail
            );

        if ($existingEmail !== null) {
            throw new RuntimeException(
                'This email address already belongs to '
                    . 'an existing member.'
            );
        }
    }

    /**
     * Migrate normal member profile-section records.
     *
     * @param array<string, mixed> $profile
     */
    private function insertProfileDetails(
        int $memberId,
        array $profile
    ): void {
        $now = date(
            'Y-m-d H:i:s'
        );

        $basicInserted = $this->database
            ->table(
                'member_basic_details'
            )
            ->insert(
                [
                    'user_id' =>
                    $memberId,

                    'date_of_birth' =>
                    $profile['date_of_birth']
                        ?? null,

                    'marital_status_id' =>
                    $profile['marital_status_id']
                        ?? null,

                    'height_id' =>
                    $profile['height_id']
                        ?? null,

                    'country_id' =>
                    $profile['country_id']
                        ?? null,

                    'state_id' =>
                    $profile['state_id']
                        ?? null,

                    'city_id' =>
                    $profile['city_id']
                        ?? null,

                    'created_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ]
            );

        if ($basicInserted === false) {
            throw new RuntimeException(
                'The member basic details could not be migrated.'
            );
        }

        $educationInserted = $this->database
            ->table(
                'member_education_profession_details'
            )
            ->insert(
                [
                    'user_id' =>
                    $memberId,

                    'highest_education_id' =>
                    $profile['highest_education_id']
                        ?? null,

                    'employed_in' =>
                    $profile['employed_in']
                        ?? null,

                    'occupation_id' =>
                    $profile['occupation_id']
                        ?? null,

                    'created_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ]
            );

        if ($educationInserted === false) {
            throw new RuntimeException(
                'The member education and profession details '
                    . 'could not be migrated.'
            );
        }

        $familyInserted = $this->database
            ->table(
                'member_family_details'
            )
            ->insert(
                [
                    'user_id' =>
                    $memberId,

                    'father_name' =>
                    $profile['father_name']
                        ?? null,

                    'mother_name' =>
                    $profile['mother_name']
                        ?? null,

                    'parent_contact_number' =>
                    $this->nullableText(
                        $profile['parent_contact_number'] ?? null
                    ),

                    /*
                     * Source prelaunch field:
                     *     sikh_community_id
                     *
                     * Destination member-family field:
                     *     community_id
                     */
                    'community_id' =>
                    $profile['sikh_community_id']
                        ?? null,

                    'gotra' =>
                    $this->nullableText(
                        $profile['gotra']
                            ?? null
                    ),

                    'nearest_gurudwara' =>
                    $this->nullableText(
                        $profile['nearest_gurudwara']
                            ?? null
                    ),

                    /*
                     * The prelaunch profile location represents the member's
                     * primary location. Reuse it for family location where
                     * the destination fields exist.
                     */
                    'country_id' =>
                    $profile['country_id']
                        ?? null,

                    'state_id' =>
                    $profile['state_id']
                        ?? null,

                    'city_id' =>
                    $profile['city_id']
                        ?? null,

                    'created_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ]
            );

        if ($familyInserted === false) {
            throw new RuntimeException(
                'The member family details could not be migrated.'
            );
        }
    }

    /**
     * Upload and persist all administrator-approved prelaunch photographs.
     *
     * @param list<array<string, mixed>> $approvedPhotos
     * @param list<string>               $uploadedObjectKeys
     */
    private function migrateApprovedPhotos(
        int $memberId,
        int $adminUserId,
        array $approvedPhotos,
        array &$uploadedObjectKeys
    ): int {
        $migratedPhotoCount = 0;
        $approvedAt = date(
            'Y-m-d H:i:s'
        );

        foreach (
            $approvedPhotos
            as $index => $photo
        ) {
            $prelaunchPhotoId = (int) (
                $photo['id']
                ?? 0
            );

            if ($prelaunchPhotoId <= 0) {
                throw new RuntimeException(
                    'An approved prelaunch photograph '
                        . 'contains an invalid ID.'
                );
            }

            if (
                $this->memberPhotoModel
                ->prelaunchPhotoWasMigrated(
                    $prelaunchPhotoId
                )
            ) {
                throw new RuntimeException(
                    'A prelaunch photograph has already been migrated.'
                );
            }

            $relativePath = trim(
                (string) (
                    $photo['original_path']
                    ?? ''
                )
            );

            if ($relativePath === '') {
                throw new RuntimeException(
                    sprintf(
                        'Approved photograph %d has no local path.',
                        $index + 1
                    )
                );
            }

            $localPath = $this
                ->prelaunchPhotoService
                ->absolutePath(
                    $relativePath
                );

            if (
                !is_file($localPath)
                || !is_readable($localPath)
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Approved photograph %d is unavailable.',
                        $index + 1
                    )
                );
            }

            /*
             * Reuse the normal member-photo pipeline:
             *
             * - processed original;
             * - medium WebP;
             * - thumbnail WebP;
             * - private S3 upload.
             */
            $media = $this->awsMediaService
                ->uploadProfilePhotoFromPath(
                    $localPath,
                    (string) (
                        $photo['original_filename']
                        ?? 'prelaunch-photo.webp'
                    ),
                    $memberId
                );

            $uploadedObjectKeys[] =
                $media['originalObjectKey'];

            $uploadedObjectKeys[] =
                $media['mediumObjectKey'];

            $uploadedObjectKeys[] =
                $media['thumbnailObjectKey'];

            $memberPhotoId =
                $this->memberPhotoModel->insert(
                    [
                        'uuid' =>
                        $media['uuid'],

                        'member_id' =>
                        $memberId,

                        'prelaunch_photo_id' =>
                        $prelaunchPhotoId,

                        'media_type' =>
                        'PROFILE_PHOTO',

                        'original_object_key' =>
                        $media['originalObjectKey'],

                        'medium_object_key' =>
                        $media['mediumObjectKey'],

                        'thumbnail_object_key' =>
                        $media['thumbnailObjectKey'],

                        'original_filename' =>
                        $media['originalFilename'],

                        'original_mime_type' =>
                        $media['mimeType'],

                        'original_extension' =>
                        $media['extension'],

                        'original_file_size' =>
                        $media['fileSize'],

                        'original_width' =>
                        $media['width'],

                        'original_height' =>
                        $media['height'],

                        /*
                         * The administrator has already reviewed the source
                         * prelaunch photograph.
                         */
                        'status' =>
                        'APPROVED',

                        'visibility' =>
                        'PUBLIC',

                        /*
                         * The first approved photo in sequence order becomes
                         * the main profile photograph.
                         */
                        'is_primary' =>
                        $index === 0,

                        'uploaded_by_type' =>
                        'ADMIN',

                        'uploaded_by_id' =>
                        $adminUserId,

                        'approved_by' =>
                        $adminUserId,

                        'approved_at' =>
                        $approvedAt,

                        'rejected_by' =>
                        null,

                        'rejected_at' =>
                        null,

                        'rejection_reason' =>
                        null,

                        'deleted_at' =>
                        null,
                    ],
                    true
                );

            if (!is_numeric($memberPhotoId)) {
                throw new RuntimeException(
                    'The migrated member photograph record '
                        . 'could not be created.'
                );
            }

            $migratedPhotoCount++;
        }

        return $migratedPhotoCount;
    }

    /**
     * Normalize an Indian mobile number using the same canonical format as
     * normal registration.
     *
     * Output:
     *
     * +919876543210
     */
    private function normalizeMobile(
        string $countryCode,
        string $mobileNumber
    ): string {
        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                trim($countryCode)
                    . trim($mobileNumber)
            );

        if ($normalizedMobile === null) {
            throw new RuntimeException(
                'The prelaunch profile does not contain '
                    . 'a valid Indian mobile number.'
            );
        }

        return $normalizedMobile;
    }

    /**
     * Normalize optional email consistently for uniqueness and storage.
     *
     * PostgreSQL comparisons may otherwise treat differently cased values as
     * separate contacts when the database column is case-sensitive.
     */
    private function normalizeEmail(
        mixed $email
    ): ?string {
        $normalizedEmail = mb_strtolower(
            trim(
                (string) $email
            )
        );

        if ($normalizedEmail === '') {
            return null;
        }

        if (
            filter_var(
                $normalizedEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'The prelaunch profile contains an invalid email address.'
            );
        }

        return $normalizedEmail;
    }

    /**
     * Convert the prelaunch relationship format to the users-table format.
     */
    private function resolveMemberProfileCreatedFor(
        string $profileCreatedFor
    ): string {
        return match (mb_strtoupper(
            trim($profileCreatedFor)
        )) {
            'SELF' =>
            'self',

            'SON' =>
            'son',

            'DAUGHTER' =>
            'daughter',

            'BROTHER' =>
            'brother',

            'SISTER' =>
            'sister',

            'RELATIVE' =>
            'relative',

            'FRIEND' =>
            'friend',

            default =>
            throw new RuntimeException(
                'The prelaunch profile relationship '
                    . 'is not supported.'
            ),
        };
    }

    /**
     * Convert readable prelaunch gender values to users.gender CHAR(1).
     */
    private function resolveMemberGender(
        string $gender,
        string $profileCreatedFor
    ): string {
        $normalizedGender = mb_strtoupper(
            trim($gender)
        );

        if (
            in_array(
                $normalizedGender,
                [
                    'M',
                    'MALE',
                ],
                true
            )
        ) {
            return 'M';
        }

        if (
            in_array(
                $normalizedGender,
                [
                    'F',
                    'FEMALE',
                ],
                true
            )
        ) {
            return 'F';
        }

        /*
         * Fall back to relationship for relationships where gender is
         * unambiguous.
         */
        return match (mb_strtoupper(
            trim($profileCreatedFor)
        )) {
            'SON',
            'BROTHER' =>
            'M',

            'DAUGHTER',
            'SISTER' =>
            'F',

            default =>
            throw new RuntimeException(
                'The prelaunch profile does not contain '
                    . 'a valid gender.'
            ),
        };
    }

    /**
     * Generate a unique member reference matching the existing database
     * check constraint:
     *
     * SAK followed by exactly seven digits.
     */
    private function generateMemberReference(): string
    {
        for (
            $attempt = 1;
            $attempt <= self::PROFILE_REFERENCE_ATTEMPTS;
            $attempt++
        ) {
            $reference = 'SAK' . str_pad(
                (string) random_int(
                    0,
                    9_999_999
                ),
                7,
                '0',
                STR_PAD_LEFT
            );

            if (
                !$this->userModel
                    ->profileReferenceExists(
                        $reference
                    )
            ) {
                return $reference;
            }
        }

        throw new RuntimeException(
            'A unique member profile reference '
                . 'could not be generated.'
        );
    }

    /**
     * Require a non-empty text value.
     */
    private function requireText(
        mixed $value,
        string $message
    ): string {
        $text = trim(
            (string) $value
        );

        if ($text === '') {
            throw new RuntimeException(
                $message
            );
        }

        return $text;
    }

    /**
     * Normalize an optional text value for nullable database columns.
     */
    private function nullableText(
        mixed $value
    ): ?string {
        $text = trim(
            (string) $value
        );

        return $text !== ''
            ? $text
            : null;
    }
}
