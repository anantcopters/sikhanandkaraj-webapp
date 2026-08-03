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
use RuntimeException;
use Throwable;

/**
 * Atomically migrates an approved prelaunch profile into member storage.
 *
 * Database records are committed only after all approved photographs have
 * been processed and uploaded to S3.
 */
final class PrelaunchMemberMigrationService
{
    public function __construct(
        private readonly PrelaunchProfileModel $prelaunchProfileModel,
        private readonly PrelaunchPhotoModel $prelaunchPhotoModel,
        private readonly UserModel $userModel,
        private readonly UserContactModel $userContactModel,
        private readonly MemberPhotoModel $memberPhotoModel,
        private readonly PrelaunchPhotoService $prelaunchPhotoService,
        private readonly AwsMediaService $awsMediaService,
        private readonly BaseConnection $database
    ) {}

    /**
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
        if (
            $prelaunchProfileId <= 0
            || $adminUserId <= 0
        ) {
            throw new RuntimeException(
                'Invalid prelaunch migration request.'
            );
        }

        /*
         * Fast idempotency check before opening the transaction.
         */
        $existingMember = $this
            ->userModel
            ->findByPrelaunchProfileId(
                $prelaunchProfileId
            );

        if ($existingMember !== null) {
            throw new RuntimeException(
                'This prelaunch profile has already been migrated.'
            );
        }

        $uploadedObjectKeys = [];

        $this->database->transBegin();

        try {
            /*
             * Serialize approval attempts for the same profile.
             */
            $lockedProfile = $this->database
                ->query(
                    'SELECT * FROM prelaunch_profiles '
                        . 'WHERE id = ? '
                        . 'AND deleted_at IS NULL '
                        . 'FOR UPDATE',
                    [$prelaunchProfileId]
                )
                ->getRowArray();

            if (!is_array($lockedProfile)) {
                throw new RuntimeException(
                    'The prelaunch profile was not found.'
                );
            }

            if (
                (string) $lockedProfile['status']
                !== PrelaunchProfileModel::STATUS_DRAFT
            ) {
                throw new RuntimeException(
                    'Only draft prelaunch profiles may be approved.'
                );
            }

            if (
                $lockedProfile['migrated_user_id']
                !== null
            ) {
                throw new RuntimeException(
                    'This prelaunch profile has already been migrated.'
                );
            }

            $approvedPhotos = $this
                ->prelaunchPhotoModel
                ->findApprovedByProfile(
                    $prelaunchProfileId
                );

            if ($approvedPhotos === []) {
                throw new RuntimeException(
                    'Approve at least one photograph before approving '
                        . 'the profile.'
                );
            }

            $email = mb_strtolower(
                trim(
                    (string) (
                        $lockedProfile['email']
                        ?? ''
                    )
                )
            );

            $countryCode = trim(
                (string) (
                    $lockedProfile['country_code']
                    ?? '+91'
                )
            );

            $mobileNumber = trim(
                (string) (
                    $lockedProfile['mobile_number']
                    ?? ''
                )
            );

            $normalizedMobile =
                IndianMobileNormalizer::normalize(
                    $countryCode . $mobileNumber
                );

            if ($normalizedMobile === null) {
                throw new RuntimeException(
                    'The prelaunch profile does not contain '
                        . 'a valid Indian mobile number.'
                );
            }

            if (
                $this->userContactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_MOBILE,
                    $normalizedMobile
                ) !== null
            ) {
                throw new RuntimeException(
                    'This mobile number already belongs to an existing '
                        . 'member.'
                );
            }

