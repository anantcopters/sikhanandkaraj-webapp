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

    let stream = null;

    let recorder = null;

    let chunks = [];

    let startedAt = 0;

    let elapsed = 0;

    let timer = null;

    let validRecording = false;

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

    const updateSubmit = () => {
        submit.disabled = !(
            validRecording
            && consent.checked
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
                                        ideal: 720,
                                    },

                                    height: {
                                        ideal: 720,
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

                status.textContent =
                    'Camera and microphone ready';
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

            chunks = [];

            elapsed = 0;

            validRecording = false;

            updateSubmit();

            const recorderOptions = {
                videoBitsPerSecond: 1800000,
                audioBitsPerSecond: 96000,
            };

            if (mimeType !== '') {
                recorderOptions.mimeType = mimeType;
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
                finishRecording
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

            status.textContent = 'Recording';

            timer = window.setInterval(
                () => {
                    elapsed = Math.floor(
                        (
                            performance.now()
                            - startedAt
                        ) / 1000
                    );

                    countdown.textContent =
                        `${Math.max(
                            0,
                            maxSeconds - elapsed
                        )} seconds remaining`;

                    stop.disabled =
                        elapsed < minSeconds;

                    if (
                        elapsed >= maxSeconds
                        && recorder?.state
                        === 'recording'
                    ) {
                        recorder.stop();
                    }
                },
                250
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
                recorder.stop();
            }
        }
    );

    function finishRecording() {
        window.clearInterval(
            timer
        );

        elapsed = Math.round(
            (
                performance.now()
                - startedAt
            ) / 1000
        );

        stop.classList.add(
            'd-none'
        );

        countdown.textContent =
            `${elapsed} seconds recorded`;

        if (
            elapsed < minSeconds
            || elapsed > maxSeconds + 1
        ) {
            showError(
                `Record between ${minSeconds} `
                + `and ${maxSeconds} seconds.`
            );

            retake.classList.remove(
                'd-none'
            );

            return;
        }

        const recordedMimeType =
            recorder?.mimeType
            || mimeType
            || chunks[0]?.type
            || 'video/webm';

        const blob = new Blob(
            chunks,
            {
                type: recordedMimeType.split(';')[0],
            }
        );

        if (blob.size > maxSizeBytes) {
            showError(
                'The recording is too large. '
                + 'Please retake it.'
            );

            retake.classList.remove(
                'd-none'
            );

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

        const transfer = new DataTransfer();

        transfer.items.add(file);

        fileInput.files = transfer.files;

        preview.src =
            URL.createObjectURL(blob);

        live.classList.add(
            'd-none'
        );

        preview.classList.remove(
            'd-none'
        );

        retake.classList.remove(
            'd-none'
        );

        status.textContent =
            'Preview your recording before submitting';

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
        releaseCamera
    );
});