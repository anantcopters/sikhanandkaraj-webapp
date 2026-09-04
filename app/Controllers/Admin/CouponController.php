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
                session(
                    'formAlert'
                ),
            ]
        );
    }

    public function create(): string
    {
        return view(
            'Admin/Coupons/Form',
            [
                'pageTitle' =>
                'Create Coupon',

                'coupon' =>
                null,

                'validationErrors' =>
                session(
                    'validationErrors'
                ) ?? [],

                'formAlert' =>
                session(
                    'formAlert'
                ),

                'membershipPlans' =>
                model(
                    \App\Models\MembershipPlanModel::class
                )
                    ->where(
                        'is_active',
                        1
                    )
                    ->orderBy(
                        'sort_order',
                        'ASC'
                    )
                    ->findAll(),

                'pageScripts' => [
                    'assets/js/pages/admin-coupons.js',
                    'assets/js/components/form-validator.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    public function store(): RedirectResponse
    {
        $input =
            $this->couponInput();

        $validation =
            service(
                'validation'
            );

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
            service(
                'couponManagementService'
            )->create(
                $input,
                $this->adminUserId()
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
                        'The coupon is ready for use according to its validity and eligibility rules.',
                    ]
                );
        } catch (
            DomainException
            | RuntimeException $exception
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Coupon not created',

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
                    operation: 'admin_coupon_create',

                    component: self::class,

                    method: __FUNCTION__
                )
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Coupon not created',

                        'message' =>
                        'The coupon could not be created.',
                    ]
                );
        }
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
                        ->getPost(
                            'code'
                        )
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
}
