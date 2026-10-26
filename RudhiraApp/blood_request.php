<?php
/* blood_request.php - create a blood request (with a unique code like RUD-2026-001) and list/close requests. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = getDB();
$errors = [];
$old = ['hospital_name' => '', 'blood_group' => '', 'units_required' => '1', 'urgency' => 'Normal', 'required_date' => '', 'notes' => ''];

/** Builds the next request code for this year: RUD-2026-001, RUD-2026-002 ... */
function next_request_code(PDO $pdo): string
{
    $year   = date('Y');
    $prefix = "RUD-$year-";
    // Find the biggest number used so far this year. SUBSTRING(...,10) skips "RUD-2026-" (9 characters).
    $stmt = $pdo->prepare('SELECT MAX(CAST(SUBSTRING(request_code, 10) AS UNSIGNED)) FROM blood_requests WHERE request_code LIKE :prefix');
    $stmt->execute([':prefix' => $prefix . '%']);
    $next = (int)$stmt->fetchColumn() + 1;
    return $prefix . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            // ---- read + validate input on the SERVER ----
            $old['hospital_name']  = trim($_POST['hospital_name'] ?? '');
            $old['blood_group']    = $_POST['blood_group'] ?? '';
            $old['units_required'] = trim($_POST['units_required'] ?? '');
            $old['urgency']        = $_POST['urgency'] ?? '';
            $old['required_date']  = $_POST['required_date'] ?? '';
            $old['notes']          = trim($_POST['notes'] ?? '');

            if ($old['hospital_name'] === '' || strlen($old['hospital_name']) > 150) $errors[] = 'Hospital name is required (max 150 characters).';
            if (!in_array($old['blood_group'], BLOOD_GROUPS, true))                  $errors[] = 'Please choose a valid blood group.';
            if (!ctype_digit($old['units_required']) || (int)$old['units_required'] < 1 || (int)$old['units_required'] > 20) $errors[] = 'Units required must be between 1 and 20.';
            if (!in_array($old['urgency'], URGENCIES, true))                         $errors[] = 'Please choose an urgency level.';
            $d = DateTime::createFromFormat('Y-m-d', $old['required_date']);
            if (!$d || $d->format('Y-m-d') !== $old['required_date'])                $errors[] = 'Please enter a valid required date.';

            if (!$errors) {
                // Try up to 3 times: if two volunteers save at the same instant, the UNIQUE request_code
                // makes one insert fail, and we simply generate the next code and try again.
                for ($try = 0; $try < 3; $try++) {
                    try {
                        $code = next_request_code($pdo);
                        $stmt = $pdo->prepare(
                            'INSERT INTO blood_requests (request_code, hospital_name, blood_group, units_required, urgency, required_date, notes, created_by)
                             VALUES (:code, :hospital, :bg, :units, :urgency, :req_date, :notes, :created_by)'
                        );
                        $stmt->execute([
                            ':code' => $code, ':hospital' => $old['hospital_name'], ':bg' => $old['blood_group'],
                            ':units' => (int)$old['units_required'], ':urgency' => $old['urgency'],
                            ':req_date' => $old['required_date'], ':notes' => $old['notes'] !== '' ? $old['notes'] : null,
                            ':created_by' => $_SESSION['volunteer_id'],   // from the SESSION, never from a hidden field
                        ]);
                        $newId = (int)$pdo->lastInsertId();
                        set_flash('success', "Request $code created. Now search for donors.");
                        header('Location: find_donors.php?request_id=' . $newId . '&blood_group=' . urlencode($old['blood_group']));
                        exit;
                    } catch (PDOException $ex) {
                        if ($ex->getCode() !== '23000') throw $ex;   // not a duplicate-code problem -> real error
                    }
                }
                $errors[] = 'Could not generate a unique request code. Please try again.';
            }
        } elseif ($action === 'close') {
            $id = (int)($_POST['request_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'Closed' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash('success', 'Request closed.');
            header('Location: blood_request.php');
            exit;
        }
    } catch (PDOException $ex) {
        error_log('blood_request error: ' . $ex->getMessage());
        $errors[] = 'Database error. Please try again later.';
    }
}

// List of open requests with how many donors were contacted for each
$openList = $pdo->query(
    "SELECT br.*, (SELECT COUNT(*) FROM contact_history ch WHERE ch.request_id = br.id) AS contacted_count
     FROM blood_requests br WHERE br.status = 'Open' ORDER BY br.created_at DESC"
)->fetchAll();

$pageTitle = 'Blood Requests';
require __DIR__ . '/includes/header.php';
?>
<h2>Blood Requests</h2>

<div class="grid-2">
    <div class="card">
        <h3>Create a new request</h3>
        <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" action="blood_request.php" id="requestForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <label for="hospital_name">Hospital name *</label>
            <input type="text" id="hospital_name" name="hospital_name" maxlength="150" value="<?= e($old['hospital_name']) ?>" required>

            <div class="row">
                <div>
                    <label for="blood_group">Blood group *</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="">Select</option>
                        <?php foreach (BLOOD_GROUPS as $bg): ?>
                            <option value="<?= e($bg) ?>" <?= $old['blood_group'] === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="units_required">Units *</label>
                    <input type="number" id="units_required" name="units_required" min="1" max="20" value="<?= e($old['units_required']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="urgency">Urgency *</label>
                    <select id="urgency" name="urgency">
                        <?php foreach (URGENCIES as $u): ?>
                            <option <?= $old['urgency'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="required_date">Required date *</label>
                    <input type="date" id="required_date" name="required_date" value="<?= e($old['required_date']) ?>" required>
                </div>
            </div>

            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3"><?= e($old['notes']) ?></textarea>

            <button type="submit" class="btn btn-primary">Create request</button>
        </form>
    </div>

    <div class="card">
        <h3>Open requests</h3>
        <?php if (!$openList): ?>
            <p class="empty">No open requests. Create one on the left.</p>
        <?php endif; ?>
        <?php foreach ($openList as $r): ?>
            <div class="request-item">
                <div>
                    <strong><?= e($r['request_code']) ?></strong> <?= status_badge($r['urgency']) ?>
                    <div><?= e($r['hospital_name']) ?> &middot; <?= e($r['blood_group']) ?> &middot; <?= (int)$r['units_required'] ?> unit(s)</div>
                    <div class="muted small">Needed by <?= e(date('d M Y', strtotime($r['required_date']))) ?> &middot; <?= (int)$r['contacted_count'] ?> donor(s) contacted</div>
                </div>
                <div class="item-actions">
                    <a class="btn btn-small" href="find_donors.php?request_id=<?= (int)$r['id'] ?>&blood_group=<?= urlencode($r['blood_group']) ?>">Find donors</a>
                    <form method="post" action="blood_request.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="close">
                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-small btn-outline" type="submit" data-confirm="Close request <?= e($r['request_code']) ?>? Contacts can no longer be added.">Close</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