            if (
                $email !== ''
                && $this->userContactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_EMAIL,
                    $email
                ) !== null
            ) {
                throw new RuntimeException(
                    'This email address already belongs to an existing '
                        . 'member.'
                );
            }

            $profileReference =
                $this->generateMemberReference();

            $memberId = $this->userModel->insert(
                [
                    'prelaunch_profile_id' =>
                    $prelaunchProfileId,

                    'profile_ref_number' =>
                    $profileReference,

                    /*
         * The normal member registration flow stores relationship values
         * in lowercase. Normalize the prelaunch uppercase value before
         * inserting it into users.
         */
                    'profile_created_for' =>
                    $this->resolveMemberProfileCreatedFor(
                        (string) (
                            $lockedProfile['profile_created_for']
                            ?? ''
                        )
                    ),

                    /*
         * users.gender is CHAR(1). The prelaunch table stores readable
         * values such as MALE/FEMALE, so it must be converted to M/F.
         */
                    'gender' =>
                    $this->resolveMemberGender(
                        (string) (
                            $lockedProfile['gender']
                            ?? ''
                        ),
                        (string) (
                            $lockedProfile['profile_created_for']
                            ?? ''
                        )
                    ),

                    'full_name' =>
                    trim(
                        (string) (
                            $lockedProfile['full_name']
                            ?? ''
                        )
                    ),

                    /*
         * The migrated member will complete the normal password and mobile
         * verification flow before the account becomes active.
         */
                    'password_hash' =>
                    null,

                    'account_status' =>
                    UserModel::STATUS_PENDING,
                ],
                true
            );

            if (!is_numeric($memberId)) {
                $modelErrors = $this->userModel
                    ->errors();

                log_message(
                    'error',
                    'Prelaunch member account insert failed. '
                        . 'Profile: {profileId}; errors: {errors}.',
                    [
                        'profileId' =>
                        $prelaunchProfileId,

                        'errors' =>
                        json_encode(
                            $modelErrors,
                            JSON_UNESCAPED_SLASHES
                                | JSON_UNESCAPED_UNICODE
                        ),
                    ]
                );

                throw new RuntimeException(
                    'The member account could not be created.'
                );
            }

            $memberId = (int) $memberId;

            if (!is_numeric($memberId)) {
                throw new RuntimeException(
                    'The member account could not be created.'
                );
            }

            $memberId = (int) $memberId;

            $this->insertMemberContacts(
                $memberId,
                $email,
                $countryCode,
                $normalizedMobile
            );

            $this->insertProfileDetails(
                $memberId,
                $lockedProfile
            );

            $migratedPhotoCount = 0;

            foreach (
                $approvedPhotos as $index => $photo
            ) {
                $prelaunchPhotoId = (int) (
                    $photo['id']
                    ?? 0
                );

                if (
                    $prelaunchPhotoId <= 0
                    || $this->memberPhotoModel
                    ->prelaunchPhotoWasMigrated(
                        $prelaunchPhotoId
                    )
                ) {
                    throw new RuntimeException(
                        'A prelaunch photograph was already migrated.'
                    );
                }

                $localPath = $this
                    ->prelaunchPhotoService
                    ->absolutePath(
                        (string) (
                            $photo['original_path']
                            ?? ''
                        )
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

                $media = $this
                    ->awsMediaService
                    ->uploadProfilePhotoFromPath(
                        $localPath,
                        (string) (
                            $photo['original_filename']
                            ?? 'prelaunch-photo.webp'
                        ),
                        $memberId
                    );

                $uploadedObjectKeys = array_merge(
                    $uploadedObjectKeys,
                    [
                        $media['originalObjectKey'],
                        $media['mediumObjectKey'],
                        $media['thumbnailObjectKey'],
                    ]
                );

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
                             * The prelaunch administrator has already
                             * approved the source image.
                             */
                            'status' =>
                            'APPROVED',
                            'visibility' =>
                            'PUBLIC',
                            'is_primary' =>
                            $index === 0,
                            'uploaded_by_type' =>
                            'ADMIN',
                            'uploaded_by_id' =>
                            $adminUserId,

                            'approved_by' =>
                            $adminUserId,

                            'approved_at' =>
                            date('Y-m-d H:i:s'),

                            'rejected_by' =>
                            null,

                            'rejected_at' =>
                            null,

                            'rejection_reason' =>
                            null,
                        ],
                        true
                    );

                if (!is_numeric($memberPhotoId)) {
                    throw new RuntimeException(
                        'The migrated member photo record '
                            . 'could not be created.'
                    );
                }

                $migratedPhotoCount++;
            }

            $now = date('Y-m-d H:i:s');

            $cleanupAfter = date(
                'Y-m-d H:i:s',
                strtotime('+7 days')
            );

            if (
                !$this->prelaunchProfileModel->update(
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
                )
            ) {
                throw new RuntimeException(
                    'The prelaunch migration status '
                        . 'could not be recorded.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The profile migration transaction failed.'
                );
            }

            $this->database->transCommit();

            return [
                'memberId' => $memberId,
                'profileReference' =>
                $profileReference,
                'migratedPhotoCount' =>
                $migratedPhotoCount,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            /*
             * Database rollback cannot undo completed S3 uploads.
             */
            if ($uploadedObjectKeys !== []) {
                $this->awsMediaService
                    ->deleteObjectKeys(
                        $uploadedObjectKeys
                    );
            }

            log_message(
                'error',
                'Prelaunch member migration failed. '
                    . 'Profile: {profileId}; '
                    . 'reason: {message}',
                [
                    'profileId' =>
                    $prelaunchProfileId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    private function insertMemberContacts(
        int $memberId,
        string $email,
        string $countryCode,
        string $normalizedMobile
    ): void {
        $mobileContactId =
            $this->userContactModel->insert(
                [
                    'user_id' =>
                    $memberId,
                    'contact_type' =>
                    UserContactModel::TYPE_MOBILE,
                    'contact_value' =>
                    $countryCode
                        . ' '
                        . $normalizedMobile,
                    'normalized_value' =>
                    $countryCode
                        . $normalizedMobile,
                    'is_primary' =>
                    true,
                    'is_verified' =>
                    false,
                    'verified_at' =>
                    null,
                ],
                true
            );

        if (!is_numeric($mobileContactId)) {
            throw new RuntimeException(
                'The member mobile contact could not be created.'
            );
        }

        if ($email === '') {
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
                    $email,
                    'normalized_value' =>
                    $email,
                    'is_primary' =>
                    true,
                    'is_verified' =>
                    false,
                    'verified_at' =>
                    null,
                ],
                true
            );

        if (!is_numeric($emailContactId)) {
            throw new RuntimeException(
                'The member email contact could not be created.'
            );
        }
    }

    /**
     * Map the prelaunch fields into the normal member profile tables.
     *
     * Keep this method in the migration service so controllers remain thin.
     *
     * @param array<string, mixed> $profile
     */
    private function insertProfileDetails(
        int $memberId,
        array $profile
    ): void {
        $now = date('Y-m-d H:i:s');

        $this->database->table(
            'member_basic_details'
        )->insert([
            'user_id' =>
            $memberId,
            'date_of_birth' =>
            $profile['date_of_birth'],
            'marital_status_id' =>
            $profile['marital_status_id'],
            'height_id' =>
            $profile['height_id'],
            'country_id' =>
            $profile['country_id'],
            'state_id' =>
            $profile['state_id'],
            'city_id' =>
            $profile['city_id'],
            'created_at' =>
            $now,
            'updated_at' =>
            $now,
        ]);

        $this->database->table(
            'member_education_profession_details'
        )->insert([
            'user_id' =>
            $memberId,
            'highest_education_id' =>
            $profile['highest_education_id'],
            'employed_in' =>
            $profile['employed_in'],
            'occupation_id' =>
            $profile['occupation_id'],
            'created_at' =>
            $now,
            'updated_at' =>
            $now,
        ]);

        $this->database->table(
            'member_family_details'
        )->insert([
            'user_id' =>
            $memberId,
            'father_name' =>
            $profile['father_name'],
            'mother_name' =>
            $profile['mother_name'],
            'community_id' =>
            $profile['sikh_community_id'],
            'gotra' =>
            $profile['gotra'],
            'nearest_gurudwara' =>
            $profile['nearest_gurudwara'],
            'created_at' =>
            $now,
            'updated_at' =>
            $now,
        ]);
    }

    /**
     * Convert the prelaunch relationship value to the format used by users.
     */
    private function resolveMemberProfileCreatedFor(
        string $profileCreatedFor
    ): string {
        $normalized = mb_strtoupper(
            trim($profileCreatedFor)
        );

        return match ($normalized) {
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

            /*
         * These values are supported by the prelaunch form but may not be
         * supported by the older public registration screen. Retain them in
         * normalized lowercase form when the users column permits them.
         */
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
     * Convert prelaunch gender values to users.gender CHAR(1).
     */
    private function resolveMemberGender(
        string $gender,
        string $profileCreatedFor
    ): string {
        $normalizedGender = mb_strtoupper(
            trim($gender)
        );

        /*
     * First accept all known representations. This supports existing records
     * that may contain M/F as well as newer prelaunch records containing
     * MALE/FEMALE.
     */
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
     * Fall back to relationship when gender is unambiguous.
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
     * Generate a unique member profile reference.
     *
     * This must follow the same database-approved format used by normal
     * registration: SAK followed by exactly seven numeric digits.
     */
    private function generateMemberReference(): string
    {
        $maximumAttempts = 20;

        for (
            $attempt = 1;
            $attempt <= $maximumAttempts;
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
}
