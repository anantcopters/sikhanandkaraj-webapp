<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Models\MemberPhotoModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Aws\AwsMediaService;
use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Services\Profile\BasicDetailsService;
use App\Services\Profile\EducationProfessionService;
use App\Support\PartnerPreference\AdditionalPreferenceItem;
use App\Support\PartnerPreference\BasicPreferenceItem;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use FilesystemIterator;
use RuntimeException;
use Throwable;

/**
 * Creates development-only member profiles from numerically named image folders.
 *
 * Expected structure:
 *
 * public/assets/images/male/1/*
 * public/assets/images/male/2/*
 * public/assets/images/female/1/*
 * public/assets/images/female/2/*
 *
 * Every numeric directory creates exactly one member, and every supported image
 * inside that directory is uploaded to that member.
 */
final class DevelopmentProfileLoaderService
{
    private const GENDER_MALE = 'M';

    private const GENDER_FEMALE = 'F';

    private const PROFILE_CREATED_FOR = 'self';

    private const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    private const MAX_PHOTOS_PER_PROFILE = 5;

    private const DEFAULT_PASSWORD = 'Test@12345';

    private const MOBILE_START = 7000000000;

    /**
     * Common first names used only for non-production development data.
     *
     * @var list<string>
     */
    private const MALE_NAMES = [
        'Gurpreet Singh',
        'Harpreet Singh',
        'Manpreet Singh',
        'Jaspreet Singh',
        'Amritpal Singh',
        'Navjot Singh',
        'Simranjit Singh',
        'Dilpreet Singh',
        'Ravinder Singh',
        'Parminder Singh',
        'Hardeep Singh',
        'Sukhman Singh',
    ];

    /**
     * @var list<string>
     */
    private const FEMALE_NAMES = [
        'Gurpreet Kaur',
        'Harpreet Kaur',
        'Manpreet Kaur',
        'Jaspreet Kaur',
        'Amrit Kaur',
        'Navjot Kaur',
        'Simran Kaur',
        'Dilpreet Kaur',
        'Ravinder Kaur',
        'Parminder Kaur',
        'Hardeep Kaur',
        'Sukhman Kaur',
    ];

    /**
     * @var list<string>
     */
    private const EMPLOYMENT_TYPES = [
        'GOVERNMENT_PSU',
        'PRIVATE',
        'BUSINESS',
        'DEFENSE',
        'SELF_EMPLOYED',
        'NOT_WORKING',
    ];

