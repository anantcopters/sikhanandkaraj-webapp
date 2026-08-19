# Permanent Regression Suite

## Purpose

This file contains regression cases that must survive individual feature work. It grows as the application baseline is established, features are QA-reviewed, and defects are fixed.

Do not populate cases from assumptions. Add a case after current behavior/requirement is confirmed by code review, accepted requirement, or resolved defect.

## Rules

- Every case has a stable unique ID.
- Do not reuse deleted IDs.
- A defect that could recur should result in a regression case.
- Feature QA must identify which existing regression cases are affected.
- Re-QA must execute/review the regression cases affected by the fix.
- Automated and manual cases may coexist.
- A case that was not actually executed is `NOT RUN`, not PASS.

## ID convention

Use `REG-<AREA>-NNN`.

Suggested area codes: AUTH, PROF, MEDIA, MATCH, INT, MSG, ADMIN, MASTER, PRE, DB, SEC.

## Execution status

- `PASS`
- `FAIL`
- `NOT RUN`
- `BLOCKED`
- `RETIRED`

## Authentication

No permanent cases recorded yet.

## Member Profile

No permanent cases recorded yet.

## Photos / Media

No permanent cases recorded yet.

## Matches / Search

No permanent cases recorded yet.

## Interests

No permanent cases recorded yet.

## Messages / Notifications

No permanent cases recorded yet.

## Admin

No permanent cases recorded yet.

## Master Data

No permanent cases recorded yet.

## Prelaunch Profile

### REG-PRE-001 - Valid public prelaunch profile save
**Origin:** Feature `QA-0001`  
**Expected:** Valid required data, consent and exactly two valid photos create one DRAFT profile plus two photo records atomically and redirect to success.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-002 - Duplicate member mobile rejected
**Origin:** Feature `QA-0001`  
**Expected:** A mobile already used by a prelaunch profile or live member contact is rejected and no partial profile/photo state is committed.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-003 - Optional email behavior
**Origin:** Feature `QA-0001`  
**Expected:** Missing email is accepted as NULL; supplied email must be valid and unique according to the prelaunch/member rules.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-004 - Photo upload safety
**Origin:** Feature `QA-0001`  
**Expected:** Invalid type, undecodable content, undersized/oversized images and duplicate raw photos are rejected without partial persistent state.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-005 - Admin prelaunch authorization
**Origin:** Feature `QA-0001`  
**Expected:** Admin list/review/photo/moderation/migration endpoints are inaccessible without a valid authenticated administrator session.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-006 - Approval requires approved photo
**Origin:** Feature `QA-0001`  
**Expected:** A DRAFT profile with zero approved photos cannot be approved/migrated; one or more approved photos satisfies the media prerequisite.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-007 - Migration is one-time
**Origin:** Feature `QA-0001`  
**Expected:** Concurrent/repeated approval attempts create at most one member for a prelaunch profile.  
**Automation:** Integration/manual pending automation  
**Last result:** NOT RUN

### REG-PRE-008 - Migration rollback consistency
**Origin:** Feature `QA-0001`  
**Expected:** A migration failure rolls back DB changes and removes S3 objects created by that failed attempt where cleanup succeeds.  
**Automation:** Integration/manual fault injection  
**Last result:** NOT RUN

### REG-PRE-009 - Relationship and gender contract
**Origin:** Feature `QA-0001`, finding `QA-PRE-002`  
**Expected:** SELF uses a valid submitted gender; SON/BROTHER resolve to MALE; DAUGHTER/SISTER resolve to FEMALE; unsupported relationship values fail validation cleanly.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-010 - Responsive form states
**Origin:** Feature `QA-0001`  
**Expected:** Desktop/tablet/mobile layouts, dependent dropdowns, validation, loading, duplicate-submit prevention, back/refresh and large-photo save states remain usable and consistent.  
**Automation:** Manual / Playwright candidate  
**Last result:** NOT RUN

## International Location Hierarchy

### REG-MASTER-001 - Country dependent location options
**Origin:** Canada international-location rollout

**Expected:** India remains the default. Changing country clears stale state/city values and loads only active states for the newest country request; changing state loads only active cities for the newest state request.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-002 - Location hierarchy tampering
**Origin:** Canada international-location rollout

**Expected:** Prelaunch and live member saves reject a country, state and city combination when the state does not belong to the country or the city does not belong to the state.

