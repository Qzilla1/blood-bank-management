<?php
/**
 * Emergency Blood Requests Log
 * Lists patient demands, provides advanced search/filters, and processes record deletion.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Check if a DELETE request is submitted
if (isset($_GET['delete_id'])) {
    check_login();
    $deleteId = (int)$_GET['delete_id'];

    try {
        // Fetch patient name for logging/toast message
        $infoStmt = $pdo->prepare("SELECT patient_name FROM requests WHERE id = :id");
        $infoStmt->execute([':id' => $deleteId]);
        $patientName = $infoStmt->fetchColumn();

        if ($patientName) {
            $deleteStmt = $pdo->prepare("DELETE FROM requests WHERE id = :id");
            $deleteStmt->execute([':id' => $deleteId]);
            $_SESSION['success_message'] = "Blood request for '" . htmlspecialchars($patientName) . "' was deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Request record not found. Unable to delete.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error executing database deletion query.";
    }

    header("Location: requests.php");
    exit();
}

// Include header layout
require_once 'includes/header.php';

// Read URL Parameters for Search & Filters
$search       = trim($_GET['search']      ?? '');
$filterBg     = trim($_GET['blood_group'] ?? '');
$filterStatus = trim($_GET['status']      ?? '');

// Build parameterized dynamic query
$queryStr = "SELECT * FROM requests WHERE 1=1";
$params = [];

if (!empty($search)) {
    $queryStr .= " AND (patient_name LIKE :search OR hospital LIKE :search OR contact LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filterBg)) {
    $queryStr .= " AND blood_group = :blood_group";
    $params[':blood_group'] = $filterBg;
}

if (!empty($filterStatus)) {
    $queryStr .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

$queryStr .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($queryStr);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
    $_SESSION['error_message'] = "Failed to load request logs from server.";
}

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$statuses    = ['Pending', 'Approved', 'Fulfilled', 'Rejected'];
?>

<!-- Search, Filter & Addition Action Panel -->
<div class="premium-card mb-4 animate__animated animate__fadeIn">
    <div class="premium-card-body">
        <form method="GET" action="requests.php" class="row g-3 align-items-center">

            <!-- Search Text -->
            <div class="col-lg-3 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control form-control-premium"
                           placeholder="Search patient, hospital..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <!-- Blood group selection -->
            <div class="col-lg-2 col-md-6">
                <select name="blood_group" class="form-control form-control-premium">
                    <option value="">-- Group --</option>
                    <?php foreach ($bloodGroups as $group): ?>
                        <option value="<?php echo $group; ?>"
                            <?php echo $filterBg === $group ? 'selected' : ''; ?>>
                            <?php echo $group; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status filter selection -->
            <div class="col-lg-2 col-md-6">
                <select name="status" class="form-control form-control-premium">
                    <option value="">-- Status --</option>
                    <?php foreach ($statuses as $stat): ?>
                        <option value="<?php echo $stat; ?>"
                            <?php echo $filterStatus === $stat ? 'selected' : ''; ?>>
                            <?php echo $stat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action buttons -->
            <div class="col-lg-3 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-premium-primary w-100 py-2">
                    <i class="fa-solid fa-filter me-1"></i> Apply Filter
                </button>
                <?php if (!empty($search) || !empty($filterBg) || !empty($filterStatus)): ?>
                    <a href="requests.php" class="btn btn-premium-secondary py-2" title="Reset Filters">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Request Blood trigger button -->
            <div class="col-lg-2 col-md-6 text-lg-end">
                <a href="request-add.php" class="btn btn-premium-primary w-100 py-2">
                    <i class="fa-solid fa-square-plus me-1"></i> Add Request
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Requests Table Log -->
<div class="premium-card animate__animated animate__fadeIn">
    <div class="premium-card-header">
        <h3 class="premium-card-title">
            <i class="fa-solid fa-hand-holding-droplet text-danger"></i>
            Emergency Requests (<?php echo count($requests); ?>)
        </h3>
    </div>

    <div class="premium-card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-receipt mb-3" style="font-size: 40px;"></i>
                <h4>No Requests Logged</h4>
                <p class="mb-0 mt-2">Adjust your parameters or add an emergency request form above.</p>
            </div>
        <?php else: ?>
            <div class="table-premium-container">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Patient / Hospital</th>
                            <th>Blood Type</th>
                            <th>Units Needed</th>
                            <th>Date Requested</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="font-monospace text-muted">#<?php echo $req['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-light">
                                        <?php echo htmlspecialchars($req['patient_name']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fa-solid fa-hospital me-1"></i>
                                        <?php echo htmlspecialchars($req['hospital']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-blood">
                                        <i class="fa-solid fa-droplet"></i>
                                        <?php echo htmlspecialchars($req['blood_group']); ?>
                                    </span>
                                </td>
                                <td class="font-monospace fw-bold text-center text-md-start">
                                    <?php echo htmlspecialchars($req['units_needed']); ?> Unit(s)
                                </td>
                                <td>
                                    <span class="font-monospace text-info fw-bold">
                                        <?php echo $req['requested_on']; ?>
                                    </span>
                                </td>
                                <td class="font-monospace">
                                    <?php echo htmlspecialchars($req['contact']); ?>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = 'badge-status-pending';
                                        if ($req['status'] === 'Approved')  $statusClass = 'badge-status-approved';
                                        if ($req['status'] === 'Fulfilled') $statusClass = 'badge-status-fulfilled';
                                        if ($req['status'] === 'Rejected')  $statusClass = 'badge-status-cancelled';
                                    ?>
                                    <span class="badge-status <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($req['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="request-edit.php?id=<?php echo $req['id']; ?>"
                                           class="btn-premium-action edit-btn"
                                           title="Process / Edit Request">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </a>
                                        <a href="requests.php?delete_id=<?php echo $req['id']; ?>"
                                           class="btn-premium-action delete-btn delete-trigger"
                                           data-item="blood request: #<?php echo $req['id']; ?> (<?php echo htmlspecialchars($req['patient_name']); ?>)"
                                           title="Delete Request">
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