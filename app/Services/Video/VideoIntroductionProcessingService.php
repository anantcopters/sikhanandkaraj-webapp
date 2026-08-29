<?php

declare(strict_types=1);

namespace App\Services\Video;


use App\Models\MemberVideoIntroductionModel;
use App\Models\MemberVideoProcessingJobModel;
use App\Services\Aws\S3Service;

use CodeIgniter\Database\BaseConnection;
use Config\VideoIntroduction;
use RuntimeException;
use Throwable;

final class PermanentVideoProcessingException
extends RuntimeException {}

final class VideoIntroductionProcessingService
{
    public function __construct(
        private readonly MemberVideoIntroductionModel $videoModel,
        private readonly MemberVideoProcessingJobModel $jobModel,
        private readonly S3Service $s3Service,
        private readonly BaseConnection $database,
        private readonly VideoIntroduction $config
    ) {}

    public function processNext(
        string $workerId
    ): bool {
        $job = $this->claimNext(
            $workerId
        );

        if (! is_array($job)) {
            return false;
        }

        $videoId = (int) (
            $job['video_introduction_id']
            ?? 0
        );

        $video = $this->videoModel->find(
            $videoId
        );

        if (! is_array($video)) {
            $this->failJob(
                (int) $job['id'],
                $videoId,
                'Video record not found.',
                true,
                (int) (
                    $job['attempt_count']
                    ?? 1
                )
            );

            return true;
        }

        $workDirectory =
            rtrim(
                WRITEPATH,
                DIRECTORY_SEPARATOR
            )
            . DIRECTORY_SEPARATOR
            . 'video-introduction'
            . DIRECTORY_SEPARATOR
            . (string) $video['public_id'];

        $sourcePath =
            $workDirectory
            . DIRECTORY_SEPARATOR
            . 'source';

        $playbackPath =
            $workDirectory
            . DIRECTORY_SEPARATOR
            . 'playback.mp4';

        try {
            $this->s3Service->download(
                (string) $video['original_object_key'],
                $sourcePath
            );

            $metadata = $this->probe(
                $sourcePath
            );

            $duration = (float) (
                $metadata['duration']
                ?? 0.0
            );

            if (
                $duration
                < $this->config->minimumDurationSeconds
                || $duration
                > (
                    $this->config
                    ->maximumDurationSeconds
                    + 0.5
                )
            ) {
                throw new PermanentVideoProcessingException(
                    'Recorded duration must be between '
                        . $this->config
                        ->minimumDurationSeconds
                        . ' and '
                        . $this->config
                        ->maximumDurationSeconds
                        . ' seconds.'
                );
            }

            if (
                ($metadata['videoCodec'] ?? '') === ''
                || ($metadata['audioCodec'] ?? '') === ''
            ) {
                throw new RuntimeException(
                    'Both video and audio tracks are required.'
                );
            }

            $this->transcode(
                $sourcePath,
                $playbackPath
            );

            $publicId = trim(
                (string) (
                    $video['public_id']
                    ?? ''
                )
            );

            if ($publicId === '') {
                throw new RuntimeException(
                    'The Video Introduction identifier '
                        . 'is unavailable.'
                );
            }

            $playbackKey =
                'members/video-introduction/playback/'
                . $publicId
                . '.mp4';

            $this->s3Service->upload(
                $playbackPath,
                $playbackKey,
                'video/mp4',
                [
                    'media-type' =>
                    'member-video-introduction-playback',

                    'public-id' =>
                    $publicId,
                ],
                'inline; filename="video-introduction.mp4"'
            );

            $this->database->transBegin();

            try {
                $this->videoModel->update(
                    $videoId,
                    [
                        'moderation_status' =>
                        MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,

                        'playback_object_key' =>
                        $playbackKey,

                        /*
                        * New submissions do not generate a poster.
                        * The nullable column remains for legacy records.
                        */
                        'poster_object_key' =>
                        null,

                        'duration_seconds' =>
                        $duration,

                        'video_codec' =>
                        (string) $metadata['videoCodec'],

                        'audio_codec' =>
                        (string) $metadata['audioCodec'],

                        'width' =>
                        (int) $metadata['width'],

                        'height' =>
                        (int) $metadata['height'],

                        'processing_error' =>
                        null,

                        'processed_at' =>
                        date('Y-m-d H:i:sP'),
                    ]
                );

                $this->jobModel->update(
                    (int) $job['id'],
                    [
                        'status' =>
                        MemberVideoProcessingJobModel::STATUS_COMPLETED,

                        'completed_at' =>
                        date('Y-m-d H:i:sP'),

                        'locked_at' =>
                        null,

                        'locked_by' =>
                        null,

                        'last_error' =>
                        null,
                    ]
                );

                $this->database->transCommit();
            } catch (Throwable $exception) {
                $this->database->transRollback();

                $this->s3Service->deleteMany(
                    [
                        $playbackKey
                    ]
                );

                throw $exception;
            }
        } catch (Throwable $exception) {
            $attemptCount = (int) (
                $job['attempt_count']
                ?? 1
            );

            $permanent =
                $exception
                instanceof PermanentVideoProcessingException
                || $attemptCount
                >= $this->config
                ->maximumProcessingAttempts;

            log_message(
                $permanent
                    ? 'error'
                    : 'warning',
                'Video Introduction processing failed. '
                    . 'VideoId={videoId}, JobId={jobId}, '
                    . 'Attempt={attempt}, Permanent={permanent}, '
                    . 'Message={message}',
                [
                    'videoId' =>
                    $videoId,

                    'jobId' =>
                    (int) $job['id'],

                    'attempt' =>
                    $attemptCount,

                    'permanent' =>
                    $permanent
                        ? 'true'
                        : 'false',

                    'message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        500
                    ),
                ]
            );

            $this->failJob(
                (int) $job['id'],
                $videoId,
                $exception->getMessage(),
                $permanent,
                $attemptCount
            );
        } finally {
            $this->removeDirectory(
                $workDirectory
            );
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function claimNext(
        string $workerId
    ): ?array {
        $this->database->transBegin();

        try {
            $row = $this->database
                ->query(
                    "SELECT *
                        FROM member_video_processing_jobs
                        WHERE (
                            (
                                status IN (?, ?)
                                AND available_at <= CURRENT_TIMESTAMP
                            )
                            OR
                            (
                                status = ?
                                AND locked_at <=
                                    CURRENT_TIMESTAMP
                                    - INTERVAL '15 minutes'
                            )
                        )
                        AND attempt_count < ?
                        ORDER BY available_at, id
                        FOR UPDATE SKIP LOCKED
                        LIMIT 1",
                                    [
                                        MemberVideoProcessingJobModel::STATUS_PENDING,
                                        MemberVideoProcessingJobModel::STATUS_FAILED,
                                        MemberVideoProcessingJobModel::STATUS_PROCESSING,
                                        $this->config
                                            ->maximumProcessingAttempts,
                                    ]
                                )
                ->getRowArray();

            if (! is_array($row)) {
                $this->database->transCommit();

                return null;
            }

            $attempt =
                ((int) $row['attempt_count'])
                + 1;

            $this->jobModel->update(
                (int) $row['id'],
                [
                    'status' =>
                    MemberVideoProcessingJobModel::STATUS_PROCESSING,

                    'attempt_count' =>
                    $attempt,

                    'locked_at' =>
                    date('Y-m-d H:i:sP'),

                    'locked_by' =>
                    mb_substr(
                        trim($workerId),
                        0,
                        100
                    ),

                    'last_error' =>
                    null,
                ]
            );

            $this->videoModel->update(
                (int) $row['video_introduction_id'],
                [
                    'processing_attempts' =>
                    $attempt,

                    'processing_started_at' =>
                    date('Y-m-d H:i:sP'),
                ]
            );

            $this->database->transCommit();

            $row['attempt_count'] = $attempt;

            return $row;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Read and validate the media stream metadata.
     *
     * Browser-created WebM recordings, particularly Firefox
     * MediaRecorder output, may not contain format-level duration
     * metadata. In that situation duration is calculated from packet
     * timestamps instead.
     *
     * @return array{
     *     duration:float,
     *     videoCodec:string,
     *     audioCodec:string,
     *     width:int,
     *     height:int
     * }
     */
    private function probe(
        string $sourcePath
    ): array {
        $command =
            escapeshellarg(
                $this->config->ffprobeBinary
            )
            . ' -v error'
            . ' -show_streams'
            . ' -show_format'
            . ' -of json '
            . escapeshellarg(
                $sourcePath
            )
            . ' 2>&1';

        $output = [];

        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'The recorded video could not be decoded.'
            );
        }

        $data = json_decode(
            implode(
                "\n",
                $output
            ),
            true
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                'The recorded video metadata is invalid.'
            );
        }

        $video = [];

        $audio = [];

        foreach (
            ($data['streams'] ?? [])
            as $stream
        ) {
            if (!is_array($stream)) {
                continue;
            }

            if (
                ($stream['codec_type'] ?? '') === 'video'
                && $video === []
            ) {
                $video = $stream;
            }

            if (
                ($stream['codec_type'] ?? '') === 'audio'
                && $audio === []
            ) {
                $audio = $stream;
            }
        }

        if (
            $video === []
            || $audio === []
        ) {
            throw new RuntimeException(
                'Both video and audio tracks are required.'
            );
        }

        $duration = $this->numericDuration(
            $data['format']['duration']
                ?? null
        );

        if ($duration <= 0.0) {
            $duration = $this->numericDuration(
                $video['duration']
                    ?? null
            );
        }

        if ($duration <= 0.0) {
            $duration = $this->numericDuration(
                $audio['duration']
                    ?? null
            );
        }

        /*
     * Firefox MediaRecorder WebM output commonly reports N/A for
     * container and stream duration. Packet timestamps remain
     * available and provide the authoritative fallback.
     */
        if ($duration <= 0.0) {
            $duration =
                $this->durationFromPackets(
                    $sourcePath
                );
        }

        if ($duration <= 0.0) {
            throw new RuntimeException(
                'The recorded video duration could not be determined.'
            );
        }

        return [
            'duration' =>
            $duration,

            'videoCodec' =>
            trim(
                (string) (
                    $video['codec_name']
                    ?? ''
                )
            ),

            'audioCodec' =>
            trim(
                (string) (
                    $audio['codec_name']
                    ?? ''
                )
            ),

            'width' =>
            (int) (
                $video['width']
                ?? 0
            ),

            'height' =>
            (int) (
                $video['height']
                ?? 0
            ),
        ];
    }

