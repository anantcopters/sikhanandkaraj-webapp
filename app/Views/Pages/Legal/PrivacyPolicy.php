<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 * @var string|null $effectiveDate
 */

$resolvedEffectiveDate = isset($effectiveDate)
    && is_string($effectiveDate)
    && trim($effectiveDate) !== ''
    ? trim($effectiveDate)
    : '06 August 2026';

$this->setVar(
    'footerView',
    'Components/Home/Footer'
)->extend(
    'Layouts/Main'
);

$this->section('content');
?>
<section class="section py-5 light-yellowish">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <header class="text-center mb-4">
                    <p class="fs-13 fw-semibold text-danger text-uppercase mb-2">
                        Your information and privacy
                    </p>

                    <h1 class="fs-36 fw-bold mb-3">
                        Privacy Policy
                    </h1>

                    <p class="text-secondary mb-0">
                        Effective date:
                        <?= esc($resolvedEffectiveDate) ?>
                    </p>
                </header>

                <article class="card border border-danger border-opacity-25 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <section
                            class="mb-3"
                            aria-labelledby="privacy-introduction">

                            <h2 id="privacy-introduction" class="fs-22 fw-semibold mb-3">
                                1. Introduction
                            </h2>

                            <p class="lh-lg">
                                This Privacy Policy explains how Sikhanandkaraj
                                collects, uses, stores, shares and protects personal
                                information when you access or use our website,
                                applications and related matrimonial services.
                            </p>

                            <p class="lh-lg mb-0">
                                It should be read together with our
                                <a href="<?= route_to('web.legal.terms') ?>">
                                    Terms and Conditions
                                </a>.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-scope">

                            <h2 id="privacy-scope" class="fs-22 fw-semibold mb-3">
                                2. Scope
                            </h2>

                            <p>
                                This Policy applies to personal information
                                processed through the Platform, including
                                information submitted by:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    A person creating their own matrimonial profile.
                                </li>

                                <li class="mb-2">
                                    A parent, guardian, sibling or authorised family
                                    member managing a profile.
                                </li>

                                <li class="mb-2">
                                    A visitor who contacts support or submits
                                    feedback.
                                </li>

                                <li>
                                    A field officer or authorised representative
                                    who assists with profile creation.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-information">

                            <h2 id="privacy-information" class="fs-22 fw-semibold mb-3">
                                3. Information we collect
                            </h2>

                            <h3 class="fs-20 fw-medium mb-3">
                                3.1 Account and contact information
                            </h3>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Name, date of birth and gender.
                                </li>

                                <li class="mb-2">
                                    Mobile number and email address.
                                </li>

                                <li class="mb-2">
                                    Login, verification and account-status
                                    information.
                                </li>

                                <li>
                                    Profile reference number and registration date.
                                </li>
                            </ul>

                            <h3 class="fs-20 fw-medium mb-3">
                                3.2 Matrimonial profile information
                            </h3>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Education, occupation, income and organisation.
                                </li>

                                <li class="mb-2">
                                    Religion, community and family information.
                                </li>

                                <li class="mb-2">
                                    Location, lifestyle and personal-preference
                                    information.
                                </li>

                                <li class="mb-2">
                                    Photographs and profile descriptions.
                                </li>

                                <li>
                                    Partner preferences and matchmaking criteria.
                                </li>
                            </ul>

                            <h3 class="fs-20 fw-medium mb-3">
                                3.3 Verification and safety information
                            </h3>

                            <p>
                                Where necessary and lawfully permitted, we may
                                collect information used to validate profile
                                authenticity, investigate abuse, respond to
                                grievances or protect members.
                            </p>

                            <h3 class="fs-20 fw-medium mb-3">
                                3.4 Usage and technical information
                            </h3>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    IP address, browser and device information.
                                </li>

                                <li class="mb-2">
                                    Login dates, session data and security events.
                                </li>

                                <li class="mb-2">
                                    Pages viewed and Platform interactions.
                                </li>

                                <li>
                                    Error, performance and diagnostic information.
                                </li>
                            </ul>

                            <h3 class="fs-20 fw-medium">
                                3.5 Communications
                            </h3>

                            <p>
                                We may process support enquiries, complaints,
                                feedback, consent records and communications sent
                                through Platform features.
                            </p>

                            <p>
                                Private member communications should be accessed
                                only where necessary for safety, support, fraud
                                prevention, legal compliance or enforcement of our
                                Terms.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-source">

                            <h2 id="privacy-source" class="fs-22 fw-semibold mb-3">
                                4. How information is collected
                            </h2>

                            <p>
                                We collect information:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Directly from you or your authorised family
                                    representative.
                                </li>

                                <li class="mb-2">
                                    When you create, edit or verify a profile.
                                </li>

                                <li class="mb-2">
                                    When you upload photographs or use Platform
                                    features.
                                </li>

                                <li class="mb-2">
                                    Automatically through security logs, cookies
                                    and similar technologies.
                                </li>

                                <li class="mb-2">
                                    From authorised field officers or service
                                    providers assisting with registration.
                                </li>

                                <li>
                                    From another member when they submit a genuine
                                    safety or grievance report.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-purpose">

                            <h2 id="privacy-purpose" class="fs-22 fw-semibold mb-3">
                                5. How we use information
                            </h2>

                            <p>
                                Personal information may be used to:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Create, verify and maintain member accounts.
                                </li>

                                <li class="mb-2">
                                    Display matrimonial profiles according to
                                    selected visibility settings.
                                </li>

                                <li class="mb-2">
                                    Generate matches and recommendations.
                                </li>

                                <li class="mb-2">
                                    Enable interests, messages and notifications.
                                </li>

                                <li class="mb-2">
                                    Moderate photographs and profile content.
                                </li>

                                <li class="mb-2">
                                    Prevent impersonation, fraud, abuse and
                                    unauthorised access.
                                </li>

                                <li class="mb-2">
                                    Provide member support and resolve grievances.
                                </li>

                                <li class="mb-2">
                                    Process subscriptions and payments.
                                </li>

                                <li class="mb-2">
                                    Improve security, performance and user
                                    experience.
                                </li>

                                <li>
                                    Meet legal, regulatory and record-keeping
                                    obligations.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-basis">

                            <h2 id="privacy-basis" class="fs-22 fw-semibold mb-3">
                                6. Consent and lawful processing
                            </h2>

                            <p>
                                We process personal information with your consent,
                                for purposes connected with the service you request,
                                and where processing is otherwise permitted or
                                required under applicable law.
                            </p>

                            <p>
                                You may withdraw consent for consent-based
                                processing by contacting us or using an available
                                account control. Withdrawal does not affect
                                processing already lawfully completed and may limit
                                our ability to continue providing features that
                                require the information.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-display">

                            <h2 id="privacy-display" class="fs-22 fw-semibold mb-3">
                                7. Profile visibility
                            </h2>

                            <p>
                                Matrimonial profile information is intended to be
                                shown to eligible members according to Platform
                                access rules and the visibility settings available
                                to you.
                            </p>

                            <p>
                                Depending on the selected settings, photographs may
                                be visible to:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Eligible registered members.
                                </li>

                                <li class="mb-2">
                                    Members whose interest has been accepted.
                                </li>

                                <li>
                                    Authorised administrators performing
                                    moderation.
                                </li>
                            </ul>

                            <p>
                                Information marked private will not be intentionally
                                displayed publicly, except where disclosure is
                                required by law or necessary to protect the
                                Platform and its users.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-sharing">

                            <h2 id="privacy-sharing" class="fs-22 fw-semibold mb-3">
                                8. Information sharing
                            </h2>

                            <p>
                                We do not sell member personal information.
                            </p>

                            <p>
                                Information may be shared with:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Other eligible members according to profile
                                    visibility and matchmaking functionality.
                                </li>

                                <li class="mb-2">
                                    Cloud hosting, storage, CDN, email, SMS,
                                    payment, security and support providers.
                                </li>

                                <li class="mb-2">
                                    Professional advisers and auditors under
                                    confidentiality obligations.
                                </li>

                                <li class="mb-2">
                                    Government, regulatory, law-enforcement or
                                    judicial authorities where disclosure is
                                    legally required.
                                </li>

                                <li>
                                    A successor organisation during a lawful
                                    restructuring, merger or transfer, subject to
                                    appropriate protection.
                                </li>
                            </ul>

                            <p>
                                Service providers should receive only the
                                information reasonably necessary to perform their
                                contracted function.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-storage">

                            <h2 id="privacy-storage" class="fs-22 fw-semibold mb-3">
                                9. Storage and international processing
                            </h2>

                            <p>
                                Information may be stored or processed using
                                infrastructure operated by Sikhanandkaraj and its
                                authorised service providers.
                            </p>

                            <p>
                                Where information is processed outside your state
                                or country, reasonable contractual, technical and
                                organisational safeguards will be applied as
                                required by applicable law.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-retention">

                            <h2 id="privacy-retention" class="fs-22 fw-semibold mb-3">
                                10. Data retention
                            </h2>

                            <p>
                                We retain personal information only for as long as
                                reasonably necessary to:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Operate the member account and provide requested
                                    services.
                                </li>

                                <li class="mb-2">
                                    Maintain security, fraud-prevention and consent
                                    records.
                                </li>

                                <li class="mb-2">
                                    Resolve disputes and grievances.
                                </li>

                                <li>
                                    Comply with legal, tax, accounting and
                                    regulatory requirements.
                                </li>
                            </ul>

                            <p>
                                Following account closure, information will be
                                deleted or anonymised when it is no longer required,
                                unless continued retention is permitted or required
                                by law.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-security">

                            <h2 id="privacy-security" class="fs-22 fw-semibold mb-3">
                                11. Information security
                            </h2>

                            <p>
                                We use reasonable technical and organisational
                                safeguards designed to protect information against
                                unauthorised access, misuse, alteration, disclosure
                                or loss.
                            </p>

                            <p>
                                Measures may include:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Access controls and role-based authorisation.
                                </li>

                                <li class="mb-2">
                                    Password hashing and OTP-based verification.
                                </li>

                                <li class="mb-2">
                                    Private media storage and controlled delivery.
                                </li>

                                <li class="mb-2">
                                    Security logging and monitoring.
                                </li>

                                <li class="mb-2">
                                    Data backup and recovery procedures.
                                </li>

                                <li>
                                    Administrative review of sensitive operations.
                                </li>
                            </ul>

                            <p>
                                No internet service can guarantee absolute
                                security. Members should use strong credentials and
                                immediately report suspected account misuse.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-rights">

                            <h2 id="privacy-rights" class="fs-22 fw-semibold mb-3">
                                12. Your privacy choices and rights
                            </h2>

                            <p>
                                Subject to applicable law and verification of the
                                request, you may ask us to:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Provide information about personal data being
                                    processed.
                                </li>

                                <li class="mb-2">
                                    Correct inaccurate or incomplete information.
                                </li>

                                <li class="mb-2">
                                    Update outdated information.
                                </li>

                                <li class="mb-2">
                                    Delete information that is no longer required.
                                </li>

                                <li class="mb-2">
                                    Withdraw consent for consent-based processing.
                                </li>

                                <li class="mb-2">
                                    Close your account.
                                </li>

                                <li>
                                    Address a complaint or grievance.
                                </li>
                            </ul>

                            <p>
                                Certain requests may be limited where retention or
                                processing is necessary for legal compliance,
                                security, fraud prevention, dispute resolution or
                                the rights of another person.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-cookies">

                            <h2 id="privacy-cookies" class="fs-22 fw-semibold mb-3">
                                13. Cookies and similar technologies
                            </h2>

                            <p>
                                The Platform may use cookies and similar
                                technologies for:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Authentication and session management.
                                </li>

                                <li class="mb-2">
                                    Security and fraud prevention.
                                </li>

                                <li class="mb-2">
                                    Remembering user preferences.
                                </li>

                                <li>
                                    Measuring reliability and performance.
                                </li>
                            </ul>

                            <p>
                                Disabling essential cookies may prevent login or
                                other Platform functions from operating correctly.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-children">

                            <h2 id="privacy-children" class="fs-22 fw-semibold mb-3">
                                14. Children
                            </h2>

                            <p>
                                The Platform is not intended for children or for
                                creating matrimonial profiles of persons who have
                                not attained the legally permitted age for
                                marriage.
                            </p>

                            <p>
                                If we learn that an ineligible minor’s information
                                has been submitted, we may restrict the profile and
                                take appropriate steps to delete or otherwise
                                lawfully handle the information.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-third-party">

                            <h2 id="privacy-third-party" class="fs-22 fw-semibold mb-3">
                                15. Third-party links
                            </h2>

                            <p>
                                The Platform may contain links to independently
                                operated websites or services. Their privacy
                                practices are governed by their own policies, and
                                Sikhanandkaraj is not responsible for their
                                independent processing.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="privacy-change">

                            <h2 id="privacy-change" class="fs-22 fw-semibold mb-3">
                                16. Policy updates
                            </h2>

                            <p>
                                We may revise this Policy to reflect changes in law,
                                technology, security practices or Platform
                                functionality.
                            </p>

                            <p>
                                The current version will display its effective date.
                                Material changes will be communicated through an
                                appropriate notice or consent mechanism where
                                required.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4"
                            aria-labelledby="privacy-contact">

                            <h2 id="privacy-contact" class="fs-22 fw-semibold mb-3">
                                17. Contact and grievances
                            </h2>
                            <p>
                                Privacy questions, correction requests, deletion
                                requests and grievances may be sent to:
                            </p>
                            <div class="card bg-light border border-danger border-opacity-25">
                                <div class="card-body">
                                    <address class="lh-lg mb-0">
                                        <strong>
                                            Grievance Officer
                                        </strong>

                                        <br>

                                        Sikhanandkaraj

                                        <br>

                                        Kota, Rajasthan, India

                                        <br>

                                        Email:

                                        <a
                                            href="
                                                mailto:info@sikhanandkaraj.com
                                            "
                                            class="
                                                color-pink
                                                fw-semibold
                                            ">

                                            info@sikhanandkaraj.com
                                        </a>
                                    </address>
                                </div>
                            </div>
                        </section>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>