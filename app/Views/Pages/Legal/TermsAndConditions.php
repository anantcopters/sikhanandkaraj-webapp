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

/*
 * Use the same footer currently used by the public homepage.
 */
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
                        Legal information
                    </p>

                    <h1 class="fs-36 fw-bold mb-3">
                        Terms and Conditions
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
                            aria-labelledby="terms-introduction">

                            <h2 id="terms-introduction" class="fs-22 fw-semibold mb-3">
                                1. Introduction
                            </h2>

                            <p>
                                Welcome to Sikhanandkaraj. These Terms and
                                Conditions govern your access to and use of the
                                Sikhanandkaraj website, applications, services,
                                features and related facilities collectively
                                referred to as the “Platform”.
                            </p>

                            <p>
                                Sikhanandkaraj is intended solely to help eligible
                                individuals and families explore matrimonial
                                introductions. It is not a dating, escort,
                                employment, financial, immigration or background
                                verification service.
                            </p>

                            <p>
                                By registering, accessing or using the Platform,
                                you confirm that you have read, understood and
                                agreed to these Terms and Conditions and our
                                Privacy Policy.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-eligibility">

                            <h2 id="terms-eligibility" class="fs-22 fw-semibold mb-3">
                                2. Eligibility
                            </h2>

                            <p>
                                You may use the Platform only when you are legally
                                competent to enter into a binding contract under
                                applicable law.
                            </p>

                            <p>
                                You must also have attained the legally permitted
                                age for marriage applicable to you and must not be
                                prohibited from marriage by any applicable law,
                                court order or existing marital relationship.
                            </p>

                            <p>
                                Where a profile is created or managed by a parent,
                                guardian, sibling or authorised family member, that
                                person confirms that they have the profile
                                individual’s permission to provide and manage the
                                information.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-registration">

                            <h2 id="terms-registration" class="fs-22 fw-semibold mb-3">
                                3. Account registration
                            </h2>

                            <p>
                                You agree to provide accurate, current and complete
                                information during registration and while
                                maintaining your profile.
                            </p>

                            <p>
                                You are responsible for:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Keeping your login credentials and OTPs
                                    confidential.
                                </li>

                                <li class="mb-2">
                                    Maintaining control over your registered mobile
                                    number and email account.
                                </li>

                                <li class="mb-2">
                                    Reviewing and updating inaccurate or outdated
                                    profile information.
                                </li>

                                <li>
                                    Informing us promptly about suspected
                                    unauthorised access.
                                </li>
                            </ul>

                            <p>
                                One person must not create multiple misleading,
                                duplicate or impersonating profiles.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-profile-content">

                            <h2 id="terms-profile-content" class="fs-22 fw-semibold mb-3">
                                4. Profile information and content
                            </h2>

                            <p>
                                You retain responsibility for the information,
                                photographs and other content submitted through
                                your account.
                            </p>

                            <p>
                                By submitting content, you grant Sikhanandkaraj a
                                limited, non-exclusive and revocable permission to
                                store, process, resize, watermark, moderate and
                                display that content as necessary to operate and
                                secure the Platform.
                            </p>

                            <p>
                                You must not upload or publish content that:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Belongs to another person without their
                                    permission.
                                </li>

                                <li class="mb-2">
                                    Is false, misleading, fraudulent or materially
                                    incomplete.
                                </li>

                                <li class="mb-2">
                                    Is obscene, abusive, threatening,
                                    discriminatory or unlawful.
                                </li>

                                <li class="mb-2">
                                    Contains malware, advertisements, commercial
                                    solicitation or unrelated promotional material.
                                </li>

                                <li>
                                    Infringes privacy, copyright, trademark or
                                    another person’s legal rights.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-review">

                            <h2 id="terms-review" class="fs-22 fw-semibold mb-3">
                                5. Profile review and verification
                            </h2>

                            <p>
                                Sikhanandkaraj may review profile information and
                                photographs for moderation, quality and safety.
                                A reviewed, approved or verified status does not
                                constitute a guarantee of a member’s identity,
                                character, marital status, education, employment,
                                income, family information or intentions.
                            </p>

                            <p>
                                Members must independently verify information
                                before making personal, financial, travel or
                                matrimonial decisions.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-conduct">

                            <h2 id="terms-conduct" class="fs-22 fw-semibold mb-3">
                                6. Acceptable use
                            </h2>

                            <p>
                                While using the Platform, you must not:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Harass, threaten, stalk or intimidate another
                                    person.
                                </li>

                                <li class="mb-2">
                                    Request or transfer money as a condition of a
                                    matrimonial interaction.
                                </li>

                                <li class="mb-2">
                                    Collect, scrape, download or republish member
                                    information without permission.
                                </li>

                                <li class="mb-2">
                                    Circumvent access controls, privacy settings or
                                    security protections.
                                </li>

                                <li class="mb-2">
                                    Introduce automated bots, crawlers, malicious
                                    code or excessive traffic.
                                </li>

                                <li class="mb-2">
                                    Use the Platform for dating, friendship-only
                                    solicitation, commercial promotion,
                                    recruitment or unlawful activity.
                                </li>

                                <li>
                                    Share another member’s photographs or personal
                                    information outside the Platform without their
                                    permission.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-interactions">

                            <h2 id="terms-interactions" class="fs-22 fw-semibold mb-3">
                                7. Member interactions
                            </h2>

                            <p>
                                Sikhanandkaraj provides a technology platform for
                                matrimonial introductions. We are not a party to
                                communications, meetings, engagements, marriages,
                                financial arrangements or other decisions between
                                members and their families.
                            </p>

                            <p>
                                Exercise reasonable caution before:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Sharing sensitive personal or financial
                                    information.
                                </li>

                                <li class="mb-2">
                                    Meeting another member in person.
                                </li>

                                <li class="mb-2">
                                    Transferring money, gifts or documents.
                                </li>

                                <li>
                                    Making travel, engagement or marriage
                                    arrangements.
                                </li>
                            </ul>

                            <p>
                                Initial meetings should occur at a safe public
                                location, and a trusted family member or friend
                                should be informed.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-fees">

                            <h2 id="terms-fees" class="fs-22 fw-semibold mb-3">
                                8. Free and paid services
                            </h2>

                            <p>
                                Certain Platform features may be available without
                                charge, while others may require payment. The
                                applicable price, duration, taxes and included
                                features will be shown before purchase.
                            </p>

                            <p>
                                Unless required by applicable law or expressly
                                stated at the time of purchase, payments for
                                consumed or activated services may be
                                non-refundable.
                            </p>

                            <p>
                                Paid access does not guarantee a response,
                                matrimonial match, engagement or marriage.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-suspension">

                            <h2 id="terms-suspension" class="fs-22 fw-semibold mb-3">
                                9. Suspension and termination
                            </h2>

                            <p>
                                Sikhanandkaraj may restrict, suspend or terminate an
                                account where we reasonably believe that:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    These Terms have been violated.
                                </li>

                                <li class="mb-2">
                                    The account presents a safety, fraud or security
                                    risk.
                                </li>

                                <li class="mb-2">
                                    Information or documents are materially false.
                                </li>

                                <li class="mb-2">
                                    The account is being used without proper
                                    authority.
                                </li>

                                <li>
                                    Suspension is necessary to comply with law or a
                                    valid authority request.
                                </li>
                            </ul>

                            <p>
                                A member may request account closure through the
                                account settings or the contact method stated
                                below, subject to legally required retention.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-ip">

                            <h2 id="terms-ip" class="fs-22 fw-semibold mb-3">
                                10. Intellectual property
                            </h2>

                            <p>
                                The Platform’s software, branding, design,
                                databases, original text, graphics and related
                                materials are owned by or licensed to
                                Sikhanandkaraj.
                            </p>

                            <p>
                                You may not copy, reverse engineer, modify,
                                distribute, sell or commercially exploit Platform
                                materials except where expressly permitted in
                                writing or allowed by law.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-third-party">

                            <h2 id="terms-third-party" class="fs-22 fw-semibold mb-3">
                                11. Third-party services
                            </h2>

                            <p>
                                The Platform may rely on or link to third-party
                                services such as payment gateways, cloud hosting,
                                email, SMS, analytics or identity-verification
                                providers.
                            </p>

                            <p>
                                Third-party services may have separate terms and
                                privacy practices. Sikhanandkaraj is not
                                responsible for an independent third party’s
                                services, availability or content.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-availability">

                            <h2 id="terms-availability" class="fs-22 fw-semibold mb-3">
                                12. Platform availability
                            </h2>

                            <p>
                                We aim to provide a secure and reliable service,
                                but uninterrupted or error-free operation cannot be
                                guaranteed.
                            </p>

                            <p>
                                Features may be modified, suspended or withdrawn
                                for maintenance, security, legal, operational or
                                business reasons.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-liability">

                            <h2 id="terms-liability" class="fs-22 fw-semibold mb-3">
                                13. Disclaimer and limitation of liability
                            </h2>

                            <p>
                                The Platform is provided on an “as available” basis
                                to the maximum extent permitted by law.
                            </p>

                            <p>
                                Sikhanandkaraj does not guarantee:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    The accuracy of every member-provided detail.
                                </li>

                                <li class="mb-2">
                                    Compatibility between members.
                                </li>

                                <li class="mb-2">
                                    That a member will respond or proceed with a
                                    proposal.
                                </li>

                                <li>
                                    Any particular matrimonial outcome.
                                </li>
                            </ul>

                            <p>
                                To the extent permitted by applicable law,
                                Sikhanandkaraj will not be liable for indirect,
                                incidental, special or consequential loss arising
                                from member conduct, third-party services or use of
                                the Platform.
                            </p>

                            <p>
                                Nothing in these Terms excludes a liability or
                                consumer right that cannot legally be excluded.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-indemnity">

                            <h2 id="terms-indemnity" class="fs-22 fw-semibold mb-3">
                                14. Indemnity
                            </h2>

                            <p>
                                To the extent permitted by law, you agree to
                                indemnify Sikhanandkaraj against claims, losses or
                                expenses resulting from your unlawful use of the
                                Platform, your submitted content or your material
                                breach of these Terms.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-law">

                            <h2 id="terms-law" class="fs-22 fw-semibold mb-3">
                                15. Governing law and disputes
                            </h2>

                            <p>
                                These Terms are governed by the laws of India.
                                Subject to any mandatory consumer forum or other
                                statutory jurisdiction, courts having jurisdiction
                                over Kota, Rajasthan will have jurisdiction over
                                disputes relating to the Platform.
                            </p>

                            <p>
                                Before initiating formal proceedings, the parties
                                should make a reasonable attempt to resolve the
                                concern through the grievance contact provided
                                below.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-changes">

                            <h2 id="terms-changes" class="fs-22 fw-semibold mb-3">
                                16. Changes to these Terms
                            </h2>

                            <p>
                                We may revise these Terms to reflect legal,
                                security, operational or service changes.
                                The revised version will display its effective date.
                            </p>

                            <p>
                                Where a material change requires renewed notice or
                                consent, it will be obtained through an appropriate
                                Platform mechanism.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="terms-contact">

                            <h2 id="terms-contact" class="fs-22 fw-semibold mb-3">
                                17. Contact and grievances
                            </h2>

                            <p>
                                Questions, complaints or grievance requests
                                concerning these Terms may be sent to:
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