**Automation:** Integration test pending

**Last result:** NOT RUN

### REG-MASTER-003 - Migrated Canadian profile editing
**Origin:** Canada international-location rollout

**Expected:** A Canadian prelaunch profile can migrate and then reopen/save Basic Details, Family Details and Sikh/Religious birth location without being forced back to India.

**Automation:** End-to-end/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-004 - International Search and Partner Preference
**Origin:** Canada international-location rollout

**Expected:** Search and Partner Preference show active Canadian provinces/territories with country-qualified labels and load cities for selected Canadian states.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

## Cross-module Database Integrity

No dedicated permanent DB cases recorded yet. Prelaunch migration integrity is covered by `REG-PRE-007` and `REG-PRE-008` pending DDL baseline verification.

## Cross-module Security

### REG-SEC-001 - Migrated accounts do not share one reusable login secret
**Origin:** Feature `QA-0001`, finding `QA-PRE-001`  
**Expected:** Initial access for one migrated member cannot be reused to authenticate another migrated member; bootstrap access must be member-specific or prove control of that member's verified contact.  
**Automation:** Authentication integration/manual pending automation  
**Last result:** NOT RUN

### REG-SEC-003 - Migrated-member password setup isolation
**Origin:** Prelaunch migration, Account Settings and Forgot Password

**Expected:** Public Forgot Password and authenticated migrated-member password
setup have separate session flow identifiers and separate OTP purposes.

Public Forgot Password uses `PASSWORD_RESET`. Authenticated initial-password
creation uses `PASSWORD_SETUP`. Starting either flow clears previous temporary
password authorization state in that browser.

The authenticated setup route accepts no user, mobile or email identifier. The
member ID comes only from `auth_user_id`, and the service resolves that member's
verified primary mobile. Every verify, resend, password-form and password-update
step confirms that the authenticated member still matches the setup member.

An OTP issued for `PASSWORD_RESET` cannot verify or authorize
`PASSWORD_SETUP`, and an OTP issued for `PASSWORD_SETUP` cannot verify or
authorize `PASSWORD_RESET`.

After successful password creation or reset, the browser session is destroyed
and the member must log in using the new password.

**Required cases:**

1. Public reset OTP cannot be entered in a prelaunch setup flow.
2. Prelaunch setup OTP cannot be entered in public Forgot Password.
3. Starting public reset does not retain authenticated setup session state.
4. Starting authenticated setup does not retain public reset state.
5. Direct setup POST without authentication is rejected.
6. Setup rejects another member ID, mobile number or email input.
7. Setup rejects a non-migrated member.
8. Setup rejects a migrated member whose password is already set.
9. Setup rejects an unverified or non-primary mobile contact.
10. Logout or authenticated-user change invalidates an active setup flow.
11. OTP expiry, resend cooldown, attempt limit and daily quota remain enforced.
12. A consumed OTP cannot be reused during concurrent password submissions.
13. Successful password creation destroys the authenticated session.
14. Account Settings no longer shows setup guidance after password creation.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-005 - Dashboard Match View All navigation
**Origin:** Member Dashboard and Matches navigation

**Expected:** Every Dashboard match/activity section displays View All at the
top-right when that section contains at least one profile.

The destinations are:

- All Matches → `web.matches`
- New Matches → `activity=new-profiles`
- Profiles Shortlisted By You → `activity=shortlisted-by-you`
- Who Shortlisted You → `activity=shortlisted-you`
- Who Viewed Your Profile → `activity=viewed-you`
- Profiles You Viewed → `activity=viewed-by-you`

Each destination uses the existing Match/Search service collection and does not
create a separate profile query. The Matches header remains active for every
destination.

View All is hidden when a section has no records. With one record, View All is
shown and carousel controls are hidden. With two or more records, View All and
carousel controls are shown.

**Required cases:**

1. A section with zero profiles does not display View All.
2. A section with one profile displays View All without carousel controls.
3. A section with two or more profiles displays View All and carousel controls.
4. Every View All destination opens the corresponding complete collection.
5. Query-string manipulation is restricted by the existing activity allowlist.
6. Blocked and admin-actioned reported profiles remain excluded.
7. The Matches header remains active for all six destinations.
8. Desktop and mobile section headers remain aligned.
9. View All links have visible keyboard focus and meaningful accessible text.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-006 - Thumbnail member account type
**Origin:** Shared member presentation and Dashboard ProfileThumbnail

