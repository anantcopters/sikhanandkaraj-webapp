<div
    class="d-flex flex-column flex-md-row
        align-items-md-center justify-content-between
        gap-3 mb-4">
    <div>
        <a
            href="<?= url_to('web.dashboard') ?>"
            class="d-inline-flex align-items-center
                gap-1 text-primary fw-medium mb-2">
            <i
                class="ri-arrow-left-line"
                aria-hidden="true"></i>

            Back to dashboard
        </a>

        <h1 class="fs-24 fw-semibold mb-1">
            Complete Your Profile
        </h1>

        <p class="text-muted mb-0">
            Add one section at a time to improve your
            profile visibility and match quality.
        </p>
    </div>

    <a
        href="<?= url_to('web.profile.view') ?>"
        class="btn btn-outline-primary waves-effect waves-light shadow-none
        d-inline-flex align-items-center
        justify-content-center gap-2">
        <i
            class="ri-eye-line"
            aria-hidden="true"></i>

        View Profile
    </a>
</div>