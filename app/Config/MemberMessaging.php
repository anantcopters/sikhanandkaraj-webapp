<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

final class MemberMessaging extends BaseConfig
{
    public bool $enabled = true;

    public int $maximumMessageLength = 200;

    public int $maximumConsecutiveUnanswered = 3;

    public string $interestMessage =
    '{PROFILE_ID} has expressed interest in your profile.';

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
            'newConversations' => 0,
            'perMember' => 0,
            'totalOutgoing' => 0,
        ],

        'GO' => [
            'newConversations' => 5,
            'perMember' => 10,
            'totalOutgoing' => 25,
        ],

        'PLUS' => [
            'newConversations' => 10,
            'perMember' => 15,
            'totalOutgoing' => 50,
        ],

        'PRO' => [
            'newConversations' => 20,
            'perMember' => 25,
            'totalOutgoing' => 100,
        ],
    ];
}
