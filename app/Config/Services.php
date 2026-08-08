<?php

declare(strict_types=1);

namespace Config;

use App\Models\AdminAuditLogModel;
use App\Models\AdminInvitationModel;
use App\Models\AdminMemberPhotoApprovalModel;
use App\Models\AdminUserModel;
use App\Models\ContactVerificationModel;
use App\Models\FieldOfficerModel;
use App\Models\HttpRequestLogModel;
use App\Models\MasterAnnualIncomeModel;
use App\Models\MasterBirthStarModel;
use App\Models\MasterCityModel;
use App\Models\MasterCountryModel;
use App\Models\MasterDrinkingHabitModel;
use App\Models\MasterEatingHabitModel;
use App\Models\MasterEducationModel;
use App\Models\MasterFamilyOccupationModel;
use App\Models\MasterFamilyStatusModel;
use App\Models\MasterFamilyTypeModel;
use App\Models\MasterFamilyValueModel;
use App\Models\MasterHeightModel;
use App\Models\MasterLifestyleCategoryModel;
use App\Models\MasterLifestyleOptionModel;
use App\Models\MasterMaritalStatusModel;
use App\Models\MasterMoonSignModel;
use App\Models\MasterMotherTongueModel;
use App\Models\MasterOccupationModel;
use App\Models\MasterPhysicalStatusModel;
use App\Models\MasterSikhCommunityModel;
use App\Models\MasterStateModel;
use App\Models\MemberBasicDetailModel;
use App\Models\MemberEducationProfessionDetailModel;
use App\Models\MemberFamilyDetailModel;
use App\Models\MemberLifestyleOptionModel;
use App\Models\MemberNotificationModel;
use App\Models\MemberPhotoModel;
use App\Models\MemberSikhReligiousDetailModel;
use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Admin\AdminInvitationService;
use App\Services\Admin\AdminManagementService;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Admin\Authentication\AdminLoginService;
use App\Services\Admin\FieldOfficerService;
use App\Services\Admin\MemberPhotoApprovalService;
use App\Services\Authentication\LoginService;
use App\Services\Authentication\OtpLoginService;
use App\Services\Authentication\PasswordResetService;
use App\Services\Aws\AwsMediaService;
use App\Services\Aws\CloudFrontService;
use App\Services\Aws\MediaPathService;
use App\Services\Aws\S3Service;
use App\Services\Email\EmailQueueService;
use App\Services\Logging\HttpRequestLogService;
use App\Services\Logging\RequestDataSanitizer;
use App\Services\Maintenance\FileCleanupService;
use App\Services\Maintenance\TableCleanupService;
use App\Services\Media\ImageProcessorService;
use App\Services\Notification\MemberNotificationService;
use App\Services\Prelaunch\PrelaunchAdminReviewService;
use App\Services\Prelaunch\PrelaunchContactAvailabilityService;
use App\Services\Prelaunch\PrelaunchFieldOfficerService;
use App\Services\Prelaunch\PrelaunchMemberMigrationService;
use App\Services\Prelaunch\PrelaunchPhotoService;
use App\Services\Prelaunch\PrelaunchProfileService;
use App\Services\Profile\AboutMeService;
use App\Services\Profile\BasicDetailsService;
use App\Services\Profile\EducationProfessionService;
use App\Services\Profile\FamilyDetailsService;
use App\Services\Profile\LifestyleService;
use App\Services\Profile\MemberPhotoService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberProfileSummaryService;
use App\Services\Profile\ProfileCompletionService;
use App\Services\Profile\ProfileMasterDataService;
use App\Services\Profile\SikhReligiousDetailsService;
use App\Services\Registration\RegisterFreeService;
use App\Services\Registration\RegistrationOtpService;
use App\Models\MemberPartnerBasicPreferenceModel;
use App\Models\MemberPartnerPreferenceDrinkingHabitModel;
use App\Models\MemberPartnerPreferenceEatingHabitModel;
use App\Models\MemberPartnerPreferenceMotherTongueModel;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Models\MemberPartnerLocationPreferenceModel;
use App\Models\MemberPartnerProfessionalPreferenceModel;
use App\Models\MemberPartnerReligiousPreferenceModel;
use App\Models\MemberPartnerSpecialRequestModel;
use App\Models\PartnerPreferenceSelectionModel;
use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
use App\Models\MemberAccountStatusHistoryModel;
use App\Services\Admin\MemberManagementService;
use App\Services\Development\DevelopmentProfileLoaderService;
use App\Models\MemberBlockModel;
use App\Models\MemberInterestModel;
use App\Models\MemberMatchCandidateModel;
use App\Models\MemberProfileViewModel;

