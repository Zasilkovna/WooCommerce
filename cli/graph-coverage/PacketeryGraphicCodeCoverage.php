<?php

class PacketeryGraphicCodeCoverage
{
    private static string $csvFile = 'coverage_report.csv';

    public static function downloadDataToCsv(string $owner, string $repo, string $token, string $startDate): void
    {
        // Fetch PRs
        $page = 1;
        $allCoverage = [];

        do {
            $prUrl = "https://api.github.com/repos/$owner/$repo/pulls?state=all&per_page=100&page=$page";
            $pullRequests = self::githubApiRequest($prUrl, $token);
            $page++;
            $filterDate = new DateTime($startDate);

            foreach ($pullRequests as $pr) {
                $createdAt = new DateTime($pr['created_at']);
                if ($createdAt < $filterDate) {
                    continue; // Skip older PRs
                }

                $prNumber = $pr['number'];
                $prTitle = $pr['title'];
                $baseBranch = $pr['base']['ref'];
                $headBranch = $pr['head']['ref'];

                $commentsUrl = "https://api.github.com/repos/$owner/$repo/issues/$prNumber/comments";
                $comments = self::githubApiRequest($commentsUrl, $token);

                foreach ($comments as $comment) {
                    $coverage = self::parseCoverageReport($comment['body']);
                    if ($coverage) {
                        $coverage['pr_number'] = $prNumber;
                        $coverage['pr_title'] = $prTitle;
                        $coverage['base_branch'] = $baseBranch;
                        $coverage['head_branch'] = $headBranch;
                        $allCoverage[] = $coverage;
                        break; // One report per PR assumed
                    }
                }
            }

        } while (count($pullRequests) === 100); // Pagination

        // Output results as CSV
        $fp = fopen(self::$csvFile, 'wb');
        fputcsv($fp, ['PR Number', 'Title', 'Base Branch', 'Head Branch', 'Date', 'Classes %', 'Methods %', 'Lines %']);

        foreach ($allCoverage as $row) {
            fputcsv($fp, [
                $row['pr_number'],
                $row['pr_title'],
                $row['base_branch'],
                $row['head_branch'],
                $row['date'],
                $row['classes'],
                $row['methods'],
                $row['lines'],
            ]);
        }

        fclose($fp);

        echo "Coverage data saved to " . self::$csvFile . "\n";
    }

