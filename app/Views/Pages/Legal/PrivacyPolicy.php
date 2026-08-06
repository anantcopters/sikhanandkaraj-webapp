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
<section class="section py-5 bg-light">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-introduction">

                            <h2 id="privacy-introduction">
                                1. Introduction
                            </h2>

                            <p>
                                This Privacy Policy explains how Sikhanandkaraj
                                collects, uses, stores, shares and protects personal
                                information when you access or use our website,
                                applications and related matrimonial services.
                            </p>

                            <p>
                                It should be read together with our
                                <a href="<?= route_to('web.legal.terms') ?>">
                                    Terms and Conditions
                                </a>.
                            </p>
                        </section>

                        <section
                            class="legal-page__section"
                            aria-labelledby="privacy-scope">

                            <h2 id="privacy-scope">
                                2. Scope
                            </h2>

                            <p>
                                This Policy applies to personal information
                                processed through the Platform, including
                                information submitted by:
                            </p>

                            <ul>
                                <li>
                                    A person creating their own matrimonial profile.
                                </li>

                                <li>
                                    A parent, guardian, sibling or authorised family
                                    member managing a profile.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-information">

                            <h2 id="privacy-information">
                                3. Information we collect
                            </h2>

                            <h3>
                                3.1 Account and contact information
                            </h3>

                            <ul>
                                <li>
                                    Name, date of birth and gender.
                                </li>

                                <li>
                                    Mobile number and email address.
                                </li>

                                <li>
                                    Login, verification and account-status
                                    information.
                                </li>

                                <li>
                                    Profile reference number and registration date.
                                </li>
                            </ul>

                            <h3>
                                3.2 Matrimonial profile information
                            </h3>

                            <ul>
                                <li>
                                    Education, occupation, income and organisation.
                                </li>

                                <li>
                                    Religion, community and family information.
                                </li>

                                <li>
                                    Location, lifestyle and personal-preference
                                    information.
                                </li>

                                <li>
                                    Photographs and profile descriptions.
                                </li>

                                <li>
                                    Partner preferences and matchmaking criteria.
                                </li>
                            </ul>

                            <h3>
                                3.3 Verification and safety information
                            </h3>

                            <p>
                                Where necessary and lawfully permitted, we may
                                collect information used to validate profile
                                authenticity, investigate abuse, respond to
                                grievances or protect members.
                            </p>

                            <h3>
                                3.4 Usage and technical information
                            </h3>

                            <ul>
                                <li>
                                    IP address, browser and device information.
                                </li>

                                <li>
                                    Login dates, session data and security events.
                                </li>

                                <li>
                                    Pages viewed and Platform interactions.
                                </li>

                                <li>
                                    Error, performance and diagnostic information.
                                </li>
                            </ul>

                            <h3>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-source">

                            <h2 id="privacy-source">
                                4. How information is collected
                            </h2>

                            <p>
                                We collect information:
                            </p>

                            <ul>
                                <li>
                                    Directly from you or your authorised family
                                    representative.
                                </li>

                                <li>
                                    When you create, edit or verify a profile.
                                </li>

                                <li>
                                    When you upload photographs or use Platform
                                    features.
                                </li>

                                <li>
                                    Automatically through security logs, cookies
                                    and similar technologies.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-purpose">

                            <h2 id="privacy-purpose">
                                5. How we use information
                            </h2>

                            <p>
                                Personal information may be used to:
                            </p>

                            <ul>
                                <li>
                                    Create, verify and maintain member accounts.
                                </li>

                                <li>
                                    Display matrimonial profiles according to
                                    selected visibility settings.
                                </li>

                                <li>
                                    Generate matches and recommendations.
                                </li>

                                <li>
                                    Enable interests, messages and notifications.
                                </li>

                                <li>
                                    Moderate photographs and profile content.
                                </li>

                                <li>
                                    Prevent impersonation, fraud, abuse and
                                    unauthorised access.
                                </li>

                                <li>
                                    Provide member support and resolve grievances.
                                </li>

                                <li>
                                    Process subscriptions and payments.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-basis">

                            <h2 id="privacy-basis">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-display">

                            <h2 id="privacy-display">
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

                            <ul>
                                <li>
                                    Eligible registered members.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-sharing">

                            <h2 id="privacy-sharing">
                                8. Information sharing
                            </h2>

                            <p>
                                We do not sell member personal information.
                            </p>

                            <p>
                                Information may be shared with:
                            </p>

                            <ul>
                                <li>
                                    Other eligible members according to profile
                                    visibility and matchmaking functionality.
                                </li>

                                <li>
                                    Cloud hosting, storage, CDN, email, SMS,
                                    payment, security and support providers.
                                </li>

                                <li>
                                    Professional advisers and auditors under
                                    confidentiality obligations.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-storage">

                            <h2 id="privacy-storage">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-retention">

                            <h2 id="privacy-retention">
                                10. Data retention
                            </h2>

                            <p>
                                We retain personal information only for as long as
                                reasonably necessary to:
                            </p>

                            <ul>
                                <li>
                                    Operate the member account and provide requested
                                    services.
                                </li>

                                <li>
                                    Maintain security, fraud-prevention and consent
                                    records.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-security">

                            <h2 id="privacy-security">
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

                            <ul>
                                <li>
                                    Access controls and role-based authorisation.
                                </li>

                                <li>
                                    Password hashing and OTP-based verification.
                                </li>

                                <li>
                                    Private media storage and controlled delivery.
                                </li>

                                <li>
                                    Security logging and monitoring.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-rights">

                            <h2 id="privacy-rights">
                                12. Your privacy choices and rights
                            </h2>

                            <p>
                                Subject to applicable law and verification of the
                                request, you may ask us to:
                            </p>

                            <ul>
                                <li>
                                    Provide information about personal data being
                                    processed.
                                </li>

                                <li>
                                    Correct inaccurate or incomplete information.
                                </li>

                                <li>
                                    Update outdated information.
                                </li>

                                <li>
                                    Delete information that is no longer required.
                                </li>

                                <li>
                                    Withdraw consent for consent-based processing.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-cookies">

                            <h2 id="privacy-cookies">
                                13. Cookies and similar technologies
                            </h2>

                            <p>
                                The Platform may use cookies and similar
                                technologies for:
                            </p>

                            <ul>
                                <li>
                                    Authentication and session management.
                                </li>

                                <li>
                                    Security and fraud prevention.
                                </li>

                                <li>
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
                            class="legal-page__section"
                            aria-labelledby="privacy-children">

                            <h2 id="privacy-children">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-third-party">

                            <h2 id="privacy-third-party">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-change">

                            <h2 id="privacy-change">
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
                            class="legal-page__section"
                            aria-labelledby="privacy-contact">

                            <h2 id="privacy-contact">
                                17. Contact and grievances
                            </h2>

                            <p>
                                Privacy questions, correction requests, deletion
                                requests and grievances may be sent to:
                            </p>

                            <address class="legal-page__contact mb-0">
                                <strong>
                                    Grievance Officer
                                </strong>

                                <br>

                                Sikhanandkaraj

                                <br>

                                Kota, Rajasthan, India

                                <br>

                                Email:
                                <a href="mailto:info@sikhanandkaraj.com">
                                    info@sikhanandkaraj.com
                                </a>
                            </address>
                        </section>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>