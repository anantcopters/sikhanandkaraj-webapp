<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Support\AdminErrorContext;
use App\Validation\Admin\CouponValidation;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use RuntimeException;
use Throwable;

final class CouponController extends BaseController
{
    public function index(): string
    {
        return view(
            'Admin/Coupons/Index',
            [
                'pageTitle' =>
                'Coupon Management',

                'coupons' =>
                service(
                    'couponManagementService'
                )->coupons(),

                'formAlert' =>
                session('formAlert'),
            ]
        );
    }

    public function create(): string
    {
        return $this->formView(
            null,
            'Create Coupon'
        );
    }

    public function store(): RedirectResponse
    {
        $input =
            $this->couponInput();

        $validation =
            service('validation');

        $validation->setRules(
            CouponValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            $adminUserId =
                (int) session(
                    'admin_user_id'
                );

            if ($adminUserId <= 0) {
                throw new DomainException(
                    'Authenticated administrator could not be determined.'
                );
            }

            service(
                'couponManagementService'
            )->create(
                $input,
                $adminUserId
            );

            return redirect()
                ->route(
                    'admin.coupons.index'
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Coupon created',

                        'message' =>
                        'The coupon has been created successfully.',
                    ]
                );
        } catch (
            DomainException
            | RuntimeException $exception
        ) {
            return $this->redirectWithError(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $this->logException(
                $exception,
                'admin_coupon_create',
                __FUNCTION__
            );

            return $this->redirectWithError(
                'The coupon could not be created.'
            );
        }
    }

    public function edit(
        int $couponId
    ): string|RedirectResponse {
        $coupon =
            service(
                'couponManagementService'
            )->find($couponId);

        if ($coupon === null) {
            return redirect()
                ->route(
                    'admin.coupons.index'
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Coupon not found',

                        'message' =>
                        'The requested coupon does not exist.',
                    ]
                );
        }

