<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Matchmaking\MatchScoreConfigurationService;
use App\Support\AdminErrorContext;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

/**
 * Super Admin Match Score configuration.
 *
 * Route protection is enforced by the existing superAdmin filter.
 */
final class MatchScoreConfigurationController extends BaseController
{
    /**
     * Display current Match Score weights and immutable history.
     */
    public function index(): string
    {
        /** @var MatchScoreConfigurationService $service */
        $service =
            service(
                'matchScoreConfigurationService'
            );

        return view(
            'Admin/MatchScore/Index',
            [
                'pageTitle' =>
                'Match Score Configuration',

                'configuration' =>
                $service
                    ->activeConfiguration(),

                'configurationHistory' =>
                $service->history(
                    25
                ),

                'maximumCommercialWeight' =>
                MatchScoreConfigurationService
                ::MAX_COMMERCIAL_WEIGHT,

                'validationErrors' =>
                session(
                    'validationErrors'
                ) ?? [],

                'formAlert' =>
                session(
                    'formAlert'
                ),

                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/admin-match-score.js',
                ],
            ]
        );
    }

    /**
     * Replace the active configuration.
     *
     * Both client and server validate:
     *
     * - whole numbers only;
     * - every value between 0 and 100;
     * - commercial <= configured maximum;
     * - total exactly 100.
     */
    public function update(): RedirectResponse
    {
        $input = [
            'preference' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'preference'
                    )
            ),

            'profileCompletion' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'profileCompletion'
                    )
            ),

            'approvedPhotos' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'approvedPhotos'
                    )
            ),

            'trust' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'trust'
                    )
            ),

            'commercial' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'commercial'
                    )
            ),
        ];

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            [
                'preference' => [
                    'label' =>
                    'Partner Preference',

                    'rules' =>
                    'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                ],

                'profileCompletion' => [
                    'label' =>
                    'Profile Completion',

                    'rules' =>
                    'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                ],

                'approvedPhotos' => [
                    'label' =>
                    'Approved Photos',

                    'rules' =>
                    'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                ],

                'trust' => [
                    'label' =>
                    'Trust & Verification',

                    'rules' =>
                    'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                ],

                'commercial' => [
                    'label' =>
                    'Membership Priority',

                    'rules' =>
                    'required|integer|greater_than_equal_to[0]|less_than_equal_to['
                        . MatchScoreConfigurationService
                        ::MAX_COMMERCIAL_WEIGHT
                        . ']',
                ],
            ]
        );

        if (!$validation->run($input)) {
            return $this->validationRedirect(
                $input,
                $validation->getErrors()
            );
        }

        /*
         * Total validation is cross-field and therefore belongs here rather
         * than inventing a second custom validation rule.
         */
        $total =
            array_sum(
                array_map(
                    'intval',
                    $input
                )
            );

        if ($total !== 100) {
            return $this->validationRedirect(
                $input,
                [
                    'total' =>
                    'The Match Score weights must total exactly 100%.',
                ]
            );
        }

        $adminId =
            (int) session(
                'admin_user_id'
            );

        try {
            /** @var MatchScoreConfigurationService $service */
            $service =
                service(
                    'matchScoreConfigurationService'
                );

            $before =
                $service->weights();

            $saved =
                $service
                ->replaceActiveConfiguration(
                    $input,
                    $adminId
                );

            /*
             * Reuse the existing centralized Admin audit architecture.
             */
            service(
                'adminAuditService'
            )->record(
                new AdminAuditEvent(
                    action: 'MATCH_SCORE_CONFIGURATION_CHANGED',

                    outcome: 'SUCCESS',

                    targetType: 'MATCH_SCORE_CONFIGURATION',

                    targetId: (int) (
                        $saved['id']
                        ?? 0
                    ),

                    targetLabel: 'Global Match Score weights',

                    description: 'Super Administrator changed the global Match Score weights.',

                    beforeData: $before,

                    afterData: [
                        'preference' =>
                        $saved['preference'],

                        'profileCompletion' =>
                        $saved['profileCompletion'],

                        'approvedPhotos' =>
                        $saved['approvedPhotos'],

                        'trust' =>
                        $saved['trust'],

                        'commercial' =>
                        $saved['commercial'],
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.match-score.index'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Match Score updated',

                        'message' =>
                        'The new weights are now active for member ranking.',
                    ]
                );
        } catch (RuntimeException $exception) {
            return $this->validationRedirect(
                $input,
                [
                    'total' =>
                    $exception
                        ->getMessage(),
                ]
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_match_score_configuration_update',

                    component: self::class,

                    method: __FUNCTION__
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.match-score.index'
                    )
                )
                ->withInput()
                ->with(
                    'matchScoreFormInput',
                    $input
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to update Match Score',

                        'message' =>
                        'The Match Score configuration could not be saved.',
                    ]
                );
        }
    }

    /**
     * @param array<string, string> $input
     * @param array<string, string> $errors
     */
    private function validationRedirect(
        array $input,
        array $errors
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'admin.match-score.index'
                )
            )
            ->withInput()
            ->with(
                'matchScoreFormInput',
                $input
            )
            ->with(
                'validationErrors',
                $errors
            );
    }
}