**Expected:** Every Dashboard ProfileThumbnail receives its account type through
the backend `MemberProfilePresentationService` contract. The view does not
hardcode or independently determine the account type.

Until subscription entitlements are implemented, the backend supplies
`Free Account`. ProfileThumbnail displays `Account: Free Account`.

The existing logged-in-member `accountPlan` Dashboard value must not be reused
for candidate thumbnails because it represents the viewer rather than the
member displayed by the thumbnail.

**Required cases:**

1. Every Dashboard collection supplies `accountType`.
2. ProfileThumbnail displays `Account: Free Account`.
3. Missing or empty `accountType` does not produce an empty Account label.
4. Dynamic output is escaped.
5. Search ProfileCard and ProfileInterestCard remain visually unchanged.
6. Blocked and globally hidden reported profiles remain excluded.
7. Mobile and desktop thumbnail layouts remain aligned.

**Automation:** Unit/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-007 - Profile card account and verification indicators
**Origin:** Common member presentation, ProfileCard and ProfileInterestCard

**Expected:** ProfileCard and ProfileInterestCard display the backend-supplied
account type. Until subscription entitlement integration is implemented, the
backend supplies `Free Account`.

Both cards display one bottom verification row containing badges only for
successfully verified profile attributes:

- Mobile Verified
- Email Verified
- Aadhaar Verified
- Selfie Verified

Verification values are loaded by MemberMatchCandidateModel and normalized by
MemberProfilePresentationService using the existing BooleanValue support class.
Views do not interpret raw PostgreSQL booleans and do not determine verification
state.

Unverified, pending, rejected and missing verification values must not be shown
as verified.

**Required cases:**

1. ProfileCard displays the backend-supplied account type.
2. ProfileInterestCard displays the backend-supplied account type.
3. A verified primary mobile displays Mobile Verified.
4. A verified primary email displays Email Verified.
5. A missing or unverified email does not display Email Verified.
6. Approved Aadhaar displays Aadhaar Verified.
7. Under-review, rejected or missing Aadhaar does not display Aadhaar Verified.
8. Verified Selfie displays Selfie Verified.
9. Unverified Selfie does not display Selfie Verified.
10. All four verified badges remain in one horizontally scrollable row on
    narrow screens.
11. Dynamic labels and icon classes are escaped.
12. Blocked and globally hidden reported members remain excluded.
13. Search pagination does not duplicate candidates after contact joins.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-007 - Profile card account and verification indicators
**Origin:** Shared member presentation, ProfileCard and ProfileInterestCard

**Expected:** ProfileCard and ProfileInterestCard use the same member-summary
hierarchy.

Both cards display the backend-supplied Account Type immediately below the
member photo. Interest status remains at the top-right of ProfileInterestCard.

Both cards render the shared VerificationBadges component outside card-body so
the verification strip occupies the complete card width. Only successfully
verified attributes appear.

ProfileInterestCard retains its Interest-specific behaviour:

- Pending received Interest displays Decline and Accept Interest.
- Sent, accepted and declined Interests display View Profile.
- All forms retain CSRF protection and the existing submit-loader contract.

**Required cases:**

1. Account Type appears below the image in both card types.
2. Missing Account Type does not leave empty badge spacing.
3. Verification strip spans the complete card width.
4. Verification strip is hidden when no verification is true.
5. Mobile, Email, Aadhaar and Selfie states match backend values.
6. Pending received Interest retains Accept and Decline actions.
7. Non-actionable Interest retains View Profile.
8. Status badge remains visible at the top-right.
9. Desktop and mobile layouts remain aligned.
10. Blocked and admin-hidden reported members remain excluded.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

## Retired cases

Keep retired cases here or retain their original section with status `RETIRED` and the reason. Do not erase regression history without explanation.


---

## 5. Regression-suite changes

Location: `/docs/qa/REGRESSION_SUITE.md`

Replace the verified-attribute list:

```markdown
- Mobile Verified
- Email Verified
- Aadhaar Verified
- Selfie Verified
```

With:

```markdown
- Mobile Verified
- Email Verified
- Aadhaar Verified
- Video Introduction
```

Replace the Selfie regression cases:

```markdown
8. Verified Selfie displays Selfie Verified.
9. Unverified Selfie does not display Selfie Verified.
```

With:

