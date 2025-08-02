<?php
// Target URL
$url = 'https://dpboss.boston/';

// Fetch the HTML content using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$html = curl_exec($ch);
curl_close($ch);

// Load HTML in DOMDocument
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Select the div with class "lv-mc"
$resultsBlock = $xpath->query('//div[@class="lv-mc"]')->item(0);

// Final output array
$output = [];

if ($resultsBlock) {
    $spans = $resultsBlock->getElementsByTagName('span');

    for ($i = 0; $i < $spans->length; $i++) {
        $span = $spans->item($i);
        $class = $span->getAttribute('class');

        if ($class === 'h8') {
            $game = trim($span->nodeValue);
            $result = '';
            // check next sibling is h9
            if (isset($spans->item($i + 1)) && $spans->item($i + 1)->getAttribute('class') === 'h9') {
                $result = trim($spans->item($i + 1)->nodeValue);
            }
            $output[] = [
                'game' => $game,
                'result' => $result
            ];
        }
    }
}

// Display the result as plain text (or convert to JSON if needed)
foreach ($output as $row) {
    echo $row['game'] . "\n";
    echo $row['result'] . "\n\n";
}

// If you want JSON output instead:
// echo json_encode($output, JSON_PRETTY_PRINT);