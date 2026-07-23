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
use App\Services\Authentication\LoginService;
use App\Models\AdminInvitationModel;
use App\Models\AdminUserModel;
use App\Services\Admin\AdminInvitationService;
use App\Services\Admin\AdminManagementService;
use App\Services\Admin\Authentication\AdminLoginService;
use App\Services\Email\EmailQueueService;
use App\Models\AdminAuditLogModel;
use App\Services\Admin\Audit\AdminAuditService;
use App\Models\MemberBasicDetailModel;
use App\Services\Profile\BasicDetailsService;
use App\Models\MasterCityModel;
use App\Models\MasterCountryModel;
use App\Models\MasterHeightModel;
use App\Models\MasterMaritalStatusModel;
use App\Models\MasterMotherTongueModel;
use App\Models\MasterStateModel;
use App\Services\Profile\ProfileMasterDataService;
use App\Models\MasterAnnualIncomeModel;
use App\Models\MasterEducationModel;
use App\Models\MasterOccupationModel;
use App\Models\MemberEducationProfessionDetailModel;
use App\Services\Profile\EducationProfessionService;
use App\Services\Profile\ProfileCompletionService;
use App\Models\MasterFamilyOccupationModel;
use App\Models\MemberFamilyDetailModel;
use App\Services\Profile\FamilyDetailsService;


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

    /**
     * Return the password login service.
     */
    public static function loginService(
        bool $getShared = true
    ): LoginService {
        if ($getShared) {
            return static::getSharedInstance(
                'loginService'
            );
        }

        $database = db_connect();

        return new LoginService(
            new UserModel($database),
            new UserContactModel($database)
        );
    }

    public static function adminLoginService(
        bool $getShared = true
    ): AdminLoginService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminLoginService'
            );
        }

        $database = db_connect();

        return new AdminLoginService(
            new AdminUserModel($database)
        );
    }

    public static function adminInvitationService(
        bool $getShared = true
    ): AdminInvitationService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminInvitationService'
            );
        }

        $database = db_connect();

        return new AdminInvitationService(
            new AdminUserModel($database),
            new AdminInvitationModel($database),
            new EmailQueueService(
                $database
            ),
            $database
        );
    }

    public static function adminManagementService(
        bool $getShared = true
    ): AdminManagementService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminManagementService'
            );
        }

        $database = db_connect();

        return new AdminManagementService(
            new AdminUserModel($database),
            static::adminAuditService(false)
        );
    }

    public static function adminAuditService(
        bool $getShared = true
    ): AdminAuditService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminAuditService'
            );
        }

        $database = db_connect();

        return new AdminAuditService(
            new AdminAuditLogModel($database)
        );
    }

    /**
     * Return the Basic Details profile service.
     */
    public static function basicDetailsService(
        bool $getShared = true
    ): BasicDetailsService {
        if ($getShared) {
            return static::getSharedInstance(
                'basicDetailsService'
            );
        }

        $database = db_connect();

        return new BasicDetailsService(
            new UserModel($database),
            new MemberBasicDetailModel($database),
            static::profileMasterDataService(false)
        );
    }

    /**
     * Return profile master-data service.
     */
    public static function profileMasterDataService(
        bool $getShared = true
    ): ProfileMasterDataService {
        if ($getShared) {
            return static::getSharedInstance(
                'profileMasterDataService'
            );
        }

        $database = db_connect();

        return new ProfileMasterDataService(
            new MasterMaritalStatusModel($database),
            new MasterHeightModel($database),
            new MasterMotherTongueModel($database),
            new MasterCountryModel($database),
            new MasterStateModel($database),
            new MasterCityModel($database),
            new MasterEducationModel($database),
            new MasterOccupationModel($database),
            new MasterAnnualIncomeModel($database),
            new MasterFamilyOccupationModel($database)
        );
    }

    /**
     * Return the Education & Profession profile service.
     */
    public static function educationProfessionService(
        bool $getShared = true
    ): EducationProfessionService {
        if ($getShared) {
            return static::getSharedInstance(
                'educationProfessionService'
            );
        }

        $database = db_connect();

        return new EducationProfessionService(
            new UserModel($database),
            new MemberEducationProfessionDetailModel(
                $database
            ),
            static::profileMasterDataService(false),
            $database
        );
    }

    /**
     * Return the Family Details profile service.
     */
    public static function familyDetailsService(
        bool $getShared = true
    ): FamilyDetailsService {
        if ($getShared) {
            return static::getSharedInstance(
                'familyDetailsService'
            );
        }

        $database = db_connect();

        return new FamilyDetailsService(
            new UserModel($database),
            new MemberFamilyDetailModel($database),
            static::profileMasterDataService(false),
            $database
        );
    }

    /**
     * Return the overall profile completion service.
     */
    public static function profileCompletionService(
        bool $getShared = true
    ): ProfileCompletionService {
        if ($getShared) {
            return static::getSharedInstance(
                'profileCompletionService'
            );
        }

        return new ProfileCompletionService();
    }
}