    private const SUPPORTED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $userContactModel,
        private readonly MemberPhotoModel $memberPhotoModel,
        private readonly BasicDetailsService $basicDetailsService,
        private readonly EducationProfessionService $educationProfessionService,
        private readonly BasicPartnerPreferenceService $basicPreferenceService,
        private readonly AdditionalPartnerPreferenceService $additionalPreferenceService,
        private readonly AwsMediaService $awsMediaService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Load every valid numeric profile folder.
     *
     * @return array{
     *     batch:string,
     *     created:int,
     *     skipped:int,
     *     failed:int,
     *     profiles:list<array<string, mixed>>,
     *     errors:list<array<string, string>>
     * }
     */
    public function loadAll(): array
    {
        $this->assertDevelopmentEnvironment();

        $batch = 'DEV-' . date('Ymd-His');

        $result = [
            'batch' => $batch,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'profiles' => [],
            'errors' => [],
        ];

        foreach ($this->profileDirectories() as $profileDirectory) {
            try {
                $profileResult = $this->loadProfile(
                    gender: $profileDirectory['gender'],
                    folderNumber: $profileDirectory['folderNumber'],
                    directory: $profileDirectory['directory'],
                    batch: $batch
                );

                if ($profileResult['status'] === 'SKIPPED') {
                    ++$result['skipped'];
                } else {
                    ++$result['created'];
                }

                $result['profiles'][] = $profileResult;
            } catch (Throwable $exception) {
                ++$result['failed'];

                $result['errors'][] = [
                    'folder' =>
                    $profileDirectory['gender']
                        . '/'
                        . $profileDirectory['folderNumber'],

                    'message' => $exception->getMessage(),
                ];

                log_message(
                    'error',
                    'Development profile creation failed for {folder}: {message}',
                    [
                        'folder' =>
                        $profileDirectory['gender']
                            . '/'
                            . $profileDirectory['folderNumber'],

                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Create one member from one numeric folder.
     *
     * @return array<string, mixed>
     */
    private function loadProfile(
        string $gender,
        int $folderNumber,
        string $directory,
        string $batch
    ): array {
        $images = $this->imageFiles($directory);

        $this->assertSupportedImages(
            $images
        );

        if ($images === []) {
            throw new DomainException(
                'The profile folder does not contain a supported image.'
            );
        }

        if (count($images) > self::MAX_PHOTOS_PER_PROFILE) {
            throw new DomainException(
                sprintf(
                    'The folder contains %d images. A member may have a maximum of %d photos.',
                    count($images),
                    self::MAX_PHOTOS_PER_PROFILE
                )
            );
        }

        $sourceKey = $this->sourceKey(
            $gender,
            $folderNumber
        );

        $existing = $this->findExistingSourceProfile(
            $sourceKey
        );

        if (is_array($existing)) {
            return [
                'status' => 'SKIPPED',
                'source' => $sourceKey,
                'userId' => (int) $existing['id'],
                'profileReference' =>
                (string) $existing['profile_ref_number'],
                'reason' =>
                'This numeric image folder has already been imported.',
            ];
        }
        $userId = null;
        $uploadedObjectKeys = [];

        try {
            /*
             * Account/contact creation is one database decision.
             *
             * AWS upload is deliberately performed only after this transaction
             * commits, following the project rule that external calls must not
             * keep a database transaction open.
             */
            $identity = $this->createIdentity(
                gender: $gender,
                folderNumber: $folderNumber,
                sourceKey: $sourceKey,
                batch: $batch
            );

            $userId = $identity['userId'];

            $this->saveProfileSections(
                $userId,
                $gender
            );

            $this->savePartnerPreferences(
                $userId,
                $gender
            );

            $photoIds = [];

            foreach ($images as $index => $imagePath) {
                $uploaded = $this->awsMediaService
                    ->uploadProfilePhotoFromPath(
                        sourcePath: $imagePath,
                        originalFilename: basename($imagePath),
                        memberId: $userId
                    );

                $uploadedObjectKeys = array_merge(
                    $uploadedObjectKeys,
                    [
                        $uploaded['originalObjectKey'],
                        $uploaded['mediumObjectKey'],
                        $uploaded['thumbnailObjectKey'],
                    ]
                );

                $photoId = $this->insertApprovedPhoto(
                    userId: $userId,
                    uploaded: $uploaded,
                    isPrimary: $index === 0
                );

                $photoIds[] = $photoId;
            }

            return [
                'status' => 'CREATED',
                'source' => $sourceKey,
                'gender' => $gender,
                'userId' => $userId,
                'profileReference' =>
                $identity['profileReference'],
                'mobile' => $identity['mobile'],
                'password' => self::DEFAULT_PASSWORD,
                'photoCount' => count($photoIds),
                'photoIds' => $photoIds,
            ];
        } catch (Throwable $exception) {
            /*
             * Remove any AWS variants created before a later failure.
             *
             * The source files under public/assets/images remain untouched.
             */
            if ($uploadedObjectKeys !== []) {
                try {
                    $this->awsMediaService->deleteObjectKeys(
                        array_values(
                            array_unique(
                                array_filter(
                                    $uploadedObjectKeys
                                )
                            )
                        )
                    );
                } catch (Throwable $cleanupException) {
                    log_message(
                        'critical',
                        'Development profile AWS cleanup failed: {message}',
                        [
                            'message' =>
                            $cleanupException->getMessage(),
                        ]
                    );
                }
            }

            if (is_int($userId) && $userId > 0) {
                try {
                    $this->deleteFailedGeneratedProfile(
                        $userId
                    );
                } catch (\Throwable $cleanupException) {
                    log_message(
                        'critical',
                        'Failed development profile database cleanup for '
                            . 'user {userId}: {message}',
                        [
                            'userId' => $userId,
                            'message' =>
                            $cleanupException->getMessage(),
                        ]
                    );
                }
            }

            throw $exception;
        }
    }

    /**
     * Remove a generated profile when its media import fails.
     *
     * This method is development-only and relies on the existing foreign-key
     * cascade rules for member-owned child rows.
     */
    private function deleteFailedGeneratedProfile(
        int $userId
    ): void {
        $this->database->transException(true);
        $this->database->transBegin();

        try {
            $this->database
                ->table('development_profile_imports')
                ->where('user_id', $userId)
                ->delete();

            $deleted = $this->userModel->delete(
                $userId,
                true
            );

            if ($deleted === false) {
                throw new RuntimeException(
                    'The failed generated member could not be removed.'
                );
            }

            if (
                $this->database->transStatus() === false
            ) {
                throw new RuntimeException(
                    'The failed generated member cleanup transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (\Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Create the active account and verified primary mobile contact.
     *
     * @return array{
     *     userId:int,
     *     profileReference:string,
     *     mobile:string
     * }
     */
    private function createIdentity(
        string $gender,
        int $folderNumber,
        string $sourceKey,
        string $batch
    ): array {
        $profileReference = $this->nextProfileReference();

        $mobile = $this->nextMobileNumber(
            $gender,
            $folderNumber
        );

        $fullName = $this->randomName(
            $gender
        );

        $this->database->transException(true);
        $this->database->transStart();

        try {
            $inserted = $this->userModel->insert(
                [
                    /*
                     * The source key is stored in the existing nullable
                     * prelaunch_profile_id-compatible marker table below rather
                     * than overloading a live business field.
                     */
                    'profile_ref_number' => $profileReference,
                    'profile_created_for' =>
                    self::PROFILE_CREATED_FOR,
                    'gender' => $gender,
                    'full_name' => $fullName,
                    'password_hash' => password_hash(
                        self::DEFAULT_PASSWORD,
                        PASSWORD_DEFAULT
                    ),
                    'account_status' =>
                    UserModel::STATUS_ACTIVE,
                ],
                true
            );

            if (!is_numeric($inserted)) {
                throw new RuntimeException(
                    'The development member account could not be created.'
                );
            }

            $userId = (int) $inserted;

            $contactInserted = $this->userContactModel->insert(
                [
                    'user_id' => $userId,
                    'contact_type' =>
                    UserContactModel::TYPE_MOBILE,
                    'contact_value' => $mobile,
                    'normalized_value' => '+91' . $mobile,
                    'is_primary' => true,
                    'is_verified' => true,
                    'verified_at' => date('Y-m-d H:i:s'),
                ],
                false
            );

            if ($contactInserted === false) {
                throw new RuntimeException(
                    'The verified development mobile contact could not be created.'
                );
            }

            $this->insertImportMarker(
                userId: $userId,
                sourceKey: $sourceKey,
                batch: $batch
            );

            $this->database->transComplete();

            if ($this->database->transStatus() === false) {
                throw new RuntimeException(
                    'The development member transaction failed.'
                );
            }

            return [
                'userId' => $userId,
                'profileReference' => $profileReference,
                'mobile' => $mobile,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Save sections through current reusable profile services.
     */
    private function saveProfileSections(
        int $userId,
        string $gender
    ): void {
        $basicProfile = $this->basicDetailsService
            ->getForUser($userId);

        $basicMaster = $basicProfile['masterData'];

        $country = $basicMaster['country'] ?? null;

        if (!is_array($country)) {
            throw new RuntimeException(
                'India master data is unavailable.'
            );
        }

        $state = $this->randomRecord(
            $basicMaster['states'] ?? [],
            'No active state master data is available.'
        );

        $cities = service('profileMasterDataService')
            ->citiesForState(
                (int) $state['id']
            );

        $city = $this->randomRecord(
            $cities,
            'No active city is available for the selected state.'
        );

        $maritalStatus = $this->randomRecord(
            $basicMaster['maritalStatuses'] ?? [],
            'Marital status master data is unavailable.'
        );

        $height = $this->randomRecord(
            $basicMaster['heights'] ?? [],
            'Height master data is unavailable.'
        );

        $motherTongue = $this->randomRecord(
            $basicMaster['motherTongues'] ?? [],
            'Mother tongue master data is unavailable.'
        );

        $drinkingHabit = $this->randomOptionalRecord(
            $basicMaster['drinkingHabits'] ?? []
        );

        $eatingHabit = $this->randomOptionalRecord(
            $basicMaster['eatingHabits'] ?? []
        );

        $physicalStatus = $this->randomOptionalRecord(
            $basicMaster['physicalStatuses'] ?? []
        );

        $user = $basicProfile['user'];

        $this->basicDetailsService->save(
            $userId,
            [
                'full_name' => (string) $user['full_name'],
                'date_of_birth' =>
                $this->randomDateOfBirth($gender),
                'marital_status_id' =>
                (string) $maritalStatus['id'],
                'height_id' => (string) $height['id'],
                'mother_tongue_id' =>
                (string) $motherTongue['id'],
                'country_id' => (string) $country['id'],
                'state_id' => (string) $state['id'],
                'city_id' => (string) $city['id'],
                'drinking_habit_id' =>
                $drinkingHabit !== null
                    ? (string) $drinkingHabit['id']
                    : '',
                'eating_habit_id' =>
                $eatingHabit !== null
                    ? (string) $eatingHabit['id']
                    : '',
                'physical_status_id' =>
                $physicalStatus !== null
                    ? (string) $physicalStatus['id']
                    : '',
                'number_of_children' => '',
                'children_living_together' => '',
            ]
        );

        $educationProfile = $this
            ->educationProfessionService
            ->getForUser($userId);

        $educationMaster = $educationProfile['masterData'];

        $education = $this->randomRecord(
            $educationMaster['highestEducations']
                ?? $educationMaster['educations']
                ?? [],
            'Education master data is unavailable.'
        );

        $occupation = $this->randomRecord(
            $educationMaster['occupations'] ?? [],
            'Occupation master data is unavailable.'
        );

        $annualIncome = $this->randomOptionalRecord(
            $educationMaster['annualIncomes'] ?? []
        );

        $this->educationProfessionService->save(
            $userId,
            [
                'highest_education_id' =>
                (string) $education['id'],
                'education_detail' =>
                (string) ($education['name'] ?? ''),
                'college_institution' =>
                'Development Test Institution',
                'employed_in' =>
                $this->randomValue(
                    self::EMPLOYMENT_TYPES
                ),
                'occupation_id' =>
                (string) $occupation['id'],
                'occupation_detail' =>
                (string) ($occupation['name'] ?? ''),
                'organization' =>
                'Development Test Organization',
                'annual_income_id' =>
                $annualIncome !== null
                    ? (string) $annualIncome['id']
                    : '',
            ]
        );
    }

    /**
     * Create randomized, internally consistent partner preferences.
     */
    private function savePartnerPreferences(
        int $userId,
        string $gender
    ): void {
        $ageFrom = $gender === self::GENDER_MALE
            ? random_int(22, 28)
            : random_int(25, 31);

        $ageTo = $ageFrom + random_int(3, 7);

        $this->basicPreferenceService->saveItem(
            $userId,
            BasicPreferenceItem::AGE,
            [
                'age_from' => (string) $ageFrom,
                'age_to' => (string) $ageTo,
                'is_compulsory' =>
                $this->randomBooleanString(),
            ]
        );

        $this->saveRandomBasicMasterPreference(
            $userId,
            BasicPreferenceItem::HEIGHT,
            'heights',
            static function (
                array $selected
            ): array {
                usort(
                    $selected,
                    static fn(array $left, array $right): int =>
                    (int) $left['id']
                        <=> (int) $right['id']
                );

                return [
                    'height_from_id' =>
                    (string) $selected[0]['id'],
                    'height_to_id' =>
                    (string) $selected[count($selected) - 1]['id'],
                ];
            }
        );

        $this->saveRandomBasicMasterPreference(
            $userId,
            BasicPreferenceItem::MARITAL_STATUS,
            'maritalStatuses',
            static fn(array $selected): array => [
                'marital_status_id' =>
                (string) $selected[0]['id'],
            ]
        );

        $this->basicPreferenceService->saveItem(
            $userId,
            BasicPreferenceItem::HAVE_CHILDREN,
            [
                'have_children' =>
                $this->randomValue(
                    ['YES', 'NO', 'DOES_NOT_MATTER']
                ),
                'is_compulsory' =>
                $this->randomBooleanString(),
            ]
        );

        $this->saveRandomBasicMultiPreference(
            $userId,
            BasicPreferenceItem::MOTHER_TONGUE,
            'motherTongues',
            'mother_tongue_ids'
        );

        $this->saveRandomBasicMasterPreference(
            $userId,
            BasicPreferenceItem::PHYSICAL_STATUS,
            'physicalStatuses',
            static fn(array $selected): array => [
                'physical_status_id' =>
                (string) $selected[0]['id'],
            ]
        );

        $this->saveRandomBasicMultiPreference(
            $userId,
            BasicPreferenceItem::EATING_HABITS,
            'eatingHabits',
            'eating_habit_ids'
        );

        $this->saveRandomBasicMultiPreference(
            $userId,
            BasicPreferenceItem::DRINKING_HABITS,
            'drinkingHabits',
            'drinking_habit_ids'
        );

        $this->saveRandomAdditionalMultiPreference(
            $userId,
            AdditionalPreferenceItem::COMMUNITY,
            'communities',
            'community_ids'
        );

        $this->saveRandomAdditionalMultiPreference(
            $userId,
            AdditionalPreferenceItem::EDUCATION,
            'educations',
            'education_ids'
        );

        $employmentCount = random_int(1, 3);

        $employmentValues = self::EMPLOYMENT_TYPES;

        shuffle($employmentValues);

        $this->additionalPreferenceService->saveItem(
            $userId,
            AdditionalPreferenceItem::EMPLOYED_IN,
            [
                'employed_in_values' =>
                array_slice(
                    $employmentValues,
                    0,
                    $employmentCount
                ),
                'is_compulsory' =>
                $this->randomBooleanString(),
            ]
        );

        $this->saveRandomAdditionalMultiPreference(
            $userId,
            AdditionalPreferenceItem::OCCUPATION,
            'occupations',
            'occupation_ids'
        );

        $this->saveRandomAdditionalMultiPreference(
            $userId,
            AdditionalPreferenceItem::ANNUAL_INCOME,
            'annualIncomes',
            'annual_income_ids'
        );

        $location = $this->additionalPreferenceService
            ->getItemForUser(
                $userId,
                AdditionalPreferenceItem::LOCATION
            );

        $states = $location['states'] ?? [];

        if (is_array($states) && $states !== []) {
            $selectedStates = $this->randomSubset(
                $states,
                min(2, count($states))
            );

            $stateIds = array_map(
                static fn(array $state): string =>
                (string) $state['id'],
                $selectedStates
            );

            $cities = service('profileMasterDataService')
                ->citiesForStates(
                    array_map(
                        'intval',
                        $stateIds
                    )
                );

            $selectedCities = $this->randomSubset(
                $cities,
                min(3, count($cities))
            );

            $this->additionalPreferenceService->saveItem(
                $userId,
                AdditionalPreferenceItem::LOCATION,
                [
                    'state_ids' => $stateIds,
                    'city_ids' => array_map(
                        static fn(array $city): string =>
                        (string) $city['id'],
                        $selectedCities
                    ),
                    'is_compulsory' =>
                    $this->randomBooleanString(),
                ]
            );
        }

        $this->additionalPreferenceService->saveItem(
            $userId,
            AdditionalPreferenceItem::SPECIAL_REQUEST,
            [
                'request_text' =>
                'Looking for a compatible, respectful and family-oriented partner.',
            ]
        );
    }

    /**
     * @param callable(list<array<string, mixed>>):array<string, mixed> $mapper
     */
    private function saveRandomBasicMasterPreference(
        int $userId,
        string $item,
        string $optionsKey,
        callable $mapper
    ): void {
        $itemData = $this->basicPreferenceService
            ->getItemForUser(
                $userId,
                $item
            );

        $options = $itemData[$optionsKey] ?? [];

        if (!is_array($options) || $options === []) {
            return;
        }

        $maximum = $item === BasicPreferenceItem::HEIGHT
            ? min(8, count($options))
            : 1;

        $selected = $this->randomSubset(
            $options,
            $maximum
        );

        $payload = $mapper($selected);

        $payload['is_compulsory'] =
            $this->randomBooleanString();

        $this->basicPreferenceService->saveItem(
            $userId,
            $item,
            $payload
        );
    }

    private function saveRandomBasicMultiPreference(
        int $userId,
        string $item,
        string $optionsKey,
        string $payloadKey
    ): void {
        $itemData = $this->basicPreferenceService
            ->getItemForUser(
                $userId,
                $item
            );

        $options = $itemData[$optionsKey] ?? [];

        if (!is_array($options) || $options === []) {
            return;
        }

        $selected = $this->randomSubset(
            $options,
            min(random_int(1, 3), count($options))
        );

        $this->basicPreferenceService->saveItem(
            $userId,
            $item,
            [
                $payloadKey => array_map(
                    static fn(array $option): string =>
                    (string) $option['id'],
                    $selected
                ),
                'is_compulsory' =>
                $this->randomBooleanString(),
            ]
        );
    }

    private function saveRandomAdditionalMultiPreference(
        int $userId,
        string $item,
        string $optionsKey,
        string $payloadKey
    ): void {
        $itemData = $this->additionalPreferenceService
            ->getItemForUser(
                $userId,
                $item
            );

        $options = $itemData[$optionsKey] ?? [];

        if (!is_array($options) || $options === []) {
            return;
        }

        $selected = $this->randomSubset(
            $options,
            min(random_int(1, 3), count($options))
        );

        $this->additionalPreferenceService->saveItem(
            $userId,
            $item,
            [
                $payloadKey => array_map(
                    static fn(array $option): string =>
                    (string) $option['id'],
                    $selected
                ),
                'is_compulsory' =>
                $this->randomBooleanString(),
            ]
        );
    }

    /**
     * Insert one photo after all S3 variants have been created.
     *
     * @param array<string, mixed> $uploaded
     */
    private function insertApprovedPhoto(
        int $userId,
        array $uploaded,
        bool $isPrimary
    ): int {
        $photoId = $this->memberPhotoModel->insert(
            [
                'uuid' => $uploaded['uuid'],
                'member_id' => $userId,
                'media_type' => 'PROFILE_PHOTO',
                'original_object_key' =>
                $uploaded['originalObjectKey'],
                'medium_object_key' =>
                $uploaded['mediumObjectKey'],
                'thumbnail_object_key' =>
                $uploaded['thumbnailObjectKey'],
                'original_filename' =>
                $uploaded['originalFilename'],
                'original_mime_type' =>
                $uploaded['mimeType'],
                'original_extension' =>
                $uploaded['extension'],
                'original_file_size' =>
                $uploaded['fileSize'],
                'original_width' =>
                $uploaded['width'],
                'original_height' =>
                $uploaded['height'],
                'status' =>
                MemberPhotoModel::STATUS_APPROVED,
                'visibility' => 'PUBLIC',
                'is_primary' => $isPrimary,
                'uploaded_by_type' => 'MEMBER',
                'uploaded_by_id' => $userId,
                /*
                 * Development auto-approval has no administrator actor.
                 * approved_by therefore remains NULL while approved_at records
                 * when the trusted loader approved the image.
                 */
                'approved_by' => null,
                'approved_at' => date('Y-m-d H:i:s'),
            ],
            true
        );

        if (!is_numeric($photoId)) {
            throw new RuntimeException(
                'The approved member photo row could not be created.'
            );
        }

        return (int) $photoId;
    }

    /**
     * Return every numeric profile directory sorted numerically.
     *
     * @return list<array{
     *     gender:string,
     *     folderNumber:int,
     *     directory:string
     * }>
     */
    private function profileDirectories(): array
    {
        $result = [];

        $genderDirectories = [
            self::GENDER_MALE => [
                'directory' =>
                FCPATH . 'assets/images/male',

                'folderLabel' => 'male',
            ],

            self::GENDER_FEMALE => [
                'directory' =>
                FCPATH . 'assets/images/female',

                'folderLabel' => 'female',
            ],
        ];

        foreach ($genderDirectories as $gender => $configuration) {
            $root = $configuration['directory'];

            if (!is_dir($root) || !is_readable($root)) {
                throw new RuntimeException(
                    sprintf(
                        'The %s image directory is missing or unreadable: %s',
                        $configuration['folderLabel'],
                        $root
                    )
                );
            }

            $iterator = new FilesystemIterator(
                $root,
                FilesystemIterator::SKIP_DOTS
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    continue;
                }

                $folderName = $fileInfo->getFilename();

                if (
                    !ctype_digit($folderName)
                    || (int) $folderName <= 0
                ) {
                    continue;
                }

                $result[] = [
                    'gender' => $gender,
                    'genderFolder' =>
                    $configuration['folderLabel'],
                    'folderNumber' => (int) $folderName,
                    'directory' => $fileInfo->getPathname(),
                ];
            }
        }

        usort(
            $result,
            static function (
                array $left,
                array $right
            ): int {
                $genderComparison = strcmp(
                    $left['gender'],
                    $right['gender']
                );

                if ($genderComparison !== 0) {
                    return $genderComparison;
                }

                return $left['folderNumber']
                    <=> $right['folderNumber'];
            }
        );

        return $result;
    }

    /**
     * Return every supported image in natural filename order.
     *
     * @return list<string>
     */
    private function imageFiles(
        string $directory
    ): array {
        $files = [];

        $iterator = new FilesystemIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $extension = strtolower(
                $fileInfo->getExtension()
            );

            if (
                !in_array(
                    $extension,
                    self::IMAGE_EXTENSIONS,
                    true
                )
            ) {
                continue;
            }

            $files[] = $fileInfo->getPathname();
        }

        natsort($files);

        return array_values($files);
    }

    private function sourceKey(
        string $gender,
        int $folderNumber
    ): string {
        $genderFolder = match ($gender) {
            self::GENDER_MALE => 'male',
            self::GENDER_FEMALE => 'female',

            default => throw new DomainException(
                'Unsupported development profile gender.'
            ),
        };

        return sprintf(
            'development-profile:%s:%d',
            $genderFolder,
            $folderNumber
        );
    }

    /**
     * Record import ownership without changing production business tables.
     */
    private function insertImportMarker(
        int $userId,
        string $sourceKey,
        string $batch
    ): void {
        $inserted = $this->database
            ->table('development_profile_imports')
            ->insert([
                'user_id' => $userId,
                'source_key' => $sourceKey,
                'batch_key' => $batch,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        if ($inserted === false) {
            throw new RuntimeException(
                'The development import marker could not be created.'
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findExistingSourceProfile(
        string $sourceKey
    ): ?array {
        $record = $this->database
            ->table('development_profile_imports AS import')
            ->select([
                'users.id',
                'users.profile_ref_number',
            ])
            ->join(
                'users',
                'users.id = import.user_id',
                'inner'
            )
            ->where(
                'import.source_key',
                $sourceKey
            )
            ->get()
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    private function nextProfileReference(): string
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $reference = sprintf(
                'SAK%07d',
                random_int(1, 9999999)
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
            'A unique profile reference could not be generated.'
        );
    }

    private function nextMobileNumber(
        string $gender,
        int $folderNumber
    ): string {
        $genderOffset = $gender === self::GENDER_MALE
            ? 0
            : 100000;

        $candidate = self::MOBILE_START
            + $genderOffset
            + $folderNumber;

        if ($candidate > 9999999999) {
            throw new DomainException(
                'The numeric folder is too large to generate a test mobile number.'
            );
        }

        $mobile = (string) $candidate;

        $existing = $this->userContactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                '+91' . $mobile
            );

        if (is_array($existing)) {
            throw new DomainException(
                sprintf(
                    'Generated development mobile %s is already in use.',
                    $mobile
                )
            );
        }

        return $mobile;
    }

    private function randomName(
        string $gender
    ): string {
        $names = $gender === self::GENDER_MALE
            ? self::MALE_NAMES
            : self::FEMALE_NAMES;

        return $this->randomValue($names);
    }

    private function randomDateOfBirth(
        string $gender
    ): string {
        $minimumAge = $gender === self::GENDER_MALE
            ? 25
            : 22;

        $maximumAge = $gender === self::GENDER_MALE
            ? 38
            : 35;

        $age = random_int(
            $minimumAge,
            $maximumAge
        );

        $timestamp = strtotime(
            sprintf(
                '-%d years -%d days',
                $age,
                random_int(0, 364)
            )
        );

        if ($timestamp === false) {
            throw new RuntimeException(
                'A development date of birth could not be generated.'
            );
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, mixed>
     */
    private function randomRecord(
        array $records,
        string $errorMessage
    ): array {
        if ($records === []) {
            throw new RuntimeException($errorMessage);
        }

        return $records[array_rand($records)];
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, mixed>|null
     */
    private function randomOptionalRecord(
        array $records
    ): ?array {
        if ($records === []) {
            return null;
        }

        return $records[array_rand($records)];
    }

    /**
     * @template T
     *
     * @param list<T> $values
     *
     * @return T
     */
    private function randomValue(
        array $values
    ): mixed {
        if ($values === []) {
            throw new RuntimeException(
                'A random value cannot be selected from an empty list.'
            );
        }

        return $values[array_rand($values)];
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function randomSubset(
        array $records,
        int $maximum
    ): array {
        if ($records === [] || $maximum <= 0) {
            return [];
        }

        shuffle($records);

        return array_slice(
            $records,
            0,
            min($maximum, count($records))
        );
    }

    private function randomBooleanString(): string
    {
        return random_int(0, 1) === 1
            ? '1'
            : '0';
    }

    private function assertDevelopmentEnvironment(): void
    {
        $deployment = strtolower(
            trim(
                (string) env(
                    'APP_DEPLOYMENT',
                    'development'
                )
            )
        );

        if (
            $_ENV['CI_ENVIRONMENT'] !== 'development'
            || $deployment !== 'development'
        ) {
            throw new RuntimeException(
                'Development profile loading is disabled outside development.'
            );
        }

        $enabled = filter_var(
            env(
                'DEVELOPMENT_PROFILE_LOADER_ENABLED',
                false
            ),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$enabled) {
            throw new RuntimeException(
                'Set DEVELOPMENT_PROFILE_LOADER_ENABLED=true before running the loader.'
            );
        }
    }

    /**
     * Ensure every source image is genuinely supported by the existing media
     * pipeline. The extension alone is not trusted.
     *
     * @param list<string> $images
     */
    private function assertSupportedImages(
        array $images
    ): void {
        $fileInfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        foreach ($images as $imagePath) {
            $mimeType = $fileInfo->file(
                $imagePath
            );

            $mimeType = is_string($mimeType)
                ? strtolower(trim($mimeType))
                : '';

            if (
                !in_array(
                    $mimeType,
                    self::SUPPORTED_IMAGE_MIME_TYPES,
                    true
                )
            ) {
                throw new DomainException(
                    sprintf(
                        'Unsupported image "%s". '
                            . 'Detected MIME type: %s. '
                            . 'Convert it to JPEG, PNG or WebP.',
                        basename($imagePath),
                        $mimeType !== ''
                            ? $mimeType
                            : 'unknown'
                    )
                );
            }
        }
    }
}
