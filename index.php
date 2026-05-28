<?php
/**
 * Admin Dashboard - Home Page
 * Renders high-level overview metrics, inventory analytics, and urgent request states.
 */

require_once 'includes/header.php';

$totalDonors       = 0;
$totalRequests     = 0;
$pendingRequests   = 0;
$totalUnitsInStock = 0;
$stockGroups       = [];
$stockUnits        = [];
$recentRequests    = [];

try {
    // 1. Fetch count metrics
    $totalDonors       = $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn();
    $totalRequests     = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    $pendingRequests   = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
    $totalUnitsInStock = $pdo->query("SELECT SUM(units) FROM blood_stock")->fetchColumn() ?? 0;

    // 2. Fetch stock levels for Chart.js
    $stockQuery = $pdo->query("SELECT blood_group, units FROM blood_stock ORDER BY FIELD(blood_group, 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')");
    while ($row = $stockQuery->fetch()) {
        $stockGroups[] = $row['blood_group'];
        $stockUnits[]  = (int)$row['units'];
    }

    // 3. Fetch recent urgent requests
    $recentRequestsStmt = $pdo->query("SELECT * FROM requests ORDER BY FIELD(status, 'Pending', 'Approved', 'Fulfilled', 'Rejected'), requested_on ASC LIMIT 4");
    $recentRequests = $recentRequestsStmt->fetchAll();

} catch (PDOException $e) {
    // handled gracefully
}
?>

<!-- Metrics Grid -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="metrics-card">
            <div class="metrics-card-glow crimson"></div>
            <div class="metrics-card-icon crimson"><i class="fa-solid fa-users"></i></div>
            <div class="metrics-card-value"><?php echo number_format($totalDonors); ?></div>
            <h3 class="metrics-card-title">Voluntary Donors</h3>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metrics-card">
            <div class="metrics-card-glow emerald"></div>
            <div class="metrics-card-icon emerald"><i class="fa-solid fa-droplet"></i></div>
            <div class="metrics-card-value"><?php echo number_format($totalUnitsInStock); ?> <span style="font-size:14px;font-weight:500;color:var(--text-muted);">Units</span></div>
            <h3 class="metrics-card-title">Total Stock in Bank</h3>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metrics-card">
            <div class="metrics-card-glow amber"></div>
            <div class="metrics-card-icon amber"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="metrics-card-value"><?php echo number_format($pendingRequests); ?></div>
            <h3 class="metrics-card-title">Pending Emergency Orders</h3>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="metrics-card">
            <div class="metrics-card-glow blue"></div>
            <div class="metrics-card-icon blue"><i class="fa-solid fa-receipt"></i></div>
            <div class="metrics-card-value"><?php echo number_format($totalRequests); ?></div>
            <h3 class="metrics-card-title">Accumulated Requests Log</h3>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Stock Chart -->
    <div class="col-xl-7">
        <div class="premium-card h-100">
            <div class="premium-card-header">
                <h3 class="premium-card-title">
                    <i class="fa-solid fa-chart-simple text-danger"></i> Inventory Stock Metrics
                </h3>
                <span class="badge bg-secondary-subtle text-light border border-secondary rounded-pill font-monospace" style="font-size:11px;">
                    Units Available
                </span>
            </div>
            <div class="premium-card-body d-flex flex-column justify-content-center" style="position:relative;min-height:320px;">
                <canvas id="bloodStockChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Urgent Requests -->
    <div class="col-xl-5">
        <div class="premium-card h-100">
            <div class="premium-card-header">
                <h3 class="premium-card-title">
                    <i class="fa-solid fa-kit-medical text-danger"></i> Urgent Hospital Requests
                </h3>
                <a href="requests.php" class="btn btn-sm btn-premium-secondary border-0 py-1" style="font-size:12px;">View All</a>
            </div>
            <div class="premium-card-body p-0">

                <?php if (empty($recentRequests)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fa-regular fa-folder-open mb-3" style="font-size:32px;"></i>
                        <p class="mb-0">No hospital requests recorded.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush border-0 bg-transparent rounded-0">
                        <?php foreach ($recentRequests as $request): ?>
                            <div class="list-group-item bg-transparent border-0 border-bottom border-secondary-subtle p-3 transition-bg-hover">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0 fw-bold text-light" style="font-size:15px;">
                                        <?php echo htmlspecialchars($request['patient_name']); ?>
                                    </h5>
                                    <span class="badge-blood">
                                        <i class="fa-solid fa-droplet"></i> <?php echo htmlspecialchars($request['blood_group']); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div style="font-size:13px;color:var(--text-muted);">
                                        <div>
                                            <i class="fa-solid fa-hospital me-1" style="font-size:11px;"></i>
                                            <?php echo htmlspecialchars($request['hospital']); ?>
                                        </div>
                                        <div>
                                            <i class="fa-regular fa-calendar-days me-1" style="font-size:11px;"></i>
                                            Requested: <strong><?php echo $request['requested_on']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php
                                            $statusClass = 'badge-status-pending';
                                            if ($request['status'] === 'Approved')  $statusClass = 'badge-status-approved';
                                            if ($request['status'] === 'Fulfilled') $statusClass = 'badge-status-fulfilled';
                                            if ($request['status'] === 'Rejected')  $statusClass = 'badge-status-cancelled';
                                        ?>
                                        <span class="badge-status <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                        <a href="request-edit.php?id=<?php echo $request['id']; ?>"
                                           class="btn-premium-action edit-btn" title="Process request">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
$jsGroups = json_encode($stockGroups);
$jsUnits  = json_encode($stockUnits);
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initBloodStockChart(<?php echo $jsGroups; ?>, <?php echo $jsUnits; ?>);
    });
</script>

<?php require_once 'includes/footer.php'; ?>