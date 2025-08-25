<?php
// Bulk import results from Matka API into satta_result for a selected game.
// dev by www.gowebs.in
// Contact 858585814444 ,  8585858844 

declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');

/* ========= DB: proper variables + PDO ========= */
$db_host = "localhost";
$db_name = "add your dbname";
$db_user = "add your user name";
$db_pass = "add your db password";

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
try {
    $db = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("DB Connection failed: " . htmlspecialchars($e->getMessage()));
}

/* ========= API config (static parts) ========= */
$API_URL  = 'https://www.matkaapi.com/apis/market_api.php';
$API_BASE_BODY = [
    "domain"     => "add domain name",
    "api_key"    => "add api key",
    "domain_key" => "add domain key",
    "old"        => true, // include history if API supports it
];

/* ========= Helpers ========= */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* ========= Get active games ========= */
try {
    $games = $db->query("SELECT id, name FROM satta_list WHERE status='1' ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $games = [];
}

$msg = $err = '';
$report = null;
$recentRows = [];

/* ========= Handle POST: call API, upsert all results ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = (int)($_POST['game_id'] ?? 0);

    if ($game_id <= 0) {
        $err = "Please select a game.";
    } else {
        // Find game name
        $chosen = null;
        foreach ($games as $g) {
            if ((int)$g['id'] === $game_id) { $chosen = $g; break; }
        }
        if (!$chosen) {
            $err = "Selected game not found or inactive.";
        } else {
            $game_name = $chosen['name'];

            // Build payload with gali = game name
            $payload = $API_BASE_BODY + ["gali" => $game_name];

            // cURL call
            $ch = curl_init($API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $errC = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resp === false || $errC !== '' || $code < 200 || $code >= 300) {
                $err = "API request failed. HTTP={$code} " . ($errC ?: '');
            } else {
                $json = json_decode($resp, true);
                if (!is_array($json) || empty($json['status']) || !isset($json['data']) || !is_array($json['data'])) {
                    $err = "Unexpected API response format.";
                } else {
                    $now = date('H:i:s');

                    $stmt = $db->prepare(
                        "INSERT INTO satta_result (game_id, result, date, update_time)
                         VALUES (:gid, :res, :dt, :ut)
                         ON DUPLICATE KEY UPDATE result = VALUES(result), update_time = VALUES(update_time)"
                    );

                    $countInserted = 0;
                    $countUpdatedApprox = 0;
                    $countInvalid = 0;

                    foreach ($json['data'] as $item) {
                        $date   = trim((string)($item['date'] ?? ''));
                        $result = strtoupper(trim((string)($item['result'] ?? '')));

                        // Validate YYYY-MM-DD and 2-char alnum result (e.g., "XX", "12", "AB")
                        $validDate   = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
                        $validResult = (bool)preg_match('/^[A-Za-z0-9]{2}$/', $result);

                        if (!$validDate || !$validResult) {
                            $countInvalid++;
                            continue;
                        }

                        try {
                            $stmt->execute([
                                ':gid' => $game_id,
                                ':res' => $result,
                                ':dt'  => $date,
                                ':ut'  => $now,
                            ]);
                            // rowCount may be 1 on insert; duplicate updates may vary by driver -> approximate updated count
                            $countInserted += (int)$stmt->rowCount();
                            $countUpdatedApprox++;
                        } catch (PDOException $e) {
                            // If any odd constraint issue, skip
                            $countInvalid++;
                        }
                    }

                    $report = [
                        'game_id'   => $game_id,
                        'game_name' => $game_name,
                        'inserted'  => $countInserted,
                        'updated~'  => $countUpdatedApprox,
                        'invalid'   => $countInvalid,
                    ];
                    $msg = "Imported successfully. Inserted={$countInserted}, Updated~={$countUpdatedApprox}, Invalid={$countInvalid}.";

                    // fetch a few recent rows for quick verify (latest dates first)
                    $q = $db->prepare("SELECT result, date, update_time FROM satta_result WHERE game_id = :gid ORDER BY date DESC LIMIT 10");
                    $q->execute([':gid' => $game_id]);
                    $recentRows = $q->fetchAll();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Satta Results Importer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f8fa; }
    .card { border-radius: 14px; }
    .form-select, .btn { border-radius: 10px; }
    .table thead th { background:#f0f3f7; }
    code { background: #f1f3f5; padding: 2px 6px; border-radius: 6px; }
  </style>
</head>
<body class="py-4">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Fetch & Insert Satta Results</h4>
            <span class="small">IST: <?=h(date('Y-m-d H:i:s'))?></span>
          </div>
        </div>
        <div class="card-body">

          <?php if ($msg): ?>
            <div class="alert alert-success mb-3"><?=h($msg)?></div>
          <?php endif; ?>
          <?php if ($err): ?>
            <div class="alert alert-danger mb-3"><?=h($err)?></div>
          <?php endif; ?>

          <form method="post" class="row g-3 align-items-end">
            <div class="col-md-8">
              <label for="game_id" class="form-label">Select Game (from <code>satta_list</code>)</label>
              <select name="game_id" id="game_id" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php foreach ($games as $g): ?>
                  <option value="<?=$g['id']?>" <?= (($_POST['game_id'] ?? '') == $g['id']) ? 'selected' : '' ?>>
                    <?=h($g['name'])?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-primary w-100">Fetch from API & Save</button>
            </div>
          </form>

          <?php if ($report): ?>
            <hr>
            <h5 class="mb-3">Import Summary</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle">
                <tbody>
                  <tr><th style="width:180px;">Game</th><td><?=h($report['game_name'])?> (ID: <?=h((string)$report['game_id'])?>)</td></tr>
                  <tr><th>Inserted</th><td><?=h((string)$report['inserted'])?></td></tr>
                  <tr><th>Updated~</th><td><?=h((string)$report['updated~'])?></td></tr>
                  <tr><th>Invalid</th><td><?=h((string)$report['invalid'])?></td></tr>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <?php if (!empty($recentRows)): ?>
            <h5 class="mt-4 mb-2">Recent Results (Top 10)</h5>
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm">
                <thead>
                  <tr>
                    <th style="width:140px;">Date</th>
                    <th style="width:100px;">Result</th>
                    <th>Update Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentRows as $r): ?>
                    <tr>
                      <td><?=h((string)$r['date'])?></td>
                      <td><span class="badge text-bg-secondary"><?=h((string)$r['result'])?></span></td>
                      <td><?=h((string)$r['update_time'])?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <div class="mt-4">
            <details>
              <summary class="fw-semibold">API Payload (reference)</summary>
              <pre class="mt-2"><code><?=
h(json_encode($API_BASE_BODY + ['gali' => ($_POST['game_id'] ?? '') ? ($report['game_name'] ?? '{selected game name}') : '{selected game name}'],
JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
?></code></pre>
            </details>
          </div>

        </div>
      </div>

      <p class="text-center text-muted small mt-3">
        Writes into <code>satta_result</code> with unique <code>(game_id, date)</code> using <code>ON DUPLICATE KEY UPDATE</code>.
      </p>
    </div>
  </div>
</div>

<!-- Bootstrap JS (optional for components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
