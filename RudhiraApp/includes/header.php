<?php
// includes/header.php
// Top part shared by all pages: <head>, site name and the navigation menu.
// Before including this file, a page can set $page_title and $current_page.
if (!isset($page_title))   { $page_title = 'RudhiraApp'; }
if (!isset($current_page)) { $current_page = ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?> - RudhiraApp</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">&#129656; RudhiraApp <span>Blood Donor Management System</span></div>
        <nav>
            <a href="dashboard.php"      class="<?php echo $current_page == 'home'     ? 'active' : ''; ?>">Home</a>
            <a href="find_donors.php"    class="<?php echo $current_page == 'donors'   ? 'active' : ''; ?>">Find Donors</a>
            <a href="blood_requests.php" class="<?php echo $current_page == 'requests' ? 'active' : ''; ?>">Blood Requests</a>
            <a href="camps.php"          class="<?php echo $current_page == 'camps'    ? 'active' : ''; ?>">Camps</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="container">
