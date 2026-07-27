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
use App\Models\MasterBirthStarModel;
use App\Models\MasterMoonSignModel;
use App\Models\MasterSikhCommunityModel;
use App\Models\MasterSikhSubcommunityModel;
use App\Models\MemberSikhReligiousDetailModel;
use App\Services\Profile\SikhReligiousDetailsService;
use App\Models\MasterLifestyleCategoryModel;
use App\Models\MasterLifestyleOptionModel;
use App\Models\MemberLifestyleOptionModel;
use App\Services\Profile\LifestyleService;
use App\Services\Profile\AboutMeService;
use App\Models\MemberPhotoModel;
use App\Services\Aws\AwsMediaService;
use App\Services\Aws\CloudFrontService;
use App\Services\Aws\MediaPathService;
use App\Services\Aws\S3Service;
use App\Services\Media\ImageProcessorService;
use App\Services\Profile\MemberPhotoService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Models\AdminMemberPhotoApprovalModel;
use App\Services\Admin\MemberPhotoApprovalService;
use App\Services\Authentication\PasswordResetService;
use App\Models\MemberNotificationModel;
use App\Services\Notification\MemberNotificationService;
use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;
use Config\MemberMedia;


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

    /**
     * Return the member password-reset service.
     */
    public static function passwordResetService(
        bool $getShared = true
    ): PasswordResetService {
        if ($getShared) {
            return static::getSharedInstance(
                'passwordResetService'
            );
        }

        $database = db_connect();

        return new PasswordResetService(
            new UserModel($database),
            new UserContactModel($database),
            new ContactVerificationModel($database),
            $database,
            static::smsProvider(false)
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
     * Return the About Me profile service.
     */
    public static function aboutMeService(
        bool $getShared = true
    ): AboutMeService {
        if ($getShared) {
            return static::getSharedInstance(
                'aboutMeService'
            );
        }

        $database = db_connect();

        return new AboutMeService(
            new UserModel($database),
            new MemberBasicDetailModel($database)
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

    /**
     * Return Sikh and Religious Details profile service.
     */
    public static function sikhReligiousDetailsService(
        bool $getShared = true
    ): SikhReligiousDetailsService {
        if ($getShared) {
            return static::getSharedInstance(
                'sikhReligiousDetailsService'
            );
        }

        $database = db_connect();

        return new SikhReligiousDetailsService(
            new UserModel($database),
            new MemberSikhReligiousDetailModel($database),
            new MasterSikhCommunityModel($database),
            new MasterSikhSubcommunityModel($database),
            new MasterMoonSignModel($database),
            new MasterBirthStarModel($database),
            new MasterCountryModel($database),
            new MasterStateModel($database),
            new MasterCityModel($database),
            $database
        );
    }

    /**
     * Return Lifestyle profile service.
     */
    public static function lifestyleService(
        bool $getShared = true
    ): LifestyleService {
        if ($getShared) {
            return static::getSharedInstance(
                'lifestyleService'
            );
        }

        $database = db_connect();

        return new LifestyleService(
            new UserModel($database),
            new MasterLifestyleCategoryModel($database),
            new MasterLifestyleOptionModel($database),
            new MemberLifestyleOptionModel($database),
            $database
        );
    }

    /**
     * Return the configured S3 client.
     */
    public static function memberMediaS3Client(
        bool $getShared = true
    ): S3Client {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMediaS3Client'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        $config->assertS3Configured();

        $options = [
            'version' => 'latest',
            'region' => $config->awsRegion,
        ];

        /*
         * Explicit credentials are used only when both values exist.
         * Otherwise the AWS SDK uses EC2 IAM role/default chain.
         */
        if (
            $config->awsAccessKey !== ''
            && $config->awsSecretKey !== ''
        ) {
            $options['credentials'] = [
                'key' => $config->awsAccessKey,
                'secret' => $config->awsSecretKey,
            ];
        }

        return new S3Client($options);
    }

    /**
     * Return the configured CloudFront client.
     */
    public static function memberMediaCloudFrontClient(
        bool $getShared = true
    ): CloudFrontClient {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMediaCloudFrontClient'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        $config->assertCloudFrontConfigured();

        $options = [
            'version' => 'latest',
            'region' => $config->awsRegion,
        ];

        if (
            $config->awsAccessKey !== ''
            && $config->awsSecretKey !== ''
        ) {
            $options['credentials'] = [
                'key' => $config->awsAccessKey,
                'secret' => $config->awsSecretKey,
            ];
        }

        return new CloudFrontClient($options);
    }

    public static function s3Service(
        bool $getShared = true
    ): S3Service {
        if ($getShared) {
            return static::getSharedInstance(
                's3Service'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new S3Service(
            static::memberMediaS3Client(false),
            $config
        );
    }

    public static function cloudFrontService(
        bool $getShared = true
    ): CloudFrontService {
        if ($getShared) {
            return static::getSharedInstance(
                'cloudFrontService'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new CloudFrontService(
            static::memberMediaCloudFrontClient(false),
            $config
        );
    }

    public static function mediaPathService(
        bool $getShared = true
    ): MediaPathService {
        if ($getShared) {
            return static::getSharedInstance(
                'mediaPathService'
            );
        }

        return new MediaPathService();
    }

    public static function imageProcessorService(
        bool $getShared = true
    ): ImageProcessorService {
        if ($getShared) {
            return static::getSharedInstance(
                'imageProcessorService'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new ImageProcessorService($config);
    }

    public static function awsMediaService(
        bool $getShared = true
    ): AwsMediaService {
        if ($getShared) {
            return static::getSharedInstance(
                'awsMediaService'
            );
        }

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new AwsMediaService(
            static::s3Service(false),
            static::cloudFrontService(false),
            static::mediaPathService(false),
            static::imageProcessorService(false),
            $config
        );
    }

    public static function memberPhotoService(
        bool $getShared = true
    ): MemberPhotoService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberPhotoService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new MemberPhotoService(
            new UserModel($database),
            new MemberPhotoModel($database),
            static::awsMediaService(false),
            $database,
            $config
        );
    }

    public static function memberPhotoUrlService(
        bool $getShared = true
    ): MemberPhotoUrlService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberPhotoUrlService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        return new MemberPhotoUrlService(
            new MemberPhotoModel($database),
            static::cloudFrontService(false),
            $config
        );
    }

    /**
     * Return the administrator member-photo moderation service.
     */
    public static function memberPhotoApprovalService(
        bool $getShared = true
    ): MemberPhotoApprovalService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberPhotoApprovalService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $mediaConfig */
        $mediaConfig = config('MemberMedia');

        return new MemberPhotoApprovalService(
            new AdminMemberPhotoApprovalModel(
                $database
            ),

            new MemberPhotoModel(
                $database
            ),

            /*
            * Retain the exact existing CloudFront service factory name.
            */
            static::CloudFrontService(
                false
            ),

            $mediaConfig,

            static::adminAuditService(
                false
            ),

            /*
            * Notification creation remains behind its reusable service.
            *
            * Both services use the application's shared database connection, so the
            * notification participates in the photo-rejection transaction.
            */
            static::memberNotificationService(
                false
            ),

            $database
        );
    }

    /**
     * Return the shared member-notification service.
     */
    public static function memberNotificationService(
        bool $getShared = true
    ): MemberNotificationService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberNotificationService'
            );
        }

        $database = db_connect();

        return new MemberNotificationService(
            new MemberNotificationModel($database)
        );
    }
}