use App\Services\Dashboard\MemberDashboardDataService;

use App\Services\Matchmaking\MemberInteractionService;
use App\Services\Matchmaking\MemberMatchmakingService;
use App\Services\Matchmaking\MemberProfileViewService;
use App\Services\Matchmaking\PartnerPreferenceMatchService;
use App\Models\MemberShortlistModel;
use Config\Matchmaking;
use App\Logging\ApplicationErrorLogWriter;
use App\Logging\ErrorLogSanitizer;
use App\Services\Logging\ApplicationErrorLogger;
use Config\ErrorLogging;
use App\Services\Sms\SmsProviderFactory;
use App\Services\Sms\SmsProviderInterface;
use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;
use CodeIgniter\Config\BaseService;

/**
 * Application service configuration.
 */
final class Services extends BaseService
{
    /**
     * Return the public registration service.
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
     * Return the member password-login service.
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
     * Return the member OTP-login service.
     */
    public static function otpLoginService(
        bool $getShared = true
    ): OtpLoginService {
        if ($getShared) {
            return static::getSharedInstance(
                'otpLoginService'
            );
        }

        $database = db_connect();

        return new OtpLoginService(
            new UserModel($database),
            new UserContactModel($database),
            new ContactVerificationModel($database),
            $database,
            static::smsProvider(false)
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

    /**
     * Return the administrator login service.
     */
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

    /**
     * Return the administrator invitation service.
     */
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
            new EmailQueueService($database),
            $database
        );
    }

    /**
     * Return the administrator management service.
     */
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

