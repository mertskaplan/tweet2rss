<?php
/*
    Name: tweet2rss
    Version: 1.0
    Author: Mert S. Kaplan, mail@mertskaplan.com
    Licence: MIT Licence - https://github.com/mertskaplan/tweet2rss/blob/main/LICENSE
    Source: https://github.com/mertskaplan/tweet2rss
*/

// Set default timezone for date operations
date_default_timezone_set('Europe/London');

// Set HTTP headers to output RSS XML
header('Content-Type: application/rss+xml; charset=UTF-8');
header('Content-Disposition: inline; filename="tweet2rss.xml"');

// Initialize response array structure
$response = [
    'success' => false,
    'message' => '',
    'account' => null,
    'fetched_url' => null,
    'tweets' => []
];

// Get the 'account' parameter from query string
$account = isset($_GET['account']) ? trim($_GET['account']) : '';
if ($account === '') {
    // If no account is provided, return error and RSS response
    http_response_code(400);
    $response['message'] = 'Account parameter required. Example: ?account=mertskaplan';
    output_rss($response);
    exit;
}
$response['account'] = $account;

// Build the source URL to fetch tweets from
$source_url = "https://r.jina.ai/https://xstalk.com/profile/" . rawurlencode($account);
$response['fetched_url'] = $source_url;

// Use cURL to fetch the content from the source URL
$ch = curl_init($source_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$content = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle errors if fetching fails
if ($content === false || $http_code >= 400) {
    http_response_code(502);
    $response['message'] = 'Could not get source account. ' . ($curl_err ? $curl_err : "HTTP $http_code");
    output_rss($response);
    exit;
}

// Split the fetched content into individual tweet parts
$parts = preg_split('/\*\s*\*\s*\*/u', $content, -1, PREG_SPLIT_NO_EMPTY);
array_pop($parts); // Remove empty last element if present

// Function to convert relative time strings to GMT+3 RSS date format
function parse_relative_time_to_gmt3($rel_str) {
    $rel_str = trim(preg_replace('/\bago\b/i', '', $rel_str));
    $dt = new DateTime('now', new DateTimeZone('Europe/London'));

    // Parse relative time like "5 min" or "2 days"
    if (preg_match('/^(\d+)\s*([a-zA-Z]+)$/u', $rel_str, $m)) {
        $num = (int)$m[1];
        $unit = strtolower($m[2]);
        switch ($unit) {
            case 's': case 'sec': case 'secs': case 'second': case 'seconds': $dt->modify("-{$num} seconds"); break;
            case 'm': case 'min': case 'mins': case 'minute': case 'minutes': $dt->modify("-{$num} minutes"); break;
            case 'h': case 'hr': case 'hrs': case 'hour': case 'hours': $dt->modify("-{$num} hours"); break;
            case 'd': case 'day': case 'days': $dt->modify("-{$num} days"); break;
            case 'w': case 'wk': case 'wks': case 'week': case 'weeks': $dt->modify("-" . ($num * 7) . " days"); break;
            case 'mo': case 'mos': case 'month': case 'months': $dt->sub(new DateInterval("P{$num}M")); break;
            case 'y': case 'yr': case 'yrs': case 'year': case 'years': $dt->sub(new DateInterval("P{$num}Y")); break;
        }
        return $dt->format(DATE_RSS);
    }

    // Parse absolute date strings like "Jan 2, 2024"
    if (preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})(?:,\s*(\d{4}))?/i', $rel_str, $m)) {
        $month = $m[1]; $day = (int)$m[2];
        $year = isset($m[3]) ? (int)$m[3] : (int)date('Y');
        $date = DateTime::createFromFormat('M j Y H:i', "$month $day $year 00:00", new DateTimeZone('Europe/London'));
        return $date ? $date->format(DATE_RSS) : null;
    }

    // Default: current time
    return $dt->format(DATE_RSS);
}

