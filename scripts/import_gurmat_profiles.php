<?php

declare(strict_types=1);

use CodeIgniter\Boot;
use Config\Paths;

/*
 * Gurmat development profile importer.
 *
 * DEVELOPMENT USE ONLY.
 *
 * Flow:
 *
 * 1. Browse all Gurmat result pages.
 * 2. Collect every unique /view-entry/{id}.
 * 3. Check each ID against the local database.
 * 4. Existing IDs are skipped WITHOUT requesting their detail page.
 * 5. New profile pages are downloaded, parsed and inserted.
 *
 * Run:
 *
 *     php scripts/import_gurmat_profiles.php
 *
 * IMPORTANT:
 * This script must never be exposed through Apache/a browser route.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);

    exit(1);
}

const GURMAT_BASE_URL = 'https://www.gurmat.com';

const GURMAT_BROWSE_PATH = '/browse'
    . '?gender=f'
    . '&min_height_cm=122'
    . '&max_height_cm=250'
    . '&min_age=18'
    . '&max_age=120'
    . '&marital_status=any'
    . '&amrit=any'
    . '&general_location=any'
    . '&sort_order=co_desc';

/*
 * Be polite to the remote server.
 *
 * This delay applies between profile requests.
 */
const GURMAT_REQUEST_DELAY_MICROSECONDS = 750000;

/*
 * Retry temporary failures.
 */
const GURMAT_MAX_RETRIES = 3;

const GURMAT_CONNECT_TIMEOUT_SECONDS = 10;

const GURMAT_REQUEST_TIMEOUT_SECONDS = 30;

const GURMAT_TABLE = 'gurmat_profiles';

/*
 * Prevent an unexpected pagination loop from running forever.
 */
const GURMAT_MAX_BROWSE_PAGES = 500;

/*
 * Browser-like but honest User-Agent.
 */
const GURMAT_USER_AGENT =
'Mozilla/5.0 (compatible; SikhanandkarajDevelopmentImporter/1.0)';

/*
|--------------------------------------------------------------------------
| Bootstrap CodeIgniter
|--------------------------------------------------------------------------
*/

$projectRoot = realpath(
    __DIR__ . DIRECTORY_SEPARATOR . '..'
);

if ($projectRoot === false) {
    fwrite(
        STDERR,
        'The project root directory could not be resolved.'
            . PHP_EOL
    );

    exit(1);
}

$publicPath = realpath(
    $projectRoot . DIRECTORY_SEPARATOR . 'public'
);

if ($publicPath === false) {
    fwrite(
        STDERR,
        'The public directory could not be resolved.'
            . PHP_EOL
    );

    exit(1);
}

define(
    'FCPATH',
    rtrim($publicPath, '\\/')
        . DIRECTORY_SEPARATOR
);

if (!defined('ENVIRONMENT')) {
    $environment = getenv('CI_ENVIRONMENT');

    if (
        !is_string($environment)
        || trim($environment) === ''
    ) {
        $environment = 'development';
    }

    define(
        'ENVIRONMENT',
        strtolower(trim($environment))
    );
}

chdir($projectRoot);

require $projectRoot
    . DIRECTORY_SEPARATOR
    . 'app'
    . DIRECTORY_SEPARATOR
    . 'Config'
    . DIRECTORY_SEPARATOR
    . 'Paths.php';

$paths = new Paths();

require rtrim(
    $paths->systemDirectory,
    '\\/'
) . DIRECTORY_SEPARATOR . 'Boot.php';

Boot::bootConsole($paths);

/*
|--------------------------------------------------------------------------
| Development-only safety check
|--------------------------------------------------------------------------
*/

$deployment = strtolower(
    trim((string) env('APP_DEPLOYMENT', ''))
);

if ($deployment !== 'development') {
    fwrite(
        STDERR,
        PHP_EOL
            . 'Gurmat import is DEVELOPMENT ONLY.'
            . PHP_EOL
            . 'Current APP_DEPLOYMENT: '
            . ($deployment !== '' ? $deployment : '[not set]')
            . PHP_EOL
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Normalize visible HTML text.
 */
function normalizeText(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $value = preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    if ($value === null) {
        return null;
    }

    $value = trim($value);

    return $value !== ''
        ? $value
        : null;
}

/**
 * Convert relative Gurmat URLs to absolute URLs.
 */
function absoluteGurmatUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return GURMAT_BASE_URL;
    }

    if (
        str_starts_with($url, 'http://')
        || str_starts_with($url, 'https://')
    ) {
        return $url;
    }

    if (str_starts_with($url, '//')) {
        return 'https:' . $url;
    }

    return GURMAT_BASE_URL
        . '/'
        . ltrim($url, '/');
}


