<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 */

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
    aria-labelledby="payment-page-title">

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

                        Secure payments
                    </p>

                    <h1
                        id="payment-page-title"
                        class="fs-36 fw-bold mb-3">

                        Payment Options
                    </h1>

                    <p
                        class="
                            fs-16
                            text-secondary
                            lh-lg
                            mx-auto
                            mb-0
                        ">

                        Pay for eligible Sikhanandkaraj services through the
                        secure options displayed during checkout.
                    </p>
                </header>

                <div
                    class="
                        alert
                        alert-warning
                        d-flex
                        align-items-start
                        gap-3
                        mb-4
                    "
                    role="alert">

                    <i
                        class="
                            ri-error-warning-line
                            fs-24
                            flex-shrink-0
                        "
                        aria-hidden="true">
                    </i>

                    <div>
                        <h2 class="fs-18 fw-semibold mb-2">
                            Use only official payment channels
                        </h2>

                        <p class="lh-lg mb-0">
                            Do not transfer subscription payments to a personal
                            bank account, mobile number, UPI ID, QR code or
                            wallet shared through a matrimonial conversation.
                        </p>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-bank-card-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Credit and debit cards
                                </h2>

                                <p class="lh-lg mb-0">
                                    Supported domestic or international credit
                                    and debit cards may be presented by the
                                    authorised payment gateway during checkout.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-qr-code-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    UPI
                                </h2>

                                <p class="lh-lg mb-0">
                                    Where available, you may complete payment
                                    using a supported UPI application or UPI ID
                                    through the official payment page.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-bank-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Net banking
                                </h2>

                                <p class="lh-lg mb-0">
                                    Supported banks may be shown by the payment
                                    gateway. You will be redirected to the
                                    bank’s authorised payment flow.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-wallet-3-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Supported wallets
                                </h2>

                                <p class="lh-lg mb-0">
                                    Digital-wallet options may be displayed
                                    where supported by the authorised payment
                                    provider and your location.
                                </p>
                            </div>
                        </article>
                    </div>
                </div>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="payment-process">
                            <h2
                                id="payment-process"
                                class="fs-24 fw-semibold mb-3">

                                How payment works
                            </h2>

                            <ol class="lh-lg mb-0">
                                <li class="mb-3">
                                    Select an available membership plan or paid
                                    service.
                                </li>

                                <li class="mb-3">
                                    Review the price, duration, included
                                    features and applicable taxes.
                                </li>

                                <li class="mb-3">
                                    Continue to the authorised payment gateway.
                                </li>

                                <li class="mb-3">
                                    Select an available payment method and
                                    complete authentication.
                                </li>

                                <li>
                                    Return to Sikhanandkaraj and verify that the
                                    payment status is successful.
                                </li>
                            </ol>
                        </section>

                        <section
                            class="border-top pt-4 mt-4"
                            aria-labelledby="payment-status">

                            <h2
                                id="payment-status"
                                class="fs-24 fw-semibold mb-3">

                                Payment status
                            </h2>

                            <p class="lh-lg">
                                A payment may temporarily appear as pending
                                while confirmation is awaited from the payment
                                provider.
                            </p>

                            <p class="lh-lg mb-0">
                                Do not repeat a payment immediately when money
                                has been debited but the Platform displays a
                                pending or failed status. First check your bank
                                or payment-provider statement and contact
                                support with the transaction reference.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mt-4"
                            aria-labelledby="payment-receipt">

                            <h2
                                id="payment-receipt"
                                class="fs-24 fw-semibold mb-3">

                                Payment confirmation and receipt
                            </h2>

                            <p class="lh-lg mb-0">
                                Successful payment details may be displayed in
                                your account and sent to your registered contact
                                details. Retain the payment reference and
                                receipt for future support requests.
                            </p>
                        </section>
                    </div>
                </article>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="payment-security">
                            <h2
                                id="payment-security"
                                class="fs-24 fw-semibold mb-3">

                                Payment safety
                            </h2>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Never share your OTP, PIN, UPI PIN, CVV or
                                    banking password.
                                </li>

                                <li class="mb-2">
                                    Confirm that you are using the official
                                    Sikhanandkaraj website.
                                </li>

                                <li class="mb-2">
                                    Review the amount before authorising a
                                    payment.
                                </li>

                                <li class="mb-2">
                                    Avoid payments over public or untrusted
                                    internet connections.
                                </li>

                                <li class="mb-2">
                                    Do not allow another member to make a
                                    payment on your behalf using remote-access
                                    software.
                                </li>

                                <li>
                                    Report an unauthorised transaction to your
                                    bank and Sikhanandkaraj promptly.
                                </li>
                            </ul>
                        </section>
                    </div>
                </article>

                <article class="card border border-danger border-opacity-25 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="payment-support">
                            <h2
                                id="payment-support"
                                class="fs-24 fw-semibold mb-3">

                                Payment support
                            </h2>

                            <p class="lh-lg">
                                Include the following information when
                                reporting a payment concern:
                            </p>

                            <ul class="lh-lg mb-4">
                                <li class="mb-2">
                                    Registered mobile number or email address.
                                </li>

                                <li class="mb-2">
                                    Profile reference number.
                                </li>

                                <li class="mb-2">
                                    Payment date and amount.
                                </li>

                                <li class="mb-2">
                                    Payment-provider transaction reference.
                                </li>

                                <li>
                                    Screenshot showing the payment status,
                                    without exposing sensitive banking details.
                                </li>
                            </ul>

                            <a
                                href="
                                    mailto:info@sikhanandkaraj.com
                                "
                                class="btn btn-danger">

                                <i
                                    class="ri-customer-service-2-line me-2"
                                    aria-hidden="true">
                                </i>

                                Contact Payment Support
                            </a>

                            <p class="fs-13 text-secondary mt-3 mb-0">
                                Email: info@sikhanandkaraj.com
                            </p>
                        </section>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>