// Extract relative time string from brackets like "[5 min ago]"
function extract_relative_bracket($text) {
    if (!preg_match_all('/\[([^\]]+)\]/u', $text, $all)) return null;
    foreach ($all[1] as $c) {
        $c_trim = trim($c);
        if (preg_match('/^\d+\s*(s|sec|secs|second|seconds|m|min|mins|minute|minutes|h|hr|hrs|hour|hours|d|day|days|w|wk|wks|week|weeks|mo|mos|month|months|y|yr|yrs|year|years)$/i', $c_trim)) return $c_trim;
        if (preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2}(?:,\s*\d{4})?$/i', $c_trim)) return $c_trim;
    }
    return null;
}

// === Process each tweet part ===
foreach ($parts as $part) {
    $orig = trim($part);
    if ($orig === '') continue;

    // Initialize tweet object
    $tweet_obj = [
        'url' => null,
        'account' => $response['account'],
        'tweet_time' => null,
        'tweet' => null
    ];

    // Extract and convert tweet time
    $time_rel = extract_relative_bracket($orig);
    if ($time_rel !== null) $tweet_obj['tweet_time'] = parse_relative_time_to_gmt3($time_rel);

    // Extract tweet URL and convert to x.com format
    if (preg_match('/(https:\/\/xstalk\.com\/profile\/[^)\s]+\/status\/\d+)/u', $orig, $um)) {
        $url = $um[1];
        $converted = preg_replace('#^https://xstalk\.com/profile/#', 'https://x.com/', $url);
        $tweet_obj['url'] = $converted;
    }

    // Extract tweet text after URL
    $tweet_text = '';
    if (preg_match('/(https:\/\/xstalk\.com\/profile\/[^)\s]+\/status\/\d+)\)/u', $orig, $mpos)) {
        $pos = strrpos($orig, ')');
        if ($pos !== false) $tweet_text = trim(substr($orig, $pos + 1));
    }
    if ($tweet_text === '' && isset($url)) {
        $pos2 = strpos($orig, $url);
        if ($pos2 !== false) $tweet_text = trim(substr($orig, $pos2 + strlen($url)));
    }
    if ($tweet_text === '') $tweet_text = $orig;
    $tweet_text = preg_replace('/^\)\s*\n*/', '', $tweet_text);
    $tweet_obj['tweet'] = $tweet_text;

    // Add processed tweet to response array
    $response['tweets'][] = $tweet_obj;
}

// Mark success and output RSS feed
$response['success'] = true;
output_rss($response);

// Function to generate RSS XML output
function output_rss($data) {
    $account = $data['account'] ?? 'unknown';
    $feed_title = "X Feed for @$account";
    $feed_link = "https://x.com/" . htmlspecialchars($account);
    $feed_desc = htmlspecialchars("Tweets of account X with username $account.", ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $feed_date = date(DATE_RSS);

    // Print RSS XML header and channel information
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<rss version=\"2.0\">\n";
    echo "<channel>\n";
    echo "<title>{$feed_title}</title>\n";
    echo "<link>{$feed_link}</link>\n";
    echo "<description>{$feed_desc}</description>\n";
    echo "<lastBuildDate>{$feed_date}</lastBuildDate>\n";
    echo "<generator>tweet2rss by mertskaplan</generator>\n";

    // Print each tweet as an RSS item
    if (!empty($data['tweets'])) {
        foreach ($data['tweets'] as $tweet) {
            $title = htmlspecialchars(mb_strimwidth($tweet['tweet'], 0, 100, '...'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars($tweet['tweet'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $link = htmlspecialchars($tweet['url'] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $pubDate = $tweet['tweet_time'] ?? date(DATE_RSS);

            echo "<item>\n";
            echo "  <title>{$title}</title>\n";
            echo "  <link>{$link}</link>\n";
            echo "  <guid isPermaLink=\"true\">{$link}</guid>\n";
            echo "  <description>{$description}</description>\n";
            echo "  <pubDate>{$pubDate}</pubDate>\n";
            echo "</item>\n";
        }
    }

    echo "</channel>\n";
    echo "</rss>\n";
}