/**
 * Download HTML with retries.
 */
function fetchHtml(string $url): string
{
    $lastError = null;

    for (
        $attempt = 1;
        $attempt <= GURMAT_MAX_RETRIES;
        $attempt++
    ) {
        $ch = curl_init();

        if ($ch === false) {
            throw new RuntimeException(
                'Unable to initialize cURL.'
            );
        }

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT =>
                GURMAT_CONNECT_TIMEOUT_SECONDS,
                CURLOPT_TIMEOUT =>
                GURMAT_REQUEST_TIMEOUT_SECONDS,
                CURLOPT_USERAGENT =>
                GURMAT_USER_AGENT,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: en-US,en;q=0.9',
                    'Cache-Control: no-cache',
                ],
            ]
        );

        $body = curl_exec($ch);

        $curlError = curl_error($ch);

        $httpStatus = (int) curl_getinfo(
            $ch,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($ch);

        if (
            is_string($body)
            && $body !== ''
            && $httpStatus >= 200
            && $httpStatus < 300
        ) {
            return $body;
        }

        $lastError = sprintf(
            'HTTP %d%s',
            $httpStatus,
            $curlError !== ''
                ? ' - ' . $curlError
                : ''
        );

        /*
         * Do not keep retrying normal client-side errors.
         *
         * 429 is intentionally retryable.
         */
        if (
            $httpStatus >= 400
            && $httpStatus < 500
            && $httpStatus !== 429
        ) {
            break;
        }

        if ($attempt < GURMAT_MAX_RETRIES) {
            sleep($attempt * 2);
        }
    }

    throw new RuntimeException(
        sprintf(
            'Unable to fetch %s (%s)',
            $url,
            $lastError ?? 'unknown error'
        )
    );
}

/**
 * Create DOMDocument safely from remote HTML.
 */
function createDom(string $html): DOMDocument
{
    $dom = new DOMDocument();

    $previous = libxml_use_internal_errors(true);

    try {
        $loaded = $dom->loadHTML(
            $html,
            LIBXML_NOWARNING | LIBXML_NOERROR
        );

        if ($loaded === false) {
            throw new RuntimeException(
                'Unable to parse HTML response.'
            );
        }
    } finally {
        libxml_clear_errors();

        libxml_use_internal_errors($previous);
    }

    return $dom;
}

/**
 * Extract Gurmat's reported result count.
 *
 * Example:
 *
 *     <p><b>696 results.</b></p>
 */
function extractReportedResultCount(
    DOMXPath $xpath
): ?int {
    $nodes = $xpath->query('//p/b');

    if ($nodes === false) {
        return null;
    }

    foreach ($nodes as $node) {
        $text = normalizeText(
            $node->textContent
        );

        if (
            $text !== null
            && preg_match(
                '/^([\d,]+)\s+results?\.?$/i',
                $text,
                $matches
            ) === 1
        ) {
            return (int) str_replace(
                ',',
                '',
                $matches[1]
            );
        }
    }

    return null;
}

/**
 * Extract pagination information from the Gurmat browse page.
 *
 * Example:
 *
 *     <p>
 *         <b>696 results.</b>
 *         Page 1 of 35
 *     </p>
 *
 * Returns:
 *
 *     [
 *         'current_page' => 1,
 *         'total_pages'  => 35,
 *     ]
 */
function extractPagination(
    DOMXPath $xpath
): array {
    $nodes = $xpath->query(
        '//p[b[contains(., "results")]]'
    );

    if ($nodes === false) {
        throw new RuntimeException(
            'Unable to find Gurmat pagination information.'
        );
    }

    foreach ($nodes as $node) {
        $text = normalizeText(
            $node->textContent
        );

        if ($text === null) {
            continue;
        }

        if (
            preg_match(
                '/Page\s+(\d+)\s+of\s+(\d+)/i',
                $text,
                $matches
            ) !== 1
        ) {
            continue;
        }

        $currentPage = (int) $matches[1];
        $totalPages = (int) $matches[2];

        if (
            $currentPage <= 0
            || $totalPages <= 0
            || $currentPage > $totalPages
        ) {
            throw new RuntimeException(
                sprintf(
                    'Invalid pagination values: page %d of %d.',
                    $currentPage,
                    $totalPages
                )
            );
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
        ];
    }

    throw new RuntimeException(
        'Unable to parse Gurmat pagination information.'
    );
}

