<?php
/**
 * TICKETS PAGE
 * Location: tickets.php
 * 
 * PURPOSE: Display all tickets with search and filter functionality
 * FLOW: Database Connection → Get Tickets → Display Table
 */

$page_title = "All Tickets";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get search term if exists
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Get filter status if exists
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Build query with search and filter
$sql = "SELECT * FROM vw_tickets_detailed WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (ticket_number LIKE ? OR violator_name LIKE ? OR license_number LIKE ? OR plate_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY date_issued DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1>All Tickets</h1>
        <a href="add_ticket.php" class="btn btn-primary">
            ➕ Add New Ticket
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="card">
        <form method="GET" action="tickets.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by ticket #, name, license, or plate..." 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="form-group">
                    <label>Filter by Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="UNPAID" <?= $status_filter == 'UNPAID' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="PAID" <?= $status_filter == 'PAID' ? 'selected' : '' ?>>Paid</option>
                        <option value="OVERDUE" <?= $status_filter == 'OVERDUE' ? 'selected' : '' ?>>Overdue</option>
                        <option value="CANCELLED" <?= $status_filter == 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="tickets.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <h2>Ticket Records (<?= count($tickets) ?> found)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Violator Name</th>
                        <th>License #</th>
                        <th>Plate #</th>
                        <th>Violation</th>
                        <th>Amount</th>
                        <th>Date Issued</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="10" class="empty-state">
                            <h3>No tickets found</h3>
                            <p>Try adjusting your search or filters</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ticket['violator_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['license_number']) ?></td>
                            <td><?= htmlspecialchars($ticket['plate_number']) ?></td>
                            <td>
                                <?= htmlspecialchars($ticket['violation_name']) ?>
                                <br>
                                <small><span class="badge severity-<?= strtolower($ticket['severity_level']) ?>">
                                    <?= $ticket['severity_level'] ?>
                                </span></small>
                            </td>
                            <td><strong>₱<?= number_format($ticket['total_amount'], 2) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($ticket['date_issued'])) ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($ticket['due_date'])) ?>
                                <?php if ($ticket['days_overdue'] > 0): ?>
                                    <br><small style="color: #e74c3c;">(<?= $ticket['days_overdue'] ?> days overdue)</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= strtolower($ticket['status']) ?>"><?= $ticket['status'] ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="action-btn view" title="View Details">
                                        👁️
                                    </a>
                                    <?php if ($ticket['status'] != 'PAID'): ?>
                                    <a href="add_payment.php?ticket_id=<?= $ticket['id'] ?>" class="action-btn edit" title="Process Payment">
                                        💳
                                    </a>
                                    <?php endif; ?>
                                    <form method="POST" action="process/process_delete.php" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this ticket?')">
                                        <input type="hidden" name="delete_ticket" value="<?= $ticket['id'] ?>">
                                        <button type="submit" class="action-btn delete" title="Delete">
                                            🗑️
                                        </button>
                                    </form>
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