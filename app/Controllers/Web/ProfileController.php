<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\BasicDetailsService;
use App\Services\Profile\EducationProfessionService;
use App\Validation\Profile\BasicDetailsValidation;
use App\Validation\Profile\EducationProfessionValidation;
use App\Services\Profile\FamilyDetailsService;
use App\Validation\Profile\FamilyDetailsValidation;
use App\Services\Profile\LifestyleService;
use App\Validation\Profile\LifestyleValidation;
use App\Services\Profile\AboutMeService;
use App\Validation\Profile\AboutMeValidation;
use App\Services\Profile\MemberPhotoService;
use App\Services\Profile\MemberProfileSummaryService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use App\Support\BooleanValue;
use DomainException;
use Throwable;

/**
 * Displays and updates authenticated member profile sections.
 */
final class ProfileController extends BaseController
{
    /**
     * Display the Basic Details add/edit page.
     */
    public function basicDetails(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var BasicDetailsService $basicDetailsService */
        $basicDetailsService = service(
            'basicDetailsService'
        );

        $basicProfile = $basicDetailsService->getForUser(
            $userId
        );

        return view(
            'Pages/Profile/Sections/BasicDetails/Edit',
            [
                'pageTitle' => 'Basic Details',

                'user' => $basicProfile['user'],

                'basicDetails' =>
                $basicProfile['basicDetails'],

                'basicDetailsCompletion' =>
                $basicProfile['completion'],

                'masterData' =>
                $basicProfile['masterData'],

                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),

                'isProfileJourney' =>
                $this->isProfileJourney(),

                'pageScripts' => [
                    'assets/js/pages/profile-basic-details.js',
                ],
            ]
        );
    }

    /**
     * Display the member profile completion journey.
     */
    public function edit(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var MemberProfileSummaryService $profileSummaryService */
        $profileSummaryService = service(
            'memberProfileSummaryService'
        );

        $profileSummary = $profileSummaryService->getForUser(
            $userId
        );

        return view(
            'Pages/Profile/Edit',
            array_merge(
                [
                    'pageTitle' =>
                    'Complete Your Profile',

                    'formAlert' =>
                    $this->readFormAlert(),
                ],
                $profileSummary
            )
        );
    }

    /**
     * Display the authenticated member's public-profile preview.
     *
     * Initial image signing:
     *
     * - main approved photo: medium;
     * - approved gallery photos: thumbnail;
     * - original photos: not generated during initial rendering.
     */
    public function view(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var MemberProfileSummaryService $profileSummaryService */
        $profileSummaryService = service(
            'memberProfileSummaryService'
        );

        /** @var MemberPhotoUrlService $photoUrlService */
        $photoUrlService = service(
            'memberPhotoUrlService'
        );

        /*
     * MemberProfileSummaryService already requests the approved primary
     * image using the medium variant.
     */
        $profileSummary = $profileSummaryService
            ->getForUser(
                $userId
            );

        /*
     * Gallery data contains thumbnail URLs only.
     */
        $approvedPhotos = $photoUrlService
            ->getApprovedThumbnailPhotos(
                $userId
            );

        return view(
            'Pages/Profile/View',
            array_merge(
                [
                    'pageTitle' =>
                    'View Profile',

                    'approvedPhotos' =>
                    $approvedPhotos,

                    'pageScripts' => [
                        'assets/js/pages/profile-view.js',
                    ],
                ],
                $profileSummary
            )
        );
    }

    /**
     * Save the Basic Details profile section.
     */
    public function updateBasicDetails(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = $this->basicDetailsInput();

        $validation = service('validation');

        $validation->setRules(
            BasicDetailsValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.basic-details'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $validatedData = $validation->getValidated();

        try {
            /** @var BasicDetailsService $service */
            $service = service('basicDetailsService');

            $service->save(
                $userId,
                $validatedData
            );

            /*
             * The header reads this session value, so keep it current.
             */
            session()->set(
                'auth_user_name',
                $validatedData['full_name']
            );

            $redirectUrl = $this->isProfileJourney()
                ? $this->profileSectionUrl(
                    'web.profile.education-profession'
                )
                : route_to('web.profile.edit') . '#basic-details';

            return redirect()
                ->to($redirectUrl)
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Basic details updated',
                    'message' =>
                    'Your basic profile information has been saved.',
                ]);
        } catch (DomainException $exception) {
            $message = $exception->getMessage();

            $dateOfBirthErrors = [
                'Please enter a valid date of birth.',
                'The member must be at least 18 years old.',
            ];



            $redirect = redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.basic-details'
                    )
                )
                ->withInput();

            if (in_array(
                $message,
                $dateOfBirthErrors,
                true
            )) {
                return $redirect->with(
                    'validationErrors',
                    [
                        'date_of_birth' => $message,
                    ]
                );
            }

            if (
                $message ===
                'Please enter the number of children before selecting '
                . 'whether they live together.'
            ) {
                return $redirect->with(
                    'validationErrors',
                    [
                        'number_of_children' =>
                        $message,
                    ]
                );
            }

            return $redirect->with(
                'formAlert',
                [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' => $message,
                ]
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Basic profile update failed for user {userId}: '
                    . '{message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.basic-details'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' =>
                    'We could not save your details. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Display the Lifestyle add/edit page.
     */
    public function lifestyle(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var LifestyleService $service */
        $service = service('lifestyleService');

        $profile = $service->getForUser($userId);

        return view(
            'Pages/Profile/Sections/Lifestyle/Edit',
            [
                'pageTitle' => 'Lifestyle',
                'user' => $profile['user'],
                'categories' => $profile['categories'],
                'optionsByCategory' =>
                $profile['optionsByCategory'],
                'selectedOptionIds' =>
                $profile['selectedOptionIds'],
                'lifestyleCompletion' =>
                $profile['completion'],
                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),
                'isProfileJourney' => $this->isProfileJourney(),
                'pageScripts' => [
                    'assets/js/pages/profile-lifestyle.js',
                ],
            ]
        );
    }

    /**
     * Save the member's Lifestyle selections.
     */
    public function updateLifestyle(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = $this->lifestyleInput();

        $validation = service('validation');

        $validation->setRules(
            LifestyleValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.lifestyle'
                    )
                )

                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var LifestyleService $service */
            $service = service('lifestyleService');

            $service->save(
                $userId,
                $input['lifestyle_option_ids']
            );

            $redirectUrl = $this->isProfileJourney()
                ? $this->profileSectionUrl(
                    'web.profile.about-me'
                )
                : route_to('web.profile.edit')
                . '#lifestyle';


            return redirect()
                ->to($redirectUrl)
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Lifestyle updated',
                    'message' =>
                    'Your interests and lifestyle choices '
                        . 'have been saved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.lifestyle'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Lifestyle not saved',
                    'message' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Lifestyle update failed for user {userId}: '
                    . '{message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.lifestyle'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Lifestyle not saved',
                    'message' =>
                    'We could not save your lifestyle details. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Read and normalize Lifestyle option IDs.
     *
     * @return array{lifestyle_option_ids: list<int|string>}
     */
    private function lifestyleInput(): array
    {
        $submitted = $this->request->getPost(
            'lifestyle_option_ids'
        );

        return [
            'lifestyle_option_ids' =>
            is_array($submitted)
                ? array_values($submitted)
                : [],
        ];
    }

    /**
     * Determine whether the current request belongs to the guided
     * profile-completion journey.
     */
    private function isProfileJourney(): bool
    {
        return $this->request->getGet('journey') === '1';
    }

    /**
     * Build a profile-section URL while preserving journey mode.
     */
    private function profileSectionUrl(
        string $routeName
    ): string {
        $url = route_to($routeName);

        if (!$this->isProfileJourney()) {
            return $url;
        }

        return $url . '?journey=1';
    }

    /**
     * Resolve the authenticated user identifier.
     */
    private function authenticatedUserId(): int
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $userId;
    }

    /**
     * Read and normalize only expected Basic Details fields.
     *
     * @return array<string, string>
     */
    private function basicDetailsInput(): array
    {
        $birthDay = trim(
            (string) $this->request->getPost(
                'birth_day'
            )
        );

        $birthMonth = trim(
            (string) $this->request->getPost(
                'birth_month'
            )
        );

        $birthYear = trim(
            (string) $this->request->getPost(
                'birth_year'
            )
        );

        $dateOfBirth = '';

        if (
            ctype_digit($birthDay)
            && ctype_digit($birthMonth)
            && ctype_digit($birthYear)
        ) {
            $dateOfBirth = sprintf(
                '%04d-%02d-%02d',
                (int) $birthYear,
                (int) $birthMonth,
                (int) $birthDay
            );
        }

        return [
            'full_name' => preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request->getPost(
                        'full_name'
                    )
                )
            ) ?? '',

            'date_of_birth' => $dateOfBirth,

            'marital_status_id' => trim(
                (string) $this->request->getPost(
                    'marital_status_id'
                )
            ),

            'height_id' => trim(
                (string) $this->request->getPost(
                    'height_id'
                )
            ),

            'mother_tongue_id' => trim(
                (string) $this->request->getPost(
                    'mother_tongue_id'
                )
            ),

            'drinking_habit_id' => trim(
                (string) $this->request->getPost(
                    'drinking_habit_id'
                )
            ),

            'eating_habit_id' => trim(
                (string) $this->request->getPost(
                    'eating_habit_id'
                )
            ),

            'physical_status_id' => trim(
                (string) $this->request->getPost(
                    'physical_status_id'
                )
            ),

            'number_of_children' => trim(
                (string) $this->request->getPost(
                    'number_of_children'
                )
            ),

            'children_living_together' => trim(
                (string) $this->request->getPost(
                    'children_living_together'
                )
            ),

            'country_id' => trim(
                (string) $this->request->getPost(
                    'country_id'
                )
            ),

            'state_id' => trim(
                (string) $this->request->getPost(
                    'state_id'
                )
            ),

            'city_id' => trim(
                (string) $this->request->getPost(
                    'city_id'
                )
            ),
        ];
    }

    /**
     * Display the Education & Profession add/edit page.
     */
    public function educationProfession(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var EducationProfessionService $service */
        $service = service(
            'educationProfessionService'
        );

        $profile = $service->getForUser($userId);

        return view(
            'Pages/Profile/Sections/EducationProfession/Edit',
            [
                'pageTitle' => 'Education & Profession',

                'user' => $profile['user'],

                'educationProfession' =>
                $profile['educationProfession'],

                'educationProfessionCompletion' =>
                $profile['completion'],

                'masterData' =>
                $profile['masterData'],

                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),

                'isProfileJourney' => $this->isProfileJourney(),

                'pageScripts' => [
                    'assets/js/pages/profile-education-profession.js',
                ],
            ]
        );
    }

    /**
     * Save the Education & Profession profile section.
     */
    public function updateEducationProfession(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = $this->educationProfessionInput();

        $validation = service('validation');

        $validation->setRules(
            EducationProfessionValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.education-profession'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $validatedData = $validation->getValidated();

        try {
            /** @var EducationProfessionService $service */
            $service = service(
                'educationProfessionService'
            );

            $service->save(
                $userId,
                $validatedData
            );

            $redirectUrl = $this->isProfileJourney()
                ? $this->profileSectionUrl(
                    'web.profile.family-details'
                )
                : route_to('web.profile.edit')
                . '#education-profession';

            return redirect()
                ->to($redirectUrl)
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Education and profession updated',
                    'message' =>
                    'Your education and professional '
                        . 'information has been saved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.education-profession'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Education and profession update failed '
                    . 'for user {userId}: {message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.education-profession'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' =>
                    'We could not save your details. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Read and normalize expected Education & Profession fields.
     *
     * @return array<string, string>
     */
    private function educationProfessionInput(): array
    {
        return [
            'highest_education_id' => trim(
                (string) $this->request->getPost(
                    'highest_education_id'
                )
            ),

            'education_detail' => $this->normalizeProfileText(
                $this->request->getPost(
                    'education_detail'
                )
            ),

            'college_institution' => $this->normalizeProfileText(
                $this->request->getPost(
                    'college_institution'
                )
            ),

            'employed_in' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'employed_in'
                    )
                )
            ),

            'occupation_id' => trim(
                (string) $this->request->getPost(
                    'occupation_id'
                )
            ),

            'occupation_detail' => $this->normalizeProfileText(
                $this->request->getPost(
                    'occupation_detail'
                )
            ),

            'organization' => $this->normalizeProfileText(
                $this->request->getPost(
                    'organization'
                )
            ),

            'annual_income_id' => trim(
                (string) $this->request->getPost(
                    'annual_income_id'
                )
            ),
        ];
    }

    /**
     * Display the Family Details add/edit page.
     */
    public function familyDetails(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var FamilyDetailsService $service */
        $service = service('familyDetailsService');

        $profile = $service->getForUser($userId);

        return view(
            'Pages/Profile/Sections/FamilyDetails/Edit',
            [
                'pageTitle' => 'Family Details',

                'user' => $profile['user'],

                'familyDetails' =>
                $profile['familyDetails'],

                'familyDetailsCompletion' =>
                $profile['completion'],

                'masterData' =>
                $profile['masterData'],

                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),

                'isProfileJourney' => $this->isProfileJourney(),

                'pageScripts' => [
                    'assets/js/pages/profile-family-details.js',
                ],
            ]
        );
    }

    /**
     * Save the Family Details profile section.
     */
    public function updateFamilyDetails(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = $this->familyDetailsInput();

        $validation = service('validation');

        $validation->setRules(
            FamilyDetailsValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.family-details'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $validatedData = $validation->getValidated();

        try {
            /** @var FamilyDetailsService $service */
            $service = service('familyDetailsService');

            $service->save(
                $userId,
                $validatedData
            );

            $redirectUrl = $this->isProfileJourney()
                ? $this->profileSectionUrl(
                    'web.profile.lifestyle'
                )
                : route_to('web.profile.edit')
                . '#family-details';

            return redirect()
                ->to($redirectUrl)
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Family details updated',
                    'message' =>
                    'Your family information has been saved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.family-details'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Family details update failed for user '
                    . '{userId}: {message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.family-details'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' =>
                    'We could not save your family details. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Display Sikh and Religious Details.
     */
    // public function sikhReligiousDetails(): string
    // {
    //     $userId = $this->authenticatedUserId();

    //     /** @var SikhReligiousDetailsService $service */
    //     $service = service(
    //         'sikhReligiousDetailsService'
    //     );

    //     $profile = $service->getForUser($userId);

    //     return view(
    //         'Pages/Profile/Sections/'
    //             . 'SikhReligiousDetails/Edit',
    //         [
    //             'pageTitle' =>
    //             'Sikh & Religious Details',

    //             'user' => $profile['user'],

    //             'sikhReligiousDetails' =>
    //             $profile['sikhReligiousDetails'],

    //             'sikhReligiousDetailsCompletion' =>
    //             $profile['completion'],

    //             'masterData' =>
    //             $profile['masterData'],

    //             'validationErrors' =>
    //             $this->readValidationErrors() ?? [],

    //             'formAlert' =>
    //             $this->readFormAlert(),

    //             'isProfileJourney' => $this->isProfileJourney(),

    //             'pageScripts' => [
    //                 'assets/js/pages/'
    //                     . 'profile-sikh-religious-details.js',
    //             ],
    //         ]
    //     );
    // }

    /**
     * Save Sikh and Religious Details.
     */
    // public function updateSikhReligiousDetails(): RedirectResponse
    // {
    //     $userId = $this->authenticatedUserId();

    //     $input = $this->sikhReligiousDetailsInput();

    //     $validation = service('validation');

    //     $validation->setRules(
    //         SikhReligiousDetailsValidation::rules()
    //     );

    //     if (!$validation->run($input)) {
    //         return redirect()
    //             ->to(
    //                 $this->profileSectionUrl(
    //                     'web.profile.sikh-religious-details'
    //                 )
    //             )
    //             ->withInput()
    //             ->with(
    //                 'validationErrors',
    //                 $validation->getErrors()
    //             );
    //     }

    //     try {
    //         /** @var SikhReligiousDetailsService $service */
    //         $service = service(
    //             'sikhReligiousDetailsService'
    //         );

    //         $service->save(
    //             $userId,
    //             $validation->getValidated()
    //         );

    //         $redirectUrl = $this->isProfileJourney()
    //             ? $this->profileSectionUrl(
    //                 'web.profile.lifestyle'
    //             )
    //             : route_to('web.profile.edit')
    //             . '#sikh-religious-details';

    //         return redirect()
    //             ->to($redirectUrl)
    //             ->with('formAlert', [
    //                 'type' => 'success',
    //                 'title' =>
    //                 'Sikh and religious details updated',
    //                 'message' =>
    //                 'Your Sikh, birthplace and optional '
    //                     . 'astrological details have been saved.',
    //             ]);
    //     } catch (DomainException $exception) {
    //         return redirect()
    //             ->to(
    //                 $this->profileSectionUrl(
    //                     'web.profile.sikh-religious-details'
    //                 )
    //             )
    //             ->withInput()
    //             ->with('formAlert', [
    //                 'type' => 'danger',
    //                 'title' => 'Details not saved',
    //                 'message' => $exception->getMessage(),
    //             ]);
    //     } catch (Throwable $exception) {
    //         log_message(
    //             'error',
    //             'Sikh religious details update failed '
    //                 . 'for user {userId}: {message}',
    //             [
    //                 'userId' => $userId,
    //                 'message' => $exception->getMessage(),
    //             ]
    //         );

    //         return redirect()
    //             ->to(
    //                 $this->profileSectionUrl(
    //                     'web.profile.sikh-religious-details'
    //                 )
    //             )
    //             ->withInput()
    //             ->with('formAlert', [
    //                 'type' => 'danger',
    //                 'title' => 'Details not saved',
    //                 'message' =>
    //                 'We could not save your details. '
    //                     . 'Please try again.',
    //             ]);
    //     }
    // }

    /**
     * Display the About Me add/edit page.
     */
    public function aboutMe(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var AboutMeService $service */
        $service = service('aboutMeService');

        $profile = $service->getForUser($userId);

        return view(
            'Pages/Profile/Sections/AboutMe/Edit',
            [
                'pageTitle' => 'About Me',
                'user' => $profile['user'],
                'aboutMe' => $profile['aboutMe'],
                'aboutMeCompletion' =>
                $profile['completion'],
                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),
                'isProfileJourney' => $this->isProfileJourney(),
                'pageScripts' => [
                    'assets/js/pages/profile-about-me.js',
                ],
            ]
        );
    }

    /**
     * Save the member's About Me introduction.
     */
    public function updateAboutMe(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = [
            'about_me' => trim(
                (string) $this->request->getPost('about_me')
            ),
        ];

        $validation = service('validation');

        $validation->setRules(
            AboutMeValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.about-me'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AboutMeService $service */
            $service = service('aboutMeService');

            $service->save(
                $userId,
                (string) $input['about_me']
            );

            return redirect()
                ->to(
                    route_to('web.profile.edit')
                        . '#about-me'
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'About Me updated',
                    'message' =>
                    'Your profile introduction has been saved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.about-me'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'About Me not saved',
                    'message' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'About Me update failed for user {userId}: '
                    . '{message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.about-me'
                    )
                )
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'About Me not saved',
                    'message' =>
                    'We could not save your introduction. '
                        . 'Please try again.',
                ]);
        }
    }

    /**
     * Read and normalize expected Family Details fields.
     *
     * @return array<string, string>
     */
    private function familyDetailsInput(): array
    {
        return [
            'family_value_id' => trim(
                (string) $this->request->getPost(
                    'family_value_id'
                )
            ),

            'family_type_id' => trim(
                (string) $this->request->getPost(
                    'family_type_id'
                )
            ),

            'family_status_id' => trim(
                (string) $this->request->getPost(
                    'family_status_id'
                )
            ),

            'community_id' => trim(
                (string) $this->request->getPost(
                    'community_id'
                )
            ),

            'gotra' => $this->normalizeProfileText(
                $this->request->getPost(
                    'gotra'
                )
            ),

            'father_name' => $this->normalizeProfileText(
                $this->request->getPost(
                    'father_name'
                )
            ),

            'mother_name' => $this->normalizeProfileText(
                $this->request->getPost(
                    'mother_name'
                )
            ),

            'parent_contact_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'parent_contact_number'
                    )
            ) ?? '',

            'father_occupation_id' => trim(
                (string) $this->request->getPost(
                    'father_occupation_id'
                )
            ),

            'mother_occupation_id' => trim(
                (string) $this->request->getPost(
                    'mother_occupation_id'
                )
            ),

            'brothers_count' => trim(
                (string) $this->request->getPost(
                    'brothers_count'
                )
            ),

            'sisters_count' => trim(
                (string) $this->request->getPost(
                    'sisters_count'
                )
            ),

            'country_id' => trim(
                (string) $this->request->getPost(
                    'country_id'
                )
            ),

            'state_id' => trim(
                (string) $this->request->getPost(
                    'state_id'
                )
            ),

            'city_id' => trim(
                (string) $this->request->getPost(
                    'city_id'
                )
            ),

            'nearest_gurudwara' =>
            $this->normalizeProfileText(
                $this->request->getPost(
                    'nearest_gurudwara'
                )
            ),

            'reference_person_1' =>
            $this->normalizeProfileText(
                $this->request->getPost(
                    'reference_person_1'
                )
            ),

            'reference_person_2' =>
            $this->normalizeProfileText(
                $this->request->getPost(
                    'reference_person_2'
                )
            ),
        ];
    }

    /**
     * Normalize profile text while preserving safe readable spacing.
     */
    private function normalizeProfileText(mixed $value): string
    {
        return preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $value)
        ) ?? '';
    }
}