/**
 * Build the browse URL for a specific Gurmat result page.
 *
 * Gurmat uses:
 *
 *     &p=2
 *     &p=3
 *     ...
 *
 * Page 1 does not require the p parameter.
 */
function buildBrowsePageUrl(
    int $page
): string {
    $url = GURMAT_BASE_URL
        . GURMAT_BROWSE_PATH;

    if ($page <= 1) {
        return $url;
    }

    return $url
        . '&p='
        . $page;
}

/**
 * Extract profile IDs from a browse page.
 *
 * Examples:
 *
 *     /view-entry/15762
 *     https://www.gurmat.com/view-entry/15762
 */
function extractProfileIds(
    DOMXPath $xpath
): array {
    $ids = [];

    $nodes = $xpath->query(
        '//a[contains(@href, "/view-entry/")]'
    );

    if ($nodes === false) {
        return [];
    }

    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }

        $href = trim(
            $node->getAttribute('href')
        );

        if (
            preg_match(
                '~(?:^|/)view-entry/(\d+)(?:[/?#]|$)~',
                $href,
                $matches
            ) !== 1
        ) {
            continue;
        }

        $id = (int) $matches[1];

        if ($id <= 0) {
            continue;
        }

        $ids[$id] = $id;
    }

    return array_values($ids);
}


/**
 * Find the first node text matching XPath.
 */
function xpathText(
    DOMXPath $xpath,
    string $expression
): ?string {
    $nodes = $xpath->query($expression);

    if (
        $nodes === false
        || $nodes->length === 0
    ) {
        return null;
    }

    $node = $nodes->item(0);

    if (!$node instanceof DOMNode) {
        return null;
    }

    return normalizeText(
        $node->textContent
    );
}

/**
 * Extract the profile's table attributes.
 *
 * Gurmat currently renders:
 *
 *     <tr>
 *         <th>Gender</th>
 *         <td>f</td>
 *     </tr>
 */
function extractProfileAttributes(
    DOMXPath $xpath
): array {
    $attributes = [];

    $rows = $xpath->query('//table//tr');

    if ($rows === false) {
        return $attributes;
    }

    foreach ($rows as $row) {
        if (!$row instanceof DOMElement) {
            continue;
        }

        $headers = $xpath->query(
            './th',
            $row
        );

        $values = $xpath->query(
            './td',
            $row
        );

        if (
            $headers === false
            || $values === false
            || $headers->length === 0
            || $values->length === 0
        ) {
            continue;
        }

        $keyNode = $headers->item(0);
        $valueNode = $values->item(0);

        if (
            !$keyNode instanceof DOMNode
            || !$valueNode instanceof DOMNode
        ) {
            continue;
        }

        $key = normalizeText(
            $keyNode->textContent
        );

        $value = normalizeText(
            $valueNode->textContent
        );

        if ($key === null) {
            continue;
        }

        $attributes[strtolower($key)] = $value;
    }

    return $attributes;
}

/**
 * Extract description, contact, image and timestamp from
 * the right-hand profile column.
 */
