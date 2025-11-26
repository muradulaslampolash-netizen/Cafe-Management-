<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/helpers.php';

function out($s) { echo htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (isset($_GET['opcache']) && $_GET['opcache'] === '1') {
    if (function_exists('opcache_reset')) {
        $ok = opcache_reset();
        $msg = $ok ? 'OPcache reset successfully.' : 'OPcache reset failed.';
    } else {
        $msg = 'opcache_reset() not available on this PHP build.';
    }
}
// Auto-fix: match files to DB entries by filename (basename) and set image field
$autofixReport = null;
if (isset($_GET['action']) && $_GET['action'] === 'autofix') {
    try {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT id, name, image FROM menu_items');
        $menuRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updates = 0;
        foreach ($menuRows as $r) {
            $id = (int)$r['id'];
            $name = $r['name'];
            $dbimg = $r['image'] ?? '';
            if ($dbimg) continue; // already set
            $matched = '';
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $base = pathinfo($f, PATHINFO_FILENAME);
                if (strcasecmp($base, $name) === 0) { $matched = $f; break; }
            }
            if ($matched) {
                $u = $pdo->prepare('UPDATE menu_items SET image = ? WHERE id = ?');
                $u->execute([$matched, $id]);
                $updates++;
            }
        }
        $autofixReport = "$updates items updated.";
    } catch (Exception $e) {
        $autofixReport = 'Auto-fix failed: ' . $e->getMessage();
    }
}

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Image debug</title>
<style>body{font-family:Segoe UI,Arial;background:#0f1020;color:#e8eef7;padding:18px}table{border-collapse:collapse;width:100%;max-width:1000px}th,td{padding:8px;border:1px solid rgba(255,255,255,.06)}th{background:#0b1220;text-align:left}code{background:#071024;padding:2px 6px;border-radius:4px;color:#8ff}</style>
</head>
<body>
<h2>Image debug</h2>
<?php if (!empty($msg)): ?>
    <div style="padding:8px;background:#072a14;color:#b7f7c8;border-radius:6px;margin-bottom:12px;"><?php out($msg); ?></div>
<?php endif; ?>

<h3>Config</h3>
<ul>
    <li><strong>BASE_URL:</strong> <code><?php out(BASE_URL); ?></code></li>
    <li><strong>UPLOAD_URL:</strong> <code><?php out(UPLOAD_URL); ?></code></li>
    <li><strong>UPLOAD_DIR:</strong> <code><?php out(UPLOAD_DIR); ?></code></li>
    <li><strong>OPcache enabled:</strong> <code><?php out(ini_get('opcache.enable') ? 'yes' : 'no'); ?></code></li>
    <li><strong>OPcache validate timestamps:</strong> <code><?php out(ini_get('opcache.validate_timestamps') ? 'yes' : 'no'); ?></code></li>
</ul>
<p>
<a href="<?php out(BASE_URL . '/debug_image.php?opcache=1'); ?>">Clear OPcache (if available)</a>
</p>

<h3>Uploads directory listing</h3>
<?php
$files = @scandir(UPLOAD_DIR);
if ($files === false) {
    echo '<div style="padding:8px;background:#420c0c;color:#ffd7d7;border-radius:6px;">Unable to read uploads directory (check permissions)</div>';
} else {
    echo '<table><tr><th>Filename</th><th>Size (bytes)</th><th>Exists</th><th>URL</th></tr>';
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $fp = UPLOAD_DIR . DIRECTORY_SEPARATOR . $f;
        $exists = is_file($fp);
        $size = $exists ? filesize($fp) : '';
        $url = $exists ? (UPLOAD_URL . '/' . rawurlencode($f)) : '';
        echo '<tr><td>' . htmlspecialchars($f) . '</td><td>' . htmlspecialchars((string)$size) . '</td><td>' . ($exists ? 'file' : 'no') . '</td><td>' . ($url ? '<a href="' . htmlspecialchars($url) . '" target="_blank">open</a>' : '') . '</td></tr>';
    }
    echo '</table>';
}

// Now check DB entries for menu_items.image
try {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, name, image FROM menu_items');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = null;
    echo '<h3>Database access</h3>';
    echo '<div style="padding:8px;background:#420c0c;color:#ffd7d7;border-radius:6px;">Unable to query database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

if (is_array($rows)) {
    echo '<h3>Menu items and image checks</h3>';
    echo '<table><tr><th>ID</th><th>Name</th><th>Image (DB)</th><th>File exists</th><th>Match (case-insensitive)</th></tr>';
    foreach ($rows as $r) {
        $dbimg = $r['image'] ?? '';
        $found = false;
        $matchedName = '';
        if ($dbimg !== '') {
            $candidate = UPLOAD_DIR . DIRECTORY_SEPARATOR . $dbimg;
            if (is_file($candidate)) {
                $found = true;
                $matchedName = $dbimg;
            } else {
                // try case-insensitive match
                foreach ($files as $f) {
                    if (strcasecmp($f, $dbimg) === 0) { $found = true; $matchedName = $f; break; }
                }
            }
        }
        echo '<tr>';
        echo '<td>' . (int)$r['id'] . '</td>';
        echo '<td>' . htmlspecialchars($r['name']) . '</td>';
        echo '<td><code>' . htmlspecialchars($dbimg) . '</code></td>';
        echo '<td>' . ($dbimg !== '' ? ($found ? 'yes' : 'no') : 'none') . '</td>';
        echo '<td>' . ($matchedName ? htmlspecialchars($matchedName) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

?>

</body>
</html>
