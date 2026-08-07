<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Support\PartnerPreference\AdditionalPreferenceItem;
use App\Support\PartnerPreference\BasicPreferenceItem;
use App\Validation\PartnerPreference\AdditionalPartnerPreferenceValidation;
use App\Validation\PartnerPreference\BasicPartnerPreferenceValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Displays and saves authenticated member partner preferences.
 */
final class PartnerPreferenceController extends BaseController
{
    /**
     * Display the two-column Partner Preference overview.
     */
    public function index(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var BasicPartnerPreferenceService $basicService */
        $basicService = service(
            'basicPartnerPreferenceService'
        );

        /** @var AdditionalPartnerPreferenceService $additionalService */
        $additionalService = service(
            'additionalPartnerPreferenceService'
        );

        $basicSummary = $basicService
            ->getSummaryForUser($userId);

        $basicSection = $basicSummary['sections'][0] ?? null;

        $sections = [];

        if (is_array($basicSection)) {
            $sections[] = $basicSection;
        }

        $sections = array_merge(
            $sections,
            $additionalService
                ->getSummarySections($userId)
        );

        return view(
            'Pages/PartnerPreference/Index',
            [
                'pageTitle' =>
                'Partner Preference',

                'formAlert' =>
                $this->readFormAlert(),

                'sections' => $sections,
            ]
        );
    }