```markdown
8. An active approved Video Introduction displays its badge.
9. Processing, pending, rejected, resubmission-requested and deleted videos do not display the badge.
```

Replace:

```markdown
5. Mobile, Email, Aadhaar and Selfie states match backend values.
```

With:

```markdown
5. Mobile, Email, Aadhaar and Video Introduction states match backend values.
```

The resulting verification section should contain:

```markdown
Profile components display the following successfully verified profile attributes:

- Mobile Verified
- Email Verified
- Aadhaar Verified
- Video Introduction

Verification values are loaded by MemberMatchCandidateModel and normalized by MemberProfilePresentationService using the existing BooleanValue support class.

1. Verified primary mobile displays Mobile Verified.
2. Unverified primary mobile does not display Mobile Verified.
3. Verified primary email displays Email Verified.
4. Boolean database forms such as `true`, `t`, `1`, and `yes` are normalized as verified.
5. A missing or unverified email does not display Email Verified.
6. Approved Aadhaar displays Aadhaar Verified.
7. Under-review, rejected or missing Aadhaar does not display Aadhaar Verified.
8. An active approved Video Introduction displays its badge.
9. Processing, pending, rejected, resubmission-requested and deleted videos do not display the badge.
10. All four verified badges remain in one horizontally scrollable row on narrow screens.
11. Dynamic labels and icon classes are escaped.
```

---

# Server setup

## 6. Install FFmpeg on Ubuntu EC2

Run:

```bash
sudo apt-get update
sudo apt-get install -y ffmpeg
```

Verify:

```bash
/usr/bin/ffmpeg -version
/usr/bin/ffprobe -version
```

Verify the codecs used by the worker:

```bash
/usr/bin/ffmpeg -encoders | grep libx264
/usr/bin/ffmpeg -encoders | grep aac
```

The application configuration expects:

```ini
videoIntroduction.ffmpegBinary = /usr/bin/ffmpeg
videoIntroduction.ffprobeBinary = /usr/bin/ffprobe
```

---

## 7. Create the private worker directory

QA:

```bash
cd /var/www/sikhanandkaraj-qa

sudo mkdir -p writable/video-introduction
sudo chown -R www-data:www-data writable/video-introduction
sudo chmod 750 writable/video-introduction
```

Production:

```bash
cd /var/www/sikhanandkaraj

sudo mkdir -p writable/video-introduction
sudo chown -R www-data:www-data writable/video-introduction
sudo chmod 750 writable/video-introduction
```

Do not use:

```bash
chmod 777
```

The `writable` directory must remain outside the Apache document root. The application’s Apache `DocumentRoot` must continue pointing to:

```text
/var/www/sikhanandkaraj/public
```

---

## 8. PHP upload limits

A 40 MB application limit requires PHP and Apache to permit a slightly larger request.

Find the active configuration:

```bash
php --ini
php -r "echo php_ini_loaded_file(), PHP_EOL;"
```

Update the Apache PHP configuration:

```ini
upload_max_filesize = 45M
post_max_size = 50M
max_file_uploads = 10
max_execution_time = 120
max_input_time = 120
memory_limit = 256M
```

Update the CLI PHP configuration with compatible limits because the background worker runs through CLI PHP:

```ini
memory_limit = 512M
max_execution_time = 0
```

Restart Apache:

```bash
sudo apachectl configtest
sudo systemctl restart apache2
```

Verify:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
```

To inspect the Apache PHP configuration rather than CLI configuration, temporarily use an internal diagnostic or inspect the correct Apache `php.ini`. Do not leave `phpinfo()` publicly accessible.

---

# AWS setup

## 9. S3 bucket settings

The existing member-media bucket may be reused. A separate bucket is not required.

The feature uses these private prefixes:

```text
members/video-introduction/original/
members/video-introduction/playback/
members/video-introduction/poster/
```

In the AWS Console:

1. Open S3.
2. Select the existing member-media bucket.
3. Open **Permissions**.
4. Keep all four **Block Public Access** options enabled.
5. Ensure Object Ownership is **Bucket owner enforced**.
6. Do not add public ACLs.
7. Do not configure S3 static website hosting.
8. Keep default encryption enabled, preferably SSE-S3 or SSE-KMS.

Because videos are uploaded by the PHP server, browser-to-S3 CORS configuration is not required for this implementation.

---

## 10. EC2 IAM permissions

Attach the following policy to the EC2 instance role used by the QA or production application.

Replace:

```text
YOUR_PRIVATE_MEDIA_BUCKET
```

With the actual bucket name.

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "ListVideoIntroductionPrefix",
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::YOUR_PRIVATE_MEDIA_BUCKET"
      ],
      "Condition": {
        "StringLike": {
          "s3:prefix": [
            "members/video-introduction/*"
          ]
        }
      }
    },
    {
      "Sid": "ManageVideoIntroductionObjects",
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": [
        "arn:aws:s3:::YOUR_PRIVATE_MEDIA_BUCKET/members/video-introduction/*"
      ]
    }
  ]
}
```