    public static function generateImage(string $branchName, string $outputFile, ?int $maxCount = 30): void
    {
        // --- Step 1: Read and filter CSV ---
        $data = array_map('str_getcsv', file(self::$csvFile));

        $headers = $data[0];
        unset($data[0]);

        // Get column indices
        $baseIdx = array_search('Base Branch', $headers, true);
        $dateIdx = array_search('Date', $headers, true);
        $classesIdx = array_search('Classes %', $headers, true);
        $methodsIdx = array_search('Methods %', $headers, true);
        $linesIdx = array_search('Lines %', $headers, true);

        $entries = [];
        foreach ($data as $row) {
            $row = array_map('trim', $row);
            if ($row[$baseIdx] === $branchName) {
                $entries[] = [
                    'pr' => $row[0],
                    'date' => strtotime($row[$dateIdx]),
                    'classes' => (float)rtrim($row[$classesIdx], '%'),
                    'methods' => (float)rtrim($row[$methodsIdx], '%'),
                    'lines' => (float)rtrim($row[$linesIdx], '%'),
                ];
            }
        }

        // --- Step 2: Sort by date ---
        usort($entries, static fn($a, $b) => $a['date'] <=> $b['date']);

        // Limit to maximum count, defaults to 30
        $entries = array_slice($entries, -$maxCount);

        if (empty($entries)) {
            exit("No data found for base branch '{$branchName}'.\n");
        }

        // --- Step 3: Extract values and Y-axis range ---
        $allValues = [];
        foreach ($entries as $e) {
            $allValues[] = $e['classes'];
            $allValues[] = $e['methods'];
            $allValues[] = $e['lines'];
        }
        $min = floor(min($allValues) / 10) * 10;
        $max = ceil(max($allValues) / 10) * 10;

        // --- Step 4: Setup GD image ---
        $width = 1440; // approximate comment content width
        $height = 500;
        $padding = 60;
        $graphWidth = $width - 2 * $padding;
        $graphHeight = $height - 2 * $padding;

        $img = imagecreate($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $blue = imagecolorallocate($img, 66, 133, 244);
        $green = imagecolorallocate($img, 52, 168, 83);
        $orange = imagecolorallocate($img, 255, 167, 38);
        $gray = imagecolorallocate($img, 200, 200, 200);

        imagefill($img, 0, 0, $white);

        // --- Step 5: Draw axes and grid ---
        $steps = 5;
        for ($i = 0; $i <= $steps; $i++) {
            $y = $padding + $i * ($graphHeight / $steps);
            $val = $max - ($i * ($max - $min) / $steps);
            imageline($img, $padding, $y, $width - $padding, $y, $gray);
            imagestring($img, 2, 10, $y - 7, round($val) . '%', $black);
        }

        // --- Step 6: Plot each line ---
        self::plotLine($img, $entries, 'classes', $blue, $padding, $graphWidth, $graphHeight, $min, $max);
        self::plotLine($img, $entries, 'methods', $green, $padding, $graphWidth, $graphHeight, $min, $max);
        self::plotLine($img, $entries, 'lines', $orange, $padding, $graphWidth, $graphHeight, $min, $max);

        // --- Step 7: Draw legend ---
        imagestring($img, 3, $width - 150, 30, 'Classes %', $blue);
        imagestring($img, 3, $width - 150, 50, 'Methods %', $green);
        imagestring($img, 3, $width - 150, 70, 'Lines %', $orange);

        // --- Draw value labels above points ---
        $labelFontY = 2;
        $count = count($entries);
        foreach (['classes' => $blue, 'methods' => $green, 'lines' => $orange] as $key => $color) {
            for ($i = 0; $i < $count; $i++) {
                $value = $entries[$i][$key];
                $x = $padding + ($i * $graphWidth / ($count - 1));
                $y = $padding + $graphHeight * (1 - (($value - $min) / ($max - $min)));

                $label = number_format($value, 1); // without percent sign
                $textWidth = imagefontwidth($labelFontY) * strlen($label);
                imagestring($img, $labelFontY, (int)($x - $textWidth / 2), (int)($y - 15), $label, $color);
            }
        }

        // --- Draw X-axis labels ---
        $labelFontX = 2;
        $labelYOffset = 5;
        $count = count($entries);
        for ($i = 0; $i < $count; $i++) {
            $x = $padding + ($i * $graphWidth / ($count - 1));
            $y = $padding + $graphHeight + $labelYOffset;

            $label = date('Y-m-d', $entries[$i]['date']) . ' #' . $entries[$i]['pr'];
            imagestringup($img, $labelFontX, $x - 5, $y + 40, $label, $black);
        }

        // --- Step 8: Output PNG ---
        imagepng($img, $outputFile);
        imagedestroy($img);

        echo "Graph saved to {$outputFile}\n";
    }

    private static function plotLine($img, $entries, $key, $color, $padding, $graphWidth, $graphHeight, $min, $max): void
    {
        $count = count($entries);
        for ($i = 0; $i < $count - 1; $i++) {
            $x1 = $padding + ($i * $graphWidth / ($count - 1));
            $y1 = $padding + $graphHeight * (1 - (($entries[$i][$key] - $min) / ($max - $min)));
            $x2 = $padding + (($i + 1) * $graphWidth / ($count - 1));
            $y2 = $padding + $graphHeight * (1 - (($entries[$i + 1][$key] - $min) / ($max - $min)));

            // Draw 2px line manually by offsetting vertically
            imageline($img, $x1, $y1, $x2, $y2, $color);
            imageline($img, $x1, $y1 + 1, $x2, $y2 + 1, $color);
        }
    }


    private static function githubApiRequest(string $url, string $token): array
    {
        $headers = [
            "Accept: application/vnd.github+json",
            "User-Agent: CodeCoverageFetcher",
            // maximum 60 requests without the Authorization
            "Authorization: Bearer $token",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            return [];
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    private static function parseCoverageReport(string $comment): ?array
    {
        $pattern = '/Code Coverage Report:\s+([0-9\-:\s]+)\s+Summary:\s+Classes:\s+([\d.]+)% \(\d+\/\d+\)\s+Methods:\s+([\d.]+)% \(\d+\/\d+\)\s+Lines:\s+([\d.]+)% \(\d+\/\d+\)/m';

        if (preg_match($pattern, $comment, $matches)) {
            return [
                'date' => trim($matches[1]),
                'classes' => (float)$matches[2],
                'methods' => (float)$matches[3],
                'lines' => (float)$matches[4],
            ];
        }
        return null;
    }

}
