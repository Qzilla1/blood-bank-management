<?php
/**
 * Donors Directory
 * Displays donor records in a table, supports search & blood-group filtering, and handles deletions.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Check if a DELETE request was submitted
if (isset($_GET['delete_id'])) {
    // Secure page validation
    check_login();
    $deleteId = (int)$_GET['delete_id'];
    
    try {
        // Fetch details for logging/toast message
        $infoStmt = $pdo->prepare("SELECT name FROM donors WHERE id = :id");
        $infoStmt->execute([':id' => $deleteId]);
        $donorName = $infoStmt->fetchColumn();

        if ($donorName) {
            // Delete donor record
            $deleteStmt = $pdo->prepare("DELETE FROM donors WHERE id = :id");
            $deleteStmt->execute([':id' => $deleteId]);
            
            $_SESSION['success_message'] = "Donor profile for '" . htmlspecialchars($donorName) . "' was deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Donor record not found. Unable to delete.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database constraint conflict: Unable to delete donor profile.";
    }
    
    header("Location: donors.php");
    exit();
}

// Include header layout
require_once 'includes/header.php';

// Parameters for Search & Filters
$search = trim($_GET['search'] ?? '');
$filterBg = trim($_GET['blood_group'] ?? '');

// Build dynamic search query using PDO
$queryStr = "SELECT * FROM donors WHERE 1=1";
$params = [];

if (!empty($search)) {
    $queryStr .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search OR address LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filterBg)) {
    $queryStr .= " AND blood_group = :blood_group";
    $params[':blood_group'] = $filterBg;
}

$queryStr .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($queryStr);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();
} catch (PDOException $e) {
    $donors = [];
    $_SESSION['error_message'] = "Error retrieving donor list from server database.";
}

// Fetch all unique blood groups for the filter dropdown
$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>

<!-- Action Bar: Search, Filter, and Register Trigger -->
<div class="premium-card mb-4 animate__animated animate__fadeIn">
    <div class="premium-card-body">
        <form method="GET" action="donors.php" class="row g-3 align-items-center">
            
            <!-- Text Search input -->
            <div class="col-lg-4 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control form-control-premium" placeholder="Search by name, phone, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <!-- Blood group selection -->
            <div class="col-lg-3 col-md-6">
                <select name="blood_group" class="form-control form-control-premium">
                    <option value="">-- Filter Blood Group --</option>
                    <?php foreach ($bloodGroups as $group): ?>
                        <option value="<?php echo $group; ?>" <?php echo $filterBg === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Actions buttons -->
            <div class="col-lg-3 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-premium-primary w-100 py-2">
                    <i class="fa-solid fa-filter me-1"></i> Apply Filter
                </button>
                <?php if (!empty($search) || !empty($filterBg)): ?>
                    <a href="donors.php" class="btn btn-premium-secondary py-2" title="Reset Search">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Register New Donor button -->
            <div class="col-lg-2 col-md-6 text-lg-end">
                <a href="donor-add.php" class="btn btn-premium-primary w-100 py-2" style="background: linear-gradient(135deg, var(--accent-emerald), #25a18e); box-shadow: 0 4px 12px rgba(46, 196, 182, 0.25);">
                    <i class="fa-solid fa-user-plus me-1"></i> Add Donor
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Grid Table of Donors -->
<div class="premium-card animate__animated animate__fadeIn">
    <div class="premium-card-header">
        <h3 class="premium-card-title">
            <i class="fa-solid fa-users text-danger"></i> Registered Donors (<?php echo count($donors); ?>)
        </h3>
    </div>
    
    <div class="premium-card-body p-0">
        <?php if (empty($donors)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-regular fa-face-frown mb-3" style="font-size: 40px;"></i>
                <h4>No Donors Found</h4>
                <p class="mb-0 mt-2">Adjust your filters or register a new voluntary donor profile above.</p>
            </div>
        <?php else: ?>
            <div class="table-premium-container">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Donor Name</th>
                            <th>Age / Sex</th>
                            <th>Blood Group</th>
                            <th>Contact Details</th>
                            <th>Last Donation Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donors as $donor): ?>
                            <tr>
                                <td class="font-monospace text-muted">#<?php echo $donor['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-light"><?php echo htmlspecialchars($donor['name']); ?></div>
                                    <div class="text-muted" style="font-size: 12px;"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($donor['address']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($donor['age']); ?> Years</div>
                                    <div class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($donor['gender']); ?></div>
                                </td>
                                <td>
                                    <span class="badge-blood">
                                        <i class="fa-solid fa-droplet"></i> <?php echo htmlspecialchars($donor['blood_group']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 14px;"><i class="fa-solid fa-phone me-1 text-muted" style="font-size: 11px;"></i> <?php echo htmlspecialchars($donor['phone']); ?></div>
                                    <div style="font-size: 13px;" class="text-muted"><i class="fa-regular fa-envelope me-1" style="font-size: 11px;"></i> <?php echo htmlspecialchars($donor['email']); ?></div>
                                </td>
                                <td>
                                    <?php if ($donor['last_donation_date']): ?>
                                        <span class="font-monospace text-success fw-bold">
                                            <?php echo date('Y-m-d', strtotime($donor['last_donation_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted italic" style="font-size: 13px;">Never Donated</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="donor-edit.php?id=<?php echo $donor['id']; ?>" class="btn-premium-action edit-btn" title="Edit Profile">
                                            <i class="fa-solid fa-user-pen"></i>
                                        </a>
                                        <a href="donors.php?delete_id=<?php echo $donor['id']; ?>" class="btn-premium-action delete-btn delete-trigger" data-item="donor profile: <?php echo htmlspecialchars($donor['name']); ?>" title="Delete Profile">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
