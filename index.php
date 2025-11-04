<?php
/**
 * DASHBOARD PAGE
 * Location: index.php
 * 
 * PURPOSE: Main dashboard showing statistics and recent activity
 * FLOW: Include Database → Get Statistics → Display Dashboard
 */

$page_title = "Dashboard";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get Statistics
$stats = [];

// Total tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
$stats['total_tickets'] = $stmt->fetch()['total'];

// Unpaid tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'UNPAID'");
$stats['unpaid'] = $stmt->fetch()['total'];

// Paid tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'PAID'");
$stats['paid'] = $stmt->fetch()['total'];

// Overdue tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'OVERDUE'");
$stats['overdue'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE payment_status = 'COMPLETED'");
$stats['revenue'] = $stmt->fetch()['total'];

// Recent tickets (last 10)
$stmt = $pdo->query("SELECT * FROM vw_tickets_detailed ORDER BY date_issued DESC LIMIT 10");
$recent_tickets = $stmt->fetchAll();

// Overdue alerts count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status IN ('UNPAID', 'OVERDUE') AND due_date < CURDATE()");
$overdue_alerts = $stmt->fetch()['total'];
?>

<div class="container">
    <div class="page-header">
        <h1>Dashboard Overview</h1>
    </div>

    <?php if ($overdue_alerts > 0): ?>
    <div class="alert alert-warning">
        ⚠️ You have <?= $overdue_alerts ?> overdue ticket(s) that need attention!
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Tickets</h3>
            <div class="number"><?= number_format($stats['total_tickets']) ?></div>
        </div>
        
        <div class="stat-card unpaid">
            <h3>Unpaid</h3>
            <div class="number"><?= number_format($stats['unpaid']) ?></div>
        </div>
        
        <div class="stat-card paid">
            <h3>Paid</h3>
            <div class="number"><?= number_format($stats['paid']) ?></div>
        </div>
        
        <div class="stat-card overdue">
            <h3>Overdue</h3>
            <div class="number"><?= number_format($stats['overdue']) ?></div>
        </div>
        
        <div class="stat-card revenue">
            <h3>Total Revenue</h3>
            <div class="number">₱<?= number_format($stats['revenue'], 2) ?></div>
        </div>
    </div>

    <!-- Recent Tickets Table -->
    <div class="card">
        <h2>Recent Tickets</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Violator Name</th>
                        <th>Violation</th>
                        <th>Amount</th>
                        <th>Date Issued</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_tickets)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <h3>No tickets found</h3>
                            <p>Start by adding a new ticket</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($recent_tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ticket['violator_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['violation_name']) ?></td>
                            <td><strong>₱<?= number_format($ticket['total_amount'], 2) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($ticket['date_issued'])) ?></td>
                            <td><span class="badge <?= strtolower($ticket['status']) ?>"><?= $ticket['status'] ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="action-btn view" title="View Details">
                                        👁️
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>