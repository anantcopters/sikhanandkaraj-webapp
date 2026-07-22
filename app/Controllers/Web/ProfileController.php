<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Dashboard\MemberDashboardDataService;
use App\Services\Profile\BasicDetailsService;
use App\Validation\Profile\BasicDetailsValidation;
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

        return view(
            'Pages/Profile/Edit',
            [
                'pageTitle' => 'Complete Your Profile',

                'user' => $basicProfile['user'],

                'basicDetails' =>
                $basicProfile['basicDetails'],

                'basicDetailsCompletion' =>
                $basicProfile['completion'],

                'masterData' =>
                $basicProfile['masterData'],

                'profileCompletion' =>
                $profileCompletion,

                'profileImage' =>
                $profileImage,

                'overallProfileSummary' =>
                $overallProfileSummary,

                'validationErrors' =>
                session('validationErrors') ?? [],

                'formAlert' =>
                session('formAlert'),

                'upcomingSections' =>
                $this->upcomingProfileSections(),

                'pageScripts' => [
                    'assets/js/pages/profile-basic-details.js',
                ],
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
                ->to(route_to('web.profile.edit') . '#basic-details')
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'openProfileSection',
                    'basic-details'
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
                ->to(route_to('web.profile.edit') . '#basic-details')
                ->withInput()
                ->with(
                    'openProfileSection',
                    'basic-details'
                );

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
                ->to(route_to('web.profile.edit') . '#basic-details')
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Details not saved',
                    'message' =>
                    'We could not save your details. '
                        . 'Please try again.',
                ])
                ->with(
                    'openProfileSection',
                    'basic-details'
                );
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
                'key' => 'education-profession',
                'title' => 'Education & Profession',
                'description' =>
                'Share your education, work and career details.',
                'icon' => 'ri-graduation-cap-line',
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
}
