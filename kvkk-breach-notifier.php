<?php

$leaks = scrapeWebsite();
$leakCount = count($leaks);
$today = localize_date(date('d M Y'));

$botToken = 'your-bot-token';
$channel = 'your-channel-id';

if ($leakCount) {
    $message = ":bangbang: Yeni veri ihlali var ($today)\n";
    foreach ($leaks as $leak) {
        $message .= "• <https://www.kvkk.gov.tr{$leak['link']}|{$leak['title']}>\n";
    }
    chatPostMessage($channel, $message, $botToken);
}

function scrapeWebsite()
{
    $today = localize_date(date('d M Y'));
    $url = 'https://www.kvkk.gov.tr/veri-ihlali-bildirimi/';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (iPhone14,6; U; CPU iPhone OS 15_4 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19E241 Safari/602.1'
    ));

    $response = curl_exec($curl);

    if ($response === false) {
        die('Error in cURL request: ' . curl_error($curl));
    }

    curl_close($curl);

    $dom = new DOMDocument();
    @$dom->loadHTML($response); // The @ is used to suppress warnings generated by invalid HTML structures

    $xpath = new DOMXPath($dom);
    $bigPost = $xpath->query("//*[contains(@class, 'blog-post-inner')]");

    $result = [];

    foreach ($bigPost as $big) {
        $titles = $xpath->query(".//*[contains(@class, 'blog-post-title')]", $big);
        $dates = $xpath->query(".//*[contains(@class, 'small-text')]", $big);
        $links = $xpath->query(".//*[contains(@class, 'justify-content-end')]/a", $big);

        foreach ($dates as $date) {
            $tmpDate = trim($date->textContent);
        }

        if ($today !== $tmpDate) {
            continue;
        }

        foreach ($links as $link) {
            $tmpLink = trim($link->getAttribute('href'));
        }

        foreach ($titles as $title) {
            $tmpTitle = trim($title->textContent);
        }

        $result[] = ['date' => $tmpDate, 'link' => $tmpLink, 'title' => $tmpTitle];

    }

    $smallPosts = $xpath->query("//*[contains(@class, 'box-content-inner')]");
    foreach ($smallPosts as $small) {
        $titles = $xpath->query(".//*[contains(@class, 'blog-grid-title')]", $small);
        $dates = $xpath->query(".//*[contains(@class, 'small-text')]", $small);
        $links = $xpath->query(".//*[contains(@class, 'blog-grid-title')]/a", $small);

        foreach ($dates as $date) {
            $tmpDate = trim($date->textContent);
        }

        if ($today !== $tmpDate) {
            continue;
        }

        foreach ($links as $link) {
            $tmpLink = trim($link->getAttribute('href'));
        }

        foreach ($titles as $title) {
            $tmpTitle = trim($title->textContent);
        }

        $result[] = ['date' => $tmpDate, 'link' => $tmpLink, 'title' => $tmpTitle];

    }

    return $result;
}

function chatPostMessage($channel, $message, $botToken)
{
    $url = 'https://slack.com/api/chat.postMessage';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "channel=$channel&text=$message");

    $headers = [
        "Authorization: Bearer $botToken",
        'Content-Type: application/x-www-form-urlencoded'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);

    if ($output === false) {
        echo 'Curl error: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);
}

// https://gist.github.com/CanNuhlar/75a9f9642c547fb2d5c7b3e012da2388
function localize_date($date)
{

    $days = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
    $months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

    $daysLocal = array("Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar");
    $monthsLocal = array(
        "Ocak",
        "Şubat",
        "Mart",
        "Nisan",
        "Mayıs",
        "Haziran",
        "Temmuz",
        "Ağustos",
        "Eylül",
        "Ekim",
        "Kasım",
        "Aralık"
    );

    foreach ($days as $key => $day) {
        $date = str_replace($day, $daysLocal[$key], $date);
    }

    foreach ($months as $key => $month) {
        $date = str_replace($month, $monthsLocal[$key], $date);
    }

    return $date;
}