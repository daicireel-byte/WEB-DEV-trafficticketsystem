<?php
/**
 * VIEW TICKET DETAILS
 * Location: view_ticket.php
 * 
 * PURPOSE: Display detailed information about a specific ticket
 * FLOW: Get Ticket ID → Fetch Details → Display Information
 */

$page_title = "Ticket Details";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id == 0) {
    $_SESSION['error'] = "Invalid ticket ID";
    header("Location: tickets.php");
    exit;
}

// Get ticket details
$stmt = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Ticket not found";
    header("Location: tickets.php");
    exit;
}

// Get payment information if paid
$payment = null;
if ($ticket['status'] == 'PAID') {
    $stmt = $pdo->prepare("SELECT * FROM vw_payments_detailed WHERE ticket_number = ?");
    $stmt->execute([$ticket['ticket_number']]);
    $payment = $stmt->fetch();
}

// Calculate current amount with late fees if overdue
$current_amount = $ticket['total_amount'];
$late_fee = 0;
if ($ticket['days_overdue'] > 0) {
    $late_fee = $ticket['total_amount'] * 0.03 * $ticket['days_overdue']; // 3% per day example
    $current_amount = $ticket['total_amount'] + $late_fee;
}
?>

<div class="container">
    <div class="page-header">
        <h1>Ticket Details</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="tickets.php" class="btn btn-secondary">← Back</a>
            <?php if ($ticket['status'] != 'PAID'): ?>
            <a href="add_payment.php?ticket_id=<?= $ticket['id'] ?>" class="btn btn-success">💳 Process Payment</a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
        </div>
    </div>

    <?php if ($ticket['status'] == 'OVERDUE'): ?>
    <div class="alert alert-warning">
        ⚠️ This ticket is <?= $ticket['days_overdue'] ?> day(s) overdue. Late fees may apply.
    </div>
    <?php endif; ?>

    <div class="card">
        <!-- Ticket Header -->
        <div style="text-align: center; padding: 2rem; border-bottom: 2px solid #ecf0f1;">
            <h1 style="color: #2c3e50; margin-bottom: 0.5rem;">TRAFFIC VIOLATION TICKET</h1>
            <h2 style="color: #7f8c8d; font-size: 1.5rem;"><?= $ticket['ticket_number'] ?></h2>
            <span class="badge <?= strtolower($ticket['status']) ?>" style="font-size: 1rem; padding: 0.5rem 1.5rem;">
                <?= $ticket['status'] ?>
            </span>
        </div>

        <!-- Ticket Information Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; padding: 2rem;">
            
            <!-- Violator Information -->
            <div class="form-section">
                <h3>👤 Violator Information</h3>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Full Name:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['violator_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">License #:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['license_number']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Contact:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['violator_contact']) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Violation Details -->
            <div class="form-section">
                <h3>⚠️ Violation Details</h3>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Violation:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['violation_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Code:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['ordinance_code']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Severity:</td>
                        <td style="border: none; padding: 0.5rem 0;">
                            <span class="badge severity-<?= strtolower($ticket['severity_level']) ?>">
                                <?= $ticket['severity_level'] ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Location:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['location']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Plate #:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['plate_number']) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Date Information -->
            <div class="form-section">
                <h3>📅 Date Information</h3>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Date Issued:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= date('F d, Y g:i A', strtotime($ticket['date_issued'])) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Due Date:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= date('F d, Y', strtotime($ticket['due_date'])) ?></td>
                    </tr>
                    <?php if ($ticket['days_overdue'] > 0): ?>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #e74c3c;">Days Overdue:</td>
                        <td style="border: none; padding: 0.5rem 0; color: #e74c3c; font-weight: bold;">
                            <?= $ticket['days_overdue'] ?> days
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Officer Information -->
            <div class="form-section">
                <h3>👮 Issuing Officer</h3>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Officer Name:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['officer_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0.5rem 0; font-weight: bold; color: #7f8c8d;">Badge #:</td>
                        <td style="border: none; padding: 0.5rem 0;"><?= htmlspecialchars($ticket['badge_number']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Amount Section -->
        <div style="background: #f8f9fa; padding: 2rem; margin-top: 1rem; border-radius: 8px;">
            <h3 style="margin-bottom: 1rem;">💰 Payment Information</h3>
            <table style="width: 100%; max-width: 500px; border: none;">
                <tr>
                    <td style="border: none; padding: 0.5rem 0; font-size: 1.1rem;">Base Amount:</td>
                    <td style="border: none; padding: 0.5rem 0; text-align: right; font-size: 1.1rem;">
                        ₱<?= number_format($ticket['total_amount'], 2) ?>
                    </td>
                </tr>
                <?php if ($late_fee > 0): ?>
                <tr>
                    <td style="border: none; padding: 0.5rem 0; color: #e74c3c;">Late Fees (<?= $ticket['days_overdue'] ?> days):</td>
                    <td style="border: none; padding: 0.5rem 0; text-align: right; color: #e74c3c;">
                        ₱<?= number_format($late_fee, 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr style="border-top: 2px solid #dee2e6;">
                    <td style="border: none; padding: 1rem 0; font-size: 1.5rem; font-weight: bold;">
                        <?= $ticket['status'] == 'PAID' ? 'Amount Paid:' : 'Total Amount Due:' ?>
                    </td>
                    <td style="border: none; padding: 1rem 0; text-align: right; font-size: 1.5rem; font-weight: bold; color: <?= $ticket['status'] == 'PAID' ? '#27ae60' : '#e74c3c' ?>">
                        ₱<?= number_format($current_amount, 2) ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Payment Details if Paid -->
        <?php if ($payment): ?>
        <div style="background: #d4edda; padding: 2rem; margin-top: 1rem; border-radius: 8px; border-left: 4px solid #27ae60;">
            <h3 style="color: #155724; margin-bottom: 1rem;">✓ Payment Completed</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <strong style="color: #155724;">Receipt Number:</strong><br>
                    <?= htmlspecialchars($payment['receipt_number']) ?>
                </div>
                <div>
                    <strong style="color: #155724;">Payment Date:</strong><br>
                    <?= date('F d, Y g:i A', strtotime($payment['payment_date'])) ?>
                </div>
                <div>
                    <strong style="color: #155724;">Payment Method:</strong><br>
                    <?= htmlspecialchars($payment['payment_method']) ?>
                </div>
                <div>
                    <strong style="color: #155724;">Processed By:</strong><br>
                    <?= htmlspecialchars($payment['processed_by']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes Section -->
        <?php if (!empty($ticket['notes'])): ?>
        <div style="padding: 2rem; margin-top: 1rem; border-top: 2px solid #ecf0f1;">
            <h3>📋 Additional Notes</h3>
            <p style="color: #555; line-height: 1.8;"><?= nl2br(htmlspecialchars($ticket['notes'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>