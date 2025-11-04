<?php
/**
 * HEADER TEMPLATE
 * Location: includes/header.php
 * 
 * PURPOSE: Common header section for all pages
 * USAGE: Include this at the top of every page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Traffic Ticket System</title>
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Favicon (optional) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚦</text></svg>">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                🚦 Traffic Ticket System
            </a>
            <ul class="navbar-menu">
                <li>
                    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="tickets.php" class="<?= in_array($current_page, ['tickets.php', 'add_ticket.php', 'view_ticket.php']) ? 'active' : '' ?>">
                        Tickets
                    </a>
                </li>
                <li>
                    <a href="violators.php" class="<?= in_array($current_page, ['violators.php', 'add_violator.php']) ? 'active' : '' ?>">
                        Violators
                    </a>
                </li>
                <li>
                    <a href="payments.php" class="<?= in_array($current_page, ['payments.php', 'add_payment.php']) ? 'active' : '' ?>">
                        Payments
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
                        Reports
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <?php
    // Display success message if exists
    if (isset($_SESSION['success'])) {
        echo '<div class="container" style="margin-top: 2rem;">
                <div class="alert alert-success">
                    ✓ ' . $_SESSION['success'] . '
                </div>
              </div>';
        unset($_SESSION['success']);
    }

    // Display error message if exists
    if (isset($_SESSION['error'])) {
        echo '<div class="container" style="margin-top: 2rem;">
                <div class="alert alert-error">
                    ✗ ' . $_SESSION['error'] . '
                </div>
              </div>';
        unset($_SESSION['error']);
    }
    ?>