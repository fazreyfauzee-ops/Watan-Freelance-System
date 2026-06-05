<?php
$bid = $_GET['bid'] ?? null;
if (!$bid) {
    exit("No booking ID.");
}

$historyFile = __DIR__ . "/uploads/history.json";
$history = [];
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true) ?: [];
}

if (empty($history[$bid])) {
    echo "<p style='color:#666;'>No upload history yet.</p>";
    exit;
}

foreach (array_reverse($history[$bid]) as $entry) {
    $file = htmlspecialchars($entry['file']);
    $original = htmlspecialchars($entry['original']);
    $desc = htmlspecialchars($entry['description'] ?? '');
    $time = htmlspecialchars($entry['uploaded_at'] ?? '');
    echo "<div style='margin-bottom:10px;'>";
    echo "<strong>$original</strong><br>";
    if ($desc) echo "<small>$desc</small><br>";
    echo "<a href='$file' target='_blank'>View</a><br>";
    if ($time) echo "<small>$time</small>";
    echo "</div><hr>";
}

