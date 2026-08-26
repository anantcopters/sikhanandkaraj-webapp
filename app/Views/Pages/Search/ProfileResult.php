<?php

declare(strict_types=1);

/**
 * Exact Profile-ID Search result.
 *
 * ProfileCard remains the canonical presentation rather than duplicating
 * profile summary markup here.
 *
 * @var array<string,mixed>      $profile
 * @var array<string,mixed>|null $formAlert
 * @var string                   $reportCaptcha
 */

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);
?>

<section class="py-3 py-lg-4">

    <div class="container">

        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' =>
                $formAlert
                    ?? null,
            ]
        ) ?>

        <div
            class="d-flex
                align-items-center
                justify-content-between
                gap-3 mb-4">

            <div>
                <a
                    href="<?= route_to(
                                'web.search'
                            ) ?>"
                    class="d-inline-flex
                    align-items-center
                    gap-1 text-primary
                    fw-medium mb-2">

                    <i
                        class="ri-arrow-left-line"
                        aria-hidden="true">
                    </i>

                    Back to Search

                </a>
                <h1
                    class="fs-24
                        fw-semibold mb-1">

                    Profile Search

                </h1>

                <p
                    class="text-muted
                        mb-0">

                    Profile matching the requested
                    Profile ID.

                </p>

            </div>



        </div>

        <div class="row">

            <div
                class="col-12
                    col-xl-8 mx-auto">

                <?= view(
                    'Components/Member/ProfileCard',
                    [
                        'profile' =>
                        $profile,

                        'reportCaptcha' =>
                        $reportCaptcha
                            ?? '',
                    ]
                ) ?>

            </div>

        </div>

    </div>

</section>

<?php
$this->endSection();