    /**
     * Display one Basic item.
     */
    public function editBasicItem(
        string $item
    ): string {
        if (!BasicPreferenceItem::isValid($item)) {
            throw PageNotFoundException::forPageNotFound();
        }

        /** @var BasicPartnerPreferenceService $service */
        $service = service(
            'basicPartnerPreferenceService'
        );

        return view(
            'Pages/PartnerPreference/Basic/Edit',
            array_merge(
                [
                    'pageTitle' =>
                    BasicPreferenceItem::title(
                        $item
                    ),

                    'validationErrors' =>
                    $this->readValidationErrors()
                        ?? [],

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/pages/'
                            . 'partner-preference-basic.js',
                    ],
                ],
                $service->getItemForUser(
                    $this->authenticatedUserId(),
                    $item
                )
            )
        );
    }

    /**
     * Save one Basic item.
     */
    public function updateBasicItem(
        string $item
    ): RedirectResponse {
        if (!BasicPreferenceItem::isValid($item)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $input = $this->basicInput($item);

        $validation = service('validation');

        $validation->setRules(
            BasicPartnerPreferenceValidation::rules(
                $item
            )
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.partner-preference.basic.edit',
                        $item
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var BasicPartnerPreferenceService $service */
            $service = service(
                'basicPartnerPreferenceService'
            );

            $service->saveItem(
                $this->authenticatedUserId(),
                $item,
                $validation->getValidated()
            );

            return $this->successRedirect(
                'basic',
                BasicPreferenceItem::title($item)
            );
        } catch (DomainException $exception) {
            return $this->domainFailureRedirect(
                route_to(
                    'web.partner-preference.basic.edit',
                    $item
                ),
                $exception
            );
        } catch (Throwable $exception) {
            return $this->unexpectedFailureRedirect(
                route_to(
                    'web.partner-preference.basic.edit',
                    $item
                ),
                $item,
                $exception
            );
        }
    }

    /**
     * Display one remaining preference item.
     */
    public function editItem(
        string $item
    ): string {
        if (
            !AdditionalPreferenceItem::isValid(
                $item
            )
        ) {
            throw PageNotFoundException::forPageNotFound();
        }

        /** @var AdditionalPartnerPreferenceService $service */
        $service = service(
            'additionalPartnerPreferenceService'
        );

        return view(
            'Pages/PartnerPreference/Additional/Edit',
            array_merge(
                [
                    'pageTitle' =>
                    AdditionalPreferenceItem::title(
                        $item
                    ),

                    'validationErrors' =>
                    $this->readValidationErrors()
                        ?? [],

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/pages/'
                            . 'partner-preference-additional.js',
                    ],
                ],
                $service->getItemForUser(
                    $this->authenticatedUserId(),
                    $item
                )
            )
        );
    }

    /**
     * Save one remaining preference item.
     */
    public function updateItem(
        string $item
    ): RedirectResponse {
        if (
            !AdditionalPreferenceItem::isValid(
                $item
            )
        ) {
            throw PageNotFoundException::forPageNotFound();
        }

        $input = $this->additionalInput($item);

        $validation = service('validation');

        $validation->setRules(
            AdditionalPartnerPreferenceValidation::rules(
                $item
            )
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.partner-preference.item.edit',
                        $item
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AdditionalPartnerPreferenceService $service */
            $service = service(
                'additionalPartnerPreferenceService'
            );

            $service->saveItem(
                $this->authenticatedUserId(),
                $item,
                $validation->getValidated()
            );

            return $this->successRedirect(
                AdditionalPreferenceItem::section(
                    $item
                ),
                AdditionalPreferenceItem::title(
                    $item
                )
            );
        } catch (DomainException $exception) {
            return $this->domainFailureRedirect(
                route_to(
                    'web.partner-preference.item.edit',
                    $item
                ),
                $exception
            );
        } catch (Throwable $exception) {
            return $this->unexpectedFailureRedirect(
                route_to(
                    'web.partner-preference.item.edit',
                    $item
                ),
                $item,
                $exception
            );
        }
    }

    /**
     * Return active cities for one or more selected states.
     */
    public function cities()
    {
        $stateIds = $this->request
            ->getGet('state_ids');

        if (is_string($stateIds)) {
            $stateIds = explode(
                ',',
                $stateIds
            );
        }

        if (!is_array($stateIds)) {
            $stateIds = [];
        }

        $normalizedStateIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static function (
                            mixed $stateId
                        ): int {
                            return (int) trim(
                                (string) $stateId
                            );
                        },
                        $stateIds
                    ),
                    static fn(int $stateId): bool =>
                    $stateId > 0
                )
            )
        );

        if ($normalizedStateIds === []) {
            return $this->response->setJSON([
                'data' => [],
            ]);
        }

        $cities = service(
            'profileMasterDataService'
        )->citiesForStates(
            $normalizedStateIds
        );

        return $this->response->setJSON([
            'data' => array_map(
                static fn(array $city): array => [
                    'value' =>
                    (string) $city['id'],

                    'label' =>
                    (string) $city['name'],

                    'stateId' =>
                    (string) $city['state_id'],
                ],
                $cities
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function additionalInput(
        string $item
    ): array {
        $strictMode =
            $this->request->getPost(
                'is_compulsory'
            ) === '1'
            ? '1'
            : '0';

        return match ($item) {
            AdditionalPreferenceItem::COMMUNITY => [
                'community_ids' =>
                $this->arrayInput(
                    'community_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::EDUCATION => [
                'education_ids' =>
                $this->arrayInput(
                    'education_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::EMPLOYED_IN => [
                'employed_in_values' =>
                $this->arrayInput(
                    'employed_in_values'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::OCCUPATION => [
                'occupation_ids' =>
                $this->arrayInput(
                    'occupation_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::ANNUAL_INCOME => [
                'annual_income_ids' =>
                $this->arrayInput(
                    'annual_income_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::LOCATION => [
                'state_ids' =>
                $this->arrayInput(
                    'state_ids'
                ),

                'city_ids' =>
                $this->arrayInput(
                    'city_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            AdditionalPreferenceItem::SPECIAL_REQUEST => [
                'request_text' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'request_text'
                        )
                ),
            ],

            default => [],
        };
    }

    /**
     * Keep the existing Basic input implementation from the branch.
     *
     * @return array<string, mixed>
     */
    private function basicInput(
        string $item
    ): array {
        $strictMode =
            $this->request->getPost(
                'is_compulsory'
            ) === '1'
            ? '1'
            : '0';

        return match ($item) {
            BasicPreferenceItem::AGE => [
                'age_from' =>
                trim(
                    (string) $this->request
                        ->getPost('age_from')
                ),

                'age_to' =>
                trim(
                    (string) $this->request
                        ->getPost('age_to')
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::HEIGHT => [
                'height_from_id' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'height_from_id'
                        )
                ),

                'height_to_id' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'height_to_id'
                        )
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::MARITAL_STATUS => [
                'marital_status_id' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'marital_status_id'
                        )
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::HAVE_CHILDREN => [
                'have_children' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'have_children'
                        )
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::MOTHER_TONGUE => [
                'mother_tongue_ids' =>
                $this->arrayInput(
                    'mother_tongue_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::PHYSICAL_STATUS => [
                'physical_status_id' =>
                trim(
                    (string) $this->request
                        ->getPost(
                            'physical_status_id'
                        )
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::EATING_HABITS => [
                'eating_habit_ids' =>
                $this->arrayInput(
                    'eating_habit_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            BasicPreferenceItem::DRINKING_HABITS => [
                'drinking_habit_ids' =>
                $this->arrayInput(
                    'drinking_habit_ids'
                ),

                'is_compulsory' =>
                $strictMode,
            ],

            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function arrayInput(
        string $field
    ): array {
        $value = $this->request
            ->getPost($field);

        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_map(
                static fn(mixed $item): string =>
                trim((string) $item),
                $value
            )
        );
    }

    private function successRedirect(
        string $section,
        string $title
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'web.partner-preference'
                )
                    . '#'
                    . $section
            )
            ->with(
                'formAlert',
                [
                    'type' => 'success',
                    'title' =>
                    $title . ' updated',

                    'message' =>
                    'Your partner preference has been saved.',
                ]
            );
    }

    private function domainFailureRedirect(
        string $url,
        DomainException $exception
    ): RedirectResponse {
        return redirect()
            ->to($url)
            ->withInput()
            ->with(
                'formAlert',
                [
                    'type' => 'danger',
                    'title' =>
                    'Preference not saved',

                    'message' =>
                    $exception->getMessage(),
                ]
            );
    }

    private function unexpectedFailureRedirect(
        string $url,
        string $item,
        Throwable $exception
    ): RedirectResponse {
        log_message(
            'error',
            'Partner preference update failed for '
                . 'user {userId}, item {item}: {message}',
            [
                'userId' =>
                $this->authenticatedUserId(),

                'item' => $item,

                'message' =>
                $exception->getMessage(),
            ]
        );

        return redirect()
            ->to($url)
            ->withInput()
            ->with(
                'formAlert',
                [
                    'type' => 'danger',
                    'title' =>
                    'Preference not saved',

                    'message' =>
                    'We could not save your partner '
                        . 'preference. Please try again.',
                ]
            );
    }
}
