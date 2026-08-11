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
    aria-labelledby="career-page-title">

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

                        Build with purpose
                    </p>

                    <h1
                        id="career-page-title"
                        class="fs-36 fw-bold mb-3">

                        Careers at Sikhanandkaraj
                    </h1>

                    <p
                        class="
                            fs-16
                            text-secondary
                            lh-lg
                            mx-auto
                            mb-0
                        ">

                        Help us build a trusted technology platform that
                        connects Sikh individuals and families across the
                        world.
                    </p>
                </header>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="career-introduction">
                            <h2
                                id="career-introduction"
                                class="fs-24 fw-semibold mb-3">

                                Work that serves the community
                            </h2>

                            <p class="lh-lg">
                                Sikhanandkaraj combines technology, member
                                safety and community understanding to simplify
                                the matrimonial journey. Our work involves more
                                than building software; it involves earning
                                trust and creating a respectful experience for
                                members and families.
                            </p>

                            <p class="lh-lg mb-0">
                                We welcome people who value responsibility,
                                thoughtful problem-solving, privacy and
                                long-term community impact.
                            </p>
                        </section>
                    </div>
                </article>

                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-code-s-slash-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Technology
                                </h2>

                                <p class="lh-lg mb-0">
                                    Web development, mobile applications,
                                    quality assurance, cloud infrastructure,
                                    data engineering and information security.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-palette-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Product and design
                                </h2>

                                <p class="lh-lg mb-0">
                                    Product management, user research, user
                                    experience, interface design and accessible
                                    member journeys.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-customer-service-2-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Member support
                                </h2>

                                <p class="lh-lg mb-0">
                                    Member onboarding, grievance support,
                                    profile assistance and respectful resolution
                                    of member concerns.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-shield-user-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Trust and safety
                                </h2>

                                <p class="lh-lg mb-0">
                                    Profile review, fraud prevention,
                                    moderation, privacy operations and member
                                    safety processes.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-megaphone-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Marketing and partnerships
                                </h2>

                                <p class="lh-lg mb-0">
                                    Community engagement, digital marketing,
                                    content, partnerships and wedding-service
                                    relationships.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4">
                                <i
                                    class="
                                        ri-map-pin-user-line
                                        fs-30
                                        text-danger
                                        d-inline-block
                                        mb-3
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2 class="fs-20 fw-semibold mb-3">
                                    Field operations
                                </h2>

                                <p class="lh-lg mb-0">
                                    Community outreach, assisted profile
                                    registration, local partnerships and member
                                    guidance.
                                </p>
                            </div>
                        </article>
                    </div>
                </div>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="career-values">
                            <h2
                                id="career-values"
                                class="fs-24 fw-semibold mb-3">

                                What we value in our team
                            </h2>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Respect for members, colleagues and the Sikh
                                    community.
                                </li>

                                <li class="mb-2">
                                    Ownership and accountability.
                                </li>

                                <li class="mb-2">
                                    Clear and honest communication.
                                </li>

                                <li class="mb-2">
                                    Privacy and security by design.
                                </li>

                                <li class="mb-2">
                                    Simple, maintainable and scalable
                                    solutions.
                                </li>

                                <li class="mb-2">
                                    Evidence-based decision-making.
                                </li>

                                <li>
                                    Willingness to learn and continuously
                                    improve.
                                </li>
                            </ul>
                        </section>
                    </div>
                </article>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="career-application">
                            <h2
                                id="career-application"
                                class="fs-24 fw-semibold mb-3">

                                How to apply
                            </h2>

                            <p class="lh-lg">
                                Send your application with:
                            </p>

                            <ul class="lh-lg mb-0">
                                <li class="mb-2">
                                    Updated résumé.
                                </li>

                                <li class="mb-2">
                                    Role or area of interest.
                                </li>

                                <li class="mb-2">
                                    Current city and preferred work location.
                                </li>

                                <li class="mb-2">
                                    Relevant portfolio, GitHub profile or work
                                    samples.
                                </li>

                                <li class="mb-2">
                                    Current notice period or availability.
                                </li>

                                <li>
                                    A brief note explaining why you would like
                                    to work with Sikhanandkaraj.
                                </li>
                            </ul>
                        </section>
                    </div>
                </article>

                <article class="card border border-danger border-opacity-25 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="career-contact">
                            <div
                                class="
                                    d-flex
                                    flex-column
                                    flex-md-row
                                    align-items-md-center
                                    justify-content-between
                                    gap-4
                                ">

                                <div>
                                    <h2
                                        id="career-contact"
                                        class="fs-24 fw-semibold mb-2">

                                        Join our team
                                    </h2>

                                    <p class="lh-lg mb-0">
                                        Current openings may vary. You may also
                                        submit a general application for future
                                        suitable opportunities.
                                    </p>
                                </div>

                                <a
                                    href="
                                        mailto:info@sikhanandkaraj.com
                                        ?subject=Career%20Application
                                    "
                                    class="
                                        btn
                                        btn-danger
                                        flex-shrink-0
                                    ">

                                    <i
                                        class="ri-send-plane-line me-2"
                                        aria-hidden="true">
                                    </i>

                                    Send Application
                                </a>
                            </div>

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