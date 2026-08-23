<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Support\EmailAddressMasker;
use App\Support\MobileNumberMasker;
use DateTimeImmutable;
use Throwable;

final class MemberProfilePdfDataService
{
    public function __construct(
        private readonly
        MemberProfilePdfAssetService
        $assetService,

        private readonly
        BasicPartnerPreferenceService
        $basicPreferenceService,

        private readonly
        AdditionalPartnerPreferenceService
        $additionalPreferenceService
    ) {}

    /**
     * @param array<string,mixed> $profile
     *
     * @return array<string,mixed>
     */
    public function prepare(
        int $profileOwnerUserId,
        array $profile
    ): array {
        $user =
            $this->section(
                $profile,
                'user'
            );

        $basic =
            $this->section(
                $profile,
                'basicDetails'
            );

        $education =
            $this->section(
                $profile,
                'educationProfession'
            );

        $family =
            $this->section(
                $profile,
                'familyDetails'
            );

        $aadhaar =
            $this->section(
                $profile,
                'aadhaarVerification'
            );

        $video =
            $this->section(
                $profile,
                'videoIntroductionState'
            );

        $lifestyle =
            isset(
                $profile['lifestyleDetails']
            )
            && is_array(
                $profile['lifestyleDetails']
            )
            ? $profile['lifestyleDetails']
            : [];

        $dateOfBirth = trim(
            (string) (
                $basic['date_of_birth']
                ?? ''
            )
        );

        $profileReference = trim(
            (string) (
                $user['profile_ref_number']
                ?? $profile['viewedProfileReference']
                ?? ''
            )
        );

        /*
         * PDF contact details are ALWAYS masked.
         *
         * Another-member profile service may already have masked
         * these values. Applying the maskers again is avoided.
         */
        $mobile = trim(
            (string) (
                $profile['viewedMaskedMemberMobile']
                ?? ''
            )
        );

        if ($mobile === '') {
            $mobile = trim(
                (string) (
                    $profile['viewedMobile']
                    ?? ''
                )
            );

            if (
                $mobile !== ''
                && !str_contains(
                    $mobile,
                    'X'
                )
            ) {
                $mobile =
                    MobileNumberMasker::lastThree(
                        $mobile
                    );
            }
        }

        $email = trim(
            (string) (
                $profile['viewedEmail']
                ?? ''
            )
        );

        if (
            $email !== ''
            && !str_contains(
                strtoupper($email),
                'X'
            )
        ) {
            $email =
                EmailAddressMasker::mask(
                    $email
                );
        }

        $thumbnailUrl =
            $this->primaryThumbnail(
                $profile
            );

        if ($thumbnailUrl === '') {
            log_message(
                'warning',
                'Profile PDF has no thumbnail URL. Profile: {profile}',
                [
                    'profile' =>
                    $profileReference,
                ]
            );
        }

        $thumbnail =
            $this->assetService
            ->remoteImage(
                $thumbnailUrl
            );

        if (
            $thumbnailUrl !== ''
            && $thumbnail === ''
        ) {
            log_message(
                'warning',
                'Profile PDF could not embed thumbnail. Profile: {profile}',
                [
                    'profile' =>
                    $profileReference,
                ]
            );
        }

        $employmentCode = strtoupper(
            trim(
                (string) (
                    $education['employed_in']
                    ?? ''
                )
            )
        );

        $employmentLabels = [
            'GOVERNMENT_PSU' =>
            'Government / PSU',

            'PRIVATE' =>
            'Private',

            'BUSINESS' =>
            'Business',

            'DEFENSE' =>
            'Defence',

            'SELF_EMPLOYED' =>
            'Self Employed',

            'NOT_WORKING' =>
            'Not Working',
        ];

        $quickDetails = [
            $this->row(
                'calendar',
                'Date of Birth',
                $this->formattedDate(
                    $dateOfBirth
                )
            ),

            $this->row(
                'heart',
                'Marital Status',
                $basic['marital_status_name']
                    ?? ''
            ),

            $this->row(
                'height',
                'Height',
                $basic['height_display_name']
                    ?? ''
            ),

            $this->row(
                'language',
                'Mother Tongue',
                $basic['mother_tongue_name']
                    ?? ''
            ),

            $this->row(
                'religion',
                'Religion',
                'Sikh'
            ),

            $this->row(
                'community',
                'Community',
                $family['community_name']
                    ?? ''
            ),
        ];

        $educationRows = [
            $this->row(
                'education',
                'Education',
                $education['highest_education_name']
                    ?? ''
            ),

            $this->row(
                'college',
                'College / University',
                $education['college_institution']
                    ?? ''
            ),

            $this->row(
                'occupation',
                'Occupation',
                $education['occupation_name']
                    ?? ''
            ),

            $this->row(
                'employer',
                'Employer',
                $education['organization']
                    ?? ''
            ),

            $this->row(
                'income',
                'Annual Income',
                $education['annual_income_display_name']
                    ?? ''
            ),

            $this->row(
                'occupation',
                'Employed In',
                $employmentLabels[$employmentCode]
                    ?? ''
            ),
        ];

        $familyRows = [
            $this->row(
                'user',
                "Father's Name",
                $family['father_name']
                    ?? ''
            ),

            $this->row(
                'occupation',
                "Father's Occupation",
                $family['father_occupation_name']
                    ?? ''
            ),

            $this->row(
                'user',
                "Mother's Name",
                $family['mother_name']
                    ?? ''
            ),

            $this->row(
                'family',
                'Siblings',
                $this->siblings(
                    $family
                )
            ),

            $this->row(
                'family',
                'Family Type',
                $family['family_type_name']
                    ?? ''
            ),

            $this->row(
                'location',
                'Family Location',
                $this->location(
                    $family
                )
            ),
        ];

        $icons = [];

        foreach (
            [
                'calendar',
                'heart',
                'height',
                'language',
                'religion',
                'community',
                'education',
                'college',
                'occupation',
                'employer',
                'income',
                'family',
                'location',
                'diet',
                'smoking',
                'drinking',
                'workout',
                'hobbies',
                'phone',
                'email',
                'shield-check',
                'video',
                'user',
                'preference',
            ]
            as $iconName
        ) {
            $icons[$iconName] = [
                'purple' =>
                $this->assetService
                    ->icon(
                        $iconName,
                        'purple'
                    ),

                'red' =>
                $this->assetService
                    ->icon(
                        $iconName,
                        'red'
                    ),
            ];
        }

        $aadhaarVerified =
            trim(
                (string) (
                    $aadhaar['aadhaar_name']
                    ?? ''
                )
            ) !== ''
            && trim(
                (string) (
                    $aadhaar['aadhaar_date_of_birth']
                    ?? ''
                )
            ) !== '';

        return [
            'profileReference' =>
            $profileReference,

            'fullName' =>
            trim(
                (string) (
                    $user['full_name']
                    ?? 'Member Profile'
                )
            ),

            'age' =>
            $this->age(
                $dateOfBirth
            ),

            'height' =>
            trim(
                (string) (
                    $basic['height_display_name']
                    ?? ''
                )
            ),

            'location' =>
            $this->location(
                $basic
            ),

            'thumbnail' =>
            $thumbnail,

            'quickDetails' =>
            $this->nonEmptyRows(
                $quickDetails
            ),

            'educationRows' =>
            $this->nonEmptyRows(
                $educationRows
            ),

            'familyRows' =>
            $this->nonEmptyRows(
                $familyRows
            ),

            'lifestyleRows' =>
            $this->lifestyleRows(
                $lifestyle
            ),

            /*
             * Always the PROFILE OWNER'S preferences,
             * not the viewer's matching criteria.
             */
            'preferences' =>
            $this->preferences(
                $profileOwnerUserId
            ),

            'aboutMe' =>
            trim(
                (string) (
                    $profile['aboutMe']
                    ?? ''
                )
            ),

            'maskedMobile' =>
            $mobile,

            'maskedEmail' =>
            $email,

            'isMobileVerified' => (
                $profile['isViewedMobileVerified']
                ?? false
            ) === true
                || (
                    $profile['isViewedMaskedMobileVerified']
                    ?? false
                ) === true,

            'isEmailVerified' => (
                $profile['isViewedEmailVerified']
                ?? false
            ) === true,

            'isAadhaarVerified' =>
            $aadhaarVerified,

            'hasVideoIntroduction' => (
                $video['hasBadge']
                ?? false
            ) === true
                && (
                    $video['isHidden']
                    ?? false
                ) !== true,

            'assets' =>
            $this->assetService
                ->commonAssets(),

            'icons' =>
            $icons,
        ];
    }

