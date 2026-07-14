<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 * @var string|null $loggedInUserName
 */

$formAlert = session('formAlert');

$formAlert = is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="pb-5 pt-3">
    <div class="container">

        <?= view('Components/Alerts/FormAlert', [
            'alert' => $formAlert,
        ]) ?>

        <div class="card">
            <div class="card-body p-4">
                <h1 class="fs-22 mb-2">
                    Welcome,
                    <?= esc(
                        $loggedInUserName ?? 'Member'
                    ) ?>
                </h1>

                <?php if (
                    is_string($profileReference)
                    && $profileReference !== ''
                ): ?>
                    <p class="text-muted mb-0">
                        Profile reference:
                        <strong>
                            <?= esc($profileReference) ?>
                        </strong>
                    </p>
                <?php endif; ?>

                <!-- Keep this form for dashboard-security.js. -->
                <form
                    method="post"
                    action="<?= url_to('web.logout') ?>"
                    id="dashboardLogoutForm"
                    class="d-none">

                    <?= csrf_field() ?>
                </form>
            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>