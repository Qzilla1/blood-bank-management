<?php
/**
 * Blood Stock Inventory Management
 * Displays real-time stock levels of all 8 blood groups with fast inline update forms.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Secure the page
check_login();

$errors = [];

// Handle inline stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $blood_group = trim($_POST['blood_group'] ?? '');
    $units = trim($_POST['units'] ?? '');

    if (empty($blood_group)) {
        $errors[] = "Blood group selection is required.";
    }
    
    if ($units === '') {
        $errors[] = "Stock units quantity is required.";
    } else {
        $unitsInt = (int)$units;
        if ($unitsInt < 0 || $unitsInt > 200) {
            $errors[] = "Units quantity must be a positive integer between 0 and 200.";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE blood_stock SET units = :units WHERE blood_group = :bg");
            $stmt->execute([
                ':units' => (int)$units,
                ':bg' => $blood_group
            ]);

            $_SESSION['success_message'] = "Inventory level for blood type '" . htmlspecialchars($blood_group) . "' successfully updated to $units units!";
            header("Location: inventory.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to update inventory level in database.";
        }
    }
}

// Include header layout
require_once 'includes/header.php';

// Fetch all standard blood stock levels
$stockLevels = [];
try {
    $stockQuery = $pdo->query("SELECT * FROM blood_stock ORDER BY FIELD(blood_group, 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')");
    $stockLevels = $stockQuery->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Failed to fetch stock metrics from server.";
}
?>

<!-- Alerts Panel -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger p-3 mb-4 rounded-3 animate__animated animate__fadeIn">
        <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i> Update Error:</h5>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Inventory Cards Grid -->
<div class="row g-4 animate__animated animate__fadeIn mb-4">
    <?php foreach ($stockLevels as $stock): ?>
        <div class="col-xxl-3 col-lg-4 col-sm-6">
            <div class="premium-card h-100">
                <div class="premium-card-body p-4 text-center">
                    
                    <!-- Blood Droplet Icon -->
                    <div class="mb-3 position-relative d-inline-block">
                        <span class="text-danger" style="font-size: 56px;">
                            <i class="fa-solid fa-droplet"></i>
                        </span>
                        <!-- Blood Group Overlay text -->
                        <span class="position-absolute start-50 top-50 translate-middle fw-bold text-light" style="font-size: 15px; margin-top: 5px;">
                            <?php echo htmlspecialchars($stock['blood_group']); ?>
                        </span>
                    </div>

                    <!-- Quantity metrics -->
                    <div class="mb-4">
                        <div class="h2 fw-bold text-light mb-1">
                            <?php echo htmlspecialchars($stock['units']); ?> <span class="fs-6 fw-normal text-muted">Units</span>
                        </div>
                        
                        <?php if ($stock['units'] <= 5): ?>
                            <span class="badge bg-danger-subtle text-danger px-2.5 py-1 border border-danger-subtle rounded-pill" style="font-size: 11px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Critical Low Stock
                            </span>
                        <?php elseif ($stock['units'] <= 12): ?>
                            <span class="badge bg-warning-subtle text-warning px-2.5 py-1 border border-warning-subtle rounded-pill" style="font-size: 11px;">
                                <i class="fa-solid fa-circle-info"></i> Medium Stock
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success px-2.5 py-1 border border-success-subtle rounded-pill" style="font-size: 11px;">
                                <i class="fa-solid fa-circle-check"></i> Stock Optimal
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Inline Rapid adjustment form -->
                    <form method="POST" action="inventory.php" class="border-top border-secondary-subtle pt-4">
                        <input type="hidden" name="adjust_stock" value="1">
                        <input type="hidden" name="blood_group" value="<?php echo htmlspecialchars($stock['blood_group']); ?>">
                        
                        <div class="input-group">
                            <input type="number" name="units" class="form-control form-control-premium text-center font-monospace" value="<?php echo htmlspecialchars($stock['units']); ?>" min="0" max="200" placeholder="Qty" required>
                            <button type="submit" class="btn btn-premium-primary px-3" title="Update units">
                                <i class="fa-solid fa-floppy-disk"></i>
                            </button>
                        </div>
                    </form>

                    <div class="mt-2 text-muted font-monospace" style="font-size: 10px;">
                        Last updated: <?php echo date('M d, H:i', strtotime($stock['last_updated'])); ?>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Extra Operational Guideline card -->
<div class="premium-card animate__animated animate__fadeIn">
    <div class="premium-card-header">
        <h3 class="premium-card-title text-warning">
            <i class="fa-solid fa-circle-info"></i> Standard Blood Bank Inventory Guidelines
        </h3>
    </div>
    <div class="premium-card-body">
        <div class="row g-4">
            <div class="col-md-6 border-end border-secondary">
                <h5 class="fw-bold text-light mb-2"><i class="fa-solid fa-circle-exclamation text-danger me-1"></i> Stock Deficit Prevention</h5>
                <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.5;">
                    Maintaining at least <strong>5 units</strong> of each blood group is highly recommended to manage micro-emergencies. Red alert metrics are triggered automatically when a group falls below this threshold. Fulfilling emergency orders is blocked by the matching analyzer if stock falls below requested patient volumes.
                </p>
            </div>
            <div class="col-md-6">
                <h5 class="fw-bold text-light mb-2"><i class="fa-solid fa-circle-nodes text-success me-1"></i> Fulfillments & Returns Synchronization</h5>
                <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.5;">
                    Adjusting stock levels manually via this dashboard bypasses transaction logs. For systematic request tracking, use the <strong>Emergency Request Processing</strong> terminal to update request statuses, which automatically adjusts units and maintains exact relational consistency.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
