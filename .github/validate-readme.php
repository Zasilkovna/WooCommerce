<?php

if (!function_exists('curl_version')) {
    exit ('ERROR: curl is needed');
}

/**
 * @param string $url
 * @param array $data
 * @return bool|string|int
 */
function curlPostData(string $url, array $data)
{
    $ch = curl_init();
    if ($ch === false) {
        return false;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $data = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ((string)$data !== '') {
        return $data;
    }

    return $http_status;
}

/**
 * @param string $type
 * @param string $curlResult
 * @return array|null
 */
function getList(string $type, string $curlResult): ?array
{
    $matches = [];
    if (preg_match('#<h2>' . $type . '</h2>\s+<ul>(.+?)</ul>#s', $curlResult, $matches)) {
        $uls = [];
        preg_match_all('#(<li>(.+?)</li>)\s+#s', $matches[1], $uls);
        return $uls;
    }
    return null;
}

$readmeContent = file_get_contents(__DIR__ . '/../readme.txt');

$data = [
    'readme' => $readmeContent,
];

$curlResult = curlPostData('https://wpreadme.com/', $data);

if ($curlResult === false) {
    exit ('ERROR: curl_init failed');
}

if (is_numeric($curlResult)) {
    exit ('ERROR: failed to run validation, HTTP code: ' . $curlResult);
}

file_put_contents('/tmp/validate-readme.html', $curlResult);

$warnings = getList('Warnings', $curlResult);
$notes = getList('Notes', $curlResult);

if ($warnings) {
    foreach ($warnings[2] as $row) {
        echo 'ERROR: ' . strip_tags($row) . PHP_EOL;
    }
}
if ($notes) {
    foreach ($notes[2] as $row) {
        echo 'NOTE: ' . strip_tags($row) . PHP_EOL;
    }
}
