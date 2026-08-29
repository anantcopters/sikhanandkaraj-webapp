<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MemberAccountStatusHistoryModel;
use App\Services\Admin\MemberManagementService;
use App\Validation\Admin\MemberAccountStatusValidation;
use App\Support\AdminErrorContext;
use App\Support\DateDisplay;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Administrator member listing, profile review and account management.
 */
final class MemberController extends BaseController
{
    private const MEMBERS_PER_PAGE = 10;

    /**
     * Display the searchable, filterable and paginated member listing.
     */
    public function index(): string
    {
        $status = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getGet('status')
            )
        );

        if (
            !in_array(
                $status,
                [
                    'ALL',
                    'PENDING',
                    'ACTIVE',
                    'SUSPENDED',
                    'DELETED',
                ],
                true
            )
        ) {
            $status = 'ALL';
        }

        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $this->request
                    ->getGet('search')
            )
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        /** @var MemberManagementService $service */
        $service = service(
            'memberManagementService'
        );

        $result = $service
            ->paginatedMembers(
                $status,
                $search,
                self::MEMBERS_PER_PAGE
            );

        return view(
            'Admin/Members/Index',
            [
                'pageTitle' =>
                'Members',

                'members' =>
                $result['members'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'perPage' =>
                $result['perPage'],

                'validationErrors' =>
                session(
                    'validationErrors'
                ) ?? [],

                'statusModal' =>
                session('statusModal'),

                'formAlert' =>
                session('formAlert'),

                'pageScripts' => [
                    'assets/js/pages/admin-members.js',
                ],
            ]
        );
    }

    /**
     * Display one complete member profile for administrator review.
     */
    public function view(
        int $userId
    ): string {
        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            return view(
                'Admin/Members/View',
                array_merge(
                    [
                        'pageTitle' =>
                        'Member Profile',

                        'memberId' =>
                        $userId,

                        'validationErrors' =>
                        session(
                            'validationErrors'
                        ) ?? [],

                        'statusModal' =>
                        session(
                            'statusModal'
                        ),

                        'formAlert' =>
                        session(
                            'formAlert'
                        ),

                        'matchScoreComparison' =>
                        session(
                            'matchScoreComparison'
                        ),

                        'matchScoreDiagnosticErrors' =>
                        session(
                            'matchScoreDiagnosticErrors'
                        ) ?? [],

                        'matchScoreDiagnosticInput' =>
                        session(
                            'matchScoreDiagnosticInput'
                        ) ?? [],

                        'pageScripts' => [
                            'assets/js/pages/admin-member-view.js',
                            'assets/js/pages/admin-video-introduction-review.js',
                            'assets/js/components/form-validator.js',
                            'assets/js/components/submit-loader.js',
                        ],
                    ],
                    $service->profilePreview(
                        $userId
                    )
                )
            );
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_profile_view',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,
                    ]
                )
            );

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Display all qualified Matches for one member to an administrator.
     */
    public function matches(
        int $userId
    ): string {
        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $this->request
                    ->getGet(
                        'search'
                    )
            )
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        $sort = mb_strtolower(
            trim(
                (string) $this->request
                    ->getGet(
                        'sort'
                    )
            )
        );

        if (
            !in_array(
                $sort,
                [
                    'match_score',
                    'partner_preference',
                ],
                true
            )
        ) {
            $sort =
                'match_score';
        }

        $page = max(
            1,
            (int) (
                $this->request
                ->getGet(
                    'page_adminMemberMatches'
                )
                ?? 1
            )
        );

        try {
            $result = service(
                'adminMemberMatchesService'
            )->paginatedMatches(
                memberUserId: $userId,

                search: $search,

                sort: $sort,

                page: $page,

                perPage: 9
            );

            return view(
                'Admin/Members/Matches',
                [
                    'pageTitle' =>
                    'Member Matches',

                    ...$result,

                    'pageScripts' => [
                        'assets/js/pages/admin-member-matches.js',
                    ],
                ]
            );
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_matches',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,
                    ]
                )
            );

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Calculate a read-only directional Match Score diagnostic.
     *
     * This endpoint exists only for authenticated administrators.
     *
     * It does not persist anything and does not affect actual member ranking.
     */
    public function matchScoreDiagnostic(
        int $userId
    ): RedirectResponse {
        $profileReference =
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'profile_reference'
                        )
                )
            );

        /*
     * Keep client/server validation consistent with the project.
     *
     * Profile references are deliberately treated as strings rather than
     * assuming a numeric member ID format.
     */
        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            [
                'profile_reference' => [
                    'label' =>
                    'Profile ID',

                    'rules' =>
                    'required|max_length[50]',
                ],
            ]
        );

        $input = [
            'profile_reference' =>
            $profileReference,
        ];

        $returnContext = mb_strtolower(
            trim(
                (string) $this->request
                    ->getPost(
                        'return_context'
                    )
            )
        );

        $returnUrl =
            $returnContext === 'matches'
            ? route_to(
                'admin.members.matches',
                $userId
            )
            : route_to(
                'admin.members.view',
                $userId
            );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    $returnUrl
                )
                ->with(
                    'matchScoreDiagnosticErrors',
                    $validation->getErrors()
                )
                ->with(
                    'matchScoreDiagnosticInput',
                    $input
                );
        }

        try {
            $diagnostic =
                service(
                    'memberMatchScoreDiagnosticService'
                )->compare(
                    $userId,
                    $profileReference
                );

            return redirect()
                ->to(
                    $returnUrl
                )
                ->with(
                    'matchScoreComparison',
                    $diagnostic
                )
                ->with(
                    'matchScoreDiagnosticInput',
                    $input
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    $returnUrl
                )
                ->with(
                    'matchScoreDiagnosticErrors',
                    [
                        'profile_reference' =>
                        $exception->getMessage(),
                    ]
                )
                ->with(
                    'matchScoreDiagnosticInput',
                    $input
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_match_score_diagnostic',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.members.view',
                        $userId
                    )
                )
                ->with(
                    'matchScoreDiagnosticErrors',
                    [
                        'profile_reference' =>
                        'The Match Score diagnostic could not be calculated.',
                    ]
                )
                ->with(
                    'matchScoreDiagnosticInput',
                    $input
                );
        }
    }

    public function block(
        int $userId
    ): RedirectResponse {
        return $this->changeStatus(
            $userId,
            MemberAccountStatusHistoryModel::ACTION_BLOCK
        );
    }

    public function unblock(
        int $userId
    ): RedirectResponse {
        return $this->changeStatus(
            $userId,
            MemberAccountStatusHistoryModel::ACTION_UNBLOCK
        );
    }

    /**
     * Return block/unblock history.
     *
     * UTC timestamps are converted into the configured user-facing timezone
     * before they are returned to the browser.
     */
    public function history(
        int $userId
    ): ResponseInterface {
        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            $result = $service->history(
                $userId
            );

            $history = array_map(
                static fn(array $row): array => [
                    'action' =>
                    (string) (
                        $row['action']
                        ?? ''
                    ),

                    'previousStatus' =>
                    (string) (
                        $row['previous_status']
                        ?? ''
                    ),

                    'newStatus' =>
                    (string) (
                        $row['new_status']
                        ?? ''
                    ),

                    'reason' =>
                    (string) (
                        $row['reason']
                        ?? ''
                    ),

                    'adminName' =>
                    (string) (
                        $row['admin_name']
                        ?? 'Administrator'
                    ),

                    'adminRole' =>
                    (string) (
                        $row['admin_role']
                        ?? ''
                    ),

                    /*
                    * Machine-readable ISO-8601 timestamp converted from UTC to
                    * the configured display timezone.
                    *
                    * Example:
                    * 2026-08-05T22:58:00+05:30
                    */
                    'changedAtIso' =>
                    DateDisplay::utcToDisplayIso(
                        $row['changed_at']
                            ?? null
                    ),

                    /*
                 * User-facing local date and time.
                 *
                 * Example:
                 * 5th Aug 2026 10:58 PM
                 */
                    'changedAtDisplay' =>
                    DateDisplay::formatUtcDateTime(
                        $row['changed_at']
                            ?? null
                    ),
                ],
                $result['history']
            );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setJSON([
                    'successful' =>
                    true,

                    'member' => [
                        'name' =>
                        (string) (
                            $result['member']['full_name']
                            ?? ''
                        ),

                        'reference' =>
                        (string) (
                            $result['member']['profile_ref_number']
                            ?? ''
                        ),
                    ],

                    'history' =>
                    $history,
                ]);
        } catch (PageNotFoundException) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The member was not found.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_status_history',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,
                    ]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The member history could not be loaded.',
                ]);
        }
    }

    /**
     * Return signed modal URLs for one retained member photograph.
     */
    public function photoModalUrls(
        int $userId,
        int $photoId
    ): ResponseInterface {
        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            $urls = $service
                ->adminPhotoModalUrls(
                    $userId,
                    $photoId
                );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'successful' =>
                    true,

                    'photoId' =>
                    $urls['photoId'],

                    'originalUrl' =>
                    $urls['originalUrl'],

                    'mediumUrl' =>
                    $urls['mediumUrl'],
                ]);
        } catch (
            PageNotFoundException
            | DomainException) {
            return $this->photoNotFoundResponse();
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_photo_modal_url',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,

                        'photo_id' =>
                        $photoId,
                    ]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'successful' =>
                    false,

                    'message' =>
                    'The photograph could not be loaded.',
                ]);
        }
    }

    /**
     * Validate and perform a block/unblock action.
     */
    private function changeStatus(
        int $userId,
        string $action
    ): RedirectResponse {
        $input = [
            'reason' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost('reason')
                )
            ) ?? '',
        ];

        $returnUrl = $this->safeReturnUrl();

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberAccountStatusValidation
                ::reasonRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to($returnUrl)
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var MemberManagementService $service */
            $service = service(
                'memberManagementService'
            );

            if (
                $action
                === MemberAccountStatusHistoryModel::ACTION_BLOCK
            ) {
                $service->block(
                    $userId,
                    $validation
                        ->getValidated()['reason'],
                    $this->adminUserId()
                );

                $title = 'Member blocked';
                $message =
                    'The member account has been blocked.';
            } else {
                $service->unblock(
                    $userId,
                    $validation
                        ->getValidated()['reason'],
                    $this->adminUserId()
                );

                $title = 'Member unblocked';
                $message =
                    'The member account is active again.';
            }

            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        $title,

                        'message' =>
                        $message,
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Status not changed',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_member_status_change',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_member_user_id' =>
                        $userId,

                        'status_action' =>
                        $action,
                    ]
                )
            );

            return redirect()
                ->to($returnUrl)
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Status not changed',

                        'message' =>
                        'The member status could not be changed.',
                    ]
                );
        }
    }

    /**
     * Accept only local Admin member return URLs.
     */
    private function safeReturnUrl(): string
    {
        $returnUrl = trim(
            (string) $this->request
                ->getPost('return_url')
        );

        $memberBaseUrl = rtrim(
            site_url('admin/members'),
            '/'
        );

        if (
            $returnUrl !== ''
            && (
                $returnUrl === $memberBaseUrl
                || str_starts_with(
                    $returnUrl,
                    $memberBaseUrl . '/'
                )
                || str_starts_with(
                    $returnUrl,
                    $memberBaseUrl . '?'
                )
            )
        ) {
            return $returnUrl;
        }

        return route_to(
            'admin.members.index'
        );
    }

    private function adminUserId(): int
    {
        $adminUserId = session(
            'admin_user_id'
        );

        if (!is_numeric($adminUserId)) {
            session()->destroy();

            throw PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $adminUserId;
    }

    private function photoNotFoundResponse(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'successful' =>
                false,

                'message' =>
                'The requested photograph is unavailable.',
            ]);
    }
}