    /**
     * Return the administrator audit service.
     */
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
            new MasterFamilyOccupationModel($database),
            new MasterFamilyValueModel($database),
            new MasterFamilyTypeModel($database),
            new MasterFamilyStatusModel($database),
            new MasterSikhCommunityModel($database),
            new MasterDrinkingHabitModel($database),
            new MasterEatingHabitModel($database),
            new MasterPhysicalStatusModel($database)
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
     * Return the Education and Profession profile service.
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
     * Return the Sikh and Religious Details profile service.
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
            new MasterMoonSignModel($database),
            new MasterBirthStarModel($database),
            new MasterCountryModel($database),
            new MasterStateModel($database),
            new MasterCityModel($database),
            $database
        );
    }

    /**
     * Return the Lifestyle profile service.
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

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        $configuration->assertS3Configured();

        $options = [
            'version' => 'latest',
            'region' =>
            $configuration->awsRegion,
        ];

        /*
         * Explicit credentials are used only when both are present.
         * Otherwise, the AWS SDK uses its standard credentials chain.
         */
        if (
            $configuration->awsAccessKey !== ''
            && $configuration->awsSecretKey !== ''
        ) {
            $options['credentials'] = [
                'key' =>
                $configuration->awsAccessKey,

                'secret' =>
                $configuration->awsSecretKey,
            ];
        }

        return new S3Client(
            $options
        );
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

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        $configuration->assertCloudFrontConfigured();

        $options = [
            'version' => 'latest',
            'region' =>
            $configuration->awsRegion,
        ];

        if (
            $configuration->awsAccessKey !== ''
            && $configuration->awsSecretKey !== ''
        ) {
            $options['credentials'] = [
                'key' =>
                $configuration->awsAccessKey,

                'secret' =>
                $configuration->awsSecretKey,
            ];
        }

        return new CloudFrontClient(
            $options
        );
    }

    /**
     * Return the S3 wrapper service.
     */
    public static function s3Service(
        bool $getShared = true
    ): S3Service {
        if ($getShared) {
            return static::getSharedInstance(
                's3Service'
            );
        }

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new S3Service(
            static::memberMediaS3Client(false),
            $configuration
        );
    }

    /**
     * Return the CloudFront wrapper service.
     */
    public static function cloudFrontService(
        bool $getShared = true
    ): CloudFrontService {
        if ($getShared) {
            return static::getSharedInstance(
                'cloudFrontService'
            );
        }

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new CloudFrontService(
            static::memberMediaCloudFrontClient(false),
            $configuration
        );
    }

    /**
     * Return the member-media path service.
     */
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

    /**
     * Return the image-processing service.
     */
    public static function imageProcessorService(
        bool $getShared = true
    ): ImageProcessorService {
        if ($getShared) {
            return static::getSharedInstance(
                'imageProcessorService'
            );
        }

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new ImageProcessorService(
            $configuration
        );
    }

    /**
     * Return the high-level AWS media service.
     */
    public static function awsMediaService(
        bool $getShared = true
    ): AwsMediaService {
        if ($getShared) {
            return static::getSharedInstance(
                'awsMediaService'
            );
        }

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new AwsMediaService(
            static::s3Service(false),
            static::cloudFrontService(false),
            static::mediaPathService(false),
            static::imageProcessorService(false),
            $configuration
        );
    }

    /**
     * Return the member-photo workflow service.
     */
    public static function memberPhotoService(
        bool $getShared = true
    ): MemberPhotoService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberPhotoService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new MemberPhotoService(
            new UserModel($database),
            new MemberPhotoModel($database),
            static::awsMediaService(false),
            $database,
            $configuration
        );
    }

    /**
     * Return the member-photo signed URL service.
     */
    public static function memberPhotoUrlService(
        bool $getShared = true
    ): MemberPhotoUrlService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberPhotoUrlService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new MemberPhotoUrlService(
            new MemberPhotoModel($database),
            static::cloudFrontService(false),
            $configuration
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

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new MemberPhotoApprovalService(
            new AdminMemberPhotoApprovalModel(
                $database
            ),
            new MemberPhotoModel(
                $database
            ),
            static::cloudFrontService(false),
            $configuration,
            static::adminAuditService(false),
            static::memberNotificationService(false),
            $database
        );
    }

    /**
     * Return the member notification service.
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
            new MemberNotificationModel(
                $database
            )
        );
    }

    /**
     * Return the reusable database cleanup service.
     */
    public static function tableCleanupService(
        bool $getShared = true
    ): TableCleanupService {
        if ($getShared) {
            return static::getSharedInstance(
                'tableCleanupService'
            );
        }

        /** @var TableCleanup $configuration */
        $configuration = config(
            TableCleanup::class
        );

        return new TableCleanupService(
            database: Database::connect(),

            configuration: $configuration
        );
    }

    /**
     * Return the administrator Field Officer management service.
     */
    public static function fieldOfficerService(
        bool $getShared = true
    ): FieldOfficerService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerService'
            );
        }

        $database = db_connect();

        return new FieldOfficerService(
            new FieldOfficerModel($database),
            static::profileMasterDataService(false),
            static::adminAuditService(false),
            $database
        );
    }

    /**
     * Return the prelaunch Field Officer lookup service.
     */
    public static function prelaunchFieldOfficerService(
        bool $getShared = true
    ): PrelaunchFieldOfficerService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchFieldOfficerService'
            );
        }

        $database = db_connect();

        return new PrelaunchFieldOfficerService(
            new FieldOfficerModel($database)
        );
    }

    /**
     * Return the local prelaunch-photo storage service.
     */
    public static function prelaunchPhotoService(
        bool $getShared = true
    ): PrelaunchPhotoService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchPhotoService'
            );
        }

        $database = db_connect();

        return new PrelaunchPhotoService(
            new PrelaunchPhotoModel(
                $database
            )
        );
    }

    /**
     * Return the standalone prelaunch-profile creation service.
     */
    public static function prelaunchProfileService(
        bool $getShared = true
    ): PrelaunchProfileService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchProfileService'
            );
        }

        $database = db_connect();

        /** @var Prelaunch $configuration */
        $configuration = config(
            Prelaunch::class
        );

        return new PrelaunchProfileService(
            new PrelaunchProfileModel(
                $database
            ),

            static::prelaunchFieldOfficerService(
                false
            ),

            new PrelaunchPhotoService(
                new PrelaunchPhotoModel(
                    $database
                )
            ),

            static::profileMasterDataService(
                false
            ),

            new UserContactModel(),

            $database,

            $configuration
        );
    }

    /**
     * Return the prelaunch contact-availability validator.
     */
    public static function prelaunchContactAvailabilityService(
        bool $getShared = true
    ): PrelaunchContactAvailabilityService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchContactAvailabilityService'
            );
        }

        $database = db_connect();

        return new PrelaunchContactAvailabilityService(
            new PrelaunchProfileModel(
                $database
            ),
            new UserContactModel(
                $database
            )
        );
    }

    /**
     * Return the prelaunch-to-member migration service.
     *
     * Every model uses the same database connection so all database writes
     * participate in the migration transaction.
     */
    public static function prelaunchMemberMigrationService(
        bool $getShared = true
    ): PrelaunchMemberMigrationService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchMemberMigrationService'
            );
        }

        $database = db_connect();

        /** @var Prelaunch $configuration */
        $configuration = config(
            Prelaunch::class
        );

        return new PrelaunchMemberMigrationService(
            new PrelaunchProfileModel(
                $database
            ),
            new PrelaunchPhotoModel(
                $database
            ),
            new UserModel(
                $database
            ),
            new UserContactModel(
                $database
            ),
            new MemberPhotoModel(
                $database
            ),
            new PrelaunchPhotoService(
                new PrelaunchPhotoModel(
                    $database
                )
            ),
            static::awsMediaService(
                false
            ),
            $database,
            $configuration
        );
    }

    /**
     * Return the prelaunch administrator review service.
     *
     * The dedicated factories are reused here so dependency construction is
     * maintained in one location.
     */
    public static function prelaunchAdminReviewService(
        bool $getShared = true
    ): PrelaunchAdminReviewService {
        if ($getShared) {
            return static::getSharedInstance(
                'prelaunchAdminReviewService'
            );
        }

        $database = db_connect();

        return new PrelaunchAdminReviewService(
            new PrelaunchProfileModel(
                $database
            ),
            new PrelaunchPhotoModel(
                $database
            ),
            static::adminAuditService(
                false
            ),
            $database,
            static::prelaunchContactAvailabilityService(
                false
            ),
            static::prelaunchMemberMigrationService(
                false
            )
        );
    }

    /**
     * Return the shared member profile-summary service.
     */
    public static function memberProfileSummaryService(
        bool $getShared = true
    ): MemberProfileSummaryService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfileSummaryService'
            );
        }

        return new MemberProfileSummaryService(
            static::basicDetailsService(false),
            static::educationProfessionService(false),
            static::familyDetailsService(false),
            static::lifestyleService(false),
            static::aboutMeService(false),
            static::memberPhotoService(false),
            static::memberPhotoUrlService(false),
            static::profileCompletionService(false)
        );
    }

    /**
     * Return the reusable filesystem cleanup service.
     */
    public static function fileCleanupService(
        bool $getShared = true
    ): FileCleanupService {
        if ($getShared) {
            return static::getSharedInstance(
                'fileCleanupService'
            );
        }

        $database = db_connect();

        /** @var FileCleanup $configuration */
        $configuration = config(
            FileCleanup::class
        );

        return new FileCleanupService(
            profileModel: new PrelaunchProfileModel(
                $database
            ),

            photoModel: new PrelaunchPhotoModel(
                $database
            ),

            photoService: new PrelaunchPhotoService(
                new PrelaunchPhotoModel(
                    $database
                )
            ),

            database: $database,

            configuration: $configuration
        );
    }

    /**
     * Return the Basic Partner Preference service.
     */
    public static function basicPartnerPreferenceService(
        bool $getShared = true
    ): BasicPartnerPreferenceService {
        if ($getShared) {
            return static::getSharedInstance(
                'basicPartnerPreferenceService'
            );
        }

        $database = db_connect();

        return new BasicPartnerPreferenceService(
            new UserModel($database),

            new MemberPartnerBasicPreferenceModel(
                $database
            ),

            new MemberPartnerPreferenceMotherTongueModel(
                $database
            ),

            new MemberPartnerPreferenceEatingHabitModel(
                $database
            ),

            new MemberPartnerPreferenceDrinkingHabitModel(
                $database
            ),

            static::profileMasterDataService(false),

            $database
        );
    }

    /**
     * Return the Religious, Professional, Location and
     * Special Request Partner Preference service.
     */
    public static function additionalPartnerPreferenceService(
        bool $getShared = true
    ): AdditionalPartnerPreferenceService {
        if ($getShared) {
            return static::getSharedInstance(
                'additionalPartnerPreferenceService'
            );
        }

        $database = db_connect();

        return new AdditionalPartnerPreferenceService(
            new UserModel($database),

            new MemberPartnerReligiousPreferenceModel(
                $database
            ),

            new MemberPartnerProfessionalPreferenceModel(
                $database
            ),

            new MemberPartnerLocationPreferenceModel(
                $database
            ),

            new MemberPartnerSpecialRequestModel(
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'community',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'education',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'employed_in',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'occupation',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'annual_income',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'state',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'city',
                $database
            ),

            static::profileMasterDataService(false),

            $database
        );
    }

    /**
     * Administrator member listing and account-status management.
     */
    public static function memberManagementService(
        bool $getShared = true
    ): MemberManagementService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberManagementService'
            );
        }

        $database = db_connect();

        return new MemberManagementService(
            $database,
            new UserModel($database),
            new MemberAccountStatusHistoryModel(
                $database
            ),
            static::memberProfileSummaryService(
                false
            ),
            static::memberPhotoUrlService(
                false
            ),
            static::adminAuditService(
                false
            ),

            static::memberInteractionService(
                false
            )
        );
    }

    /**
     * Structured, failure-safe application error logger.
     */
    public static function applicationErrorLogger(
        bool $getShared = true
    ): ApplicationErrorLogger {
        if ($getShared) {
            return static::getSharedInstance(
                'applicationErrorLogger'
            );
        }

        return new ApplicationErrorLogger(
            new ApplicationErrorLogWriter(
                config(ErrorLogging::class),
                new ErrorLogSanitizer()
            )
        );
    }

    /**
     * Development-only bulk member profile loader.
     */
    public static function developmentProfileLoaderService(
        bool $getShared = true
    ): DevelopmentProfileLoaderService {
        if ($getShared) {
            return static::getSharedInstance(
                'developmentProfileLoaderService'
            );
        }

        $database = db_connect();

        return new DevelopmentProfileLoaderService(
            new UserModel($database),
            new UserContactModel($database),
            new MemberPhotoModel($database),
            static::basicDetailsService(),
            static::educationProfessionService(),
            static::basicPartnerPreferenceService(),
            static::additionalPartnerPreferenceService(),
            static::awsMediaService(),
            $database
        );
    }

    public static function memberInteractionService(
        bool $getShared = true
    ): MemberInteractionService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberInteractionService'
            );
        }

        $database = db_connect();

        return new MemberInteractionService(
            new UserModel(
                $database
            ),

            new MemberBlockModel(
                $database
            ),

            new MemberInterestModel(
                $database
            ),

            new MemberShortlistModel(
                $database
            ),

            new MemberProfileViewModel(
                $database
            ),

            $database
        );
    }

    public static function partnerPreferenceMatchService(
        bool $getShared = true
    ): PartnerPreferenceMatchService {
        if ($getShared) {
            return static::getSharedInstance(
                'partnerPreferenceMatchService'
            );
        }

        $database = db_connect();

        return new PartnerPreferenceMatchService(
            new MemberPartnerBasicPreferenceModel(
                $database
            ),

            new MemberPartnerPreferenceMotherTongueModel(
                $database
            ),

            new MemberPartnerPreferenceEatingHabitModel(
                $database
            ),

            new MemberPartnerPreferenceDrinkingHabitModel(
                $database
            ),

            new MemberPartnerReligiousPreferenceModel(
                $database
            ),

            new MemberPartnerProfessionalPreferenceModel(
                $database
            ),

            new MemberPartnerLocationPreferenceModel(
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'community',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'education',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'employed_in',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'occupation',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'annual_income',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'state',
                $database
            ),

            new PartnerPreferenceSelectionModel(
                'city',
                $database
            )
        );
    }

    public static function memberMatchmakingService(
        bool $getShared = true
    ): MemberMatchmakingService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMatchmakingService'
            );
        }

        $database = db_connect();

        return new MemberMatchmakingService(
            new UserModel($database),

            new MemberMatchCandidateModel(
                $database
            ),

            static::partnerPreferenceMatchService(
                false
            ),

            static::memberInteractionService(
                false
            ),

            static::memberPhotoUrlService(
                false
            ),

            config(
                Matchmaking::class
            )
        );
    }

    public static function memberDashboardDataService(
        bool $getShared = true
    ): MemberDashboardDataService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberDashboardDataService'
            );
        }

        return new MemberDashboardDataService(
            static::memberMatchmakingService(
                false
            )
        );
    }

    public static function memberProfileViewService(
        bool $getShared = true
    ): MemberProfileViewService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfileViewService'
            );
        }

        $database = db_connect();

        return new MemberProfileViewService(
            new UserModel(
                $database
            ),

            new UserContactModel(
                $database
            ),

            static::memberProfileSummaryService(
                false
            ),

            static::memberPhotoUrlService(
                false
            ),

            static::memberInteractionService(
                false
            ),

            static::memberMatchmakingService(
                false
            )
        );
    }
}
