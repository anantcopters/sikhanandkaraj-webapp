<?php

declare(strict_types=1);

/**
 * Additional Partner Preference edit page.
 *
 * Used for:
 *
 * - Religious: Community
 * - Professional Preference
 * - Location
 * - Any Special Request
 *
 * @var string                     $item
 * @var string                     $itemTitle
 * @var string                     $sectionKey
 * @var array<string, mixed>       $masterData
 * @var array<string, list<int|string>> $selectedValues
 * @var array<string, mixed>|null  $religiousPreference
 * @var array<string, mixed>|null  $professionalPreference
 * @var array<string, mixed>|null  $locationPreference
 * @var array<string, mixed>|null  $specialRequest
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

$resolvedSectionKey = isset($sectionKey)
    ? trim((string) $sectionKey)
    : '';

$resolvedMasterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$resolvedSelectedValues = is_array(
    $selectedValues ?? null
)
    ? $selectedValues
    : [];

$resolvedReligiousPreference = is_array(
    $religiousPreference ?? null
)
    ? $religiousPreference
    : [];

$resolvedProfessionalPreference = is_array(
    $professionalPreference ?? null
)
    ? $professionalPreference
    : [];

$resolvedLocationPreference = is_array(
    $locationPreference ?? null
)
    ? $locationPreference
    : [];

$resolvedSpecialRequest = is_array(
    $specialRequest ?? null
)
    ? $specialRequest
    : [];

$resolvedValidationErrors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];
?>

<section class="py-3 py-lg-3">
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
                                    $resolvedSectionKey,
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
                        class="d-flex
                            align-items-center
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
                                    fw-semibold
                                    mb-1">

                                <?= esc(
                                    $resolvedItemTitle
                                ) ?>
                            </h2>

                            <p
                                class="text-muted
                                    fs-13 mb-0">

                                Set your preferred
                                partner criteria.
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
                                . 'Additional/_form',
                            [
                                'item' =>
                                $resolvedItem,

                                'masterData' =>
                                $resolvedMasterData,

                                'selectedValues' =>
                                $resolvedSelectedValues,

                                'religiousPreference' =>
                                $resolvedReligiousPreference,

                                'professionalPreference' =>
                                $resolvedProfessionalPreference,

                                'locationPreference' =>
                                $resolvedLocationPreference,

                                'specialRequest' =>
                                $resolvedSpecialRequest,

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