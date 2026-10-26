<?php
/*
 * import_students.php - upload a CSV exported from your spreadsheet and load it into the `students` table.
 * Required columns (header row, any order): name, roll_no, blood_group, contact_number, year, department
 * Optional column: last_donation
 * Rows whose roll_no already exists are UPDATED instead of duplicated.
 */
require_once __DIR__ . '/includes/auth.php';
require_admin();   // only administrators may bulk-import/overwrite student records
$pdo = getDB();

$report = null;   // summary shown after import

/** Makes header names flexible: "Roll No", "roll_no", "ROLLNO" all become "rollno". */
function clean_header(string $h): string
{
    $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);              // remove Excel's invisible BOM at file start
    return preg_replace('/[^a-z]/', '', strtolower($h));
}

/** Accepts YYYY-MM-DD, DD-MM-YYYY or DD/MM/YYYY and returns YYYY-MM-DD (or null if blank, false if invalid). */
function clean_date(string $v)
{
    $v = trim($v);
    if ($v === '') return null;
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
        $d = DateTime::createFromFormat($format, $v);
        if ($d && $d->format($format) === $v) return $d->format('Y-m-d');
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $file = $_FILES['csv_file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Please choose a CSV file to upload.');
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv' || $file['size'] > 2 * 1024 * 1024) {
        set_flash('error', 'Only .csv files up to 2 MB are allowed.');
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        $headerRow = fgetcsv($handle);
        // Map each header name to a column number
        $aliases = [
            'name' => 'name', 'studentname' => 'name',
            'rollno' => 'roll_no', 'rollnumber' => 'roll_no', 'roll' => 'roll_no',
            'bloodgroup' => 'blood_group', 'bloodtype' => 'blood_group', 'bg' => 'blood_group',
            'contactnumber' => 'contact_number', 'phone' => 'contact_number', 'mobile' => 'contact_number', 'contact' => 'contact_number', 'phonenumber' => 'contact_number',
            'year' => 'year', 'semester' => 'year',
            'department' => 'department', 'dept' => 'department', 'branch' => 'department',
            'lastdonation' => 'last_donation', 'lastdonationdate' => 'last_donation',
        ];
        $col = [];
        foreach ($headerRow ?: [] as $i => $h) {
            $key = $aliases[clean_header((string)$h)] ?? null;
            if ($key) $col[$key] = $i;
        }
        $missing = array_diff(['name', 'roll_no', 'blood_group', 'contact_number', 'year', 'department'], array_keys($col));

        if ($missing) {
            set_flash('error', 'Missing column(s): ' . implode(', ', $missing) . '. Check the first row of your file.');
        } else {
            $inserted = $updated = 0;
            $problems = [];
            $lineNo = 1;

            // INSERT ... ON DUPLICATE KEY UPDATE: new roll_no = insert, existing roll_no = update.
            $stmt = $pdo->prepare(
                'INSERT INTO students (name, roll_no, blood_group, contact_number, year, department, last_donation)
                 VALUES (:name, :roll, :bg, :phone, :year, :dept, :last)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), blood_group = VALUES(blood_group),
                    contact_number = VALUES(contact_number), year = VALUES(year),
                    department = VALUES(department), last_donation = VALUES(last_donation)'
            );

            $pdo->beginTransaction();    // all-or-nothing: if something crashes, nothing is half-imported
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNo++;
                    if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) continue; // skip blank lines

                    $get = fn($k) => isset($col[$k], $row[$col[$k]]) ? trim($row[$col[$k]]) : '';
                    $name  = $get('name');
                    $roll  = strtoupper($get('roll_no'));
                    $bg    = strtoupper(str_replace(' ', '', $get('blood_group')));
                    $phone = preg_replace('/\D/', '', $get('contact_number'));
                    if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
                    $year  = strtoupper($get('year'));
                    $dept  = strtoupper($get('department'));
                    $last  = clean_date($get('last_donation'));

                    $why = null;
                    if ($name === '' || strlen($name) > 100)         $why = 'name missing/too long';
                    elseif ($roll === '' || strlen($roll) > 30)      $why = 'roll_no missing/too long';
                    elseif (!in_array($bg, BLOOD_GROUPS, true))      $why = "blood group '$bg' is not valid";
                    elseif (!preg_match('/^\d{10}$/', $phone))       $why = 'phone must be 10 digits';
                    elseif (!in_array($year, YEARS, true))           $why = "year '$year' not in config/options.php";
                    elseif (!in_array($dept, DEPARTMENTS, true))     $why = "department '$dept' not in config/options.php";
                    elseif ($last === false)                         $why = 'last_donation date format invalid';

                    if ($why) { $problems[] = "Line $lineNo: $why"; continue; }

                    $stmt->execute([':name' => $name, ':roll' => $roll, ':bg' => $bg, ':phone' => $phone,
                                    ':year' => $year, ':dept' => $dept, ':last' => $last]);
                    // MySQL reports 1 affected row for an insert, 2 for an update of an existing row
                    if ($stmt->rowCount() === 1) $inserted++; elseif ($stmt->rowCount() === 2) $updated++;
                }
                $pdo->commit();
                $report = ['inserted' => $inserted, 'updated' => $updated, 'problems' => $problems];
            } catch (PDOException $ex) {
                $pdo->rollBack();
                error_log('import error: ' . $ex->getMessage());
                set_flash('error', 'Import failed and nothing was saved. Please check the file and try again.');
            }
        }
        fclose($handle);
    }
}

$pageTitle = 'Import Donors';
require __DIR__ . '/includes/header.php';
?>
<h2>Import Donors from Spreadsheet (CSV)</h2>

<?php if ($report): ?>
    <div class="alert alert-success">Import finished: <strong><?= $report['inserted'] ?></strong> new donor(s) added,
        <strong><?= $report['updated'] ?></strong> existing donor(s) updated,
        <strong><?= count($report['problems']) ?></strong> row(s) skipped.</div>
    <?php if ($report['problems']): ?>
        <div class="card"><h3>Skipped rows</h3><ul>
            <?php foreach ($report['problems'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
        </ul><p class="muted small">Fix these rows in your spreadsheet and import again. Already-imported rows will just be updated, not duplicated.</p></div>
    <?php endif; ?>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h3>Upload CSV</h3>
        <form method="post" action="import_students.php" enctype="multipart/form-data" id="importForm">
            <?= csrf_field() ?>
            <label for="csv_file">CSV file</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            <button class="btn btn-primary" type="submit">Import</button>
        </form>
    </div>
    <div class="card">
        <h3>File format</h3>
        <p>First row must be the headings:</p>
        <p><code>name, roll_no, blood_group, contact_number, year, department, last_donation</code></p>
        <ul class="small">
            <li>blood_group: A+, A-, B+, B-, AB+, AB-, O+, O-</li>
            <li>year: <?= e(implode(', ', YEARS)) ?> &nbsp;|&nbsp; department: <?= e(implode(', ', DEPARTMENTS)) ?></li>
            <li>contact_number: 10 digits</li>
            <li>last_donation (optional): YYYY-MM-DD or DD-MM-YYYY</li>
        </ul>
        <p><a href="sample_data/students_sample.csv" download>Download sample CSV</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