function extractProfileContent(
    DOMXPath $xpath
): array {
    $result = [
        'description' => null,
        'contact_text' => null,
        'image_url' => null,
        'listing_created_at' => null,
    ];

    /*
     * On the supplied page the profile details are rendered inside:
     *
     *     <div class="row">
     *         <div class="col-sm-6">...</div>
     *         <div class="col-sm-6">
     *             <p>description</p>
     *             <p><small>contact</small></p>
     *             <img ...>
     *             <small>timestamp</small>
     *         </div>
     *     </div>
     *
     * Select the column containing an image or small elements and then
     * inspect its direct content.
     */
    $columns = $xpath->query(
        '//div[contains('
            . 'concat(" ", normalize-space(@class), " "), '
            . '" col-sm-6 "'
            . ')]'
    );

    if ($columns === false) {
        return $result;
    }

    foreach ($columns as $column) {
        if (!$column instanceof DOMElement) {
            continue;
        }

        $images = $xpath->query(
            './/img',
            $column
        );

        $smallNodes = $xpath->query(
            './/small',
            $column
        );

        if (
            ($images === false || $images->length === 0)
            && (
                $smallNodes === false
                || $smallNodes->length === 0
            )
        ) {
            continue;
        }

        /*
         * Description:
         * first direct <p> whose visible text is not empty.
         */
        $paragraphs = $xpath->query(
            './p',
            $column
        );

        if ($paragraphs !== false) {
            foreach ($paragraphs as $paragraph) {
                if (!$paragraph instanceof DOMElement) {
                    continue;
                }

                /*
                 * A <p><small>...</small></p> is contact,
                 * not description.
                 */
                $smallInside = $xpath->query(
                    './small',
                    $paragraph
                );

                if (
                    $smallInside !== false
                    && $smallInside->length > 0
                ) {
                    continue;
                }

                $text = normalizeText(
                    $paragraph->textContent
                );

                if ($text !== null) {
                    $result['description'] = $text;

                    break;
                }
            }
        }

        /*
         * Contact text.
         */
        $contactNodes = $xpath->query(
            './p/small',
            $column
        );

        if (
            $contactNodes !== false
            && $contactNodes->length > 0
        ) {
            $contactNode = $contactNodes->item(0);

            if ($contactNode instanceof DOMNode) {
                $result['contact_text'] =
                    normalizeText(
                        $contactNode->textContent
                    );
            }
        }

        /*
         * Profile image.
         */
        if (
            $images !== false
            && $images->length > 0
        ) {
            $image = $images->item(0);

            if ($image instanceof DOMElement) {
                $src = trim(
                    $image->getAttribute('src')
                );

                if ($src !== '') {
                    $result['image_url'] =
                        absoluteGurmatUrl($src);
                }
            }
        }

        /*
         * Timestamp.
         *
         * Ignore <small> nested inside <p>, because that's the contact
         * element. We want the direct <small> after the image.
         */
        $timestampNodes = $xpath->query(
            './small',
            $column
        );

        if ($timestampNodes !== false) {
            foreach ($timestampNodes as $timestampNode) {
                $text = normalizeText(
                    $timestampNode->textContent
                );

                if (
                    $text !== null
                    && preg_match(
                        '/^\d{4}-\d{2}-\d{2}'
                            . '\s+'
                            . '\d{2}:\d{2}:\d{2}$/',
                        $text
                    ) === 1
                ) {
                    $result['listing_created_at'] =
                        $text;

                    break;
                }
            }
        }

        return $result;
    }

    return $result;
}

/**
 * Parse one /view-entry/{id} page.
 */
function parseProfile(
    int $expectedId,
    string $url,
    string $html
): array {
    $dom = createDom($html);

    $xpath = new DOMXPath($dom);

    /*
     * Example:
     *
     *     <h4>Viewing Entry #15762</h4>
     */
    $entryHeading = xpathText(
        $xpath,
        '//h4[contains(., "Viewing Entry #")]'
    );

    if (
        $entryHeading === null
        || preg_match(
            '/Viewing\s+Entry\s+#(\d+)/i',
            $entryHeading,
            $matches
        ) !== 1
    ) {
        throw new RuntimeException(
            'Profile page does not contain a valid entry ID.'
        );
    }

    $actualId = (int) $matches[1];

    if ($actualId !== $expectedId) {
        throw new RuntimeException(
            sprintf(
                'Profile ID mismatch. Expected %d, received %d.',
                $expectedId,
                $actualId
            )
        );
    }

    $title = xpathText(
        $xpath,
        '//h4[contains(., "Viewing Entry #")]/following-sibling::h1[1]'
    );

    $attributes = extractProfileAttributes(
        $xpath
    );

    $content = extractProfileContent(
        $xpath
    );

    $height = $attributes['height (cm)']
        ?? null;

    $age = $attributes['age']
        ?? null;

    return [
        'gurmat_id' => $actualId,

        'title' => $title,

        'gender' =>
        $attributes['gender']
            ?? null,

        'height_cm' =>
        is_numeric($height)
            ? (float) $height
            : null,

        'age' =>
        is_numeric($age)
            ? (int) $age
            : null,

        'marital_status' =>
        $attributes['marital status']
            ?? null,

        'amrit' =>
        $attributes['amrit']
            ?? null,

        'location' =>
        $attributes['location']
            ?? null,

        'education' =>
        $attributes['education']
            ?? null,

        'description' =>
        $content['description'],

        'contact_text' =>
        $content['contact_text'],

        'image_url' =>
        $content['image_url'],

        'listing_created_at' =>
        $content['listing_created_at'],

        'source_url' => $url,

        'imported_at' =>
        date('Y-m-d H:i:s'),
    ];
}

