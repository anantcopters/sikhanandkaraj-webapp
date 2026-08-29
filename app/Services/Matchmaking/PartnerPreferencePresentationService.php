<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
use App\Services\PartnerPreference\BasicPartnerPreferenceService;

/**
 * Converts authoritative Partner Preference matching criteria into
 * presentation-ready rows.
 *
 * IMPORTANT:
 *
 * PartnerPreferenceMatchService remains responsible for:
 *
 * - deciding which preferences are configured;
 * - determining whether each criterion matched;
 * - compulsory-preference behaviour;
 * - match percentage.
 *
 * This service is presentation-only. It resolves the matching keys into
 * the same readable labels and configured values used by member and Admin
 * profile screens.
 */
final class PartnerPreferencePresentationService
{
    public function __construct(
        private readonly BasicPartnerPreferenceService
        $basicPartnerPreferenceService,

        private readonly AdditionalPartnerPreferenceService
        $additionalPartnerPreferenceService
    ) {}

    /**
     * Build presentation rows for the supplied authoritative Match criteria.
     *
     * @param list<array<string, mixed>> $matchCriteria
     *
     * @return list<array{
     *     key:string,
     *     title:string,
     *     value:string,
     *     matched:bool,
     *     isCompulsory:bool
     * }>
     */
    public function displayItems(
        int $preferenceOwnerUserId,
        array $matchCriteria
    ): array {
        if (
            $preferenceOwnerUserId <= 0
            || $matchCriteria === []
        ) {
            return [];
        }

        $basicPreferenceSummary =
            $this
            ->basicPartnerPreferenceService
            ->getSummaryForUser(
                $preferenceOwnerUserId
            );

        $additionalPreferenceSections =
            $this
            ->additionalPartnerPreferenceService
            ->getSummarySections(
                $preferenceOwnerUserId
            );

        /*
         * Build one lookup containing every readable preference value.
         *
         * Do not use presentation completion state to determine whether a
         * preference participates in matching. The supplied match criteria
         * are already authoritative for that decision.
         */
        $displayItemsByKey = [];

        $basicItems =
            isset(
                $basicPreferenceSummary['items']
            )
            && is_array(
                $basicPreferenceSummary['items']
            )
            ? $basicPreferenceSummary['items']
            : [];

        foreach ($basicItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->addDisplayItem(
                $displayItemsByKey,
                $item
            );
        }

        foreach (
            $additionalPreferenceSections
            as $section
        ) {
            if (!is_array($section)) {
                continue;
            }

            $sectionItems =
                isset($section['items'])
                && is_array(
                    $section['items']
                )
                ? $section['items']
                : [];

            foreach ($sectionItems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $key = trim(
                    (string) (
                        $item['key']
                        ?? ''
                    )
                );

                /*
                 * Special Request is not part of structured matching.
                 */
                if (
                    $key === ''
                    || $key === 'special-request'
                ) {
                    continue;
                }

                $this->addDisplayItem(
                    $displayItemsByKey,
                    $item
                );
            }
        }

        /*
         * Construct rows FROM the matching criteria rather than from the
         * preference forms.
         *
         * Therefore:
         *
         * displayed row count
         *     ===
         * criteria actually used by matchmaking.
         */
        $displayItems = [];

        foreach ($matchCriteria as $criterion) {
            if (!is_array($criterion)) {
                continue;
            }

            $key = trim(
                (string) (
                    $criterion['key']
                    ?? ''
                )
            );

            if ($key === '') {
                continue;
            }

            $displayItem =
                $displayItemsByKey[$key]
                ?? null;

            if (!is_array($displayItem)) {
                continue;
            }

            $title = trim(
                (string) (
                    $displayItem['title']
                    ?? ''
                )
            );

            if ($title === '') {
                continue;
            }

            $value = trim(
                (string) (
                    $displayItem['value']
                    ?? ''
                )
            );

            if (
                $value === ''
                || $value === 'Not added'
            ) {
                $value =
                    'Preference selected';
            }

            $displayItems[] = [
                'key' =>
                $key,

                'title' =>
                $title,

                'value' =>
                $value,

                'matched' => (
                    $criterion['matched']
                    ?? false
                ) === true,

                'isCompulsory' => (
                    $criterion['compulsory']
                    ?? false
                ) === true,
            ];
        }

        return $displayItems;
    }

    /**
     * Add one readable preference item to the lookup.
     *
     * @param array<string, array<string, string>> $displayItemsByKey
     * @param array<string, mixed>                 $item
     */
    private function addDisplayItem(
        array &$displayItemsByKey,
        array $item
    ): void {
        $key = trim(
            (string) (
                $item['key']
                ?? ''
            )
        );

        if ($key === '') {
            return;
        }

        $displayItemsByKey[$key] = [
            'key' =>
            $key,

            'title' =>
            trim(
                (string) (
                    $item['title']
                    ?? ''
                )
            ),

            'value' =>
            trim(
                (string) (
                    $item['value']
                    ?? ''
                )
            ),
        ];
    }
}
