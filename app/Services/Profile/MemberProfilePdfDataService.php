<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberPhotoModel;
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
        $additionalPreferenceService,

        private readonly
        MemberPhotoModel
        $photoModel
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

        $profileImageObjectKey =
            $this->primaryProfileImageObjectKey(
                $profileOwnerUserId
            );

        if ($profileImageObjectKey === '') {
            log_message(
                'warning',
                'Profile PDF has no approved primary image. Profile: {profile}',
                [
                    'profile' =>
                    $profileReference,
                ]
            );
        }

        $thumbnail = '';

        if ($profileImageObjectKey !== '') {
            $thumbnail =
                $this->assetService
                ->storedImage(
                    $profileImageObjectKey
                );
        }

        if (
            $profileImageObjectKey !== ''
            && $thumbnail === ''
        ) {
            log_message(
                'warning',
                'Profile PDF could not embed approved primary image. Profile: {profile}',
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
                'ri-heart-2-line',
                'Marital Status',
                $this->displayValue(
                    $basic['marital_status_name']
                        ?? ''
                )
            ),

            $this->row(
                'ri-ruler-2-line',
                'Height',
                $this->displayValue(
                    $basic['height_display_name']
                        ?? ''
                )
            ),
        ];

        $educationRows = [
            $this->row(
                'ri-graduation-cap-line',
                'Highest Education',
                $this->displayValue(
                    $education['highest_education_name']
                        ?? ''
                )
            ),

            $this->row(
                'ri-building-4-line',
                'Employed In',
                $this->displayValue(
                    $employmentLabels[$employmentCode]
                        ?? ''
                )
            ),

            $this->row(
                'ri-briefcase-4-line',
                'Occupation',
                $this->displayValue(
                    $education['occupation_name']
                        ?? ''
                )
            ),

            $this->row(
                'ri-building-line',
                'Organization',
                $this->displayValue(
                    $education['organization']
                        ?? ''
                )
            ),

            $this->row(
                'ri-money-rupee-circle-line',
                'Annual Income',
                $this->displayValue(
                    $education['annual_income_display_name']
                        ?? ''
                )
            ),
        ];

        $familyRows = [
            $this->row(
                'ri-group-line',
                'Community',
                $this->displayValue(
                    $family['community_name']
                        ?? ''
                )
            ),

            $this->row(
                'ri-git-branch-line',
                'Gotra',
                $this->maskedText(
                    $family['gotra']
                        ?? ''
                )
            ),

            $this->row(
                'ri-git-branch-line',
                'Gotra (Maternal Side)',
                $this->maskedText(
                    $family['gotra_maternal']
                        ?? ''
                )
            ),


            $this->row(
                'ri-men-line',
                'No. of Brothers',
                array_key_exists(
                    'brothers_count',
                    $family
                )
                    ? (string) (
                        (int) $family['brothers_count']
                    )
                    : 'NA'
            ),

            $this->row(
                'ri-women-line',
                'No. of Sisters',
                array_key_exists(
                    'sisters_count',
                    $family
                )
                    ? (string) (
                        (int) $family['sisters_count']
                    )
                    : 'NA'
            ),

            $this->row(
                'ri-map-pin-line',
                'Family Location',
                $this->displayValue(
                    $this->location(
                        $family
                    )
                )
            ),

            $this->row(
                'ri-building-2-line',
                'Nearest Gurdwara',
                $this->maskedText(
                    $family['nearest_gurudwara']
                        ?? ''
                )
            ),
        ];

        $remixIconFont =
            $this->assetService
            ->remixIconFont();

        $remixIconCss =
            $this->assetService
            ->remixIconCss();

        // $icons = [];

        // foreach (
        //     [
        //         'calendar',
        //         'heart',
        //         'height',
        //         'language',
        //         'religion',
        //         'community',
        //         'education',
        //         'college',
        //         'occupation',
        //         'employer',
        //         'income',
        //         'family',
        //         'location',
        //         'diet',
        //         'smoking',
        //         'drinking',
        //         'workout',
        //         'hobbies',
        //         'music',
        //         'reading',
        //         'movies',
        //         'sports',
        //         'food',
        //         'phone',
        //         'email',
        //         'shield-check',
        //         'video',
        //         'user',
        //         'preference',
        //     ]
        //     as $iconName
        // ) {
        //     $icons[$iconName] = [
        //         'purple' =>
        //         $this->assetService
        //             ->icon(
        //                 $iconName,
        //                 'purple'
        //             ),

        //         'red' =>
        //         $this->assetService
        //             ->icon(
        //                 $iconName,
        //                 'red'
        //             ),
        //     ];
        // }

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

            'remixIconFont' =>
            $remixIconFont,

            'remixIconCss' =>
            $remixIconCss,
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

            $categoryCode = strtoupper(
                trim(
                    (string) (
                        $detail['category_code']
                        ?? ''
                    )
                )
            );

            $categoryName = trim(
                (string) (
                    $detail['category_name']
                    ?? ''
                )
            );

            $value = trim(
                (string) (
                    $detail['name']
                    ?? ''
                )
            );

            if (
                $categoryCode === ''
                || $value === ''
            ) {
                continue;
            }

            $categories[$categoryCode]['name'] =
                $categoryName;

            $categories[$categoryCode]['values'][] =
                $value;
        }

        $wantedCategories = [
            [
                'label' =>
                'Hobbies & Interests',

                'matches' => [
                    'HOBB',
                    'INTEREST',
                ],

                'icon' =>
                'ri-palette-line',
            ],

            [
                'label' =>
                'Music',

                'matches' => [
                    'MUSIC',
                ],

                'icon' =>
                'ri-music-2-line',
            ],

            [
                'label' =>
                'Reading',

                'matches' => [
                    'READ',
                    'BOOK',
                ],

                'icon' =>
                'ri-book-open-line',
            ],

            [
                'label' =>
                'Movies & TV Shows',

                'matches' => [
                    'MOVIE',
                    'TV',
                    'ENTERTAIN',
                ],

                'icon' =>
                'ri-movie-2-line',
            ],

            [
                'label' =>
                'Sports & Fitness',

                'matches' => [
                    'SPORT',
                    'FIT',
                    'WORKOUT',
                ],

                'icon' =>
                'ri-run-line',
            ],

            [
                'label' =>
                'Food',

                'matches' => [
                    'FOOD',
                    'DIET',
                    'EAT',
                ],

                'icon' =>
                'ri-restaurant-line',
            ],
        ];

        $rows = [];

        foreach (
            $wantedCategories
            as $wanted
        ) {
            $values = [];

            foreach (
                $categories
                as $code => $category
            ) {
                $matched = false;

                foreach (
                    $wanted['matches']
                    as $needle
                ) {
                    if (
                        str_contains(
                            $code,
                            $needle
                        )
                    ) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    continue;
                }

                $categoryValues =
                    isset(
                        $category['values']
                    )
                    && is_array(
                        $category['values']
                    )
                    ? $category['values']
                    : [];

                foreach (
                    $categoryValues
                    as $categoryValue
                ) {
                    $categoryValue =
                        trim(
                            (string)
                            $categoryValue
                        );

                    if (
                        $categoryValue !== ''
                    ) {
                        $values[] =
                            $categoryValue;
                    }
                }
            }

            $values =
                array_values(
                    array_unique(
                        $values
                    )
                );

            $rows[] =
                $this->row(
                    (string)
                    $wanted['icon'],

                    (string)
                    $wanted['label'],

                    $values !== []
                        ? implode(
                            ', ',
                            $values
                        )
                        : 'NA'
                );
        }

        return $rows;
    }

    /**
     * @return list<array<string,string>>
     */
    private function preferences(
        int $userId
    ): array {
        $available = [];

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

        foreach (
            $basicItems
            as $item
        ) {
            $this->collectPreference(
                $available,
                $item
            );
        }

        $sections =
            $this
            ->additionalPreferenceService
            ->getSummarySections(
                $userId
            );

        foreach (
            $sections
            as $section
        ) {
            if (!is_array($section)) {
                continue;
            }

            $items =
                isset(
                    $section['items']
                )
                && is_array(
                    $section['items']
                )
                ? $section['items']
                : [];

            foreach (
                $items
                as $item
            ) {
                if (!is_array($item)) {
                    continue;
                }

                if (
                    (
                        $item['key']
                        ?? ''
                    ) ===
                    'special-request'
                ) {
                    continue;
                }

                $this->collectPreference(
                    $available,
                    $item
                );
            }
        }

        return [
            $this->preferenceRow(
                $available,
                'Age',
                [
                    'age',
                ],
                'ri-calendar-line'
            ),

            $this->preferenceRow(
                $available,
                'Marital Status',
                [
                    'marital',
                ],
                'ri-heart-2-line'
            ),

            $this->preferenceRow(
                $available,
                'Community',
                [
                    'community',
                    'caste',
                ],
                'ri-group-line'
            ),

            $this->preferenceRow(
                $available,
                'Education',
                [
                    'education',
                ],
                'ri-graduation-cap-line'
            ),

            $this->preferenceRow(
                $available,
                'Employed In',
                [
                    'employed',
                    'employment',
                ],
                'ri-building-4-line'
            ),

            $this->preferenceRow(
                $available,
                'Occupation',
                [
                    'occupation',
                    'profession',
                ],
                'ri-briefcase-4-line'
            ),
        ];
    }

    /**
     * @param array<string,array{
     *     label:string,
     *     value:string
     * }> $available
     * @param mixed $item
     */
    private function collectPreference(
        array &$available,
        mixed $item
    ): void {
        if (!is_array($item)) {
            return;
        }

        $key = strtolower(
            trim(
                (string) (
                    $item['key']
                    ?? ''
                )
            )
        );

        $label = trim(
            (string) (
                $item['label']
                ?? ''
            )
        );

        $value = trim(
            (string) (
                $item['display']
                ?? $item['value']
                ?? ''
            )
        );

        if (
            $key === ''
            && $label === ''
        ) {
            return;
        }

        $lookup =
            trim(
                $key
                    . ' '
                    . strtolower(
                        $label
                    )
            );

        $available[$lookup] = [
            'label' =>
            $label,

            'value' =>
            $value,
        ];
    }

    /**
     * @param array<string,array{
     *     label:string,
     *     value:string
     * }> $available
     * @param list<string> $needles
     *
     * @return array<string,string>
     */
    private function preferenceRow(
        array $available,
        string $label,
        array $needles,
        string $icon
    ): array {
        foreach (
            $available
            as $lookup => $item
        ) {
            foreach (
                $needles
                as $needle
            ) {
                if (
                    !str_contains(
                        $lookup,
                        strtolower(
                            $needle
                        )
                    )
                ) {
                    continue;
                }

                return $this->row(
                    $icon,
                    $label,
                    $this->displayValue(
                        $item['value']
                            ?? ''
                    )
                );
            }
        }

        return $this->row(
            $icon,
            $label,
            'NA'
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
     * Return the private S3 medium object key for the
     * member's approved primary profile photo.
     *
     * The medium derivative is used instead of the thumbnail
     * because the PDF displays the photograph at portrait size.
     */
    private function primaryProfileImageObjectKey(
        int $memberId
    ): string {
        if ($memberId <= 0) {
            return '';
        }

        $photo =
            $this->photoModel
            ->findApprovedPrimaryForMember(
                $memberId
            );

        if (!is_array($photo)) {
            return '';
        }

        return ltrim(
            trim(
                (string) (
                    $photo['medium_object_key']
                    ?? ''
                )
            ),
            '/'
        );
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

    private function displayValue(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : 'NA';
    }

    private function maskedText(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return 'NA';
        }

        $length =
            mb_strlen($value);

        if ($length <= 1) {
            return 'X';
        }

        return mb_substr(
            $value,
            0,
            1
        )
            . str_repeat(
                'X',
                max(
                    1,
                    $length - 1
                )
            );
    }
}
