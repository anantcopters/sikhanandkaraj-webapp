<?= $this->extend('Prelaunch/Layouts/Main') ?>

<?= $this->section('content') ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border border-danger border-opacity-25 shadow-sm">
                <div class="card-body p-4 text-center">
                    <i
                        class="bi bi-check-circle fs-1 text-success"
                        aria-hidden="true"></i>

                    <h1 class="h3 mt-3">
                        Profile saved
                    </h1>

                    <p class="text-muted">
                        The profile and three photographs were saved
                        as DRAFT and will be reviewed by the
                        administrator.
                    </p>

                    <a
                        href="<?= route_to(
                                    'prelaunch.profile.index'
                                ) ?>"
                        class="btn btn-primary">
                        Create another profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>