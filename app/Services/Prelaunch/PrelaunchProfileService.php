<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Services\Profile\ProfileMasterDataService;
use App\Models\UserContactModel;
use App\Support\IndianMobileNormalizer;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Prelaunch;
use RuntimeException;
use Throwable;

/**
 * Handles prelaunch profile creation.
 */
final class PrelaunchProfileService
{
    /**
     * @param PrelaunchProfileModel        $profileModel
     * @param PrelaunchFieldOfficerService $fieldOfficerService
     * @param PrelaunchPhotoService        $photoService
     * @param ProfileMasterDataService     $profileMasterDataService
     * @param BaseConnection               $database
     * @param Prelaunch                    $configuration
     */
    public function __construct(
        private readonly PrelaunchProfileModel $profileModel,
        private readonly PrelaunchFieldOfficerService $fieldOfficerService,
        private readonly PrelaunchPhotoService $photoService,
        private readonly ProfileMasterDataService $profileMasterDataService,
        private readonly UserContactModel $userContactModel,
        private readonly BaseConnection $database,
        private readonly Prelaunch $configuration
    ) {}

    /**
     * Create a draft prelaunch profile and its photographs.
     *
     * User-correctable failures, such as duplicate optional email or
     * duplicate mobile number, are returned through PrelaunchProfileResult.
     *
     * An omitted email is persisted as NULL.
     *
     * @param array<string, mixed>     $input
     * @param array<int, UploadedFile> $photos
     */
    public function createDraft(
        array $input,
        array $photos
    ): PrelaunchProfileResult {
        $normalizedEmail = mb_strtolower(
            trim(
                (string) (
                    $input['email']
                    ?? ''
                )
            )
        );

        $email = $normalizedEmail !== ''
            ? $normalizedEmail
            : null;

        $gotra = mb_strtolower(
            trim(
                (string) (
                    $input['gotra']
                    ?? ''
                )
            )
        );

        if ($gotra === '') {
            return PrelaunchProfileResult::fieldFailure(
                'gotra',
                'Gotra is required.'
            );
        }

        $nearestGurudwara = trim(
            (string) (
                $input['nearest_gurudwara']
                ?? ''
            )
        );

        $nearestGurudwara =
            $nearestGurudwara !== ''
            ? $nearestGurudwara
            : null;

        $profileCreatedFor = mb_strtoupper(
            trim(
                (string) (
                    $input['profile_created_for']
                    ?? ''
                )
            )
        );

        $gender = $this->resolveGender(
            $profileCreatedFor,
            (string) (
                $input['gender']
                ?? ''
            )
        );

        $countryCode = trim(
            (string) (
                $input['country_code']
                ?? ''
            )
        );

        $mobileNumber = preg_replace(
            '/\D+/',
            '',
            (string) (
                $input['mobile_number']
                ?? ''
            )
        ) ?? '';

        /*
         * Duplicate values are user-correctable business failures.
         *
         * Return them against the exact HTML field names so the
         * controller can send them through validationErrors.
         */
        if (
            $email !== null
            && $this->profileModel->emailExists(
                $email
            )
        ) {
            return PrelaunchProfileResult::fieldFailure(
                'email',
                'A profile with this email address already exists.'
            );
        }

        /*
        * A member mobile number must be unique across both:
        *
        * 1. profiles collected during prelaunch;
        * 2. existing live member contacts.
        *
        * Parent/Guardian contact numbers are deliberately excluded because
        * one family mobile may legitimately be shared by multiple profiles.
        */
        if (
            $this->profileModel->mobileExists(
                $countryCode,
                $mobileNumber
            )
        ) {
            return PrelaunchProfileResult::fieldFailure(
                'mobile_number',
                'A profile with this mobile number already exists.'
            );
        }

        $normalizedMemberMobile =
            IndianMobileNormalizer::normalize(
                $countryCode . $mobileNumber
            );

        if ($normalizedMemberMobile === null) {
            return PrelaunchProfileResult::fieldFailure(
                'mobile_number',
                'Please enter a valid mobile number.'
            );
        }

        $existingMemberContact =
            $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $normalizedMemberMobile
            );

        if (is_array($existingMemberContact)) {
            return PrelaunchProfileResult::fieldFailure(
                'mobile_number',
                'This mobile number is already registered with an existing member.'
            );
        }

        /*
         * The Field Officer ID comes only from server configuration.
         *
         * Re-resolve the active database row for every profile so a
         * disabled or deleted officer cannot receive new profiles.
         */
        $fieldOfficer = $this
            ->fieldOfficerService
            ->resolveConfiguredOfficer(
                $this->configuration
                    ->profileFieldOfficerId
            );

        $fieldOfficerId = (int) (
            $fieldOfficer['id']
            ?? 0
        );

        if ($fieldOfficerId <= 0) {
            throw new RuntimeException(
                'The configured Field Officer is invalid.'
            );
        }

        $profileReference =
            $this->generateReference();

        $parentContactNumber =
            $this->normalizeOptionalParentContact(
                $input['parent_contact_number']
                    ?? null
            );

        $educationId = (int) (
            $input['highest_education_id']
            ?? 0
        );

        $occupationId = (int) (
            $input['occupation_id']
            ?? 0
        );

        /*
         * Prelaunch does not collect annual income, so NULL is
         * passed for that optional selection.
         *
         * Reuse the same active-master validation as the live
         * member Education & Profession flow.
         */
        $this->profileMasterDataService
            ->assertValidEducationProfessionSelection(
                $educationId,
                $occupationId,
                null
            );

        $this->database->transException(true);
        $this->database->transBegin();