    /**
     * @param list<array<string,mixed>> $details
     *
     * @return list<array<string,string>>
     */
    private function lifestyleRows(
        array $details
    ): array {
        $categories = [];

        foreach ($details as $detail) {
            if (!is_array($detail)) {
                continue;
            }

            $category = trim(
                (string) (
                    $detail['category_name']
                    ?? ''
                )
            );

            $code = strtoupper(
                trim(
                    (string) (
                        $detail['category_code']
                        ?? ''
                    )
                )
            );

            $value = trim(
                (string) (
                    $detail['name']
                    ?? ''
                )
            );

            if (
                $category === ''
                || $value === ''
            ) {
                continue;
            }

            $categories[$category]['code'] = $code;

            $categories[$category]['values'][] = $value;
        }

        $rows = [];

        foreach (
            $categories
            as $category => $data
        ) {
            $code = (string) (
                $data['code']
                ?? ''
            );

            $icon = match (true) {
                str_contains(
                    $code,
                    'DIET'
                ) =>
                'diet',

                str_contains(
                    $code,
                    'SMOK'
                ) =>
                'smoking',

                str_contains(
                    $code,
                    'DRINK'
                ) =>
                'drinking',

                str_contains(
                    $code,
                    'WORK'
                ) =>
                'workout',

                default =>
                'hobbies',
            };

            $values =
                isset($data['values'])
                && is_array(
                    $data['values']
                )
                ? array_unique(
                    array_map(
                        'strval',
                        $data['values']
                    )
                )
                : [];

            $rows[] =
                $this->row(
                    $icon,
                    (string) $category,
                    implode(
                        ', ',
                        $values
                    )
                );
        }

        return array_slice(
            $rows,
            0,
            5
        );
    }