/**
 * Create the development table if it doesn't exist.
 */
function ensureTable(
    \CodeIgniter\Database\BaseConnection $db
): void {
    /*
     * Project production uses PostgreSQL.
     *
     * This is deliberately kept inside this development-only script
     * because the imported data is not part of the application schema.
     */
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS gurmat_profiles (
    gurmat_id INTEGER PRIMARY KEY,

    title VARCHAR(500) NULL,
    gender VARCHAR(20) NULL,
    height_cm NUMERIC(6, 2) NULL,
    age INTEGER NULL,
    marital_status VARCHAR(100) NULL,
    amrit VARCHAR(50) NULL,

    location TEXT NULL,
    education TEXT NULL,

    description TEXT NULL,
    contact_text TEXT NULL,
    image_url TEXT NULL,

    listing_created_at TIMESTAMP NULL,

    source_url TEXT NOT NULL,
    imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL;

    $db->query($sql);
}

/**
 * Load all IDs already stored locally.
 *
 * Loading once avoids hundreds of individual SELECT queries.
 */
function loadExistingIds(
    \CodeIgniter\Database\BaseConnection $db
): array {
    $rows = $db
        ->table(GURMAT_TABLE)
        ->select('gurmat_id')
        ->get()
        ->getResultArray();

    $ids = [];

    foreach ($rows as $row) {
        $id = (int) (
            $row['gurmat_id']
            ?? 0
        );

        if ($id > 0) {
            $ids[$id] = true;
        }
    }

    return $ids;
}

/**
 * Collect every unique profile ID from all Gurmat browse pages.
 *
 * Gurmat explicitly renders:
 *
 *     696 results. Page 1 of 35
 *
 * and uses:
 *
 *     &p=2
 *     &p=3
 *     ...
 *
 * Therefore page 1 determines the total number of pages and we
 * explicitly request every page from 1 through total_pages.
 */
function collectProfileIds(): array
{
    $profileIds = [];

    $reportedCount = null;

    /*
     * --------------------------------------------------------------
     * Page 1
     * --------------------------------------------------------------
     *
     * We must fetch page 1 first because it tells us:
     *
     *     - total result count
     *     - total page count
     */
    $firstPageUrl = buildBrowsePageUrl(1);

    fwrite(
        STDOUT,
        sprintf(
            "Browse page 1: %s%s",
            $firstPageUrl,
            PHP_EOL
        )
    );

    $html = fetchHtml(
        $firstPageUrl
    );

    $dom = createDom(
        $html
    );

    $xpath = new DOMXPath(
        $dom
    );

    $reportedCount =
        extractReportedResultCount(
            $xpath
        );

    if ($reportedCount !== null) {
        fwrite(
            STDOUT,
            sprintf(
                "Reported results: %d%s",
                $reportedCount,
                PHP_EOL
            )
        );
    }

    $pagination = extractPagination(
        $xpath
    );

    $totalPages = (int) (
        $pagination['total_pages']
        ?? 0
    );

    if (
        $totalPages <= 0
        || $totalPages > GURMAT_MAX_BROWSE_PAGES
    ) {
        throw new RuntimeException(
            sprintf(
                'Invalid Gurmat page count: %d.',
                $totalPages
            )
        );
    }

    fwrite(
        STDOUT,
        sprintf(
            "Total browse pages: %d%s",
            $totalPages,
            PHP_EOL
        )
    );

    /*
     * Extract page 1 profile IDs.
     */
    $pageIds = extractProfileIds(
        $xpath
    );

    fwrite(
        STDOUT,
        sprintf(
            "Profiles found on page 1: %d%s",
            count($pageIds),
            PHP_EOL
        )
    );

    foreach ($pageIds as $id) {
        $profileIds[$id] = $id;
    }

    /*
     * --------------------------------------------------------------
     * Remaining pages
     * --------------------------------------------------------------
     */
    for (
        $page = 2;
        $page <= $totalPages;
        $page++
    ) {
        /*
         * Small delay between browse-page requests.
         */
        usleep(250000);

        $pageUrl = buildBrowsePageUrl(
            $page
        );

        fwrite(
            STDOUT,
            sprintf(
                "Browse page %d/%d: %s%s",
                $page,
                $totalPages,
                $pageUrl,
                PHP_EOL
            )
        );

        $html = fetchHtml(
            $pageUrl
        );

        $dom = createDom(
            $html
        );

        $xpath = new DOMXPath(
            $dom
        );

        /*
         * Validate that Gurmat returned the page we requested.
         */
        $pagePagination =
            extractPagination(
                $xpath
            );

        $returnedPage = (int) (
            $pagePagination['current_page']
            ?? 0
        );

        if ($returnedPage !== $page) {
            throw new RuntimeException(
                sprintf(
                    'Requested Gurmat page %d but received page %d.',
                    $page,
                    $returnedPage
                )
            );
        }

        $pageIds = extractProfileIds(
            $xpath
        );

        fwrite(
            STDOUT,
            sprintf(
                "Profiles found on page %d: %d%s",
                $page,
                count($pageIds),
                PHP_EOL
            )
        );

        /*
         * A normal non-final page should not suddenly contain
         * zero profiles.
         */
        if (
            $page < $totalPages
            && $pageIds === []
        ) {
            throw new RuntimeException(
                sprintf(
                    'Gurmat browse page %d returned no profile IDs.',
                    $page
                )
            );
        }

        foreach ($pageIds as $id) {
            $profileIds[$id] = $id;
        }
    }

    /*
     * Newest first.
     */
    krsort(
        $profileIds,
        SORT_NUMERIC
    );

    $discoveredCount =
        count($profileIds);

    /*
     * This is important.
     *
     * Previously we only warned and continued if Gurmat said 696
     * but we found 20. For an importer like this that is undesirable.
     *
     * Now we stop BEFORE downloading detail pages if pagination
     * didn't give us the complete result set.
     */
    if (
        $reportedCount !== null
        && $reportedCount !== $discoveredCount
    ) {
        throw new RuntimeException(
            sprintf(
                'Gurmat reported %d profiles but only %d unique '
                    . 'profile IDs were discovered across %d pages.',
                $reportedCount,
                $discoveredCount,
                $totalPages
            )
        );
    }

    return [
        'ids' => array_values(
            $profileIds
        ),

        'reported_count' =>
        $reportedCount,

        'pages' =>
        $totalPages,
    ];
}

/*
|--------------------------------------------------------------------------
| Import
|--------------------------------------------------------------------------
*/

try {
    $db = db_connect();

    ensureTable($db);

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Gurmat development profile import'
            . PHP_EOL
            . '================================='
            . PHP_EOL
            . PHP_EOL
    );

    /*
     * Phase 1:
     * Crawl browse pagination and discover IDs.
     */
    $browse = collectProfileIds();

    $profileIds = $browse['ids'];

    $reportedCount =
        $browse['reported_count'];

    $browsePages =
        $browse['pages'];

    $discoveredCount =
        count($profileIds);

    fwrite(
        STDOUT,
        PHP_EOL
            . sprintf(
                'Browse pages processed: %d',
                $browsePages
            )
            . PHP_EOL
            . sprintf(
                'Unique profile IDs discovered: %d',
                $discoveredCount
            )
            . PHP_EOL
    );

    if (
        $reportedCount !== null
        && $reportedCount !== $discoveredCount
    ) {
        fwrite(
            STDERR,
            PHP_EOL
                . 'WARNING: Gurmat reported '
                . $reportedCount
                . ' results, but '
                . $discoveredCount
                . ' unique profile IDs were discovered.'
                . PHP_EOL
                . 'The import will continue with the IDs '
                . 'that were actually discovered.'
                . PHP_EOL
                . PHP_EOL
        );
    }

    /*
     * Phase 2:
     * Load all locally existing IDs once.
     */
    $existingIds = loadExistingIds($db);

    $newIds = [];

    $skipped = 0;

    foreach ($profileIds as $gurmatId) {
        if (isset($existingIds[$gurmatId])) {
            $skipped++;

            continue;
        }

        $newIds[] = $gurmatId;
    }

    fwrite(
        STDOUT,
        sprintf(
            'Already in database: %d%s',
            $skipped,
            PHP_EOL
        )
    );

    fwrite(
        STDOUT,
        sprintf(
            'Profiles requiring download: %d%s',
            count($newIds),
            PHP_EOL
        )
    );

    /*
     * Phase 3:
     * Download ONLY missing profiles.
     */
    $inserted = 0;

    $failed = 0;

    $failures = [];

    $newTotal = count($newIds);

    foreach (
        $newIds as $index => $gurmatId
    ) {
        $position = $index + 1;

        $profileUrl = GURMAT_BASE_URL
            . '/view-entry/'
            . $gurmatId;

        fwrite(
            STDOUT,
            sprintf(
                '[%d/%d] #%d - downloading... ',
                $position,
                $newTotal,
                $gurmatId
            )
        );

        try {
            $html = fetchHtml(
                $profileUrl
            );

            $profile = parseProfile(
                $gurmatId,
                $profileUrl,
                $html
            );

            /*
             * A final DB-level duplicate-safe insert.
             *
             * The normal path has already filtered existing IDs,
             * but this protects against accidental concurrent runs.
             */
            $sql = <<<'SQL'
INSERT INTO gurmat_profiles (
    gurmat_id,
    title,
    gender,
    height_cm,
    age,
    marital_status,
    amrit,
    location,
    education,
    description,
    contact_text,
    image_url,
    listing_created_at,
    source_url,
    imported_at
) VALUES (
    :gurmat_id:,
    :title:,
    :gender:,
    :height_cm:,
    :age:,
    :marital_status:,
    :amrit:,
    :location:,
    :education:,
    :description:,
    :contact_text:,
    :image_url:,
    :listing_created_at:,
    :source_url:,
    :imported_at:
)
ON CONFLICT (gurmat_id) DO NOTHING
SQL;

            $db->query(
                $sql,
                $profile
            );

            /*
             * affectedRows() = 1 for inserted row,
             * 0 if ON CONFLICT ignored it.
             */
            if ($db->affectedRows() > 0) {
                $inserted++;

                $existingIds[$gurmatId] = true;

                fwrite(
                    STDOUT,
                    'INSERTED'
                        . PHP_EOL
                );
            } else {
                $skipped++;

                fwrite(
                    STDOUT,
                    'SKIPPED'
                        . PHP_EOL
                );
            }
        } catch (Throwable $exception) {
            $failed++;

            $failures[] = [
                'id' => $gurmatId,
                'url' => $profileUrl,
                'message' =>
                $exception->getMessage(),
            ];

            fwrite(
                STDOUT,
                'FAILED'
                    . PHP_EOL
            );

            fwrite(
                STDERR,
                '    '
                    . $exception->getMessage()
                    . PHP_EOL
            );
        }

        /*
         * Don't sleep after the final request.
         */
        if ($position < $newTotal) {
            usleep(
                GURMAT_REQUEST_DELAY_MICROSECONDS
            );
        }
    }

    /*
     * Final report.
     */
    fwrite(
        STDOUT,
        PHP_EOL
            . 'Import complete'
            . PHP_EOL
            . '==============='
            . PHP_EOL
    );

    if ($reportedCount !== null) {
        fwrite(
            STDOUT,
            sprintf(
                "Reported results:        %d%s",
                $reportedCount,
                PHP_EOL
            )
        );
    }

    fwrite(
        STDOUT,
        sprintf(
            "Browse pages:            %d%s",
            $browsePages,
            PHP_EOL
        )
    );

    fwrite(
        STDOUT,
        sprintf(
            "Unique IDs discovered:   %d%s",
            $discoveredCount,
            PHP_EOL
        )
    );

    fwrite(
        STDOUT,
        sprintf(
            "Inserted:                %d%s",
            $inserted,
            PHP_EOL
        )
    );

    fwrite(
        STDOUT,
        sprintf(
            "Skipped/already present: %d%s",
            $skipped,
            PHP_EOL
        )
    );

    fwrite(
        STDOUT,
        sprintf(
            "Failed:                  %d%s",
            $failed,
            PHP_EOL
        )
    );

    if ($failures !== []) {
        fwrite(
            STDERR,
            PHP_EOL
                . 'Failed profiles'
                . PHP_EOL
                . '---------------'
                . PHP_EOL
        );

        foreach ($failures as $failure) {
            fwrite(
                STDERR,
                sprintf(
                    "#%d | %s%s    %s%s",
                    $failure['id'],
                    $failure['url'],
                    PHP_EOL,
                    $failure['message'],
                    PHP_EOL
                )
            );
        }
    }

    exit($failed > 0
        ? 1
        : 0);
} catch (Throwable $exception) {
    log_message(
        'critical',
        'Gurmat development importer stopped: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        PHP_EOL
            . 'Gurmat import failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
