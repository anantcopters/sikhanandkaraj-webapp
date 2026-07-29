<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchProfileModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Handles prelaunch profile creation.
 */
final class PrelaunchProfileService
{
    public function __construct(
        private readonly PrelaunchProfileModel $profileModel,
        private readonly PrelaunchFieldOfficerService $fieldOfficerService,
        private readonly PrelaunchPhotoService $photoService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Create a draft prelaunch profile and its photographs.
     *
     * User-correctable failures, such as duplicate email or mobile
     * number, are returned through PrelaunchProfileResult.
     *
     * Infrastructure failures continue to throw exceptions so that
     * they can be logged without exposing technical information.
     *
     * @param array<string, mixed>     $input
     * @param array<int, UploadedFile> $photos
     */
    public function createDraft(
        array $input,
        array $photos
    ): PrelaunchProfileResult {
        $email = mb_strtolower(
            trim(
                (string) (
                    $input['email']
                    ?? ''
                )
            )
        );

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
         * Return them against the exact HTML field names so that the
         * controller can send them through validationErrors.
         */
        if (
            $this->profileModel->emailExists(
                $email
            )
        ) {
            return PrelaunchProfileResult::fieldFailure(
                'email',
                'A profile with this email address already exists.'
            );
        }

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

        $fieldOfficerId = (int) (
            $input['verified_field_officer_id']
            ?? 0
        );

        $fieldOfficerCode = (string) (
            $input['field_officer_code']
            ?? ''
        );

        /*
         * Never trust the hidden Field Officer ID alone.
         * Re-check the active officer and code on the server.
         */
        try {
            $this->fieldOfficerService
                ->assertVerifiedOfficer(
                    $fieldOfficerId,
                    $fieldOfficerCode
                );
        } catch (RuntimeException) {
            return PrelaunchProfileResult::fieldFailure(
                'field_officer_code',
                'Please verify a valid active Field Officer code.'
            );
        }

        $profileReference =
            $this->generateReference();

        $this->database->transBegin();

        try {
            $profileId =
                $this->profileModel->insert(
                    [
                        'profile_reference' =>
                        $profileReference,

                        'profile_created_for' =>
                        $profileCreatedFor,

                        'gender' =>
                        $gender,

                        'full_name' =>
                        trim(
                            (string) $input['full_name']
                        ),

                        'date_of_birth' =>
                        (string) $input['date_of_birth'],

                        'email' =>
                        $email,

                        'country_code' =>
                        $countryCode,

                        'mobile_number' =>
                        $mobileNumber,

                        'marital_status_id' =>
                        (int) $input['marital_status_id'],

                        'height_id' =>
                        (int) $input['height_id'],

                        'country_id' =>
                        (int) $input['country_id'],

                        'state_id' =>
                        (int) $input['state_id'],

                        'city_id' =>
                        (int) $input['city_id'],

                        'highest_education_id' =>
                        (int) $input['highest_education_id'],

                        'employed_in' =>
                        (string) $input['employed_in'],

                        'occupation_id' =>
                        (int) $input['occupation_id'],

                        'father_name' =>
                        trim(
                            (string) $input['father_name']
                        ),

                        'mother_name' =>
                        trim(
                            (string) $input['mother_name']
                        ),

                        'sikh_community_id' =>
                        (int) $input['sikh_community_id'],

                        'sikh_subcommunity_id' =>
                        (int) $input['sikh_subcommunity_id'],

                        'gotra' =>
                        $gotra,

                        'field_officer_id' =>
                        $fieldOfficerId,

                        /*
                         * The creator is the verified Field Officer for
                         * this standalone data-entry workflow.
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
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Resolve and enforce Gender according to Profile Created For.
     *
     * Gender supplied by the browser is never trusted for relationships
     * where the relationship itself determines the member's gender.
     *
     * @throws RuntimeException
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
                    'RELATIVE',
                    'FRIEND',
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

            if (
                $this->profileModel
                ->where(
                    'profile_reference',
                    $reference
                )
                ->first()
                === null
            ) {
                return $reference;
            }
        }

        throw new RuntimeException(
            'A unique profile reference could not be generated.'
        );
    }
}
