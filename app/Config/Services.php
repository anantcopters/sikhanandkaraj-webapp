<?php

namespace Config;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Registration\RegisterFreeService;
use App\Services\Registration\RegistrationOtpService;
use CodeIgniter\Config\BaseService;
use App\Services\Sms\SmsProviderFactory;
use App\Services\Sms\SmsProviderInterface;
use App\Models\HttpRequestLogModel;
use App\Services\Logging\HttpRequestLogService;
use App\Services\Logging\RequestDataSanitizer;


/**
 * Application service configuration.
 */
class Services extends BaseService
{
    /**
     * Return the Register Free service.
     *
     * By default, CodeIgniter returns one shared instance during
     * the current request.
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
            $database,

            /**
             * CHANGE:
             * OTP generation is delegated to RegistrationOtpService.
             *
             * Pass the same database connection and model instances
             * rather than creating an unrelated database connection.
             */
            new RegistrationOtpService(
                new UserModel($database),
                new UserContactModel($database),
                new ContactVerificationModel($database),
                $database,
                static::smsProvider(false)
            )
        );
    }

    /**
     * Return the registration OTP service.
     */
    public static function registrationOtpService(
        bool $getShared = true
    ): RegistrationOtpService {
        if ($getShared) {
            return static::getSharedInstance(
                'registrationOtpService'
            );
        }

        $database = db_connect();

        return new RegistrationOtpService(
            new UserModel($database),
            new UserContactModel($database),
            new ContactVerificationModel($database),
            $database,
            static::smsProvider(false)
        );
    }

    /**
     * Return the configured SMS provider.
     */
    public static function smsProvider(
        bool $getShared = true
    ): SmsProviderInterface {
        if ($getShared) {
            return static::getSharedInstance(
                'smsProvider'
            );
        }

        return SmsProviderFactory::create();
    }

    /**
     * Return the technical HTTP request logging service.
     */
    public static function httpRequestLogService(
        bool $getShared = true
    ): HttpRequestLogService {
        if ($getShared) {
            return static::getSharedInstance(
                'httpRequestLogService'
            );
        }

        $database = db_connect();

        return new HttpRequestLogService(
            new HttpRequestLogModel($database),
            new RequestDataSanitizer()
        );
    }
}
