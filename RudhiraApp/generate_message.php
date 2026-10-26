<?php
/* generate_message.php - builds a shareable blood requirement message from a request (no AI needed: just a template). */
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = getDB();

$requestId = (int)($_GET['request_id'] ?? 0);
$request = $requestId > 0 ? find_request($pdo, $requestId) : null;
$message = '';

if ($request) {
    // Template: just a string with the request values inserted.
    $message  = "URGENT BLOOD REQUIREMENT\n\n";
    $message .= "Blood group: {$request['blood_group']}\n";
    $message .= "Units required: {$request['units_required']}\n";
    $message .= "Hospital: {$request['hospital_name']}\n";
    $message .= "Required by: " . date('d M Y', strtotime($request['required_date'])) . "\n";
    $message .= "Urgency: {$request['urgency']}\n";
    if ($request['notes']) {
        $message .= "Details: {$request['notes']}\n";
    }
    $message .= "\nIf you can donate, please contact Rudhirasena/NSS volunteer {$_SESSION['volunteer_name']}.\n";
    $message .= "Request ID: {$request['request_code']}\n\nPlease share. Every drop counts.";
}

$requests = $pdo->query("SELECT id, request_code, hospital_name, blood_group FROM blood_requests ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Generate Message';
require __DIR__ . '/includes/header.php';
?>
<h2>Generate Message</h2>

<form method="get" action="generate_message.php" class="card">
    <label for="request_id">Choose a blood request</label>
    <select id="request_id" name="request_id" required>
        <option value="">Select</option>
        <?php foreach ($requests as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $requestId === (int)$r['id'] ? 'selected' : '' ?>>
                <?= e($r['request_code']) ?> &ndash; <?= e($r['blood_group']) ?> &ndash; <?= e($r['hospital_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Generate</button>
</form>

<?php if ($message): ?>
<div class="card">
    <h3>Message</h3>
    <textarea id="messageText" rows="12" readonly><?= e($message) ?></textarea>
    <p>
        <button type="button" class="btn btn-primary" id="copyBtn" data-target="messageText">Copy message</button>
        <!-- wa.me opens WhatsApp with the text pre-filled; urlencode makes the text safe inside a URL -->
        <a class="btn btn-outline" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($message) ?>">Share on WhatsApp</a>
        <span id="copyStatus" class="muted small" aria-live="polite"></span>
    </p>
</div>
<?php elseif ($requestId > 0): ?>
    <div class="alert alert-error">Request not found.</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
