<?php
/* statistics.php - simple tables with CSS bars. GROUP BY counts rows per category. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = getDB();

try {
    $totalDonors = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $totalReq    = (int)$pdo->query('SELECT COUNT(*) FROM blood_requests')->fetchColumn();
    $openReq     = (int)$pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status='Open'")->fetchColumn();
    $closedReq   = $totalReq - $openReq;

    // GROUP BY: one row per blood group with the number of students in it
    $byGroup  = $pdo->query('SELECT blood_group AS label, COUNT(*) AS total FROM students GROUP BY blood_group ORDER BY total DESC')->fetchAll();
    $byDept   = $pdo->query('SELECT department AS label, COUNT(*) AS total FROM students GROUP BY department ORDER BY total DESC')->fetchAll();
    $byYear   = $pdo->query('SELECT year AS label, COUNT(*) AS total FROM students GROUP BY year ORDER BY year')->fetchAll();
    $outcomes = $pdo->query('SELECT response_status AS label, COUNT(*) AS total FROM contact_history GROUP BY response_status ORDER BY total DESC')->fetchAll();
    $everDonated = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE last_donation IS NOT NULL')->fetchColumn();
} catch (PDOException $ex) {
    error_log('statistics error: ' . $ex->getMessage());
    die('Could not load statistics. Please try again later.');
}

/** Prints a table where each row has a horizontal bar sized by its share of the biggest value. */
function bar_table(string $title, array $rows): void {
    $max = $rows ? max(array_column($rows, 'total')) : 1; ?>
    <div class="card">
        <h3><?= e($title) ?></h3>
        <?php if (!$rows): ?><p class="empty">No data yet.</p><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <div class="bar-row">
                <span class="bar-label"><?= e($r['label']) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width: <?= round(($r['total'] / $max) * 100) ?>%"></span></span>
                <span class="bar-value"><?= (int)$r['total'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php }

$pageTitle = 'Statistics';
require __DIR__ . '/includes/header.php';
?>
<h2>Statistics</h2>
<section class="cards">
    <div class="card stat"><span class="stat-num"><?= $totalDonors ?></span><span>Registered donors</span></div>
    <div class="card stat"><span class="stat-num"><?= $totalReq ?></span><span>Blood requests</span></div>
    <div class="card stat"><span class="stat-num"><?= $openReq ?> / <?= $closedReq ?></span><span>Open / Completed</span></div>
    <div class="card stat"><span class="stat-num"><?= $everDonated ?></span><span>Donors with a donation record</span></div>
</section>
<div class="grid-2">
    <?php bar_table('Donors by blood group', $byGroup); ?>
    <?php bar_table('Contact outcomes', $outcomes); ?>
    <?php bar_table('Donors by department', $byDept); ?>
    <?php bar_table('Donors by year', $byYear); ?>
</div>
<p class="muted small">These numbers are for information only and do not decide anyone's medical eligibility.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