        return $this->formView(
            $coupon,
            'Edit Coupon'
        );
    }

    public function update(
        int $couponId
    ): RedirectResponse {
        $input =
            $this->couponInput();

        $validation =
            service('validation');

        $validation->setRules(
            CouponValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            $adminUserId =
                (int) session(
                    'admin_user_id'
                );

            if ($adminUserId <= 0) {
                throw new DomainException(
                    'Authenticated administrator could not be determined.'
                );
            }

            service(
                'couponManagementService'
            )->update(
                $couponId,
                $input,
                $adminUserId
            );

            return redirect()
                ->route(
                    'admin.coupons.index'
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Coupon updated',

                        'message' =>
                        'The coupon has been updated successfully.',
                    ]
                );
        } catch (
            DomainException
            | RuntimeException $exception
        ) {
            return $this->redirectWithError(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $this->logException(
                $exception,
                'admin_coupon_update',
                __FUNCTION__
            );

            return $this->redirectWithError(
                'The coupon could not be updated.'
            );
        }
    }

    public function status(
        int $couponId
    ): RedirectResponse {
        $requestedStatus =
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('status')
                )
            );

        if (
            !in_array(
                $requestedStatus,
                [
                    'ACTIVE',
                    'INACTIVE',
                ],
                true
            )
        ) {
            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Status not changed',

                        'message' =>
                        'Please select a valid coupon status.',
                    ]
                );
        }

        try {
            $adminUserId =
                (int) session(
                    'admin_user_id'
                );

            if ($adminUserId <= 0) {
                throw new DomainException(
                    'Authenticated administrator could not be determined.'
                );
            }

            service(
                'couponManagementService'
            )->setStatus(
                $couponId,
                $requestedStatus
                    === 'ACTIVE',
                $adminUserId
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Coupon status updated',

                        'message' =>
                        'The coupon status has been updated.',
                    ]
                );
        } catch (
            DomainException
            | RuntimeException $exception
        ) {
            return redirect()
                ->back()
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
        }
    }

    public function report(
        int $couponId
    ): string|RedirectResponse {
        try {
            $report =
                service(
                    'couponManagementService'
                )->report(
                    $couponId
                );

            return view(
                'Admin/Coupons/Report',
                [
                    'pageTitle' =>
                    'Coupon Report',

                    'coupon' =>
                    $report['coupon'],

                    'redemptions' =>
                    $report['redemptions'],

                    'summary' =>
                    $report['summary'],
                ]
            );
        } catch (DomainException) {
            return redirect()
                ->route(
                    'admin.coupons.index'
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Coupon not found',

                        'message' =>
                        'The requested coupon does not exist.',
                    ]
                );
        }
    }

    /**
     * @param array<string, mixed>|null $coupon
     */
    private function formView(
        ?array $coupon,
        string $pageTitle
    ): string {
        $membershipPlans =
            model(
                \App\Models\MembershipPlanModel::class
            )
            ->activePlans();

        $members =
            db_connect()
            ->table('users')
            ->select(
                'id, profile_id, first_name, last_name'
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'first_name',
                'ASC'
            )
            ->orderBy(
                'last_name',
                'ASC'
            )
            ->get()
            ->getResultArray();

        /*
        * Coupon geography uses the same master location tables used by
        * Basic Details. Only countries need to be rendered initially.
        *
        * States and cities are loaded through the existing ProfileMaster
        * endpoints when the parent selection changes.
        */
        $countries =
            db_connect()
            ->table('master_countries')
            ->select(
                'id, name'
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'name',
                'ASC'
            )
            ->get()
            ->getResultArray();

        $states = [];
        $cities = [];

        $selectedCountryId =
            (int) (
                $coupon['country_id']
                ?? 0
            );

        $selectedStateId =
            (int) (
                $coupon['state_id']
                ?? 0
            );

        if ($selectedCountryId > 0) {
            $states =
                db_connect()
                ->table('master_states')
                ->select(
                    'id, name'
                )
                ->where(
                    'country_id',
                    $selectedCountryId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name',
                    'ASC'
                )
                ->get()
                ->getResultArray();
        }

        if ($selectedStateId > 0) {
            $cities =
                db_connect()
                ->table('master_cities')
                ->select(
                    'id, name'
                )
                ->where(
                    'state_id',
                    $selectedStateId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name',
                    'ASC'
                )
                ->get()
                ->getResultArray();
        }

        return view(
            'Admin/Coupons/Form',
            [
                'pageTitle' =>
                $pageTitle,

                'coupon' =>
                $coupon,

                'membershipPlans' =>
                $membershipPlans,

                'members' =>
                $members,

                'countries' =>
                $countries,

                'states' =>
                $states,

                'cities' =>
                $cities,

                'validationErrors' =>
                session(
                    'validationErrors'
                ) ?? [],

                'formAlert' =>
                session('formAlert'),

                'pageScripts' => [
                    'assets/js/pages/admin-coupons.js',
                    'assets/js/components/form-validator.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function couponInput(): array
    {
        return [
            'code' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('code')
                )
            ),

            'description' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'description'
                    )
            ),

            'discount_type' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'discount_type'
                        )
                )
            ),

            'discount_value' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'discount_value'
                    )
            ),

            'eligibility_type' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'eligibility_type'
                        )
                )
            ),

            'eligible_gender' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'eligible_gender'
                        )
                )
            ),

            'usage_limit' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'usage_limit'
                    )
            ),

            'expiry_date' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'expiry_date'
                    )
            ),

            'plan_ids' =>
            (array) $this->request
                ->getPost(
                    'plan_ids'
                ),

            'member_ids' =>
            (array) $this->request
                ->getPost(
                    'member_ids'
                ),

            'country_id' =>
            $this->request
                ->getPost(
                    'country_id'
                ),

            'state_id' =>
            $this->request
                ->getPost(
                    'state_id'
                ),

            'city_id' =>
            $this->request
                ->getPost(
                    'city_id'
                ),

            'is_active' =>
            $this->request
                ->getPost(
                    'is_active'
                ),
        ];
    }

    private function redirectWithError(
        string $message
    ): RedirectResponse {
        return redirect()
            ->back()
            ->withInput()
            ->with(
                'formAlert',
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Coupon not saved',

                    'message' =>
                    $message,
                ]
            );
    }

    private function logException(
        Throwable $exception,
        string $operation,
        string $method
    ): void {
        service(
            'applicationErrorLogger'
        )->exception(
            $exception,
            'error',
            AdminErrorContext::forOperation(
                operation: $operation,

                component: self::class,

                method: $method
            )
        );
    }
}
