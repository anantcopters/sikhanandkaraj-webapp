<?php

declare(strict_types=1);

/**
 * @var list<string>|null $urls
 */

$resolvedUrls = is_array($urls ?? null)
    ? array_values(
        array_filter(
            $urls,
            static fn(mixed $url): bool => is_string($url)
                && trim($url) !== ''
        )
    )
    : [];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($resolvedUrls as $url): ?>
        <url>
            <loc><?= htmlspecialchars(
                        $url,
                        ENT_QUOTES | ENT_XML1,
                        'UTF-8'
                    ) ?></loc>
        </url>
    <?php endforeach; ?>
</urlset>