<?php

declare(strict_types=1);

$resolvedName = trim((string) ($recipientName ?? '')) ?: 'Member';
$resolvedHeading = trim((string) ($heading ?? 'Interest Update'));
$resolvedMessage = trim((string) ($message ?? 'There is an update to one of your interests.'));
$resolvedUrl = trim((string) ($actionUrl ?? ''));
$resolvedButton = trim((string) ($buttonLabel ?? 'View Interests'));

ob_start();
?>
<p style="margin:0 0 18px;font-size:15px;line-height:1.7;">
    Sat Sri Akal <?= esc($resolvedName) ?>,
</p>
<h2 style="margin:0 0 14px;color:#310a57;font-size:20px;line-height:1.35;">
    <?= esc($resolvedHeading) ?>
</h2>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;">
    <?= esc($resolvedMessage) ?>
</p>
<?php if ($resolvedUrl !== ''): ?>
    <?= view('Emails/Components/Button', ['url' => $resolvedUrl, 'label' => $resolvedButton]) ?>
<?php endif; ?>
<p style="margin:0;color:#6c757d;font-size:13px;line-height:1.6;">
    For your privacy, profile and contact details are not included in this email.
    Sign in to Sikhanandkaraj to view the latest information available to your account.
</p>
<?php
$emailContent = (string) ob_get_clean();

echo view('Emails/Layouts/Base', [
    'emailTitle' => $resolvedHeading,
    'emailSubtitle' => 'Matrimonial Activity',
    'emailContent' => $emailContent,
]);
