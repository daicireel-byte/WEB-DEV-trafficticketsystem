<?php
/**
 * ADD PAYMENT FORM
 * Location: add_payment.php
 * 
 * PURPOSE: Process payment for a ticket
 * FLOW: Get Ticket → Display Form → Submit to process_payment.php
 */

$page_title = "Process Payment";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get ticket ID from URL
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if ($ticket_id == 0) {
    $_SESSION['error'] = "Invalid ticket ID";
    header("Location: tickets.php");
    exit;
}

// Get ticket details
$stmt = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE id = ? AND status != 'PAID'");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Ticket not found or already paid";
    header("Location: tickets.php");
    exit;
}

// Get payment methods
$payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE status = 'ACTIVE' ORDER BY method_name")->fetchAll();

// Calculate current amount with late fees
$current_amount = $ticket['total_amount'];
$late_fee = 0;
if ($ticket['days_overdue'] > 0) {
    $late_fee = $ticket['total_amount'] * 0.03 * $ticket['days_overdue']; // 3% per day
    $current_amount = $ticket['total_amount'] + $late_fee;
}

// Generate receipt number
$receipt_number = generateReceiptNumber($pdo);
?>

<div class="container">
    <div class="page-header">
        <h1>Process Payment</h1>
        <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-secondary">← Back to Ticket</a>
    </div>

    <?php if ($ticket['days_overdue'] > 0): ?>
    <div class="alert alert-warning">
        ⚠️ This ticket is <?= $ticket['days_overdue'] ?> day(s) overdue. 
        Late fee of ₱<?= number_format($late_fee, 2) ?> has been added.
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Ticket Summary -->
        <div class="card">
            <h2>Ticket Summary</h2>
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Ticket Number:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['ticket_number']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Violator:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['violator_name']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">License #:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['license_number']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Violation:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['violation_name']) ?></t