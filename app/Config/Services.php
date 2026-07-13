<?php

namespace Config;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Registration\RegisterFreeService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Return the Register Free service.
     *
     * By default, CI4 returns one shared instance during the request.
     */
    public static function registerFreeService(
        bool $getShared = true
    ): RegisterFreeService {
        if ($getShared) {
            return static::getSharedInstance(
                'registerFreeService'
            );
        }

        $database = db_connect();

        return new RegisterFreeService(
            new UserModel($database),
            new UserContactModel($database),
            new ContactVerificationModel($database),
            $database
        );
    }
}
