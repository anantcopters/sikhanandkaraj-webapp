<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 */

$formAlert = session('formAlert');

$formAlert = is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-5">
    <div class="container">

        <?= view('Components/Alerts/FormAlert', [
            'alert' => $formAlert,
        ]) ?>

        <div class="card">
            <div class="card-body p-4">
                <h1 class="fs-22">
                    Welcome to your dashboard
                </h1>

                <?php if (
                    is_string($profileReference)
                    && $profileReference !== ''
                ): ?>
                    <p class="text-muted">
                        Profile reference:
                        <strong>
                            <?= esc($profileReference) ?>
                        </strong>
                    </p>
                <?php endif; ?>

                <form
                    method="post"
                    action="<?= url_to('web.logout') ?>"
                    id="dashboardLogoutForm">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-outline-danger">
                        Logout
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>

