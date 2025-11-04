<?php
/**
 * REPORTS PAGE
 * Location: reports.php
 * 
 * PURPOSE: Display various reports and analytics
 * FLOW: Database Connection → Generate Reports → Display Statistics
 */

$page_title = "Reports & Analytics";
require_once 'config/database.php';
require_once 'includes/header.php';

// Financial Summary
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'PAID' THEN total_amount ELSE 0 END) as total_collected,
        SUM(CASE WHEN status IN ('UNPAID', 'OVERDUE') THEN total_amount ELSE 0 END) as outstanding_balance
    FROM tickets
");
$financial = $stmt->fetch();

// Status Distribution
$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total_amount) as total_amount
    FROM tickets
    GROUP BY status
");
$status_distribution = $stmt->fetchAll();

// Top Violations
$stmt = $pdo->query("
    SELECT 
        vt.violation_name,
        vt.ordinance_code,
        COUNT(t.id) as ticket_count,
        SUM(t.total_amount) as total_revenue
    FROM violation_types vt
    LEFT JOIN tickets t ON vt.id = t.violation_type
    GROUP BY vt.id
    ORDER BY ticket_count DESC
    LIMIT 5
");
$top_violations = $stmt->fetchAll();

// Monthly Revenue (Last 6 Months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        COUNT(*) as payment_count,
        SUM(amount_paid) as total_amount
    FROM payments
    WHERE payment_status = 'COMPLETED'
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month DESC
");
$monthly_revenue = $stmt->fetchAll();

// Officer Performance
$stmt = $pdo->query("
    SELECT 
        o.full_name as officer_name,
        o.badge_number,
        COUNT(t.id) as tickets_issued,
        SUM(CASE WHEN t.status = 'PAID' THEN t.total_amount ELSE 0 END) as amount_collected
    FROM officers o
    LEFT JOIN tickets t ON o.id = t.officer_id
    GROUP BY o.id
    ORDER BY tickets_issued DESC
");
$officer_performance = $stmt->fetchAll();

// Collection Rate
$collection_rate = $financial['total_tickets'] > 0 
    ? ($stmt = $pdo->query("SELECT COUNT(*) as paid FROM tickets WHERE status = 'PAID'"))->fetch()['paid'] / $financial['total_tickets'] * 100 
    : 0;
?>

<div class="container">
    <div class="page-header">
        <h1>Reports & Analytics</h1>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
    </div>

    <!-- Financial Summary -->
    <div class="card">
        <h2>📊 Financial Summary</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Tickets</h3>
                <div class="number"><?= number_format($financial['total_tickets']) ?></div>
            </div>
            <div class="stat-card paid">
                <h3>Total Collected</h3>
                <div class="number">₱<?= number_format($financial['total_collected'], 2) ?></div>
            </div>
            <div class="stat-card unpaid">
                <h3>Outstanding Balance</h3>
                <div class="number">₱<?= number_format($financial['outstanding_balance'], 2) ?></div>
            </div>
            <div class="stat-card revenue">
                <h3>Collection Rate</h3>
                <div class="number"><?= number_format($collection_rate, 1) ?>%</div>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="card">
        <h2>📈 Status Distribution</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Total Amount</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($status_distribution as $status): ?>
                    <tr>
                        <td><span class="badge <?= strtolower($status['status']) ?>"><?= $status['status'] ?></span></td>
                        <td><strong><?= number_format($status['count']) ?></strong></td>
                        <td><strong>₱<?= number_format($status['total_amount'], 2) ?></strong></td>
                        <td>
                            <?php 
                            $percentage = $financial['total_tickets'] > 0 
                                ? ($status['count'] / $financial['total_tickets'] * 100) 
                                : 0;
                            ?>
                            <div style="background: #ecf0f1; height: 20px; border-radius: 10px; overflow: hidden; position: relative;">
                                <div style="background: <?= $status['status'] == 'PAID' ? '#27ae60' : '#e74c3c' ?>; 
                                            height: 100%; width: <?= $percentage ?>%; transition: width 0.3s;"></div>
                                <span style="position: absolute; width: 100%; text-align: center; line-height: 20px; 
                                             font-size: 0.85rem; font-weight: bold;">
                                    <?= number_format($percentage, 1) ?>%
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Top Violations -->
        <div class="card">
            <h2>🚫 Top 5 Violations</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Violation</th>
                            <th>Code</th>
                            <th>Count</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_violations as $violation): ?>
                        <tr>
                            <td><?= htmlspecialchars($violation['violation_name']) ?></td>
                            <td><?= htmlspecialchars($violation['ordinance_code']) ?></td>
                            <td><strong><?= number_format($violation['ticket_count']) ?></strong></td>
                            <td><strong>₱<?= number_format($violation['total_revenue'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="card">
            <h2>💰 Monthly Revenue (Last 6 Months)</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Payments</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthly_revenue)): ?>
                        <tr>
                            <td colspan="3" class="empty-state">No payment data available</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($monthly_revenue as $month): ?>
                            <tr>
                                <td><strong><?= date('F Y', strtotime($month['month'] . '-01')) ?></strong></td>
                                <td><?= number_format($month['payment_count']) ?></td>
                                <td><strong style="color: #27ae60;">₱<?= number_format($month['total_amount'], 2) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Officer Performance -->
    <div class="card">
        <h2>👮 Officer Performance</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Officer Name</th>
                        <th>Badge Number</th>
                        <th>Tickets Issued</th>
                        <th>Amount Collected</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($officer_performance)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">No officer data available</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $max_tickets = max(array_column($officer_performance, 'tickets_issued'));
                        foreach($officer_performance as $officer): 
                            $performance_percentage = $max_tickets > 0 ? ($officer['tickets_issued'] / $max_tickets * 100) : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($officer['officer_name']) ?></strong></td>
                            <td><?= htmlspecialchars($officer['badge_number']) ?></td>
                            <td><strong><?= number_format($officer['tickets_issued']) ?></strong></td>
                            <td><strong style="color: #27ae60;">₱<?= number_format($officer['amount_collected'], 2) ?></strong></td>
                            <td>
                                <div style="background: #ecf0f1; height: 20px; border-radius: 10px; overflow: hidden; position: relative;">
                                    <div style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); 
                                                height: 100%; width: <?= $performance_percentage ?>%; transition: width 0.3s;"></div>
                                    <span style="position: absolute; width: 100%; text-align: center; line-height: 20px; 
                                                 font-size: 0.85rem; font-weight: bold;">
                                        <?= number_format($performance_percentage, 0) ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Footer -->
    <div class="card" style="text-align: center; color: #7f8c8d;">
        <p>Report Generated: <?= date('F d, Y g:i A') ?></p>
        <p style="margin-top: 0.5rem;">City Ordinance and Traffic Violation Payment System</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>