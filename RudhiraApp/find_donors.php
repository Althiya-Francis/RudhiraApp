<?php
// find_donors.php - MAIN FEATURE: search and filter donors.
//
// HOW IT WORKS (short version)
//   1. The search form uses method="get", so the browser puts the choices in the
//      address bar, e.g.  find_donors.php?name=fat&blood_group=O%2B&place=Kozhikode
//   2. PHP reads those choices from $_GET.
//   3. PHP builds ONE SQL query, adding a "AND ..." line for every choice the user filled in.
//   4. MySQL returns only the matching rows and we show them in a table.

require 'includes/auth.php';          // only logged-in users can see donors
require 'config/database.php';

// ---- 1. Read what the user typed / selected ------------------------------
$name        = isset($_GET['name'])        ? trim($_GET['name'])        : '';
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : '';
$place       = isset($_GET['place'])       ? trim($_GET['place'])       : '';

// ---- 2. Validate the blood group ------------------------------------------
// Only these 8 values are allowed. Anything else is ignored.
$all_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
if ($blood_group != '' && !in_array($blood_group, $all_groups, true)) {
    $blood_group = '';
}

// ---- 3. Get the list of places for the drop-down menu ---------------------
// DISTINCT = each place only once. The list comes from the database itself.
$stmt = $pdo->query("SELECT DISTINCT place FROM students WHERE place IS NOT NULL AND place <> '' ORDER BY place");
$places = $stmt->fetchAll(PDO::FETCH_COLUMN);   // a simple list: ['Alappuzha', 'Ernakulam', ...]

// Validate the place too: it must be one of the places in the database.
if ($place != '' && !in_array($place, $places, true)) {
    $place = '';
}

// ---- 4. Build the SQL query ----------------------------------------------
// "WHERE 1 = 1" is always true. It lets us add "AND ..." lines one by one.
// Every "?" is a placeholder. The real value is sent separately in $values,
// so a user can never change the meaning of the query (this is a "prepared statement").
$sql = 'SELECT name, blood_group, place, contact_number, department, donor_status
        FROM students
        WHERE 1 = 1';
$values = [];                                   // the values that fill the ? marks, in order

if ($name != '') {
    $sql .= ' AND name LIKE ?';                 // LIKE = "contains" search
    $values[] = '%' . $name . '%';              // % means "anything before / after"
}
if ($blood_group != '') {
    $sql .= ' AND blood_group = ?';
    $values[] = $blood_group;
}
if ($place != '') {
    $sql .= ' AND place = ?';
    $values[] = $place;
}
$sql .= ' ORDER BY name';

// ---- 5. Run the query -----------------------------------------------------
$stmt = $pdo->prepare($sql);
$stmt->execute($values);
$donors = $stmt->fetchAll();                    // all matching rows
$count  = count($donors);

// Did the user use any filter? (used for the heading text)
$is_filtered = ($name != '' || $blood_group != '' || $place != '');

$page_title   = 'Find Donors';
$current_page = 'donors';
require 'includes/header.php';
?>

<h1>Find Donors</h1>

<!-- ---- SEARCH FORM: sends the choices to this same page with GET ---- -->
<form method="get" action="find_donors.php" class="filter-form">
    <div class="filter-field">
        <label for="name">Donor name</label>
        <input type="text" id="name" name="name" placeholder="Search by name"
               value="<?php echo htmlspecialchars($name); ?>">
    </div>

    <div class="filter-field">
        <label for="blood_group">Blood group</label>
        <select id="blood_group" name="blood_group">
            <option value="">All blood groups</option>
            <?php foreach ($all_groups as $group) { ?>
                <option value="<?php echo htmlspecialchars($group); ?>"
                    <?php if ($group == $blood_group) { echo 'selected'; } ?>>
                    <?php echo htmlspecialchars($group); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="place">District / place</label>
        <select id="place" name="place">
            <option value="">All places</option>
            <?php foreach ($places as $p) { ?>
                <option value="<?php echo htmlspecialchars($p); ?>"
                    <?php if ($p == $place) { echo 'selected'; } ?>>
                    <?php echo htmlspecialchars($p); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="filter-buttons">
        <button type="submit" class="btn">Search</button>
        <a class="btn btn-light" href="find_donors.php">Show all donors</a>
    </div>
</form>

<!-- ---- RESULT COUNT ---- -->
<p class="muted">
    <?php if ($is_filtered) { ?>
        <?php echo $count; ?> donor(s) match your search.
    <?php } else { ?>
        Showing all <?php echo $count; ?> donors.
    <?php } ?>
</p>

<!-- ---- RESULTS TABLE ---- -->
<?php if ($count == 0) { ?>

    <div class="alert alert-info">
        No donors found for this search. Try a different name, blood group or place,
        or click <a href="find_donors.php">Show all donors</a>.
    </div>

<?php } else { ?>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Blood group</th>
                <th>Place</th>
                <th>Department</th>
                <th>Contact number</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $number = 1;
            foreach ($donors as $donor) {   // one loop = one table row
            ?>
            <tr>
                <td><?php echo $number++; ?></td>
                <!-- htmlspecialchars() makes the text safe to show inside HTML -->
                <td><?php echo htmlspecialchars($donor['name']); ?></td>
                <td><span class="badge"><?php echo htmlspecialchars($donor['blood_group']); ?></span></td>
                <td><?php echo htmlspecialchars($donor['place'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($donor['department'] ?? '-'); ?></td>
                <td>
                    <?php
                    // Some donors have no phone number in the source data (NULL).
                    if ($donor['contact_number'] == '') {
                        echo '<em class="muted">Not provided</em>';
                    } else {
                        echo htmlspecialchars($donor['contact_number']);
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($donor['donor_status']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>

<?php } ?>

<?php require 'includes/footer.php'; ?>
