<?php
// dashboard.php - home page with donor statistics from the database.
require 'includes/auth.php';          // only logged-in users can see this page
require 'config/database.php';

// ---- 1. Count ALL donors -------------------------------------------------
// COUNT(*) asks MySQL "how many rows are in the students table?"
$stmt = $pdo->query('SELECT COUNT(*) FROM students');
$total_donors = $stmt->fetchColumn();           // fetchColumn() gives us the single number

// ---- 2. Count only AVAILABLE donors --------------------------------------
$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE donor_status = 'Available'");
$available_donors = $stmt->fetchColumn();

// ---- 3. Count donors in each blood group ---------------------------------
// GROUP BY puts rows with the same blood group together and counts each group.
$stmt = $pdo->query('SELECT blood_group, COUNT(*) AS total FROM students GROUP BY blood_group');
$counts = [];                                   // will look like ['O+' => 162, 'B+' => 120, ...]
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['blood_group']] = $row['total'];
}

// We list all 8 groups so a group with no donors still shows 0.
$all_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

$page_title   = 'Dashboard';
$current_page = 'home';
require 'includes/header.php';
?>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
<p class="muted">Here is a quick summary of our registered blood donors.</p>

<div class="stat-row">
    <div class="stat-card">
        <div class="stat-number"><?php echo (int)$total_donors; ?></div>
        <div class="stat-label">Total registered donors</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo (int)$available_donors; ?></div>
        <div class="stat-label">Available donors</div>
    </div>
</div>

<p><a class="btn" href="find_donors.php">Find Donors</a></p>

<h2>Donors by blood group</h2>
<div class="group-grid">
    <?php foreach ($all_groups as $group) {
        // If this group was not counted, use 0.
        $number = isset($counts[$group]) ? $counts[$group] : 0;
    ?>
        <!-- Clicking a box opens Find Donors already filtered by that group. -->
        <a class="group-box" href="find_donors.php?blood_group=<?php echo urlencode($group); ?>">
            <span class="group-name"><?php echo htmlspecialchars($group); ?></span>
            <span class="group-count"><?php echo (int)$number; ?> donors</span>
        </a>
    <?php } ?>
</div>

<?php require 'includes/footer.php'; ?>