If the bucket uses a customer-managed KMS key, the EC2 role also needs:

```json
{
  "Sid": "UsePrivateMediaKmsKey",
  "Effect": "Allow",
  "Action": [
    "kms:Decrypt",
    "kms:Encrypt",
    "kms:GenerateDataKey"
  ],
  "Resource": "arn:aws:kms:REGION:ACCOUNT_ID:key/KMS_KEY_ID"
}
```

Prefer an EC2 instance role. Do not store permanent AWS access keys in `.env` when an instance role is available.

---

## 11. CloudFront Origin Access Control

Use the project’s existing private-media CloudFront distribution if it already serves approved member photographs securely.

In CloudFront:

1. Open the existing private-media distribution.
2. Open **Origins**.
3. Select the S3 origin.
4. Edit the origin.
5. Choose **Origin access control settings**.
6. Select the existing OAC or create one.
7. Set signing behavior to **Sign requests**.
8. Save the origin.
9. Update the S3 bucket policy when prompted.

AWS recommends Origin Access Control for restricting S3 origins. See the official [CloudFront OAC documentation](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-restricting-access-to-s3.html).

Example S3 bucket policy statement:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowCloudFrontReadPrivateMedia",
      "Effect": "Allow",
      "Principal": {
        "Service": "cloudfront.amazonaws.com"
      },
      "Action": [
        "s3:GetObject"
      ],
      "Resource": [
        "arn:aws:s3:::YOUR_PRIVATE_MEDIA_BUCKET/members/video-introduction/*"
      ],
      "Condition": {
        "StringEquals": {
          "AWS:SourceArn": "arn:aws:cloudfront::AWS_ACCOUNT_ID:distribution/DISTRIBUTION_ID"
        }
      }
    }
  ]
}
```

Do not grant:

```json
"Principal": "*"
```

---

## 12. CloudFront cache behavior

Create or update a cache behavior:

```text
Path pattern:
members/video-introduction/*
```

Recommended values:

```text
Origin:
Private S3 media origin

Viewer protocol policy:
Redirect HTTP to HTTPS

Allowed HTTP methods:
GET, HEAD

Cache policy:
CachingOptimized or existing private-media policy

Compress objects automatically:
Yes

Restrict viewer access:
Yes

Trusted authorization type:
Trusted key groups

Trusted key group:
Existing private-media key group
```

Do not use query-string forwarding unless the existing signed-media architecture requires it.

The CloudFront signed URL contains authorization query parameters generated by the existing `CloudFrontService`.

---

## 13. CloudFront key group

If the existing media distribution already uses signed URLs, reuse its trusted key group.

Otherwise:

1. Generate an RSA private/public key pair securely.
2. Upload only the public key to CloudFront.
3. Create a CloudFront key group containing that public key.
4. Associate the key group with the Video Introduction cache behavior.
5. Store the private key only on the application server.

AWS’s signed-URL setup is documented under [trusted signers and trusted key groups](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-trusted-signers.html).

Example generation:

```bash
openssl genrsa -out cloudfront-video-private.pem 2048
openssl rsa \
  -pubout \
  -in cloudfront-video-private.pem \
  -out cloudfront-video-public.pem
```

Do not commit either key to Git.

Upload only:

```text
cloudfront-video-public.pem
```

To CloudFront.

---

## 14. Install the CloudFront private key

Example server path:

```text
/etc/sikhanandkaraj/cloudfront/private-key.pem
```

Create the directory:

```bash
sudo mkdir -p /etc/sikhanandkaraj/cloudfront
```

Copy the private key securely, then set ownership:

```bash
sudo chown root:www-data \
  /etc/sikhanandkaraj/cloudfront/private-key.pem

sudo chmod 640 \
  /etc/sikhanandkaraj/cloudfront/private-key.pem
```

Verify that the web/worker user can read it:

```bash
sudo -u www-data test \
  -r /etc/sikhanandkaraj/cloudfront/private-key.pem \
  && echo "CloudFront key is readable"
```

Production `.env`:

```ini
memberMedia.cloudFrontDomain = YOUR_DISTRIBUTION.cloudfront.net
memberMedia.cloudFrontKeyPairId = YOUR_CLOUDFRONT_PUBLIC_KEY_ID
memberMedia.cloudFrontPrivateKeyPath = /etc/sikhanandkaraj/cloudfront/private-key.pem
```

Use the project’s existing `memberMedia` configuration names. Do not create a second incompatible CloudFront configuration.

---

## 15. S3 lifecycle rules

Do not configure a broad S3 rule that deletes every object under:

```text
members/video-introduction/
```

That would delete approved active videos.

The application cleanup worker deletes:

- Deleted videos after 24 hours.
- Rejected or resubmission-requested videos after 14 days.
- Replaced, inactive videos after 7 days.

A safe optional S3 rule is to abort incomplete multipart uploads:

```json
{
  "Rules": [
    {
      "ID": "AbortIncompleteVideoUploads",
      "Status": "Enabled",
      "Filter": {
        "Prefix": "members/video-introduction/"
      },
      "AbortIncompleteMultipartUpload": {
        "DaysAfterInitiation": 1
      }
    }
  ]
}
```

AWS lifecycle examples are available in the official [S3 lifecycle documentation](https://docs.aws.amazon.com/AmazonS3/latest/userguide/lifecycle-configuration-examples.html).

---

# Environment configuration

## 16. QA `.env`

Use:

```ini
videoIntroduction.maximumUploadSizeKb = 40960
videoIntroduction.lockDays = 7
videoIntroduction.playbackUrlTtlSeconds = 300
videoIntroduction.maximumProcessingAttempts = 3
videoIntroduction.ffmpegBinary = /usr/bin/ffmpeg
videoIntroduction.ffprobeBinary = /usr/bin/ffprobe
videoIntroduction.consentVersion = 2026-08-19
```

Also confirm existing AWS configuration:

```ini
aws.region = ap-south-1
aws.s3.bucket = YOUR_QA_PRIVATE_MEDIA_BUCKET

memberMedia.cloudFrontDomain = YOUR_QA_DISTRIBUTION.cloudfront.net
memberMedia.cloudFrontKeyPairId = YOUR_QA_PUBLIC_KEY_ID
memberMedia.cloudFrontPrivateKeyPath = /etc/sikhanandkaraj/cloudfront/private-key.pem
```

If EC2 uses an instance role, leave static access keys empty:

```ini
aws.accessKeyId =
aws.secretAccessKey =
```

---

## 17. Production `.env`

Use:

```ini
videoIntroduction.maximumUploadSizeKb = 40960
videoIntroduction.lockDays = 7
videoIntroduction.playbackUrlTtlSeconds = 300
videoIntroduction.maximumProcessingAttempts = 3
videoIntroduction.ffmpegBinary = /usr/bin/ffmpeg
videoIntroduction.ffprobeBinary = /usr/bin/ffprobe
videoIntroduction.consentVersion = 2026-08-19
```

Confirm:

```ini
aws.region = ap-south-1
aws.s3.bucket = YOUR_PRODUCTION_PRIVATE_MEDIA_BUCKET

memberMedia.cloudFrontDomain = YOUR_PRODUCTION_DISTRIBUTION.cloudfront.net
memberMedia.cloudFrontKeyPairId = YOUR_PRODUCTION_PUBLIC_KEY_ID
memberMedia.cloudFrontPrivateKeyPath = /etc/sikhanandkaraj/cloudfront/private-key.pem
```

The production CloudFront private key must not be copied from QA unless the same key group is deliberately shared.

---

# Cron and logging

## 18. Production cron

Edit the web/worker user crontab:

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/sikhanandkaraj && /usr/bin/flock -n /var/lock/sikhanandkaraj/video-introduction-worker.lock /usr/bin/php scripts/video_introduction_worker.php 20 >> writable/logs/video-introduction-worker.log 2>&1
20 3 * * * cd /var/www/sikhanandkaraj && /usr/bin/flock -n /var/lock/sikhanandkaraj/video-introduction-cleanup.lock /usr/bin/php scripts/video_introduction_cleanup.php >> writable/logs/video-introduction-cleanup.log 2>&1
```

Create the lock and log locations:

```bash
sudo mkdir -p /var/lock/sikhanandkaraj

sudo chown www-data:www-data \
  /var/lock/sikhanandkaraj

sudo chmod 750 \
  /var/lock/sikhanandkaraj

cd /var/www/sikhanandkaraj

sudo touch \
  writable/logs/video-introduction-worker.log \
  writable/logs/video-introduction-cleanup.log

sudo chown www-data:www-data \
  writable/logs/video-introduction-worker.log \
  writable/logs/video-introduction-cleanup.log

sudo chmod 640 \
  writable/logs/video-introduction-worker.log \
  writable/logs/video-introduction-cleanup.log
```

---

## 19. Log rotation

Location: `/etc/logrotate.d/sikhanandkaraj-video-introduction`

Create:

```conf
/var/www/sikhanandkaraj/writable/logs/video-introduction-worker.log
/var/www/sikhanandkaraj/writable/logs/video-introduction-cleanup.log {
    daily
    rotate 7
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    create 0640 www-data www-data
}
```

Test:

```bash
sudo logrotate -d \
  /etc/logrotate.d/sikhanandkaraj-video-introduction
```

---

# Deployment order

## 20. QA deployment sequence

Use this order:

1. Deploy the application code.
2. Install FFmpeg.
3. Configure private S3 and CloudFront access.
4. Configure `.env`.
5. Verify PHP upload limits.
6. Verify worker directory permissions.
7. Run migration `022`.
8. Start the processing cron.
9. Start the cleanup cron.
10. Perform end-to-end QA.

Run the migration using the project’s numbered increment process.

Example:

```bash
cd /var/www/sikhanandkaraj-qa

sudo -u postgres psql \
  -d sikhanandkaraj_qa \
  -v ON_ERROR_STOP=1 \
  -f database/022_member_video_introductions.sql
```

Verify tables:

```sql
SELECT
    table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name IN (
      'member_video_introductions',
      'member_video_processing_jobs',
      'member_video_moderation_history'
  )
ORDER BY table_name;
```

Expected result:

```text
member_video_introductions
member_video_moderation_history
member_video_processing_jobs
```

---

## 21. Manual worker validation

After recording one test video:

```sql
SELECT
    id,
    public_id,
    member_user_id,
    moderation_status,
    source_mime_type,
    source_size_bytes,
    submitted_at,
    locked_until
FROM member_video_introductions
ORDER BY id DESC
LIMIT 10;
```

Check the queued job:

```sql
SELECT
    id,
    video_introduction_id,
    status,
    attempt_count,
    available_at,
    locked_at,
    locked_by,
    last_error
FROM member_video_processing_jobs
ORDER BY id DESC
LIMIT 10;
```

Run:

```bash
cd /var/www/sikhanandkaraj-qa

sudo -u www-data \
  /usr/bin/php \
  scripts/video_introduction_worker.php \
  10
```

Expected output:

```text
Processed 1 Video Introduction job(s).
```

Check processing result:

```sql
SELECT
    public_id,
    moderation_status,
    duration_seconds,
    video_codec,
    audio_codec,
    width,
    height,
    playback_object_key,
    poster_object_key,
    processing_error
FROM member_video_introductions
ORDER BY id DESC
LIMIT 10;
```

Expected state:

```text
moderation_status = PENDING_REVIEW
playback_object_key is not null
poster_object_key is not null
duration_seconds is between 15 and 30.5
```

---

# Required QA scenarios

## 22. Requirement and UI QA

Test:

1. Video Introduction appears in Account Settings.
2. No active Selfie menu or Selfie wording remains.
3. Consent must be accepted before camera activation.
4. Camera and microphone permissions are requested only after consent.
5. Recording cannot be stopped before 15 seconds.
6. Recording automatically stops at 30 seconds.
7. Countdown updates while recording.
8. Member can preview before submission.
9. Member can retake before submission.
10. Video does not autoplay.
11. Prompts and moderation conditions are visible.
12. Seven-day delete/replace restriction is visible.
13. Submission success explains that background processing continues.
14. Approved profile displays the Video Introduction badge.
15. Hidden video keeps its badge but explains that the member has hidden it.

---

## 23. Privacy QA

Test with a male owner:

1. `VISIBLE_PRO` permits Pro viewers.
2. `VISIBLE_PRO` rejects non-Pro viewers.
3. Accepted-interest visibility permits either accepted Interest direction.
4. Accepted-interest visibility rejects members without an accepted Interest.
5. Hidden visibility rejects all other members.
6. Owner can still preview a hidden video.

Test with a female owner:

1. Default visibility is accepted Interest.
2. Pro-wide option does not appear.
3. A forged POST using `VISIBLE_PRO` is rejected server-side.
4. Accepted Interest permits playback.
5. Hidden setting denies playback.

Also test:

1. Block relationship denies playback.
2. Globally hidden/reported profile denies playback.
3. Same-gender playback is denied by the current matchmaking rule.
4. Deleted video cannot be played.
5. Expired CloudFront URL fails.
6. Raw S3 object URL fails.

---

## 24. Moderation QA

Test all decisions:

### Approve

```text
PENDING_REVIEW → APPROVED
is_active = TRUE
badge displayed
notification created
```

### Reject

```text
PENDING_REVIEW → REJECTED
is_active = FALSE
reason required
member can record corrective submission
notification created
```

### Request resubmission

```text
PENDING_REVIEW → RESUBMISSION_REQUESTED
is_active = FALSE
reason required
member can record corrective submission
notification created
```

Test concurrent Admin decisions:

1. Open the same pending video in two Admin sessions.
2. Approve in the first session.
3. Attempt Reject in the second session.
4. The second action must be rejected because the video is no longer pending.

---

## 25. Processing QA

Upload or record:

- 14-second video.
- Exactly 15-second video.
- 20-second video.
- Exactly 30-second video.
- Longer than 30 seconds.
- Video without audio.
- Audio without video.
- Corrupt file renamed to `.webm`.
- Valid WebM.
- Valid MP4.
- Maximum-size recording.
- File larger than the configured limit.

Expected behavior:

- Browser duration is for user experience only.
- FFprobe performs authoritative duration and track validation.
- Invalid recordings eventually become `PROCESSING_FAILED`.
- Member receives a failure notification.
- Member can record again.
- Temporary processing failures retry up to three times.

---

# Important limitations and improvements

## 26. Download prevention limitation

The implementation makes downloading difficult by:

- Keeping S3 private.
- Using authenticated application authorization.
- Issuing short-lived CloudFront URLs.
- Avoiding raw S3 URLs.
- Avoiding public object access.
- Avoiding autoplay.
- Using inline content disposition.

However, a video displayed in a browser cannot be made impossible to copy. An authorized viewer can still use:

- Browser developer tools.
- Screen recording.
- Operating-system capture tools.
- External recording devices.

The privacy notice should therefore continue to state:

```text
Do not copy, share or misuse this member's personal video.
```

---

## 27. Recommended future improvements

Before high-volume production usage, consider:

1. Moving processing from the web EC2 instance to SQS plus a dedicated worker.
2. Using AWS MediaConvert instead of local FFmpeg when volume increases.
3. Adding malware/media scanning before processing.
4. Adding automated speech/transcript checks for phone numbers and social handles.
5. Adding face-count detection to flag recordings containing multiple people.
6. Adding CloudWatch alarms for failed/stale processing jobs.
7. Adding Admin moderation queue counts to the navigation.
8. Adding a poster-image preview in Account Settings.
9. Adding structured rejection reasons plus optional Admin notes.
10. Replacing `users.is_paid` with explicit plan entitlement checks when the Pro-plan tables are introduced.

---

## 28. Validation completed in the workspace

Completed successfully:

```text
git diff --check
node --check public/assets/js/pages/video-introduction-recorder.js
node --check public/assets/js/pages/video-introduction-playback.js
```

Also confirmed:

```text
FFmpeg 6.1.1 available
FFprobe available
No whitespace errors in the implementation patch
```

Not completed because the workspace did not provide the required runtime/services:

```text
PHP syntax execution
PostgreSQL migration execution
CodeIgniter integration tests
Browser camera/microphone testing
Real S3 upload/download testing
CloudFront signed playback testing
Admin moderation browser testing
```

Therefore, the correct QA status remains:

```text
NOT PASSED — QA deployment and end-to-end testing required
```

The complete implementation patch remains available at:

[video-introduction-implementation.patch](sandbox:/workspace/scratch/18ee2c643a55/sikhanandkaraj-webapp/video-introduction-implementation.patch)
