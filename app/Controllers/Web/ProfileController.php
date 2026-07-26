<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\BasicDetailsService;
use App\Services\Profile\EducationProfessionService;
use App\Services\Profile\ProfileCompletionService;
use App\Validation\Profile\BasicDetailsValidation;
use App\Validation\Profile\EducationProfessionValidation;
use App\Services\Profile\FamilyDetailsService;
use App\Validation\Profile\FamilyDetailsValidation;
use App\Services\Profile\SikhReligiousDetailsService;
use App\Validation\Profile\SikhReligiousDetailsValidation;
use App\Services\Profile\LifestyleService;
use App\Validation\Profile\LifestyleValidation;
use App\Services\Profile\AboutMeService;
use App\Validation\Profile\AboutMeValidation;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberPhotoService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
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
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

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

        /** @var BasicDetailsService $basicDetailsService */
        $basicDetailsService = service(
            'basicDetailsService'
        );

        $basicProfile = $basicDetailsService->getForUser(
            $userId
        );

        /** @var EducationProfessionService $educationService */
        $educationService = service(
            'educationProfessionService'
        );

        $educationProfile = $educationService->getForUser(
            $userId
        );

        /** @var ProfileCompletionService $completionService */
        $completionService = service(
            'profileCompletionService'
        );

        /** @var FamilyDetailsService $familyService */
        $familyService = service('familyDetailsService');

        $familyProfile = $familyService->getForUser(
            $userId
        );

        /** @var SikhReligiousDetailsService $religiousService */
        $religiousService = service(
            'sikhReligiousDetailsService'
        );

        $religiousProfile = $religiousService->getForUser(
            $userId
        );

        /** @var LifestyleService $lifestyleService */
        $lifestyleService = service('lifestyleService');

        $lifestyleProfile = $lifestyleService->getForUser(
            $userId
        );

        /** @var AboutMeService $aboutMeService */
        $aboutMeService = service('aboutMeService');

        $aboutMeProfile = $aboutMeService->getForUser(
            $userId
        );

        /** @var MemberPhotoService $memberPhotoService */
        $memberPhotoService = service('memberPhotoService');

        $photoSummary = $memberPhotoService->getPhotoSummary(
            $userId
        );

        /*
        * Calculate overall completion only from sections which are
        * currently implemented and usable.
        */
        $profileCompletion = $completionService->calculate(
            $basicProfile['completion'],
            $educationProfile['completion'],
            $familyProfile['completion'],
            $religiousProfile['completion'],
            $lifestyleProfile['completion'],
            $aboutMeProfile['completion'],
            $photoSummary['hasUploadedPhoto']
        );

        /** @var MemberPhotoUrlService $memberPhotoUrlService */
        $memberPhotoUrlService = service(
            'memberPhotoUrlService'
        );

        $profileImage = $memberPhotoUrlService
            ->getApprovedPrimaryUrl(
                $userId,
                'medium'
            );

        $overallProfileSummary = $this
            ->buildProfileSummary(
                $profileCompletion,
                $profileImage,
                $photoSummary
            );

        $basicDetailsComplete = (
            (int) (
                $basicProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        $educationProfessionComplete = (
            (int) (
                $educationProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        $familyDetailsComplete = (
            (int) (
                $familyProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        $sikhReligiousDetailsComplete = (
            (int) (
                $religiousProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        $lifestyleComplete = (
            (int) (
                $lifestyleProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        $aboutMeComplete = (
            (int) (
                $aboutMeProfile['completion']['percentage']
                ?? 0
            )
        ) === 100;

        /*
        * Profile photo is deliberately excluded from the guided profile
        * journey. It remains available through its independent screen.
        */
        if (!$basicDetailsComplete) {
            $nextProfileSection = [
                'title' => 'Basic Details',
                'route' => 'web.profile.basic-details',
            ];
        } elseif (!$educationProfessionComplete) {
            $nextProfileSection = [
                'title' => 'Education & Profession',
                'route' =>
                'web.profile.education-profession',
            ];
        } elseif (!$familyDetailsComplete) {
            $nextProfileSection = [
                'title' => 'Family Details',
                'route' => 'web.profile.family-details',
            ];
        } elseif (!$sikhReligiousDetailsComplete) {
            $nextProfileSection = [
                'title' => 'Sikh & Religious Details',
                'route' =>
                'web.profile.sikh-religious-details',
            ];
        } elseif (!$lifestyleComplete) {
            $nextProfileSection = [
                'title' => 'Lifestyle',
                'route' => 'web.profile.lifestyle',
            ];
        } elseif (!$aboutMeComplete) {
            $nextProfileSection = [
                'title' => 'About Me',
                'route' => 'web.profile.about-me',
            ];
        } else {
            $nextProfileSection = null;
        }

        return view(
            'Pages/Profile/Edit',
            [
                'pageTitle' => 'Complete Your Profile',

                'user' => $basicProfile['user'],

                'basicDetails' =>
                $basicProfile['basicDetails'],

                'basicDetailsCompletion' =>
                $basicProfile['completion'],

                'educationProfession' =>
                $educationProfile['educationProfession'],

                'educationProfessionCompletion' =>
                $educationProfile['completion'],

                'profileCompletion' =>
                $profileCompletion,

                'profileImage' =>
                $profileImage,

                'overallProfileSummary' =>
                $overallProfileSummary,

                'familyDetails' =>
                $familyProfile['familyDetails'],

                'familyDetailsCompletion' =>
                $familyProfile['completion'],

                'sikhReligiousDetails' =>
                $religiousProfile['sikhReligiousDetails'],

                'sikhReligiousDetailsCompletion' =>
                $religiousProfile['completion'],

                'lifestyleDetails' =>
                $lifestyleProfile['selectedDetails'],

                'lifestyleCompletion' =>
                $lifestyleProfile['completion'],

                'aboutMe' => $aboutMeProfile['aboutMe'],

                'aboutMeCompletion' =>
                $aboutMeProfile['completion'],

                'formAlert' =>
                session('formAlert'),

                'nextProfileSection' =>
                $nextProfileSection,
            ]
        );
    }

    /**
     * Build presentation-ready profile summary values.
     *
     * @param array<string, mixed> $profileCompletion
     * @param array{
     *     uploadedCount:int,
     *     approvedCount:int,
     *     hasUploadedPhoto:bool
     * } $photoSummary
     *
     * @return array<string, mixed>
     */
    private function buildProfileSummary(
        array $profileCompletion,
        string $profileImage,
        array $photoSummary
    ): array {
        $percentage = max(
            0,
            min(
                100,
                (int) (
                    $profileCompletion['percentage'] ?? 0
                )
            )
        );

        $completedSteps = max(
            0,
            (int) (
                $profileCompletion['completedSteps'] ?? 0
            )
        );

        $totalSteps = max(
            0,
            (int) (
                $profileCompletion['totalSteps'] ?? 0
            )
        );

        $pendingSections = max(
            0,
            $totalSteps - $completedSteps
        );

        if ($percentage >= 80) {
            $visibilityLabel = 'High';
            $visibilityClass = 'success';
        } elseif ($percentage >= 50) {
            $visibilityLabel = 'Medium';
            $visibilityClass = 'warning';
        } else {
            $visibilityLabel = 'Low';
            $visibilityClass = 'danger';
        }

        return [
            'percentage' => $percentage,
            'completedSteps' => $completedSteps,
            'totalSteps' => $totalSteps,
            'pendingSections' => $pendingSections,

            /*
         * Uploaded photos determine profile-journey completion.
         */
            'hasUploadedPhoto' => (
                $photoSummary['hasUploadedPhoto'] ?? false
            ) === true,

            'uploadedPhotoCount' => max(
                0,
                (int) (
                    $photoSummary['uploadedCount'] ?? 0
                )
            ),

            'approvedPhotoCount' => max(
                0,
                (int) (
                    $photoSummary['approvedCount'] ?? 0
                )
            ),

            /*
         * Only an approved primary photo may be displayed.
         */
            'hasProfilePhoto' => $profileImage !== '',
            'profilePhotoUrl' => $profileImage,

            'visibilityLabel' => $visibilityLabel,
            'visibilityClass' => $visibilityClass,
        ];
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
                session('validationErrors') ?? [],
                'formAlert' => session('formAlert'),
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
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

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
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

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
                    'web.profile.sikh-religious-details'
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
    public function sikhReligiousDetails(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var SikhReligiousDetailsService $service */
        $service = service(
            'sikhReligiousDetailsService'
        );

        $profile = $service->getForUser($userId);

        return view(
            'Pages/Profile/Sections/'
                . 'SikhReligiousDetails/Edit',
            [
                'pageTitle' =>
                'Sikh & Religious Details',

                'user' => $profile['user'],

                'sikhReligiousDetails' =>
                $profile['sikhReligiousDetails'],

                'sikhReligiousDetailsCompletion' =>
                $profile['completion'],

                'masterData' =>
                $profile['masterData'],

                'validationErrors' =>
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

                'isProfileJourney' => $this->isProfileJourney(),

                'pageScripts' => [
                    'assets/js/pages/'
                        . 'profile-sikh-religious-details.js',
                ],
            ]
        );
    }

    /**
     * Save Sikh and Religious Details.
     */
    public function updateSikhReligiousDetails(): RedirectResponse
    {
        $userId = $this->authenticatedUserId();

        $input = $this->sikhReligiousDetailsInput();

        $validation = service('validation');

        $validation->setRules(
            SikhReligiousDetailsValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.sikh-religious-details'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var SikhReligiousDetailsService $service */
            $service = service(
                'sikhReligiousDetailsService'
            );

            $service->save(
                $userId,
                $validation->getValidated()
            );

            $redirectUrl = $this->isProfileJourney()
                ? $this->profileSectionUrl(
                    'web.profile.lifestyle'
                )
                : route_to('web.profile.edit')
                . '#sikh-religious-details';

            return redirect()
                ->to($redirectUrl)
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Sikh and religious details updated',
                    'message' =>
                    'Your Sikh, birthplace and optional '
                        . 'astrological details have been saved.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.sikh-religious-details'
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
                'Sikh religious details update failed '
                    . 'for user {userId}: {message}',
                [
                    'userId' => $userId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $this->profileSectionUrl(
                        'web.profile.sikh-religious-details'
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
                session('validationErrors') ?? [],
                'formAlert' => session('formAlert'),
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
     * @return array<string, string>
     */
    private function sikhReligiousDetailsInput(): array
    {
        return [
            'community_id' => trim(
                (string) $this->request->getPost(
                    'community_id'
                )
            ),

            'subcommunity_id' => trim(
                (string) $this->request->getPost(
                    'subcommunity_id'
                )
            ),

            'birth_hour' => trim(
                (string) $this->request->getPost(
                    'birth_hour'
                )
            ),

            'birth_minute' => trim(
                (string) $this->request->getPost(
                    'birth_minute'
                )
            ),

            'birth_meridiem' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'birth_meridiem'
                    )
                )
            ),

            'birth_country_id' => trim(
                (string) $this->request->getPost(
                    'birth_country_id'
                )
            ),

            'birth_state_id' => trim(
                (string) $this->request->getPost(
                    'birth_state_id'
                )
            ),

            'birth_city_id' => trim(
                (string) $this->request->getPost(
                    'birth_city_id'
                )
            ),

            'gotra' => $this->normalizeProfileText(
                $this->request->getPost('gotra')
            ),

            'moon_sign_id' => trim(
                (string) $this->request->getPost(
                    'moon_sign_id'
                )
            ),

            'birth_star_id' => trim(
                (string) $this->request->getPost(
                    'birth_star_id'
                )
            ),

            'has_dosh' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'has_dosh'
                    )
                )
            ),
        ];
    }

    /**
     * Read and normalize expected Family Details fields.
     *
     * @return array<string, string>
     */
    private function familyDetailsInput(): array
    {
        return [
            'family_value' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'family_value'
                    )
                )
            ),

            'family_type' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'family_type'
                    )
                )
            ),

            'family_status' => strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'family_status'
                    )
                )
            ),

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

            'married_brothers_count' => trim(
                (string) $this->request->getPost(
                    'married_brothers_count'
                )
            ),

            'sisters_count' => trim(
                (string) $this->request->getPost(
                    'sisters_count'
                )
            ),

            'married_sisters_count' => trim(
                (string) $this->request->getPost(
                    'married_sisters_count'
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
