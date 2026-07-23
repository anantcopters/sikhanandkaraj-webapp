<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Dashboard\MemberDashboardDataService;
use App\Services\Profile\BasicDetailsService;
use App\Validation\Profile\BasicDetailsValidation;
use App\Services\Profile\EducationProfessionService;
use App\Validation\Profile\EducationProfessionValidation;
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

        /*
         * Reuse the existing dashboard service instead of creating
         * another profile-completion calculation.
         */
        $dashboardData = (
            new MemberDashboardDataService()
        )->getDashboardData($userId);

        $profileCompletion = is_array(
            $dashboardData['profileCompletion'] ?? null
        )
            ? $dashboardData['profileCompletion']
            : [
                'percentage' => 0,
                'completedSteps' => 0,
                'totalSteps' => 0,
            ];

        $profileImage = trim(
            (string) (
                $dashboardData['profileImage'] ?? ''
            )
        );

        $overallProfileSummary = $this
            ->buildProfileSummary(
                $profileCompletion,
                $profileImage
            );

        /**
         * Next profile section.
         *
         * Until additional sections are implemented,
         * continue profile always opens Basic Details.
         */
        $basicDetailsComplete = (
            (int) (
                $basicProfile['completion']['percentage'] ?? 0
            )
        ) === 100;

        $educationProfessionComplete = (
            (int) (
                $educationProfile['completion']['percentage'] ?? 0
            )
        ) === 100;

        if (!$basicDetailsComplete) {
            $nextProfileSection = [
                'title' => 'Basic Details',
                'route' => 'web.profile.basic-details',
            ];
        } elseif (!$educationProfessionComplete) {
            $nextProfileSection = [
                'title' => 'Education & Profession',
                'route' => 'web.profile.education-profession',
            ];
        } else {
            /*
     * Point this to the next implemented profile section later.
     */
            $nextProfileSection = [
                'title' => 'Education & Profession',
                'route' => 'web.profile.education-profession',
            ];
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

                'profileCompletion' =>
                $profileCompletion,

                'educationProfession' =>
                $educationProfile['educationProfession'],

                'educationProfessionCompletion' =>
                $educationProfile['completion'],

                'profileImage' =>
                $profileImage,

                'overallProfileSummary' =>
                $overallProfileSummary,

                'formAlert' =>
                session('formAlert'),

                'upcomingSections' =>
                $this->upcomingProfileSections(),

                'nextProfileSection' => $nextProfileSection,
            ]
        );
    }

    /**
     * Build presentation-ready profile summary values.
     *
     * @param array<string, mixed> $profileCompletion
     *
     * @return array<string, mixed>
     */
    private function buildProfileSummary(
        array $profileCompletion,
        string $profileImage
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
            'hasProfilePhoto' => $profileImage !== '',
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
                ->to(route_to('web.profile.basic-details'))
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

            return redirect()
                ->to(route_to('web.profile.edit') . '#basic-details')
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
                ->to(route_to('web.profile.basic-details'))
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
                ->to(route_to('web.profile.basic-details'))
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
     * Return profile modules that are not yet available.
     *
     * @return array<int, array<string, string>>
     */
    private function upcomingProfileSections(): array
    {
        return [
            [
                'key' => 'photos',
                'title' => 'Profile Photos',
                'description' =>
                'Add and manage photos visible to other members.',
                'icon' => 'ri-image-line',
            ],
            [
                'key' => 'family',
                'title' => 'Family Details',
                'description' =>
                'Tell members about your family background.',
                'icon' => 'ri-group-line',
            ],
            [
                'key' => 'sikh-details',
                'title' => 'Sikh & Religious Details',
                'description' =>
                'Add Sikh practices and religious preferences.',
                'icon' => 'ri-community-line',
            ],
            [
                'key' => 'lifestyle',
                'title' => 'Lifestyle',
                'description' =>
                'Share habits, interests and lifestyle choices.',
                'icon' => 'ri-leaf-line',
            ],
            [
                'key' => 'about-me',
                'title' => 'About Me',
                'description' =>
                'Introduce yourself in your own words.',
                'icon' => 'ri-file-user-line',
            ],
            [
                'key' => 'partner-preferences',
                'title' => 'Partner Preferences',
                'description' =>
                'Describe the partner you are looking for.',
                'icon' => 'ri-heart-search-line',
            ],
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
                    route_to(
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

            return redirect()
                ->to(
                    route_to('web.profile.edit')
                        . '#education-profession'
                )
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
                    route_to(
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
                    route_to(
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
