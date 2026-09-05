<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class MemberMessaging extends BaseConfig
{
    /*
     * Global member-messaging switch.
     *
     * Disabling messaging prevents new manual sends.
     * Existing conversation history remains preserved.
     */
    public bool $enabled =
    true;

    /*
     * Product decision:
     *
     * V1 uses 200 characters rather than the source document's
     * original recommended 500-character maximum.
     */
    public int $maximumMessageLength =
    200;

    /*
     * Universal safety ceiling for every Paid plan.
     */
    public int $maximumConsecutiveUnanswered =
    3;

    /*
     * System-generated Interest message.
     *
     * {PROFILE_ID} is replaced server-side.
     * This remains a SYSTEM event and is never presented as
     * member-authored content.
     */
    public string $interestMessage =
    '{PROFILE_ID} has expressed interest in your profile.';

    public string $interestAcceptedMessage =
    'Interest Accepted';

    public string $interestDeclinedMessage =
    'Interest Declined';

    public string $interestWithdrawnMessage =
    'Interest Withdrawn';

    public string $safetyWarning =
    'Stay safe: Avoid sharing OTPs, financial information, '
        . 'Aadhaar details or sending money. '
        . 'Report suspicious behaviour.';

    /**
     * @var array<string,array{
     *     newConversations:int,
     *     perMember:int,
     *     totalOutgoing:int
     * }>
     */
    public array $limits = [
        'FREE' => [
            'newConversations' =>
            0,

            'perMember' =>
            0,

            'totalOutgoing' =>
            0,
        ],

        'GO' => [
            'newConversations' =>
            5,

            'perMember' =>
            10,

            'totalOutgoing' =>
            25,
        ],

        'PLUS' => [
            'newConversations' =>
            10,

            'perMember' =>
            15,

            'totalOutgoing' =>
            50,
        ],

        'PRO' => [
            'newConversations' =>
            20,

            'perMember' =>
            25,

            'totalOutgoing' =>
            100,
        ],
    ];
}
