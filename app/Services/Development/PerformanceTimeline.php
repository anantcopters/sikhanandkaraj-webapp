<?php

declare(strict_types=1);

namespace App\Support\Development;

/**
 * Lightweight in-process performance timeline.
 *
 * Membership-27.
 *
 * This class has no database, framework or Search dependencies.
 *
 * It is instantiated only by the development Search profiler and passed into
 * the real MemberSearchService. Normal member Search does not create it.
 */
final class PerformanceTimeline
{
    /**
     * Nanosecond timestamp when profiling started.
     */
    private int $startedAt;

    /**
     * Nanosecond timestamp of the previous checkpoint.
     */
    private int $lastCheckpointAt;

    /**
     * Recorded pipeline checkpoints.
     *
     * @var list<array{
     *     name:string,
     *     elapsedMs:float,
     *     totalMs:float
     * }>
     */
    private array $checkpoints = [];

    public function __construct()
    {
        $now =
            hrtime(
                true
            );

        $this->startedAt =
            $now;

        $this->lastCheckpointAt =
            $now;
    }

    /**
     * Record completion of one Search pipeline stage.
     *
     * elapsedMs:
     * Time consumed since the previous checkpoint.
     *
     * totalMs:
     * Total time consumed since profiling began.
     */
    public function checkpoint(
        string $name
    ): void {
        $name =
            trim(
                $name
            );

        if ($name === '') {
            return;
        }

        $now =
            hrtime(
                true
            );

        $this->checkpoints[] = [
            'name' =>
            $name,

            'elapsedMs' =>
            round(
                (
                    $now
                    - $this->lastCheckpointAt
                )
                    / 1_000_000,
                3
            ),

            'totalMs' =>
            round(
                (
                    $now
                    - $this->startedAt
                )
                    / 1_000_000,
                3
            ),
        ];

        $this->lastCheckpointAt =
            $now;
    }

    /**
     * Return all recorded Search pipeline checkpoints.
     *
     * @return list<array{
     *     name:string,
     *     elapsedMs:float,
     *     totalMs:float
     * }>
     */
    public function checkpoints(): array
    {
        return $this->checkpoints;
    }

    /**
     * Return total elapsed profiling time in milliseconds.
     */
    public function totalElapsedMs(): float
    {
        return round(
            (
                hrtime(true)
                - $this->startedAt
            )
                / 1_000_000,
            3
        );
    }
}
