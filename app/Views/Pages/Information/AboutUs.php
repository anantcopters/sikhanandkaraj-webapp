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
    aria-labelledby="about-page-title">

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

                        About Sikhanandkaraj
                    </p>

                    <h1
                        id="about-page-title"
                        class="fs-36 fw-bold mb-3">

                        Faith, Family and Meaningful Connections
                    </h1>

                    <p
                        class="
                            fs-16
                            text-secondary
                            lh-lg
                            mx-auto
                            mb-0
                        ">

                        Sikhanandkaraj is a Sikh matrimonial platform created
                        to help individuals and families discover compatible
                        life partners while respecting Sikh traditions,
                        privacy and shared values.
                    </p>
                </header>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="about-who-we-are">
                            <div
                                class="
                                    d-flex
                                    align-items-center
                                    gap-3
                                    mb-3
                                ">

                                <i
                                    class="
                                        ri-community-line
                                        fs-30
                                        text-danger
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2
                                    id="about-who-we-are"
                                    class="fs-24 fw-semibold mb-0">

                                    Who we are
                                </h2>
                            </div>

                            <p class="lh-lg">
                                Sikhanandkaraj is designed specifically for the
                                global Sikh community. Our purpose is to bring
                                together members and families seeking sincere,
                                respectful and marriage-focused relationships.
                            </p>

                            <p class="lh-lg mb-0">
                                We combine technology with community values to
                                provide a simple, secure and privacy-conscious
                                matrimonial experience. The Platform is
                                strictly intended for matrimonial purposes and
                                is not a dating service.
                            </p>
                        </section>
                    </div>
                </article>

                <div class="row g-4 mb-4">
                    <div class="col-12 col-lg-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4 p-lg-5">
                                <div
                                    class="
                                        d-flex
                                        align-items-center
                                        gap-3
                                        mb-3
                                    ">

                                    <i
                                        class="
                                            ri-eye-line
                                            fs-30
                                            text-danger
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <h2 class="fs-24 fw-semibold mb-0">
                                        Our Vision
                                    </h2>
                                </div>

                                <p class="lh-lg mb-0">
                                    To become the world’s most trusted Sikh
                                    matrimonial platform by helping Sikh
                                    families find compatible life partners
                                    while respecting Sikh traditions, privacy
                                    and values.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-lg-6">
                        <article class="card border border-danger border-opacity-25 shadow-sm h-100">
                            <div class="card-body p-4 p-lg-5">
                                <div
                                    class="
                                        d-flex
                                        align-items-center
                                        gap-3
                                        mb-3
                                    ">

                                    <i
                                        class="
                                            ri-focus-3-line
                                            fs-30
                                            text-danger
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <h2 class="fs-24 fw-semibold mb-0">
                                        Our Mission
                                    </h2>
                                </div>

                                <ul class="lh-lg mb-0">
                                    <li class="mb-2">
                                        Connect Sikh families worldwide.
                                    </li>

                                    <li class="mb-2">
                                        Promote genuine and reviewed
                                        matrimonial profiles.
                                    </li>

                                    <li class="mb-2">
                                        Simplify the Anand Karaj journey.
                                    </li>

                                    <li class="mb-2">
                                        Provide complete wedding-support
                                        services.
                                    </li>

                                    <li>
                                        Preserve Sikh traditions through
                                        responsible use of technology.
                                    </li>
                                </ul>
                            </div>
                        </article>
                    </div>
                </div>

                <article class="card border border-danger border-opacity-25 shadow-sm mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="about-values">
                            <div
                                class="
                                    d-flex
                                    align-items-center
                                    gap-3
                                    mb-4
                                ">

                                <i
                                    class="
                                        ri-heart-3-line
                                        fs-30
                                        text-danger
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2
                                    id="about-values"
                                    class="fs-24 fw-semibold mb-0">

                                    Our Core Values
                                </h2>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Trust
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We work to create a dependable
                                            environment for members and their
                                            families.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Transparency
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We aim to communicate clearly
                                            about Platform features, processes
                                            and responsibilities.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Privacy
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We design our services to protect
                                            member information and respect
                                            visibility preferences.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Respect
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            Every member and family deserves
                                            courteous and dignified treatment.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Equality
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We support fair and inclusive
                                            access without degrading or
                                            discriminating against members.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Authenticity
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We encourage accurate profiles,
                                            sincere intentions and honest
                                            communication.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div
                                        class="
                                            border
                                            rounded
                                            p-3
                                            h-100
                                        ">

                                        <h3 class="fs-18 fw-semibold mb-2">
                                            Community
                                        </h3>

                                        <p class="lh-lg mb-0">
                                            We aim to strengthen connections
                                            between Sikh individuals and
                                            families around the world.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </article>

                <article class="card border border-danger border-opacity-25 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <section aria-labelledby="about-commitment">
                            <div
                                class="
                                    d-flex
                                    align-items-center
                                    gap-3
                                    mb-3
                                ">

                                <i
                                    class="
                                        ri-shield-check-line
                                        fs-30
                                        text-danger
                                    "
                                    aria-hidden="true">
                                </i>

                                <h2
                                    id="about-commitment"
                                    class="fs-24 fw-semibold mb-0">

                                    Our Commitment
                                </h2>
                            </div>

                            <p class="lh-lg">
                                We are committed to building a matrimonial
                                service that is secure, culturally respectful
                                and easy to use. We continuously work to
                                improve profile quality, privacy controls,
                                member safety and the overall matchmaking
                                experience.
                            </p>

                            <p class="lh-lg mb-0">
                                Sikhanandkaraj facilitates introductions.
                                Members and families should independently
                                verify profile details before making personal,
                                financial or matrimonial decisions.
                            </p>
                        </section>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>