    /**
     * Return a positive numeric duration or zero when FFprobe reports
     * an unavailable value such as N/A.
     */
    private function numericDuration(
        mixed $value
    ): float {
        if (!is_numeric($value)) {
            return 0.0;
        }

        $duration = (float) $value;

        return is_finite($duration)
            && $duration > 0.0
            ? $duration
            : 0.0;
    }

    /**
     * Calculate duration from the last timestamp of all audio and video
     * packets.
     *
     * This is required for browser-recorded WebM files that do not contain
     * format-level duration metadata.
     */
    private function durationFromPackets(
        string $sourcePath
    ): float {
        $command =
            escapeshellarg(
                $this->config->ffprobeBinary
            )
            . ' -v error'
            . ' -show_packets'
            . ' -show_entries '
            . escapeshellarg(
                'packet=pts_time,dts_time,duration_time'
            )
            . ' -of json '
            . escapeshellarg(
                $sourcePath
            )
            . ' 2>&1';

        $output = [];

        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'The recorded video duration could not be inspected.'
            );
        }

        $data = json_decode(
            implode(
                "\n",
                $output
            ),
            true
        );

        if (
            !is_array($data)
            || !is_array(
                $data['packets']
                    ?? null
            )
        ) {
            throw new RuntimeException(
                'The recorded video packet metadata is invalid.'
            );
        }

        $maximumEndTime = 0.0;

        foreach (
            $data['packets']
            as $packet
        ) {
            if (!is_array($packet)) {
                continue;
            }

            $timestamp =
                $this->numericDuration(
                    $packet['pts_time']
                        ?? null
                );

            if ($timestamp <= 0.0) {
                $timestamp =
                    $this->numericDuration(
                        $packet['dts_time']
                            ?? null
                    );
            }

            $packetDuration =
                $this->numericDuration(
                    $packet['duration_time']
                        ?? null
                );

            $packetEndTime =
                $timestamp
                + $packetDuration;

            if (
                is_finite($packetEndTime)
                && $packetEndTime
                > $maximumEndTime
            ) {
                $maximumEndTime =
                    $packetEndTime;
            }
        }

        return $maximumEndTime;
    }

    private function transcode(
        string $sourcePath,
        string $playbackPath
    ): void {
        /*
     * The first scale keeps the original aspect ratio and limits
     * playback width to 720 pixels.
     *
     * The second scale guarantees even dimensions because H.264
     * with yuv420p cannot encode odd dimensions such as 720x405.
     *
     * The permanent watermark is embedded in the MP4. No separate
     * HTML or poster watermark is required.
     */
        $videoFilter = implode(
            ',',
            [
                "scale='min(720,iw)':-2:"
                    . 'force_original_aspect_ratio=decrease',

                "scale='trunc(iw/2)*2':"
                    . "'trunc(ih/2)*2'",

                "drawtext="
                    . "text='Sikhanandkaraj.com':"
                    . 'fontcolor=white@0.70:'
                    . 'fontsize=18:'
                    . 'x=w-text_w-16:'
                    . 'y=16:'
                    . 'box=1:'
                    . 'boxcolor=black@0.35:'
                    . 'boxborderw=6',
            ]
        );

        $command =
            escapeshellarg(
                $this->config->ffmpegBinary
            )
            . ' -hide_banner'
            . ' -loglevel error'
            . ' -y'
            . ' -i '
            . escapeshellarg($sourcePath)
            . ' -map 0:v:0'
            . ' -map 0:a:0'
            . ' -vf '
            . escapeshellarg($videoFilter)
            . ' -c:v libx264'
            . ' -preset veryfast'
            . ' -crf 24'
            . ' -pix_fmt yuv420p'
            . ' -c:a aac'
            . ' -b:a 96k'
            . ' -movflags +faststart'
            . ' -t 30.5 '
            . escapeshellarg($playbackPath)
            . ' 2>&1';

        $output = [];

        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if (
            $exitCode !== 0
            || ! is_file($playbackPath)
            || filesize($playbackPath) === 0
        ) {
            log_message(
                'error',
                'Video Introduction FFmpeg transcoding failed. '
                    . 'ExitCode={exitCode}, Output={output}',
                [
                    'exitCode' =>
                    $exitCode,

                    'output' =>
                    mb_substr(
                        implode(
                            PHP_EOL,
                            $output
                        ),
                        0,
                        4000
                    ),
                ]
            );

            throw new RuntimeException(
                'The web playback video could not be created.'
            );
        }
    }

    private function failJob(
        int $jobId,
        int $videoId,
        string $error,
        bool $permanent,
        ?int $attemptCount = null
    ): void {
        $safeError = mb_substr(
            preg_replace(
                '/\s+/u',
                ' ',
                trim($error)
            ) ?? 'Processing failed.',
            0,
            500
        );

        $this->jobModel->update(
            $jobId,
            [
                'status' =>
                MemberVideoProcessingJobModel::STATUS_FAILED,

                'attempt_count' =>
                $permanent
                    ? $this->config
                    ->maximumProcessingAttempts
                    : max(
                        1,
                        (int) $attemptCount
                    ),

                'available_at' =>
                $permanent
                    ? date(
                        'Y-m-d H:i:sP'
                    )
                    : date(
                        'Y-m-d H:i:sP',
                        strtotime('+10 minutes')
                    ),

                'locked_at' =>
                null,

                'locked_by' =>
                null,

                'last_error' =>
                $safeError,
            ]
        );

        $this->videoModel->update(
            $videoId,
            [
                'moderation_status' =>
                $permanent
                    ? MemberVideoIntroductionModel::STATUS_PROCESSING_FAILED
                    : MemberVideoIntroductionModel::STATUS_PROCESSING,

                'processing_error' =>
                $permanent
                    ? $safeError
                    : null,
            ]
        );

        if (! $permanent) {
            return;
        }

        $video = $this->videoModel->find(
            $videoId
        );

        if (! is_array($video)) {
            return;
        }
    }

    private function removeDirectory(
        string $directory
    ): void {
        if (! is_dir($directory)) {
            return;
        }

        foreach (
            glob(
                $directory
                    . DIRECTORY_SEPARATOR
                    . '*'
            ) ?: []
            as $path
        ) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