        try {
            $profileId = $this->profileModel->insert(
                [
                    'profile_reference' =>
                    $profileReference,

                    'profile_created_for' =>
                    $profileCreatedFor,

                    'gender' =>
                    $gender,

                    'full_name' =>
                    trim(
                        (string) (
                            $input['full_name']
                            ?? ''
                        )
                    ),

                    'date_of_birth' =>
                    (string) (
                        $input['date_of_birth']
                        ?? ''
                    ),

                    'email' =>
                    $email,

                    'country_code' =>
                    $countryCode,

                    'mobile_number' =>
                    $mobileNumber,

                    'marital_status_id' =>
                    (int) (
                        $input['marital_status_id']
                        ?? 0
                    ),

                    'height_id' =>
                    (int) (
                        $input['height_id']
                        ?? 0
                    ),

                    'country_id' =>
                    (int) (
                        $input['country_id']
                        ?? 0
                    ),

                    'state_id' =>
                    (int) (
                        $input['state_id']
                        ?? 0
                    ),

                    'city_id' =>
                    (int) (
                        $input['city_id']
                        ?? 0
                    ),

                    /*
                     * Use the already normalized and validated IDs.
                     */
                    'highest_education_id' =>
                    $educationId,

                    'employed_in' =>
                    (string) (
                        $input['employed_in']
                        ?? ''
                    ),

                    'occupation_id' =>
                    $occupationId,

                    'father_name' =>
                    trim(
                        (string) (
                            $input['father_name']
                            ?? ''
                        )
                    ),

                    'mother_name' =>
                    trim(
                        (string) (
                            $input['mother_name']
                            ?? ''
                        )
                    ),

                    'parent_contact_number' =>
                    $parentContactNumber,

                    'sikh_community_id' =>
                    (int) (
                        $input['sikh_community_id']
                        ?? 0
                    ),

                    'gotra' =>
                    $gotra,

                    'nearest_gurudwara' =>
                    $nearestGurudwara,

                    'field_officer_id' =>
                    $fieldOfficerId,

                    /*
                     * The creator is the configured Field Officer
                     * for this standalone data-entry workflow.
                     */
                    'created_by' =>
                    $fieldOfficerId,

                    'created_source' =>
                    PrelaunchProfileModel
                    ::CREATED_SOURCE_FIELD_OFFICER,

                    'is_prelaunch_profile' =>
                    true,

                    'status' =>
                    PrelaunchProfileModel
                    ::STATUS_DRAFT,
                ],
                true
            );

            if ($profileId === false) {
                throw new RuntimeException(
                    'The prelaunch profile could not be saved.'
                );
            }

            $this->photoService
                ->storeProfilePhotos(
                    (int) $profileId,
                    $profileReference,
                    $photos
                );

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The profile transaction failed.'
                );
            }

            $this->database->transCommit();

            return PrelaunchProfileResult::success(
                (int) $profileId,
                $profileReference
            );
        } catch (Throwable $exception) {
            if (
                $this->database->transStatus()
                !== null
            ) {
                $this->database->transRollback();
            }

            throw $exception;
        }
    }

    /**
     * Resolve and enforce gender according to Profile Created For.
     *
     * Gender supplied by the browser is never trusted for relationships
     * where the relationship itself determines the member's gender.
     */
    private function resolveGender(
        string $profileCreatedFor,
        string $submittedGender
    ): string {
        $normalizedRelationship =
            mb_strtoupper(
                trim(
                    $profileCreatedFor
                )
            );

        $normalizedGender =
            mb_strtoupper(
                trim(
                    $submittedGender
                )
            );

        $fixedGenderByRelationship = [
            'SON' => 'MALE',
            'BROTHER' => 'MALE',
            'DAUGHTER' => 'FEMALE',
            'SISTER' => 'FEMALE',
        ];

        if (
            isset(
                $fixedGenderByRelationship[$normalizedRelationship]
            )
        ) {
            return $fixedGenderByRelationship[$normalizedRelationship];
        }

        if (
            !in_array(
                $normalizedRelationship,
                [
                    'SELF',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Please select a valid profile relationship.'
            );
        }

        if (
            !in_array(
                $normalizedGender,
                [
                    'MALE',
                    'FEMALE',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Please select gender.'
            );
        }

        return $normalizedGender;
    }

    /**
     * Generate a unique public prelaunch profile reference.
     */
    private function generateReference(): string
    {
        for (
            $attempt = 0;
            $attempt < 10;
            $attempt++
        ) {
            $reference = 'PRE'
                . date('ymd')
                . strtoupper(
                    bin2hex(
                        random_bytes(3)
                    )
                );

            $existingProfile =
                $this->profileModel
                ->where(
                    'profile_reference',
                    $reference
                )
                ->first();

            if ($existingProfile === null) {
                return $reference;
            }
        }

        throw new RuntimeException(
            'A unique profile reference could not be generated.'
        );
    }

    /**
     * Normalize an optional parent mobile number.
     *
     * The field is not used for authentication and is not subject to
     * member-contact uniqueness rules.
     */
    private function normalizeOptionalParentContact(
        mixed $value
    ): ?string {
        $submittedValue = trim(
            (string) $value
        );

        if ($submittedValue === '') {
            return null;
        }

        $normalized =
            IndianMobileNormalizer::normalize(
                $submittedValue
            );

        if ($normalized === null) {
            /*
             * The browser submits the national ten-digit value.
             * Prefix +91 when the normalizer expects a complete
             * Indian number.
             */
            $normalized =
                IndianMobileNormalizer::normalize(
                    '+91' . $submittedValue
                );
        }

        if ($normalized === null) {
            throw new RuntimeException(
                'Please enter a valid parent contact number.'
            );
        }

        return $normalized;
    }
}
