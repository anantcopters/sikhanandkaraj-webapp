<?php

declare(strict_types=1);

namespace App\Controllers\FieldOfficer;

use App\Controllers\BaseController;
use App\Services\FieldOfficer\FieldOfficerProfileService;

final class FieldOfficerDashboardController
extends BaseController
{
    public function index(): string
    {
        $fieldOfficerId = (int) session(
            'fo_field_officer_id'
        );

        /** @var FieldOfficerProfileService $service */
        $service = service(
            'fieldOfficerProfileService'
        );

        return view(
            'FieldOfficer/Dashboard/Index',
            [
                'pageTitle' =>
                'Field Officer Dashboard',

                'submittedProfileCount' =>
                $service
                    ->totalProfiles(
                        $fieldOfficerId
                    ),

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }
}
