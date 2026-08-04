<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Support\PartnerPreference\BasicPreferenceItem;
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
     * Display the Partner Preference overview page.
     */
    public function index(): string
    {
        $userId = $this->authenticatedUserId();

        /** @var BasicPartnerPreferenceService $service */
        $service = service(
            'basicPartnerPreferenceService'
        );

        return view(
            'Pages/PartnerPreference/Index',
            array_merge(
                [
                    'pageTitle' =>
                    'Partner Preference',

                    'formAlert' =>
                    $this->readFormAlert(),
                ],
                $service->getSummaryForUser($userId)
            )
        );
    }

    /**
     * Display one Basic Partner Preference item.
     */
    public function editBasicItem(
        string $item
    ): string {
        $this->assertValidItem($item);

        $userId = $this->authenticatedUserId();

        /** @var BasicPartnerPreferenceService $service */
        $service = service(
            'basicPartnerPreferenceService'
        );

        return view(
            'Pages/PartnerPreference/Basic/Edit',
            array_merge(
                [
                    'pageTitle' =>
                    BasicPreferenceItem::title($item),

                    'validationErrors' =>
                    $this->readValidationErrors() ?? [],

                    'formAlert' =>
                    $this->readFormAlert(),

                    'pageScripts' => [
                        'assets/js/pages/'
                            . 'partner-preference-basic.js',
                    ],
                ],
                $service->getItemForUser(
                    $userId,
                    $item
                )
            )
        );
    }

    /**
     * Save one Basic Partner Preference item.
     */
    public function updateBasicItem(
        string $item
    ): RedirectResponse {
        $this->assertValidItem($item);

        $input = $this->inputForItem($item);

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

            return redirect()
                ->to(
                    route_to(
                        'web.partner-preference'
                    )
                        . '#basic'
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'success',
                        'title' =>
                        BasicPreferenceItem::title(
                            $item
                        )
                            . ' updated',

                        'message' =>
                        'Your partner preference has '
                            . 'been saved.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.partner-preference.basic.edit',
                        $item
                    )
                )
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
        } catch (Throwable $exception) {
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
                ->to(
                    route_to(
                        'web.partner-preference.basic.edit',
                        $item
                    )
                )
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

    /**
     * Read and normalize only fields expected for the selected item.
     *
     * @return array<string, mixed>
     */
    private function inputForItem(string $item): array
    {
        $isCompulsory = $this->request->getPost(
            'is_compulsory'
        ) === '1'
            ? '1'
            : '0';

        return match ($item) {
            BasicPreferenceItem::AGE => [
                'age_from' => trim(
                    (string) $this->request->getPost(
                        'age_from'
                    )
                ),
                'age_to' => trim(
                    (string) $this->request->getPost(
                        'age_to'
                    )
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::HEIGHT => [
                'height_from_id' => trim(
                    (string) $this->request->getPost(
                        'height_from_id'
                    )
                ),
                'height_to_id' => trim(
                    (string) $this->request->getPost(
                        'height_to_id'
                    )
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::MARITAL_STATUS => [
                'marital_status_id' => trim(
                    (string) $this->request->getPost(
                        'marital_status_id'
                    )
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::HAVE_CHILDREN => [
                'have_children' => trim(
                    (string) $this->request->getPost(
                        'have_children'
                    )
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::MOTHER_TONGUE => [
                'mother_tongue_ids' =>
                $this->arrayInput(
                    'mother_tongue_ids'
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::PHYSICAL_STATUS => [
                'physical_status_id' => trim(
                    (string) $this->request->getPost(
                        'physical_status_id'
                    )
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::EATING_HABITS => [
                'eating_habit_ids' =>
                $this->arrayInput(
                    'eating_habit_ids'
                ),
                'is_compulsory' => $isCompulsory,
            ],

            BasicPreferenceItem::DRINKING_HABITS => [
                'drinking_habit_ids' =>
                $this->arrayInput(
                    'drinking_habit_ids'
                ),
                'is_compulsory' => $isCompulsory,
            ],

            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function arrayInput(string $field): array
    {
        $value = $this->request->getPost($field);

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

    private function assertValidItem(string $item): void
    {
        if (!BasicPreferenceItem::isValid($item)) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    private function authenticatedUserId(): int
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $userId;
    }
}