    /**
     * @return list<array<string,string>>
     */
    private function preferences(
        int $userId
    ): array {
        $result = [];

        $basic =
            $this
            ->basicPreferenceService
            ->getSummaryForUser(
                $userId
            );

        $basicItems =
            isset($basic['items'])
            && is_array(
                $basic['items']
            )
            ? $basic['items']
            : [];

        foreach ($basicItems as $item) {
            $this->appendPreference(
                $result,
                $item
            );
        }

        $sections =
            $this
            ->additionalPreferenceService
            ->getSummarySections(
                $userId
            );

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $items =
                isset($section['items'])
                && is_array(
                    $section['items']
                )
                ? $section['items']
                : [];

            foreach ($items as $item) {
                if (
                    is_array($item)
                    && (
                        $item['key']
                        ?? ''
                    ) === 'special-request'
                ) {
                    continue;
                }

                $this->appendPreference(
                    $result,
                    $item
                );
            }
        }

        return array_slice(
            $result,
            0,
            6
        );
    }

    /**
     * @param list<array<string,string>> $result
     */
    private function appendPreference(
        array &$result,
        mixed $item
    ): void {
        if (!is_array($item)) {
            return;
        }

        $label = trim(
            (string) (
                $item['title']
                ?? ''
            )
        );

        $value = trim(
            (string) (
                $item['value']
                ?? ''
            )
        );

        if (
            $label === ''
            || $value === ''
            || $value === 'Not added'
        ) {
            return;
        }

        $result[] = [
            'icon' =>
            $this->preferenceIcon(
                (string) (
                    $item['key']
                    ?? ''
                )
            ),

            'label' =>
            $label,

            'value' =>
            $value,
        ];
    }

    private function preferenceIcon(
        string $key
    ): string {
        $key =
            strtolower($key);

        return match (true) {
            str_contains(
                $key,
                'age'
            ) =>
            'calendar',

            str_contains(
                $key,
                'height'
            ) =>
            'height',

            str_contains(
                $key,
                'education'
            ) =>
            'education',

            str_contains(
                $key,
                'occupation'
            ) =>
            'occupation',

            str_contains(
                $key,
                'location'
            ),
            str_contains(
                $key,
                'city'
            ),
            str_contains(
                $key,
                'state'
            ),
            str_contains(
                $key,
                'country'
            ) =>
            'location',

            default =>
            'preference',
        };
    }

    /**
     * @param array<string,mixed> $profile
     */
    private function primaryThumbnail(
        array $profile
    ): string {
        $photos =
            isset(
                $profile['approvedPhotos']
            )
            && is_array(
                $profile['approvedPhotos']
            )
            ? $profile['approvedPhotos']
            : [];

        $fallback = '';

        foreach ($photos as $photo) {
            if (!is_array($photo)) {
                continue;
            }

            $url = trim(
                (string) (
                    $photo['thumbnailUrl']
                    ?? ''
                )
            );

            if ($url === '') {
                continue;
            }

            if ($fallback === '') {
                $fallback = $url;
            }

            if (
                (
                    $photo['isPrimary']
                    ?? false
                ) === true
            ) {
                return $url;
            }
        }

        return $fallback;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function siblings(
        array $data
    ): string {
        $parts = [];

        $brothers = max(
            0,
            (int) (
                $data['brothers_count']
                ?? 0
            )
        );

        $sisters = max(
            0,
            (int) (
                $data['sisters_count']
                ?? 0
            )
        );

        if ($brothers > 0) {
            $parts[] =
                $brothers
                . (
                    $brothers === 1
                    ? ' Brother'
                    : ' Brothers'
                );
        }

        if ($sisters > 0) {
            $parts[] =
                $sisters
                . (
                    $sisters === 1
                    ? ' Sister'
                    : ' Sisters'
                );
        }

        return implode(
            ', ',
            $parts
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function location(
        array $data
    ): string {
        return implode(
            ', ',
            array_filter(
                [
                    trim(
                        (string) (
                            $data['city_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $data['state_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $data['country_name']
                            ?? ''
                        )
                    ),
                ],
                static fn(
                    string $value
                ): bool =>
                $value !== ''
            )
        );
    }

    private function formattedDate(
        string $date
    ): string {
        if ($date === '') {
            return '';
        }

        try {
            return (
                new DateTimeImmutable(
                    $date
                )
            )->format(
                'd M Y'
            );
        } catch (Throwable) {
            return '';
        }
    }

    private function age(
        string $date
    ): ?int {
        if ($date === '') {
            return null;
        }

        try {
            $birth =
                new DateTimeImmutable(
                    $date
                );

            $today =
                new DateTimeImmutable(
                    'today'
                );

            return $birth <= $today
                ? $birth
                ->diff($today)
                ->y
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string,string>
     */
    private function row(
        string $icon,
        string $label,
        mixed $value
    ): array {
        return [
            'icon' =>
            $icon,

            'label' =>
            $label,

            'value' =>
            trim(
                (string) $value
            ),
        ];
    }

    /**
     * @param list<array<string,string>> $rows
     *
     * @return list<array<string,string>>
     */
    private function nonEmptyRows(
        array $rows
    ): array {
        return array_values(
            array_filter(
                $rows,
                static fn(
                    array $row
                ): bool =>
                trim(
                    $row['value']
                        ?? ''
                ) !== ''
            )
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function section(
        array $profile,
        string $key
    ): array {
        return isset($profile[$key])
            && is_array(
                $profile[$key]
            )
            ? $profile[$key]
            : [];
    }
}
