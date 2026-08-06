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
    aria-labelledby="fraud-page-title">

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

                        Member safety
                    </p>

                    <h1
                        id="fraud-page-title"
                        class="fs-36 fw-bold mb-3">

                        Fraud Alert
                    </h1>

                    <p class="text-secondary mb-0">
                        Effective date:
                        <?= esc($resolvedEffectiveDate) ?>
                    </p>
                </header>

                <article class="card border border-danger border-opacity-25 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div
                            class="
                                alert
                                alert-danger
                                d-flex
                                align-items-start
                                gap-3
                                mb-3
                            "
                            role="alert">

                            <i
                                class="
                                    ri-shield-flash-line
                                    fs-22
                                    flex-shrink-0
                                "
                                aria-hidden="true">
                            </i>

                            <div>
                                <h2 class="fs-20 fw-semibold mb-2">
                                    Never send money based only on an online
                                    matrimonial interaction
                                </h2>

                                <p class="lh-lg mb-0">
                                    Sikhanandkaraj does not ask members to
                                    transfer money, provide banking passwords,
                                    share OTPs or make personal payments to
                                    another member.
                                </p>
                            </div>
                        </div>

                        <section
                            class="mb-3"
                            aria-labelledby="fraud-purpose">

                            <h2
                                id="fraud-purpose"
                                class="fs-22 fw-semibold mb-3">

                                1. Purpose of this alert
                            </h2>

                            <p class="lh-lg mb-0">
                                Matrimonial platforms may be misused by
                                individuals who provide false information,
                                impersonate another person or attempt to obtain
                                money, documents or sensitive information.
                                This guidance explains common warning signs and
                                the precautions members should take.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-warning-signs">

                            <h2
                                id="fraud-warning-signs"
                                class="fs-22 fw-semibold mb-3">

                                2. Common warning signs
                            </h2>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    The person quickly claims strong emotional
                                    attachment without sufficient interaction.
                                </li>

                                <li class="mb-2">
                                    They repeatedly avoid video calls,
                                    family discussions or in-person meetings.
                                </li>

                                <li class="mb-2">
                                    Their stated education, employment,
                                    location or family details keep changing.
                                </li>

                                <li class="mb-2">
                                    They claim to be overseas, in the military,
                                    in medical distress or temporarily unable
                                    to access their funds.
                                </li>

                                <li class="mb-2">
                                    They ask for travel costs, medical expenses,
                                    customs fees, deposits, gifts or emergency
                                    assistance.
                                </li>

                                <li class="mb-2">
                                    They ask you to invest in cryptocurrency,
                                    trading, property or a business opportunity.
                                </li>

                                <li class="mb-2">
                                    They ask for an OTP, card number, password,
                                    PIN, UPI PIN or internet-banking access.
                                </li>

                                <li class="mb-2">
                                    They pressure you to communicate outside
                                    the Platform immediately.
                                </li>

                                <li>
                                    They threaten, emotionally manipulate or
                                    rush you into making a decision.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-protection">

                            <h2
                                id="fraud-protection"
                                class="fs-22 fw-semibold mb-3">

                                3. Protect yourself
                            </h2>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Involve trusted family members early in the
                                    conversation.
                                </li>

                                <li class="mb-2">
                                    Independently verify identity, marital
                                    status, education, employment and family
                                    information.
                                </li>

                                <li class="mb-2">
                                    Use a live video call before arranging a
                                    meeting.
                                </li>

                                <li class="mb-2">
                                    Meet initially at a safe public place.
                                </li>

                                <li class="mb-2">
                                    Tell a trusted person where and when you are
                                    meeting.
                                </li>

                                <li class="mb-2">
                                    Do not share bank statements, identity
                                    documents or private photographs
                                    unnecessarily.
                                </li>

                                <li class="mb-2">
                                    Never share passwords, OTPs, PINs or
                                    authentication codes.
                                </li>

                                <li>
                                    Stop communication when you feel pressured,
                                    threatened or uncomfortable.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-payments">

                            <h2
                                id="fraud-payments"
                                class="fs-22 fw-semibold mb-3">

                                4. Payments to Sikhanandkaraj
                            </h2>

                            <p class="lh-lg">
                                Pay for Sikhanandkaraj services only through
                                payment options displayed on the official
                                Platform.
                            </p>

                            <p class="lh-lg mb-0">
                                Do not make payments to a personal bank
                                account, UPI ID, wallet or QR code merely
                                because someone claims to represent
                                Sikhanandkaraj.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-compromised">

                            <h2
                                id="fraud-compromised"
                                class="fs-22 fw-semibold mb-3">

                                5. When your account may be compromised
                            </h2>

                            <p class="lh-lg">
                                Change your password and contact us promptly
                                when:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    You receive an OTP or login notification
                                    that you did not request.
                                </li>

                                <li class="mb-2">
                                    Your profile information changes without
                                    your permission.
                                </li>

                                <li class="mb-2">
                                    Messages are sent from your account without
                                    your knowledge.
                                </li>

                                <li>
                                    You lose control of your registered mobile
                                    number or email account.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-report">

                            <h2
                                id="fraud-report"
                                class="fs-22 fw-semibold mb-3">

                                6. Reporting suspected fraud
                            </h2>

                            <p class="lh-lg">
                                Preserve the relevant information before
                                blocking or deleting communication:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Profile reference number.
                                </li>

                                <li class="mb-2">
                                    Screenshots of messages and payment
                                    requests.
                                </li>

                                <li class="mb-2">
                                    Mobile numbers and email addresses used.
                                </li>

                                <li class="mb-2">
                                    Bank, UPI or payment references.
                                </li>

                                <li>
                                    Date and approximate time of each incident.
                                </li>
                            </ul>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="fraud-loss">

                            <h2
                                id="fraud-loss"
                                class="fs-22 fw-semibold mb-3">

                                7. If money has already been transferred
                            </h2>

                            <div class="alert alert-warning mb-0">
                                <p class="lh-lg mb-2">
                                    Immediately contact your bank or payment
                                    provider and request that the transaction
                                    be reviewed or stopped where possible.
                                </p>

                                <p class="lh-lg mb-0">
                                    You should also report the incident to the
                                    appropriate cybercrime or law-enforcement
                                    authority. Reporting it to Sikhanandkaraj
                                    does not replace an official complaint.
                                </p>
                            </div>
                        </section>

                        <section
                            class="border-top pt-4"
                            aria-labelledby="fraud-contact">

                            <h2
                                id="fraud-contact"
                                class="fs-22 fw-semibold mb-3">

                                8. Contact us
                            </h2>

                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <address class="lh-lg mb-0">
                                        <strong>
                                            Member Safety Team
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

                                        <br>

                                        Grievance form:

                                        <a
                                            href="<?= route_to(
                                                        'web.legal.grievances'
                                                    ) ?>"
                                            class="
                                                color-pink
                                                fw-semibold
                                            ">

                                            Submit grievance details
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