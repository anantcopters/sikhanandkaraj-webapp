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
    aria-labelledby="cookie-page-title">

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

                        Privacy information
                    </p>

                    <h1
                        id="cookie-page-title"
                        class="fs-36 fw-bold mb-3">

                        Cookie Policy
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
                            aria-labelledby="cookie-introduction">

                            <h2
                                id="cookie-introduction"
                                class="fs-22 fw-semibold mb-3">

                                1. Introduction
                            </h2>

                            <p class="lh-lg">
                                This Cookie Policy explains how
                                Sikhanandkaraj uses cookies and similar
                                technologies when you access or use the
                                Platform.
                            </p>

                            <p class="lh-lg mb-0">
                                This Policy should be read together with our
                                <a
                                    href="<?= route_to(
                                                'web.legal.privacy'
                                            ) ?>"
                                    class="color-pink fw-semibold">

                                    Privacy Policy
                                </a>.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-definition">

                            <h2
                                id="cookie-definition"
                                class="fs-22 fw-semibold mb-3">

                                2. What cookies are
                            </h2>

                            <p class="lh-lg mb-0">
                                Cookies are small text files stored by a web
                                browser on a device. They can help a website
                                remember a session, maintain security, retain
                                preferences and understand whether features are
                                operating correctly.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-types">

                            <h2
                                id="cookie-types"
                                class="fs-22 fw-semibold mb-3">

                                3. Cookies we may use
                            </h2>

                            <div class="table-responsive">
                                <table
                                    class="
                                        table
                                        table-bordered
                                        align-middle
                                        mb-0
                                    ">

                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">
                                                Category
                                            </th>

                                            <th scope="col">
                                                Purpose
                                            </th>

                                            <th scope="col">
                                                Required
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                Essential
                                            </th>

                                            <td>
                                                Maintain sessions, support
                                                login, protect forms and
                                                operate core Platform
                                                functions.
                                            </td>

                                            <td>
                                                Yes
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                Security
                                            </th>

                                            <td>
                                                Detect suspicious activity,
                                                reduce abuse and protect
                                                accounts.
                                            </td>

                                            <td>
                                                Yes
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                Preference
                                            </th>

                                            <td>
                                                Remember interface or
                                                accessibility choices where
                                                available.
                                            </td>

                                            <td>
                                                Depends on the feature
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                Performance
                                            </th>

                                            <td>
                                                Understand errors, page
                                                performance and reliability.
                                            </td>

                                            <td>
                                                Where enabled
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                Analytics
                                            </th>

                                            <td>
                                                Understand aggregate Platform
                                                usage and improve features.
                                            </td>

                                            <td>
                                                Where enabled
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-essential">

                            <h2
                                id="cookie-essential"
                                class="fs-22 fw-semibold mb-3">

                                4. Essential cookies
                            </h2>

                            <p class="lh-lg mb-0">
                                Essential cookies are necessary to provide
                                requested Platform functions. Disabling these
                                cookies may prevent login, form submission,
                                account protection or other core functionality
                                from working correctly.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-session">

                            <h2
                                id="cookie-session"
                                class="fs-22 fw-semibold mb-3">

                                5. Session and persistent cookies
                            </h2>

                            <p class="lh-lg">
                                Session cookies generally expire when the
                                browser is closed or when the authenticated
                                session ends.
                            </p>

                            <p class="lh-lg mb-0">
                                Persistent cookies may remain for a defined
                                period or until they are deleted. They may be
                                used to remember preferences or support
                                security and reliability functions.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-third-party">

                            <h2
                                id="cookie-third-party"
                                class="fs-22 fw-semibold mb-3">

                                6. Third-party technologies
                            </h2>

                            <p class="lh-lg mb-0">
                                Authorised service providers, such as payment,
                                hosting, security or analytics providers, may
                                use cookies or similar technologies while
                                providing their services. Their use of such
                                technologies may also be governed by their own
                                privacy or cookie policies.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-management">

                            <h2
                                id="cookie-management"
                                class="fs-22 fw-semibold mb-3">

                                7. Managing cookies
                            </h2>

                            <p class="lh-lg">
                                Most browsers allow users to:
                            </p>

                            <ul class="lh-lg mb-3">
                                <li class="mb-2">
                                    Review stored cookies.
                                </li>

                                <li class="mb-2">
                                    Delete existing cookies.
                                </li>

                                <li class="mb-2">
                                    Block cookies from selected websites.
                                </li>

                                <li>
                                    Block all cookies.
                                </li>
                            </ul>

                            <div class="alert alert-warning mb-0">
                                Blocking essential cookies may prevent
                                registration, login, OTP verification, profile
                                updates or other secure functions from
                                operating.
                            </div>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-sensitive">

                            <h2
                                id="cookie-sensitive"
                                class="fs-22 fw-semibold mb-3">

                                8. Information stored in cookies
                            </h2>

                            <p class="lh-lg mb-0">
                                Sikhanandkaraj should not intentionally store
                                plain-text passwords, payment PINs or complete
                                banking credentials in browser cookies.
                                Authentication cookies should contain only the
                                technical identifiers required to maintain and
                                protect the session.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4 mb-3"
                            aria-labelledby="cookie-updates">

                            <h2
                                id="cookie-updates"
                                class="fs-22 fw-semibold mb-3">

                                9. Changes to this Policy
                            </h2>

                            <p class="lh-lg mb-0">
                                This Policy may be revised when Platform
                                functionality, service providers or applicable
                                requirements change. The latest version will
                                display its effective date.
                            </p>
                        </section>

                        <section
                            class="border-top pt-4"
                            aria-labelledby="cookie-contact">

                            <h2
                                id="cookie-contact"
                                class="fs-22 fw-semibold mb-3">

                                10. Contact us
                            </h2>

                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <address class="lh-lg mb-0">
                                        <strong>
                                            Privacy and Grievance Team
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