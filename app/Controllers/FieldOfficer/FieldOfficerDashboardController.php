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
        $fieldOfficerId = max(
            0,
            (int) session(
                'fo_field_officer_id'
            )
        );

        $fieldOfficerName = trim(
            (string) session(
                'fo_field_officer_name'
            )
        );

        /** @var FieldOfficerProfileService $service */
        $service = service(
            'fieldOfficerProfileService'
        );

        return view(
            'FieldOfficer/Dashboard/Index',
            [
                'pageTitle' =>
                'SAK Volunteer Dashboard',

                'fieldOfficerName' =>
                $fieldOfficerName,

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
