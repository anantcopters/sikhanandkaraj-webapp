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
use App\Models\MemberAadhaarSubmissionModel;
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
use App\Services\Admin\Authentication\AdminCaptchaService;
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
use App\Services\Profile\MemberAadhaarService;
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
use App\Services\Matchmaking\MemberInterestService;
use App\Services\Dashboard\MemberDashboardDataService;
use App\Services\Matchmaking\MemberInteractionService;
use App\Services\Matchmaking\MemberMatchmakingService;
use App\Services\Matchmaking\MemberProfileViewService;
use App\Services\Matchmaking\PartnerPreferenceMatchService;
use App\Services\Matchmaking\MemberSearchService;
use App\Models\FieldOfficerLoginOtpModel;
use App\Models\FieldOfficerSubmittedProfileModel;
use App\Services\FieldOfficer\FieldOfficerLoginService;
use App\Services\FieldOfficer\FieldOfficerProfileService;
use App\Services\Matchmaking\MemberProfilePresentationService;
use App\Services\Admin\Authentication\AdminPasswordResetService;
use App\Services\Profile\MemberTrustVerificationService;
use App\Models\MemberContactRequestModel;
use App\Models\MemberProfileReportModel;
use App\Services\Account\MemberAccountSettingsService;
use App\Services\Account\MemberContactRequestService;
use App\Services\Account\MemberProfileReportService;
use App\Services\EmailVerification\EmailVerificationService;
use App\Models\MemberShortlistModel;
use App\Services\Admin\FieldOfficerDocumentService;
use App\Services\Admin\MemberSupportService;
use App\Models\EmailVerificationTokenModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\MemberVideoModerationHistoryModel;
use App\Models\MemberVideoProcessingJobModel;
use App\Services\Admin\MemberVideoModerationService;
use App\Services\Video\MemberVideoIntroductionService;
use App\Services\Video\VideoIntroductionProcessingService;
use App\Services\Profile\MemberProfilePdfAssetService;
use App\Services\Profile\MemberProfilePdfDataService;
use App\Services\Profile\MemberProfilePdfService;
use App\Models\MemberPartnerLifestylePreferenceModel;
use App\Models\MemberPartnerLifestylePreferenceOptionModel;
use App\Services\PartnerPreference\LifestylePartnerPreferenceService;
use App\Models\MemberMembershipModel;
use App\Models\MembershipPlanModel;
use App\Services\Membership\MembershipEntitlementService;
use App\Services\Membership\MembershipService;
use App\Services\Membership\VerifiedProfilePolicy;
use App\Models\MemberMembershipProfileViewModel;
use App\Services\Membership\MembershipProfileUsageService;
use App\Services\Membership\ProfileAccessPolicy;
use App\Models\MemberMembershipLiveIntroductionViewModel;
use App\Services\Membership\MembershipLiveIntroductionUsageService;
use App\Services\Membership\LiveIntroductionAccessPolicy;
use App\Models\MatchScoreConfigurationModel;
use App\Models\MemberMatchScoringSignalModel;
use App\Services\Matchmaking\MatchScoreConfigurationService;
use App\Services\Matchmaking\MemberMatchScoreService;
use App\Services\Matchmaking\MemberMatchScoringSignalService;
use App\Services\Admin\MemberMatchScoreDiagnosticService;
use App\Services\Membership\MembershipLifecycleService;
use App\Services\Membership\MemberMembershipHistoryService;
use App\Services\Membership\MembershipPurchaseService;
use App\Services\Development\DevelopmentSearchProfilerService;
use App\Services\Development\DevelopmentCandidateQueryProfilerService;
use Config\ProfilePdf;
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
     * Return the administrator login CAPTCHA service.
     */
    public static function adminCaptchaService(
        bool $getShared = true
    ): AdminCaptchaService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminCaptchaService'
            );
        }

        return new AdminCaptchaService();
    }

    /**
     * Return the authenticated member Contact Us CAPTCHA service.
     *
     * The established arithmetic CAPTCHA implementation is reused with
     * isolated session state so it cannot interfere with Admin login.
     */
    public static function memberContactCaptchaService(
        bool $getShared = true
    ): AdminCaptchaService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberContactCaptchaService'
            );
        }

        return new AdminCaptchaService(
            'member_contact_captcha'
        );
    }

    /**
     * Return the member profile-report CAPTCHA service.
     *
     * Reuses the established arithmetic CAPTCHA implementation with
     * isolated session state.
     */
    public static function memberProfileReportCaptchaService(
        bool $getShared = true
    ): AdminCaptchaService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfileReportCaptchaService'
            );
        }

        return new AdminCaptchaService(
            'member_profile_report_captcha'
        );
    }

    /**
     * Return the SAK Volunteer login CAPTCHA service.
     *
     * The same proven CAPTCHA implementation used by Admin
     * is reused with isolated session state.
     */
    public static function fieldOfficerCaptchaService(
        bool $getShared = true
    ): AdminCaptchaService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerCaptchaService'
            );
        }

        return new AdminCaptchaService(
            'field_officer_login_captcha'
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
            new UserModel(
                $database
            ),

            new UserContactModel(
                $database
            ),

            new MemberFamilyDetailModel(
                $database
            ),

            static::profileMasterDataService(
                false
            ),

            new FieldOfficerModel(
                $database
            ),

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
            $configuration,
            cache: static::cache()
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
     * Return the member Aadhaar upload and review service.
     */
    public static function memberAadhaarService(
        bool $getShared = true
    ): MemberAadhaarService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberAadhaarService'
            );
        }

        $database = db_connect();

        /** @var MemberMedia $configuration */
        $configuration = config(
            MemberMedia::class
        );

        return new MemberAadhaarService(
            new UserModel(
                $database
            ),

            new MemberAadhaarSubmissionModel(
                $database
            ),

            static::s3Service(
                false
            ),

            static::cloudFrontService(
                false
            ),

            static::memberPhotoUrlService(
                false
            ),

            static::adminAuditService(
                false
            ),

            $database,

            config(
                MemberMedia::class
            ),

            /*
            * Aadhaar upload/re-upload is membership controlled.
            */
            static::membershipEntitlementService(
                false
            )
        );
    }

    /**
     * Return shared Trust and Verification presentation data.
     */
    public static function memberTrustVerificationService(
        bool $getShared = true
    ): MemberTrustVerificationService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberTrustVerificationService'
            );
        }

        $database = db_connect();

        return new MemberTrustVerificationService(
            new UserModel(
                $database
            ),

            new UserContactModel(
                $database
            ),

            static::memberAadhaarService(
                false
            ),

            static::memberAccountSettingsService(
                false
            ),

            new MemberVideoIntroductionModel(
                $database
            )
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
     * Return the administrator SAK Volunteer management service.
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
     * Return the prelaunch SAK Volunteer lookup service.
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
     * Every model uses the same database connection so all
     * database writes participate in the migration transaction.
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

            /*
            * Resolve the canonical SAK Volunteer code from the
            * SAK Volunteer master during migration.
            */
            new FieldOfficerModel(
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

        $database = db_connect();

        return new MemberProfileSummaryService(
            static::basicDetailsService(
                false
            ),

            static::educationProfessionService(
                false
            ),

            static::familyDetailsService(
                false
            ),

            static::lifestyleService(
                false
            ),

            static::aboutMeService(
                false
            ),

            static::memberPhotoService(
                false
            ),

            static::memberPhotoUrlService(
                false
            ),

            static::profileCompletionService(
                false
            ),

            new MemberAadhaarSubmissionModel(
                $database
            )
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
                'country',
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
     * Administrator member listing, complete profile display and
     * account-status management.
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

            new UserModel(
                $database
            ),

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
            ),

            static::basicPartnerPreferenceService(
                false
            ),

            static::additionalPartnerPreferenceService(
                false
            ),

            new MemberAadhaarSubmissionModel(
                $database
            ),

            new MemberVideoIntroductionModel(
                $database
            ),

            static::cloudFrontService(
                false
            ),

            config(
                VideoIntroduction::class
            ),

            /*
            * Admin profile diagnostics use the same Match Score authority as member
            * Search and Dashboard.
            */
            static::memberMatchScoreDiagnosticService(
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
     * Return the non-production bulk member profile loader.
     *
     * The loader reuses normal member profile services so generated QA data
     * follows the same master validation and persistence rules as real profiles.
     */
    public static function developmentProfileLoaderService(
        bool $getShared = true
    ): DevelopmentProfileLoaderService {
        if ($getShared) {
            return static::getSharedInstance(
                'developmentProfileLoaderService'
            );
        }

        $database =
            db_connect();

        return new DevelopmentProfileLoaderService(
            new UserModel(
                $database
            ),

            new UserContactModel(
                $database
            ),

            new MemberPhotoModel(
                $database
            ),

            static::basicDetailsService(
                false
            ),

            static::educationProfessionService(
                false
            ),

            /*
         * Family Details is required by the current profile architecture.
         */
            static::familyDetailsService(
                false
            ),

            static::basicPartnerPreferenceService(
                false
            ),

            static::additionalPartnerPreferenceService(
                false
            ),

            static::awsMediaService(
                false
            ),

            $database
        );
    }

    /**
     * Return the Membership-26 PostgreSQL candidate-query profiler.
     *
     * This service is CLI/development infrastructure only. Candidate
     * eligibility remains owned by MemberMatchCandidateModel.
     */
    public static function developmentCandidateQueryProfilerService(
        bool $getShared = true
    ): DevelopmentCandidateQueryProfilerService {
        if ($getShared) {
            return static::getSharedInstance(
                'developmentCandidateQueryProfilerService'
            );
        }

        $database =
            db_connect();

        return new DevelopmentCandidateQueryProfilerService(
            new UserModel(
                $database
            ),

            new MemberMatchCandidateModel(
                $database
            ),

            $database
        );
    }

    public static function developmentSearchProfilerService(
        bool $getShared = true
    ): DevelopmentSearchProfilerService {
        if ($getShared) {
            return static::getSharedInstance(
                'developmentSearchProfilerService'
            );
        }

        $database =
            db_connect();

        return new DevelopmentSearchProfilerService(
            new UserModel(
                $database
            ),

            static::memberSearchService(
                false
            )
        );
    }

    /**
     * Return the member-to-member interaction service.
     *
     * All persistence dependencies use the same database connection so
     * interaction changes and their related notifications can participate
     * in one transaction.
     */
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

            /*
            * Intentionally construct this with the SAME database connection used by
            * the interaction models.
            *
            * Interest persistence and notification creation must continue to
            * participate in the same transaction.
            */
            new MemberNotificationService(
                new MemberNotificationModel(
                    $database
                )
            ),

            $database,

            /*
            * Shortlist entitlement is resolved centrally.
            *
            * Report, Block and Interest remain available to Free + Paid members
            * according to MembershipEntitlementService.
            */
            static::membershipEntitlementService(
                false
            )
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
                'country',
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

            new MasterLifestyleCategoryModel(
                $database
            ),

            new MemberLifestyleOptionModel(
                $database
            ),

            new MemberPartnerLifestylePreferenceModel(
                $database
            ),

            new MemberPartnerLifestylePreferenceOptionModel(
                $database
            )
        );
    }

    /**
     * Return the common member-summary presentation service.
     *
     * Multi-profile member screens use this service so thumbnail authorization,
     * profile identity and common profile values cannot drift between Dashboard,
     * Search/Matches and Interests.
     */
    public static function memberProfilePresentationService(
        bool $getShared = true
    ): MemberProfilePresentationService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfilePresentationService'
            );
        }

        return new MemberProfilePresentationService(
            static::memberPhotoUrlService(
                false
            )
        );
    }

    /**
     * Return the member matchmaking collection service.
     *
     * Candidate eligibility, Partner Preference matching, final Match Score,
     * interaction state and photo presentation remain delegated to their
     * existing domain authorities.
     */
    public static function memberMatchmakingService(
        bool $getShared = true
    ): MemberMatchmakingService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMatchmakingService'
            );
        }

        $database =
            db_connect();

        /** @var Matchmaking $configuration */
        $configuration =
            config(
                Matchmaking::class
            );

        return new MemberMatchmakingService(
            new UserModel(
                $database
            ),

            new MemberMatchCandidateModel(
                $database
            ),

            static::partnerPreferenceMatchService(
                false
            ),

            static::memberMatchScoreService(
                false
            ),

            static::memberInteractionService(
                false
            ),

            static::memberProfilePresentationService(
                false
            ),

            /*
             * Membership-25:
             *
             * Reuse the same centralized photo authorization/signing service used
             * by Search so Dashboard does not perform one photo query per card.
             */
            static::memberPhotoUrlService(
                false
            ),

            $configuration
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

    /**
     * Return the member profile-view service.
     */
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

            new MemberProfileReportModel(
                $database
            ),

            static::memberMatchmakingService(
                false
            ),

            /*
         * Reuse the existing Partner Preference services.
         *
         * These provide the human-readable preference values
         * required by the Partner Preference Match modal.
         */
            static::basicPartnerPreferenceService(
                false
            ),
            static::additionalPartnerPreferenceService(
                false
            ),
            static::profileAccessPolicy(
                false
            )
        );
    }

    /**
     * Return the member Interest service.
     */
    public static function memberInterestService(
        bool $getShared = true
    ): MemberInterestService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberInterestService'
            );
        }

        $database = db_connect();

        return new MemberInterestService(
            new UserModel(
                $database
            ),

            new MemberInterestModel(
                $database
            ),

            new MemberMatchCandidateModel(
                $database
            ),

            static::memberProfilePresentationService(
                false
            ),

            /*
            * Use the same DB connection so Interest response
            * and notification remain in one transaction.
            */
            new MemberNotificationService(
                new MemberNotificationModel(
                    $database
                )
            ),

            $database,
            static::membershipEntitlementService(
                false
            ),

            static::memberInteractionService(
                false
            )
        );
    }

    /**
     * Return the authenticated member Search service.
     *
     * Search reuses the central member interaction service for Interest state so
     * Search cards and Profile View always follow identical relationship rules.
     */
    public static function memberSearchService(
        bool $getShared = true
    ): MemberSearchService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberSearchService'
            );
        }

        $database =
            db_connect();

        return new MemberSearchService(
            new UserModel(
                $database
            ),

            new MemberMatchCandidateModel(
                $database
            ),

            static::memberInteractionService(
                false
            ),

            static::memberProfilePresentationService(
                false
            ),

            static::profileMasterDataService(
                false
            ),

            static::lifestyleService(
                false
            ),

            /*
         * Search relevance uses exactly the same Partner Preference algorithm
         * as Dashboard All Matches.
         */
            static::partnerPreferenceMatchService(
                false
            ),

            /*
         * Final weighted Match Score authority.
         *
         * Dashboard and Search must never maintain separate ranking rules.
         */
            static::memberMatchScoreService(
                false
            ),

            /*
         * All membership-controlled Search capabilities are resolved through
         * the existing entitlement authority.
         */
            static::membershipEntitlementService(
                false
            ),

            /*
         * Membership-23:
         *
         * Search card collections batch-load approved primary-photo state
         * through the existing photo URL service.
         *
         * This dependency was added to MemberSearchService in Membership-23
         * and therefore must also be supplied by the service factory.
         */
            static::memberPhotoUrlService(
                false
            )
        );
    }

    /**
     * SAK Volunteer OTP authentication.
     */
    public static function fieldOfficerLoginService(
        bool $getShared = true
    ): FieldOfficerLoginService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerLoginService'
            );
        }

        $database = db_connect();

        return new FieldOfficerLoginService(
            new FieldOfficerModel(
                $database
            ),

            new FieldOfficerLoginOtpModel(
                $database
            ),

            $database,

            static::smsProvider(
                false
            )
        );
    }

    /**
     * SAK Volunteer submitted-profile service.
     */
    public static function fieldOfficerProfileService(
        bool $getShared = true
    ): FieldOfficerProfileService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerProfileService'
            );
        }

        $database = db_connect();

        return new FieldOfficerProfileService(
            new FieldOfficerSubmittedProfileModel(
                $database
            ),

            new PrelaunchProfileModel(
                $database
            ),

            new PrelaunchPhotoModel(
                $database
            ),

            static::prelaunchPhotoService(
                false
            ),

            static::memberProfileSummaryService(
                false
            ),

            static::memberPhotoUrlService(
                false
            )
        );
    }

    /**
     * Return the CAPTCHA service for public
     * SAK Volunteer self-registration.
     *
     * Registration and login use isolated session keys so
     * one page cannot invalidate the other's challenge.
     */
    public static function fieldOfficerRegistrationCaptchaService(
        bool $getShared = true
    ): AdminCaptchaService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerRegistrationCaptchaService'
            );
        }

        return new AdminCaptchaService(
            'field_officer_registration_captcha'
        );
    }

    /**
     * SAK Volunteer private verification-document service.
     */
    public static function fieldOfficerDocumentService(
        bool $getShared = true
    ): FieldOfficerDocumentService {
        if ($getShared) {
            return static::getSharedInstance(
                'fieldOfficerDocumentService'
            );
        }

        $database = db_connect();

        return new FieldOfficerDocumentService(
            new FieldOfficerModel(
                $database
            )
        );
    }

    /**
     * Return the administrator password-reset service.
     *
     * This service deliberately uses the same SMS provider abstraction
     * and OtpGenerator behavior as the Member password-reset flow.
     */
    public static function adminPasswordResetService(
        bool $getShared = true
    ): AdminPasswordResetService {
        if ($getShared) {
            return static::getSharedInstance(
                'adminPasswordResetService'
            );
        }

        $database = db_connect();

        return new AdminPasswordResetService(
            new \App\Models\AdminUserModel(
                $database
            ),

            new \App\Models\AdminPasswordResetVerificationModel(
                $database
            ),

            $database,

            static::smsProvider(false)
        );
    }

    public static function memberAccountSettingsService(
        bool $getShared = true
    ): MemberAccountSettingsService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberAccountSettingsService'
            );
        }

        $database = db_connect();

        return new MemberAccountSettingsService(
            new UserModel($database),
            new UserContactModel($database),
            new EmailVerificationTokenModel($database),
            new EmailVerificationService(
                new UserModel($database),
                new UserContactModel($database),
                new EmailVerificationTokenModel($database),
                static::emailQueueService(false)
            ),
            $database
        );
    }

    public static function memberContactRequestService(
        bool $getShared = true
    ): MemberContactRequestService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberContactRequestService'
            );
        }

        return new MemberContactRequestService(
            new MemberContactRequestModel(
                db_connect()
            )
        );
    }

    public static function memberProfileReportService(
        bool $getShared = true
    ): MemberProfileReportService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfileReportService'
            );
        }

        $database = db_connect();

        return new MemberProfileReportService(
            new UserModel($database),
            new MemberProfileReportModel(
                $database
            )
        );
    }

    public static function memberSupportService(
        bool $getShared = true
    ): MemberSupportService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberSupportService'
            );
        }

        $database = db_connect();

        return new MemberSupportService(
            new MemberProfileReportModel(
                $database
            ),

            new MemberContactRequestModel(
                $database
            ),

            $database
        );
    }

    /**
     * Return membership-scoped Live Introduction commercial usage service.
     *
     * This is deliberately separate from video moderation/lifecycle state.
     */
    public static function membershipLiveIntroductionUsageService(
        bool $getShared = true
    ): MembershipLiveIntroductionUsageService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipLiveIntroductionUsageService'
            );
        }

        $database = db_connect();

        return new MembershipLiveIntroductionUsageService(
            new MemberMembershipLiveIntroductionViewModel(
                $database
            ),
            $database
        );
    }

    /**
     * Return the centralized another-member Live Introduction playback policy.
     *
     * Signed member-facing video URLs must be authorized through this service.
     */
    public static function liveIntroductionAccessPolicy(
        bool $getShared = true
    ): LiveIntroductionAccessPolicy {
        if ($getShared) {
            return static::getSharedInstance(
                'liveIntroductionAccessPolicy'
            );
        }

        $database = db_connect();

        return new LiveIntroductionAccessPolicy(
            static::membershipEntitlementService(
                false
            ),

            static::profileAccessPolicy(
                false
            ),

            new MemberVideoIntroductionModel(
                $database
            ),

            new MemberInterestModel(
                $database
            ),

            new MemberProfileReportModel(
                $database
            ),

            static::membershipLiveIntroductionUsageService(
                false
            )
        );
    }

    public static function memberVideoIntroductionService(
        bool $getShared = true
    ): MemberVideoIntroductionService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberVideoIntroductionService'
            );
        }

        $database = db_connect();

        return new MemberVideoIntroductionService(
            new MemberVideoIntroductionModel(
                $database
            ),

            new MemberVideoProcessingJobModel(
                $database
            ),

            new MemberPhotoModel(
                $database
            ),

            new UserModel(
                $database
            ),

            new MemberInterestModel(
                $database
            ),

            new MemberBlockModel(
                $database
            ),

            new MemberProfileReportModel(
                $database
            ),

            static::s3Service(
                false
            ),

            static::cloudFrontService(
                false
            ),

            $database,

            config(
                VideoIntroduction::class
            ),

            static::membershipEntitlementService(
                false
            ),

            static::liveIntroductionAccessPolicy(
                false
            )
        );
    }

    public static function videoIntroductionProcessingService(
        bool $getShared = true
    ): VideoIntroductionProcessingService {
        if ($getShared) {
            return static::getSharedInstance(
                'videoIntroductionProcessingService'
            );
        }

        $database = db_connect();

        return new VideoIntroductionProcessingService(
            new MemberVideoIntroductionModel($database),
            new MemberVideoProcessingJobModel($database),
            static::s3Service(false),
            $database,
            config(VideoIntroduction::class)
        );
    }

    public static function memberVideoModerationService(
        bool $getShared = true
    ): MemberVideoModerationService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberVideoModerationService'
            );
        }

        $database = db_connect();

        return new MemberVideoModerationService(
            new MemberVideoIntroductionModel(
                $database
            ),

            new MemberVideoModerationHistoryModel(
                $database
            ),

            static::cloudFrontService(
                false
            ),

            static::memberNotificationService(
                false
            ),

            static::memberPhotoUrlService(
                false
            ),

            static::memberTrustVerificationService(
                false
            ),

            $database,

            config(
                VideoIntroduction::class
            )
        );
    }

    /**
     * Embedded asset provider for profile PDF generation.
     */
    public static function memberProfilePdfAssetService(
        bool $getShared = true
    ): MemberProfilePdfAssetService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfilePdfAssetService'
            );
        }

        return new MemberProfilePdfAssetService(
            static::s3Service(
                false
            )
        );
    }

    /**
     * Build privacy-safe profile PDF presentation data.
     */
    public static function memberProfilePdfDataService(
        bool $getShared = true
    ): MemberProfilePdfDataService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfilePdfDataService'
            );
        }

        $database = db_connect();

        return new MemberProfilePdfDataService(
            static::memberProfilePdfAssetService(
                false
            ),

            static::basicPartnerPreferenceService(
                false
            ),

            static::additionalPartnerPreferenceService(
                false
            ),

            new MemberPhotoModel(
                $database
            )
        );
    }

    /**
     * Render member profile HTML through headless Chrome.
     */
    public static function memberProfilePdfService(
        bool $getShared = true
    ): MemberProfilePdfService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberProfilePdfService'
            );
        }

        return new MemberProfilePdfService(
            static::memberProfilePdfDataService(
                false
            ),

            config(
                ProfilePdf::class
            )
        );
    }

    /**
     * Return Lifestyle Partner Preference service.
     */
    public static function lifestylePartnerPreferenceService(
        bool $getShared = true
    ): LifestylePartnerPreferenceService {
        if ($getShared) {
            return static::getSharedInstance(
                'lifestylePartnerPreferenceService'
            );
        }

        $database = db_connect();

        return new LifestylePartnerPreferenceService(
            new UserModel($database),
            new MasterLifestyleCategoryModel(
                $database
            ),
            new MasterLifestyleOptionModel(
                $database
            ),
            new MemberPartnerLifestylePreferenceModel(
                $database
            ),
            new MemberPartnerLifestylePreferenceOptionModel(
                $database
            ),
            $database
        );
    }

    /**
     * Return the authoritative membership resolver.
     *
     * MembershipService is the only production authority for determining
     * whether a member currently has a paid membership and which purchased
     * limits belong to that membership.
     */
    public static function membershipService(
        bool $getShared = true
    ): MembershipService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipService'
            );
        }

        $database = db_connect();

        return new MembershipService(
            new MembershipPlanModel(
                $database
            ),
            new MemberMembershipModel(
                $database
            )
        );
    }

    /**
     * Return membership lifecycle housekeeping service.
     *
     * This service synchronizes persisted lifecycle status only.
     *
     * MembershipService remains the runtime membership authority.
     */
    public static function membershipLifecycleService(
        bool $getShared = true
    ): MembershipLifecycleService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipLifecycleService'
            );
        }

        $database =
            db_connect();

        return new MembershipLifecycleService(
            new MemberMembershipModel(
                $database
            )
        );
    }

    /**
     * Return read-only member membership/usage history service.
     *
     * The service combines existing authoritative membership and commercial
     * usage ledgers for Account Settings presentation.
     */
    public static function memberMembershipHistoryService(
        bool $getShared = true
    ): MemberMembershipHistoryService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMembershipHistoryService'
            );
        }

        $database =
            db_connect();

        return new MemberMembershipHistoryService(
            static::membershipService(
                false
            ),

            new MemberMembershipModel(
                $database
            ),

            new MemberMembershipProfileViewModel(
                $database
            ),

            new MemberMembershipLiveIntroductionViewModel(
                $database
            )
        );
    }

    /**
     * Return the centralized membership capability resolver.
     *
     * Product code should ask this service for capabilities rather than
     * introducing local paid/free conditionals.
     */
    public static function membershipEntitlementService(
        bool $getShared = true
    ): MembershipEntitlementService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipEntitlementService'
            );
        }

        return new MembershipEntitlementService(
            static::membershipService(
                false
            )
        );
    }

    /**
     * Return the centralized Verified Profile policy.
     *
     * Verification evidence remains owned by the existing Trust and
     * Verification service. This policy only defines the product rule that at
     * least one verified credential qualifies the candidate as a Verified
     * Profile.
     */
    public static function verifiedProfilePolicy(
        bool $getShared = true
    ): VerifiedProfilePolicy {
        if ($getShared) {
            return static::getSharedInstance(
                'verifiedProfilePolicy'
            );
        }

        return new VerifiedProfilePolicy(
            static::memberTrustVerificationService(
                false
            )
        );
    }

    /**
     * Return membership-scoped Full Profile usage service.
     *
     * Commercial consumption is intentionally separate from general
     * member_profile_views interaction history.
     */
    public static function membershipProfileUsageService(
        bool $getShared = true
    ): MembershipProfileUsageService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipProfileUsageService'
            );
        }

        $database = db_connect();

        return new MembershipProfileUsageService(
            new MemberMembershipProfileViewModel(
                $database
            ),
            $database
        );
    }

    /**
     * Return the centralized another-member Full Profile access policy.
     *
     * All Full Profile authorization must pass through this service before
     * sensitive profile information or signed media URLs are created.
     */
    public static function profileAccessPolicy(
        bool $getShared = true
    ): ProfileAccessPolicy {
        if ($getShared) {
            return static::getSharedInstance(
                'profileAccessPolicy'
            );
        }

        return new ProfileAccessPolicy(
            new UserModel(
                db_connect()
            ),
            static::membershipService(
                false
            ),
            static::membershipEntitlementService(
                false
            ),
            static::verifiedProfilePolicy(
                false
            ),
            static::membershipProfileUsageService(
                false
            ),
            static::memberInteractionService(
                false
            )
        );
    }

    /**
     * Return the effective Match Score configuration authority.
     */
    public static function matchScoreConfigurationService(
        bool $getShared = true
    ): MatchScoreConfigurationService {
        if ($getShared) {
            return static::getSharedInstance(
                'matchScoreConfigurationService'
            );
        }

        $database =
            db_connect();

        return new MatchScoreConfigurationService(
            new MatchScoreConfigurationModel(
                $database
            ),

            $database
        );
    }

    /**
     * Return the pure Match Score calculator.
     *
     * The calculator itself performs no candidate queries.
     */
    public static function memberMatchScoreService(
        bool $getShared = true
    ): MemberMatchScoreService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMatchScoreService'
            );
        }

        return new MemberMatchScoreService(
            /*
         * Use the shared configuration service.
         *
         * Its request-local weight cache ensures a complete candidate
         * collection performs only one configuration lookup.
         */
            static::matchScoreConfigurationService(
                true
            )
        );
    }

    /**
     * Return the candidate-intrinsic scoring-signal cache service.
     */
    public static function memberMatchScoringSignalService(
        bool $getShared = true
    ): MemberMatchScoringSignalService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMatchScoringSignalService'
            );
        }

        $database =
            db_connect();

        return new MemberMatchScoringSignalService(
            new MemberMatchScoringSignalModel(
                $database
            ),

            static::memberProfileSummaryService(
                false
            )
        );
    }

    /**
     * Return read-only Admin Match Score diagnostics.
     *
     * The diagnostic service deliberately reuses the production:
     *
     * - candidate projection/eligibility;
     * - Partner Preference algorithm;
     * - Match Score calculation.
     *
     * This prevents Admin diagnostics from drifting from actual member ranking.
     */
    public static function memberMatchScoreDiagnosticService(
        bool $getShared = true
    ): MemberMatchScoreDiagnosticService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMatchScoreDiagnosticService'
            );
        }

        $database =
            db_connect();

        return new MemberMatchScoreDiagnosticService(
            $database,

            new UserModel(
                $database
            ),

            new MemberMatchCandidateModel(
                $database
            ),

            static::partnerPreferenceMatchService(
                false
            ),

            static::matchScoreConfigurationService(
                true
            ),

            static::memberMatchScoreService(
                false
            )
        );
    }

    /**
     * Return authoritative membership purchase/upgrade/renewal service.
     *
     * IMPORTANT:
     *
     * This service activates membership only after authoritative successful
     * payment confirmation or an explicitly authorized administrative/system
     * activation while payment integration is not yet available.
     *
     * Controllers and future payment providers must not create
     * member_memberships rows directly.
     */
    public static function membershipPurchaseService(
        bool $getShared = true
    ): MembershipPurchaseService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipPurchaseService'
            );
        }

        $database =
            db_connect();

        return new MembershipPurchaseService(
            $database,

            new MembershipPlanModel(
                $database
            ),

            new MemberMembershipModel(
                $database
            )
        );
    }

    /**
     * Build authoritative membership pricing/current-plan presentation.
     *
     * Commercial values are resolved from membership_plans through
     * MembershipService. MembershipPurchaseService supplies the member-specific
     * purchase/renewal/upgrade/downgrade decision.
     */
    public static function membershipPlanPresentationService(
        bool $getShared = true
    ): \App\Services\Membership\MembershipPlanPresentationService {
        if ($getShared) {
            return static::getSharedInstance(
                'membershipPlanPresentationService'
            );
        }

        return new
            \App\Services\Membership\MembershipPlanPresentationService(
                service(
                    'membershipService'
                ),

                service(
                    'membershipPurchaseService'
                )
            );
    }

    /**
     * Member-facing read-only membership usage presentation.
     */
    public static function memberMembershipUsageService(
        bool $getShared = true
    ): \App\Services\Membership\MemberMembershipUsageService {
        if ($getShared) {
            return static::getSharedInstance(
                'memberMembershipUsageService'
            );
        }

        return new
            \App\Services\Membership\MemberMembershipUsageService(
                service(
                    'membershipService'
                ),

                model(
                    \App\Models\MemberMembershipProfileViewModel::class
                ),

                model(
                    \App\Models\MemberMembershipLiveIntroductionViewModel::class
                )
            );
    }
}
