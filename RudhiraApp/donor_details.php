<?php
/* donor_details.php - one donor's details, contact history, and the form to record a response. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = getDB();

$studentId = (int)($_GET['id'] ?? 0);
$requestId = (int)($_GET['request_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute([':id' => $studentId]);
$donor = $stmt->fetch();
if (!$donor) {                      // invalid donor id -> safe redirect
    set_flash('error', 'Donor not found.');
    header('Location: find_donors.php');
    exit;
}

$request = $requestId > 0 ? find_request($pdo, $requestId) : null;

// Has this donor ALREADY been contacted for the selected request?
$existing = null;
if ($request) {
    $stmt = $pdo->prepare(
        'SELECT ch.*, v.name AS volunteer_name
         FROM contact_history ch JOIN volunteers v ON v.id = ch.volunteer_id
         WHERE ch.request_id = :rid AND ch.student_id = :sid'
    );
    $stmt->execute([':rid' => $request['id'], ':sid' => $studentId]);
    $existing = $stmt->fetch();
}

// All past contacts for this donor (across requests)
$stmt = $pdo->prepare(
    'SELECT ch.*, v.name AS volunteer_name, br.request_code, br.hospital_name
     FROM contact_history ch
     JOIN volunteers v      ON v.id  = ch.volunteer_id
     JOIN blood_requests br ON br.id = ch.request_id
     WHERE ch.student_id = :sid ORDER BY ch.contact_date DESC'
);
$stmt->execute([':sid' => $studentId]);
$history = $stmt->fetchAll();

$openRequests = $pdo->query("SELECT id, request_code, hospital_name, blood_group FROM blood_requests WHERE status='Open' ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Donor Details';
require __DIR__ . '/includes/header.php';
?>
<p><a href="javascript:history.back()">&larr; Back to results</a></p>
<h2><?= e($donor['name']) ?></h2>

<div class="grid-2">
    <div class="card">
        <h3>Donor information</h3>
        <dl class="details">
            <dt>Roll No</dt><dd><?= e($donor['roll_no']) ?></dd>
            <dt>Blood group</dt><dd><strong><?= e($donor['blood_group']) ?></strong></dd>
            <dt>Contact number</dt><dd><?= e($donor['contact_number']) ?></dd>
            <dt>Year / Dept</dt><dd><?= e($donor['year']) ?> / <?= e($donor['department']) ?></dd>
            <dt>Last donation</dt><dd><?= $donor['last_donation'] ? e(date('d M Y', strtotime($donor['last_donation']))) : 'No record' ?></dd>
            <dt>Availability</dt><dd><?= status_badge($donor['donor_status']) ?></dd>
        </dl>
        <p class="muted small">Last donation date is shown for reference only; it is not a medical eligibility decision.</p>
    </div>

    <div class="card">
        <h3>Record contact</h3>
        <?php if (!$request): ?>
            <p>Choose which blood request you are contacting this donor for:</p>
            <form method="get" action="donor_details.php">
                <input type="hidden" name="id" value="<?= (int)$studentId ?>">
                <select name="request_id" required>
                    <option value="">Select a request</option>
                    <?php foreach ($openRequests as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= e($r['request_code']) ?> &ndash; <?= e($r['blood_group']) ?> &ndash; <?= e($r['hospital_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Select</button>
            </form>
        <?php else: ?>
            <p>Request: <strong><?= e($request['request_code']) ?></strong> &ndash; <?= e($request['hospital_name']) ?> (<?= e($request['blood_group']) ?>)</p>

            <?php if ($existing): ?>
                <!-- DUPLICATE WARNING: the donor can still be viewed, but no second record can be added -->
                <div class="alert alert-warning">
                    <strong>Already contacted for this request.</strong><br>
                    Status: <?= status_badge($existing['response_status']) ?><br>
                    By <strong><?= e($existing['volunteer_name']) ?></strong> on <?= e(date('d M Y, g:i A', strtotime($existing['contact_date']))) ?>
                    <?php if ($existing['remarks']): ?><br>Remarks: <?= e($existing['remarks']) ?><?php endif; ?>
                </div>
            <?php elseif ($request['status'] === 'Closed'): ?>
                <div class="alert alert-info">This request is closed. No new contacts can be recorded.</div>
            <?php else: ?>
                <form method="post" action="contact_donor.php" id="contactForm" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= (int)$donor['id'] ?>">
                    <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">

                    <label for="response_status">Response *</label>
                    <select id="response_status" name="response_status" required>
                        <option value="">Select outcome</option>
                        <?php foreach (CONTACT_STATUSES as $st): ?><option><?= e($st) ?></option><?php endforeach; ?>
                    </select>

                    <label for="remarks">Remarks (optional)</label>
                    <textarea id="remarks" name="remarks" rows="3" maxlength="500"></textarea>

                    <button type="submit" class="btn btn-primary" data-confirm-select="response_status" data-confirm-values="Will Donate,Already Donated">Save contact record</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Contact history for this donor</h3>
    <?php if (!$history): ?>
        <p class="empty">This donor has not been contacted for any request yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Request</th><th>Hospital</th><th>Response</th><th>By</th><th>When</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td data-label="Request"><?= e($h['request_code']) ?></td>
                <td data-label="Hospital"><?= e($h['hospital_name']) ?></td>
                <td data-label="Response"><?= status_badge($h['response_status']) ?></td>
                <td data-label="By"><?= e($h['volunteer_name']) ?></td>
                <td data-label="When"><?= e(date('d M Y, g:i A', strtotime($h['contact_date']))) ?></td>
                <td data-label="Remarks"><?= e($h['remarks'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
