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

<section
    class="section py-5 light-yellowish"
    aria-labelledby="grievance-page-title">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <header class="text-center mb-4">
                    <p
                        class="
                            fs-13
                            fw-semibold
                            text-danger
                            text-uppercase
                            mb-2
                        ">

                        Member support
                    </p>

                    <h1
                        id="grievance-page-title"
                        class="fs-36 fw-bold mb-3">

                        Grievance Redressal
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
                            aria-labelledby="grievance-introduction">

                            <h2
                                id="grievance-introduction"
                                class="fs-22 fw-semibold mb-3">

                                1. Our commitment
                            </h2>

                            <p class="lh-lg">
                                Sikhanandkaraj is committed to maintaining a
                                safe, respectful and transparent matrimonial
                                platform. Members and visitors may use this
                                grievance process to report concerns relating
                                to their account, personal information,
                                Platform content, member conduct, payments or
                                the operation of the Platform.
                            </p>

                            <p class="lh-lg mb-0">
                                A grievance should contain enough information
                                for us to identify the affected account,
                                transaction, content or interaction.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="grievance-types">

                            <h2
                                id="grievance-types"
                                class="fs-22 fw-semibold mb-3">

                                2. Concerns you may report
                            </h2>

                            <p class="lh-lg">
                                You may submit a grievance concerning:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Unauthorised access to your account.
                                </li>

                                <li class="mb-2">
                                    Impersonation or a false matrimonial
                                    profile.
                                </li>

                                <li class="mb-2">
                                    Harassment, threatening communication or
                                    inappropriate conduct.
                                </li>

                                <li class="mb-2">
                                    Misuse of your photographs or personal
                                    information.
                                </li>

                                <li class="mb-2">
                                    Inaccurate personal information that you are
                                    unable to correct through your account.
                                </li>

                                <li class="mb-2">
                                    A request to access, correct or delete
                                    personal information.
                                </li>

                                <li class="mb-2">
                                    Payment, subscription or billing concerns.
                                </li>

                                <li>
                                    Content that may violate our Terms and
                                    Conditions.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="grievance-details">

                            <h2
                                id="grievance-details"
                                class="fs-22 fw-semibold mb-3">

                                3. Information to include
                            </h2>

                            <p class="lh-lg">
                                To help us investigate efficiently, provide:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Your full name and registered mobile number
                                    or email address.
                                </li>

                                <li class="mb-2">
                                    Your profile reference number, where
                                    available.
                                </li>

                                <li class="mb-2">
                                    A clear description of the concern.
                                </li>

                                <li class="mb-2">
                                    The date and approximate time of the
                                    incident.
                                </li>

                                <li class="mb-2">
                                    The other member’s profile reference number,
                                    where relevant.
                                </li>

                                <li class="mb-2">
                                    Screenshots, payment references or other
                                    supporting evidence.
                                </li>

                                <li>
                                    The resolution you are requesting.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="grievance-process">

                            <h2
                                id="grievance-process"
                                class="fs-22 fw-semibold mb-3">

                                4. Grievance process
                            </h2>

                            <ol class="lh-lg mb-0">
                                <li class="mb-3">
                                    <strong>Acknowledgement:</strong>
                                    We will record the grievance and may send
                                    an acknowledgement to your registered
                                    contact details.
                                </li>

                                <li class="mb-3">
                                    <strong>Verification:</strong>
                                    We may verify your identity before sharing
                                    account-related or personal information.
                                </li>

                                <li class="mb-3">
                                    <strong>Investigation:</strong>
                                    Relevant account records, content,
                                    transactions and security logs may be
                                    reviewed.
                                </li>

                                <li class="mb-3">
                                    <strong>Additional information:</strong>
                                    We may request further details where the
                                    original complaint is incomplete.
                                </li>

                                <li>
                                    <strong>Resolution:</strong>
                                    We will communicate the outcome or the next
                                    appropriate action through the available
                                    contact details.
                                </li>
                            </ol>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="grievance-emergency">

                            <h2
                                id="grievance-emergency"
                                class="fs-22 fw-semibold mb-3">

                                5. Immediate safety concerns
                            </h2>

                            <div
                                class="
                                    alert
                                    alert-danger
                                    d-flex
                                    align-items-start
                                    gap-3
                                    mb-0
                                "
                                role="alert">

                                <i
                                    class="
                                        ri-alarm-warning-line
                                        fs-22
                                        flex-shrink-0
                                    "
                                    aria-hidden="true">
                                </i>

                                <div>
                                    <h3 class="fs-18 fw-semibold mb-2">
                                        Contact the appropriate authority
                                    </h3>

                                    <p class="lh-lg mb-0">
                                        Where you believe that you or another
                                        person is in immediate danger, contact
                                        local law-enforcement or emergency
                                        services first. The Platform grievance
                                        process is not an emergency-response
                                        service.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="grievance-confidentiality">

                            <h2
                                id="grievance-confidentiality"
                                class="fs-22 fw-semibold mb-3">

                                6. Confidentiality
                            </h2>

                            <p class="lh-lg mb-0">
                                Grievance information will be handled on a
                                need-to-know basis. Information may be
                                disclosed where reasonably necessary to
                                investigate the concern, protect members,
                                enforce Platform rules or comply with
                                applicable law.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4"
                            aria-labelledby="grievance-contact">

                            <h2
                                id="grievance-contact"
                                class="fs-22 fw-semibold mb-3">

                                7. Contact the Grievance Officer
                            </h2>

                            <div class="card bg-light border-0">
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