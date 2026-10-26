<?php
/* request_history.php - all requests; click one to see the donors contacted for it. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = getDB();

$selectedId = (int)($_GET['id'] ?? 0);
$selected = $selectedId > 0 ? find_request($pdo, $selectedId) : null;
if ($selectedId > 0 && !$selected) {
    set_flash('error', 'Request not found.');
    header('Location: request_history.php'); exit;
}

$requests = $pdo->query(
    'SELECT br.*, v.name AS created_by_name,
            (SELECT COUNT(*) FROM contact_history ch WHERE ch.request_id = br.id) AS contacted_count
     FROM blood_requests br JOIN volunteers v ON v.id = br.created_by
     ORDER BY br.created_at DESC'
)->fetchAll();

$contacts = [];
if ($selected) {
    $stmt = $pdo->prepare(
        'SELECT ch.*, s.name AS donor_name, s.roll_no, s.contact_number, v.name AS volunteer_name
         FROM contact_history ch
         JOIN students s   ON s.id = ch.student_id
         JOIN volunteers v ON v.id = ch.volunteer_id
         WHERE ch.request_id = :rid ORDER BY ch.contact_date DESC'
    );
    $stmt->execute([':rid' => $selectedId]);
    $contacts = $stmt->fetchAll();
}

$pageTitle = 'Request History';
require __DIR__ . '/includes/header.php';
?>
<h2>Request History</h2>

<?php if ($selected): ?>
<div class="card">
    <h3><?= e($selected['request_code']) ?> <?= status_badge($selected['status']) ?> <?= status_badge($selected['urgency']) ?></h3>
    <p><?= e($selected['hospital_name']) ?> &middot; <?= e($selected['blood_group']) ?> &middot; <?= (int)$selected['units_required'] ?> unit(s)
       &middot; needed by <?= e(date('d M Y', strtotime($selected['required_date']))) ?></p>
    <?php if ($selected['notes']): ?><p class="muted">Notes: <?= e($selected['notes']) ?></p><?php endif; ?>
    <p>
        <a class="btn btn-small" href="find_donors.php?request_id=<?= (int)$selected['id'] ?>&blood_group=<?= urlencode($selected['blood_group']) ?>">Find donors</a>
        <a class="btn btn-small btn-outline" href="generate_message.php?request_id=<?= (int)$selected['id'] ?>">Generate message</a>
    </p>

    <h4>Contacted donors (<?= count($contacts) ?>)</h4>
    <?php if (!$contacts): ?>
        <p class="empty">No donors contacted yet for this request.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Donor</th><th>Roll No</th><th>Phone</th><th>Response</th><th>Contacted by</th><th>When</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php foreach ($contacts as $c): ?>
            <tr>
                <td data-label="Donor"><?= e($c['donor_name']) ?></td>
                <td data-label="Roll No"><?= e($c['roll_no']) ?></td>
                <td data-label="Phone"><?= e($c['contact_number']) ?></td>
                <td data-label="Response"><?= status_badge($c['response_status']) ?></td>
                <td data-label="By"><?= e($c['volunteer_name']) ?></td>
                <td data-label="When"><?= e(date('d M Y, g:i A', strtotime($c['contact_date']))) ?></td>
                <td data-label="Remarks"><?= e($c['remarks'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$requests): ?>
    <div class="card empty-state"><p>No requests yet. <a href="blood_request.php">Create the first one.</a></p></div>
<?php else: ?>
<div class="table-wrap"><table>
    <thead><tr><th>Code</th><th>Hospital</th><th>Group</th><th>Units</th><th>Urgency</th><th>Needed by</th><th>Status</th><th>Contacted</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
        <tr>
            <td data-label="Code"><strong><?= e($r['request_code']) ?></strong></td>
            <td data-label="Hospital"><?= e($r['hospital_name']) ?><div class="muted small">by <?= e($r['created_by_name']) ?></div></td>
            <td data-label="Group"><?= e($r['blood_group']) ?></td>
            <td data-label="Units"><?= (int)$r['units_required'] ?></td>
            <td data-label="Urgency"><?= status_badge($r['urgency']) ?></td>
            <td data-label="Needed by"><?= e(date('d M Y', strtotime($r['required_date']))) ?></td>
            <td data-label="Status"><?= status_badge($r['status']) ?></td>
            <td data-label="Contacted"><?= (int)$r['contacted_count'] ?></td>
            <td><a class="btn btn-small" href="request_history.php?id=<?= (int)$r['id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
