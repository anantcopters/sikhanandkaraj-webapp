'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const config = document.querySelector(
        '[data-video-recorder-config]'
    );

    if (!config) {
        return;
    }

    const live = document.querySelector(
        '[data-camera-preview]'
    );

    const preview = document.querySelector(
        '[data-recording-preview]'
    );

    const enable = document.querySelector(
        '[data-enable-camera]'
    );

    const start = document.querySelector(
        '[data-start-recording]'
    );

    const stop = document.querySelector(
        '[data-stop-recording]'
    );

    const retake = document.querySelector(
        '[data-retake]'
    );

    const countdown = document.querySelector(
        '[data-countdown]'
    );

    const status = document.querySelector(
        '[data-recorder-status]'
    );

    const error = document.querySelector(
        '[data-recorder-error]'
    );

    const fileInput = document.querySelector(
        '[data-recorded-file]'
    );

    const consent = document.querySelector(
        '[data-privacy-consent]'
    );

    const submit = document.querySelector(
        '[data-video-form] [data-submit-button]'
    );

    const submitReview = document.querySelector(
        '[data-submit-review]'
    );

    const minSeconds = Number(
        config.dataset.minSeconds
        || 15
    );

    const maxSeconds = Number(
        config.dataset.maxSeconds
        || 30
    );

    const maxSizeBytes = Number(
        config.dataset.maxSizeBytes
        || 41943040
    );

    /*
     * MediaRecorder timing and encoded media duration can differ
     * slightly because recording stops on media-frame boundaries.
     * Keep this aligned with the server-side FFprobe tolerance.
     */
    const durationToleranceSeconds = 0.5;

    let stream = null;

    let recorder = null;

    let chunks = [];

    let startedAt = 0;

    let elapsed = 0;

    let timer = null;

    let maximumTimer = null;

    let validRecording = false;

    let previewUrl = null;

    const supportedMimeTypes = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm;codecs=vp8',
        'video/webm',
        'video/mp4;codecs=h264,aac',
        'video/mp4;codecs=avc1,mp4a.40.2',
        'video/mp4',
    ];

    const mimeType = window.MediaRecorder
        ? supportedMimeTypes.find((type) => (
            typeof MediaRecorder.isTypeSupported !== 'function'
            || MediaRecorder.isTypeSupported(type)
        )) || ''
        : '';

    const showError = (message) => {
        error.textContent = message;

        error.classList.remove(
            'd-none'
        );
    };

    const clearError = () => {
        error.textContent = '';

        error.classList.add(
            'd-none'
        );
    };

    const setRecorderStatus = (
        message,
        colorClass
    ) => {
        status.textContent = message;

        status.classList.remove(
            'text-muted',
            'text-danger',
            'text-success',
            'text-primary'
        );

        status.classList.add(
            colorClass
        );
    };

    const updateSubmit = () => {
        submit.disabled = !(
            validRecording
            && consent.checked
        );

        submitReview.classList.toggle(
            'd-none',
            !validRecording
        );

        if (!stream) {
            enable.disabled = !consent.checked;
        }
    };

    const releaseCamera = () => {
        if (stream) {
            stream
                .getTracks()
                .forEach((track) => {
                    track.stop();
                });
        }

        stream = null;

        live.srcObject = null;
    };

    const clearRecordingTimers = () => {
        if (timer !== null) {
            window.clearInterval(
                timer
            );

            timer = null;
        }

        if (maximumTimer !== null) {
            window.clearTimeout(
                maximumTimer
            );

            maximumTimer = null;
        }
    };

    const stopRecorder = () => {
        if (
            recorder?.state
            === 'recording'
        ) {
            recorder.stop();
        }
    };

    const readMediaDuration = (
        blob
    ) => new Promise(
        (resolve, reject) => {
            const media = document.createElement(
                'video'
            );

            const objectUrl =
                URL.createObjectURL(
                    blob
                );

            const cleanup = () => {
                URL.revokeObjectURL(
                    objectUrl
                );

                media.removeAttribute(
                    'src'
                );

                media.load();
            };

            media.preload = 'metadata';

            media.addEventListener(
                'loadedmetadata',
                () => {
                    const duration =
                        Number(
                            media.duration
                        );

                    if (
                        Number.isFinite(duration)
                        && duration > 0
                    ) {
                        cleanup();

                        resolve(
                            duration
                        );

                        return;
                    }

                    cleanup();

                    reject(
                        new Error(
                            'The recorded video duration '
                            + 'could not be determined.'
                        )
                    );
                },
                {
                    once: true,
                }
            );

            media.addEventListener(
                'error',
                () => {
                    cleanup();

                    reject(
                        new Error(
                            'The recorded video could not '
                            + 'be inspected. Please retake it.'
                        )
                    );
                },
                {
                    once: true,
                }
            );

            media.src = objectUrl;
        }
    );

    if (!window.isSecureContext) {
        enable.disabled = true;

        showError(
            'Camera and microphone access requires a secure HTTPS '
            + 'connection. Please open this page using HTTPS.'
        );

        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        enable.disabled = true;

        showError(
            'Camera and microphone access is unavailable. '
            + 'Check your browser permissions and ensure that '
            + 'camera access is allowed for this website.'
        );

        return;
    }

    if (!window.MediaRecorder) {
        enable.disabled = true;

        showError(
            'Video recording is not supported by this browser. '
            + 'Please use the latest Chrome, Edge, Firefox or Safari.'
        );

        return;
    }

    enable.addEventListener(
        'click',
        async () => {
            clearError();

            try {
                stream =
                    await navigator.mediaDevices
                        .getUserMedia(
                            {
                                video: {
                                    facingMode: 'user',

                                    width: {
                                        ideal: 1280,
                                    },

                                    height: {
                                        ideal: 720,
                                    },

                                    aspectRatio: {
                                        ideal: 16 / 9,
                                    },
                                },

                                audio: {
                                    echoCancellation: true,
                                    noiseSuppression: true,
                                },
                            }
                        );

                if (
                    !stream.getVideoTracks().length
                    || !stream.getAudioTracks().length
                ) {
                    throw new Error(
                        'Both camera and microphone are required.'
                    );
                }

                live.srcObject = stream;

                await live.play();

                enable.classList.add(
                    'd-none'
                );

                start.classList.remove(
                    'd-none'
                );

                setRecorderStatus(
                    'Camera and microphone ready',
                    'text-success'
                );
            } catch (exception) {
                releaseCamera();

                showError(
                    exception.message
                    || 'Camera or microphone permission '
                    + 'was denied.'
                );
            }
        }
    );

    start.addEventListener(
        'click',
        () => {
            clearError();

            clearRecordingTimers();

            chunks = [];

            elapsed = 0;

            validRecording = false;

            fileInput.value = '';

            submitReview.classList.add(
                'd-none'
            );

            updateSubmit();

            const recorderOptions = {
                videoBitsPerSecond: 1800000,
                audioBitsPerSecond: 96000,
            };

            if (mimeType !== '') {
                recorderOptions.mimeType =
                    mimeType;
            }

            try {
                recorder = new MediaRecorder(
                    stream,
                    recorderOptions
                );
            } catch (exception) {
                releaseCamera();

                showError(
                    'The browser could not start video recording. '
                    + 'Please close other applications using the camera '
                    + 'and try again.'
                );

                return;
            }

            recorder.addEventListener(
                'dataavailable',
                (event) => {
                    if (event.data.size > 0) {
                        chunks.push(
                            event.data
                        );
                    }
                }
            );

            recorder.addEventListener(
                'stop',
                finishRecording,
                {
                    once: true,
                }
            );

            recorder.start(1000);

            startedAt = performance.now();

            start.classList.add(
                'd-none'
            );

            stop.classList.remove(
                'd-none'
            );

            stop.disabled = true;

            setRecorderStatus(
                'Recording...',
                'text-primary'
            );

            timer = window.setInterval(
                () => {
                    const elapsedMilliseconds =
                        performance.now()
                        - startedAt;

                    elapsed =
                        elapsedMilliseconds
                        / 1000;

                    const remaining =
                        Math.max(
                            0,
                            Math.ceil(
                                maxSeconds
                                - elapsed
                            )
                        );

                    countdown.textContent =
                        `${remaining} seconds remaining`;

                    stop.disabled =
                        elapsed < minSeconds;
                },
                250
            );

            /*
             * This timeout is the hard browser-side limit.
             * The interval above is only responsible for UI updates.
             */
            maximumTimer = window.setTimeout(
                stopRecorder,
                maxSeconds * 1000
            );
        }
    );

    stop.addEventListener(
        'click',
        () => {
            if (
                recorder?.state === 'recording'
                && elapsed >= minSeconds
            ) {
                stopRecorder();
            }
        }
    );

    async function finishRecording() {
        clearRecordingTimers();

        validRecording = false;

        updateSubmit();

        stop.classList.add(
            'd-none'
        );

        const wallClockDuration =
            (
                performance.now()
                - startedAt
            ) / 1000;

        countdown.textContent =
            `${Math.round(
                wallClockDuration
            )} seconds recorded`;

        const recordedMimeType =
            recorder?.mimeType
            || mimeType
            || chunks[0]?.type
            || 'video/webm';

        const blob = new Blob(
            chunks,
            {
                type:
                    recordedMimeType
                        .split(';')[0],
            }
        );

        if (blob.size <= 0) {
            showError(
                'The recording is empty. '
                + 'Please retake it.'
            );

            retake.classList.remove(
                'd-none'
            );

            releaseCamera();

            return;
        }

        if (blob.size > maxSizeBytes) {
            showError(
                'The recording is too large. '
                + 'Please retake it.'
            );

            retake.classList.remove(
                'd-none'
            );

            releaseCamera();

            return;
        }

        let mediaDuration;

        try {
            mediaDuration =
                await readMediaDuration(
                    blob
                );
        } catch (exception) {
            showError(
                exception.message
                || 'The recorded video could not '
                + 'be validated. Please retake it.'
            );

            retake.classList.remove(
                'd-none'
            );

            releaseCamera();

            return;
        }

        if (
            mediaDuration < minSeconds
            || mediaDuration
            > (
                maxSeconds
                + durationToleranceSeconds
            )
        ) {
            showError(
                `The recorded video is ${mediaDuration.toFixed(1)} `
                + `seconds. Record between ${minSeconds} `
                + `and ${maxSeconds} seconds.`
            );

            countdown.textContent =
                `${mediaDuration.toFixed(1)} seconds recorded`;

            retake.classList.remove(
                'd-none'
            );

            releaseCamera();

            return;
        }

        const extension =
            blob.type === 'video/mp4'
                ? 'mp4'
                : 'webm';

        const file = new File(
            [blob],
            `video-introduction.${extension}`,
            {
                type: blob.type,
                lastModified: Date.now(),
            }
        );

        const transfer =
            new DataTransfer();

        transfer.items.add(
            file
        );

        fileInput.files =
            transfer.files;

        if (previewUrl !== null) {
            URL.revokeObjectURL(
                previewUrl
            );
        }

        previewUrl =
            URL.createObjectURL(
                blob
            );

        preview.src =
            previewUrl;

        live.classList.add(
            'd-none'
        );

        preview.classList.remove(
            'd-none'
        );

        retake.classList.remove(
            'd-none'
        );

        countdown.textContent =
            `${mediaDuration.toFixed(1)} seconds recorded`;

        setRecorderStatus(
            'Preview your recording before submitting',
            'text-primary'
        );

        validRecording = true;

        updateSubmit();

        releaseCamera();
    }

    retake.addEventListener(
        'click',
        () => {
            window.location.reload();
        }
    );

    consent.addEventListener(
        'change',
        updateSubmit
    );

    updateSubmit();

    window.addEventListener(
        'beforeunload',
        () => {
            clearRecordingTimers();

            releaseCamera();

            if (previewUrl !== null) {
                URL.revokeObjectURL(
                    previewUrl
                );
            }
        }
    );
});