<?php

declare(strict_types=1);

/**
 * Basic Partner Preference edit page.
 *
 * @var string                     $item
 * @var string                     $itemTitle
 * @var array<string, mixed>|null  $preference
 * @var array<string, list<int>>   $selectedIds
 * @var array<string, mixed>       $masterData
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$this->extend('Layouts/Main');

$this->section('content');

$resolvedItem = isset($item)
    ? trim((string) $item)
    : '';

$resolvedItemTitle = isset($itemTitle)
    ? trim((string) $itemTitle)
    : 'Partner Preference';

/*
 * Basic preference pages always return to the Basic section.
 */
$sectionKey = 'basic';

$resolvedPreference = is_array($preference ?? null)
    ? $preference
    : [];

$resolvedSelectedIds = is_array($selectedIds ?? null)
    ? $selectedIds
    : [];

$resolvedMasterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$resolvedValidationErrors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];
?>

<section class="py-3 py-lg-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-7">

                <?= view(
                    'Pages/Profile/Partials/_feedback_alert',
                    [
                        'formAlert' =>
                        $formAlert ?? null,
                    ]
                ) ?>

                <div class="mb-3">
                    <a
                        href="<?= url_to(
                                    'web.partner-preference'
                                ) ?>#<?= esc(
                                    $sectionKey,
                                    'attr'
                                ) ?>"
                        class="d-inline-flex
                            align-items-center
                            gap-1 text-primary
                            fw-medium mb-2">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Partner Preference
                    </a>

                    <div
                        class="d-flex align-items-center
                            gap-2 mt-2">

                        <div
                            class="avatar-sm
                                flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-user-heart-line
                                        fs-20"></i>
                            </span>
                        </div>

                        <div>
                            <h2
                                class="fs-16
                                    fw-semibold mb-1">

                                <?= esc(
                                    $resolvedItemTitle
                                ) ?>
                            </h2>

                            <p
                                class="text-muted
                                    fs-13 mb-0">

                                Set your preferred partner criteria.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="card border
                        border-danger
                        border-opacity-25
                        shadow-none mb-0">

                    <div class="card-body p-3 p-md-4">

                        <?= view(
                            'Pages/PartnerPreference/'
                                . 'Basic/_form',
                            [
                                'item' =>
                                $resolvedItem,

                                'preference' =>
                                $resolvedPreference,

                                'selectedIds' =>
                                $resolvedSelectedIds,

                                'masterData' =>
                                $resolvedMasterData,

                                'validationErrors' =>
                                $resolvedValidationErrors,
                            ]
                        ) ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>