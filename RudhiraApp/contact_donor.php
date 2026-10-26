<?php
/* contact_donor.php - saves a contact outcome. No HTML here: it processes the POST then redirects. */
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {     // this page only accepts form submissions
    header('Location: find_donors.php');
    exit;
}
csrf_check();

$studentId = (int)($_POST['student_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);
$status    = $_POST['response_status'] ?? '';
$remarks   = trim($_POST['remarks'] ?? '');
$back      = "donor_details.php?id=$studentId&request_id=$requestId";

// ---- Validation ----
if (!in_array($status, CONTACT_STATUSES, true)) {
    set_flash('error', 'Please choose a valid response.');
    header("Location: $back"); exit;
}
if (strlen($remarks) > 500) {
    set_flash('error', 'Remarks must be 500 characters or fewer.');
    header("Location: $back"); exit;
}

$pdo = getDB();
try {
    // Donor and request must really exist
    $s = $pdo->prepare('SELECT id FROM students WHERE id = :id');
    $s->execute([':id' => $studentId]);
    $request = find_request($pdo, $requestId);
    if (!$s->fetch() || !$request) {
        set_flash('error', 'Invalid donor or request.');
        header('Location: find_donors.php'); exit;
    }
    if ($request['status'] !== 'Open') {
        set_flash('error', 'This request is closed.');
        header("Location: $back"); exit;
    }

    // ---- DUPLICATE CHECK #1 (PHP): has this donor already been contacted for this request? ----
    $check = $pdo->prepare('SELECT id FROM contact_history WHERE request_id = :rid AND student_id = :sid');
    $check->execute([':rid' => $requestId, ':sid' => $studentId]);
    if ($check->fetch()) {
        set_flash('warning', 'Already contacted for this request. No duplicate record was created.');
        header("Location: $back"); exit;
    }

    // ---- Insert. volunteer_id comes from the SESSION (trusted), never from the form. ----
    try {
        $ins = $pdo->prepare(
            'INSERT INTO contact_history (request_id, student_id, volunteer_id, response_status, remarks)
             VALUES (:rid, :sid, :vid, :status, :remarks)'
        );
        $ins->execute([
            ':rid' => $requestId, ':sid' => $studentId, ':vid' => $_SESSION['volunteer_id'],
            ':status' => $status, ':remarks' => $remarks !== '' ? $remarks : null,
        ]);
    } catch (PDOException $ex) {
        // ---- DUPLICATE CHECK #2 (database): the UNIQUE (request_id, student_id) key rejects the row.
        // This catches the rare case where two volunteers press Save at the same moment.
        if ($ex->getCode() === '23000') {
            set_flash('warning', 'Another volunteer just contacted this donor. No duplicate record was created.');
            header("Location: $back"); exit;
        }
        throw $ex;
    }

    set_flash('success', 'Contact recorded successfully.');
    header("Location: $back");
    exit;
} catch (PDOException $ex) {
    error_log('contact_donor error: ' . $ex->getMessage());
    set_flash('error', 'Could not save the record. Please try again later.');
    header("Location: $back");
    exit;
}
