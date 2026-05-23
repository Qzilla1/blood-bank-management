<?php
/**
 * Edit / Process Blood Request
 * Handles request updates, stock availability checks, and automatic inventory unit adjustments.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Secure the page
check_login();

// Validate active ID parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error_message'] = "Invalid blood request identification code.";
    header("Location: requests.php");
    exit();
}

$errors = [];
$request = null;
$availableStock = 0;

// Fetch requested record details
try {
    $stmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

    if (!$request) {
        $_SESSION['error_message'] = "Requested blood demand log was not found.";
        header("Location: requests.php");
        exit();
    }

    // Check available stock levels for this blood group
    $stockStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1");
    $stockStmt->execute([':bg' => $request['blood_group']]);
    $availableStock = (int)($stockStmt->fetchColumn() ?? 0);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error retrieving request details.";
    header("Location: requests.php");
    exit();
}

// Extract variables for form render
$patient_name = $request['patient_name'];
$blood_group = $request['blood_group'];
$units_requested = $request['units_requested'];
$hospital_name = $request['hospital_name'];
$required_date = $request['required_date'];
$contact_number = $request['contact_number'];
$status = $request['status'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $units_requested = trim($_POST['units_requested'] ?? '');
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $required_date = trim($_POST['required_date'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $newStatus = trim($_POST['status'] ?? '');

    // Validations
    if (empty($patient_name)) $errors[] = "Patient name is required.";
    if (empty($blood_group)) $errors[] = "Required blood group is required.";
    
    if (empty($units_requested)) {
        $errors[] = "Number of units requested is required.";
    } else {
        $units = (int)$units_requested;
        if ($units <= 0 || $units > 20) {
            $errors[] = "Requested blood units must be a positive integer.";
        }
    }

    if (empty($hospital_name)) $errors[] = "Hospital name is required.";
    if (empty($required_date)) $errors[] = "Date required is required.";
    if (empty($contact_number)) $errors[] = "Emergency contact phone is required.";
    if (empty($newStatus)) $errors[] = "Fulfillment status is required.";

    // If validations succeed, execute relational updates
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $prevStatus = $request['status'];
            $prevUnits = (int)$request['units_requested'];
            $newUnits = (int)$units_requested;

            // Operational Inventory Adjustments
            // CASE 1: Request transitions to "Fulfilled" from any other state
            if ($newStatus === 'Fulfilled' && $prevStatus !== 'Fulfilled') {
                // Read current stock
                $checkStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1 FOR UPDATE");
                $checkStmt->execute([':bg' => $blood_group]);
                $currentStock = (int)($checkStmt->fetchColumn() ?? 0);

                if ($currentStock < $newUnits) {
                    throw new Exception("Unable to fulfill order. Insufficient stock of group '" . htmlspecialchars($blood_group) . "' ($currentStock unit(s) available, $newUnits unit(s) required).");
                }

                // Deduct stock
                $deductStmt = $pdo->prepare("UPDATE blood_stock SET units = units - :qty WHERE blood_group = :bg");
                $deductStmt->execute([':qty' => $newUnits, ':bg' => $blood_group]);
            }
            
            // CASE 2: Request transitions AWAY from "Fulfilled" back to other states (e.g. Cancelled or Pending refund)
            elseif ($newStatus !== 'Fulfilled' && $prevStatus === 'Fulfilled') {
                // Refund inventory units
                $refundStmt = $pdo->prepare("UPDATE blood_stock SET units = units + :qty WHERE blood_group = :bg");
                $refundStmt->execute([':qty' => $prevUnits, ':bg' => $blood_group]);
            }

            // CASE 3: Request remains "Fulfilled", but quantities were modified
            elseif ($newStatus === 'Fulfilled' && $prevStatus === 'Fulfilled' && $prevUnits !== $newUnits) {
                // Calculate stock variance
                $variance = $newUnits - $prevUnits; // Positive means we need more units deducted, Negative means refund

                if ($variance > 0) {
                    $checkStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1 FOR UPDATE");
                    $checkStmt->execute([':bg' => $blood_group]);
                    $currentStock = (int)($checkStmt->fetchColumn() ?? 0);

                    if ($currentStock < $variance) {
                        throw new Exception("Unable to update fulfillment quantity. Insufficient additional stock of group '" . htmlspecialchars($blood_group) . "' ($currentStock unit(s) available, $variance additional unit(s) required).");
                    }

                    // Deduct variance
                    $deductStmt = $pdo->prepare("UPDATE blood_stock SET units = units - :qty WHERE blood_group = :bg");
                    $deductStmt->execute([':qty' => $variance, ':bg' => $blood_group]);
                } else {
                    // Refund positive variance difference
                    $refundAmt = abs($variance);
                    $refundStmt = $pdo->prepare("UPDATE blood_stock SET units = units + :qty WHERE blood_group = :bg");
                    $refundStmt->execute([':qty' => $refundAmt, ':bg' => $blood_group]);
                }
            }

            // Save Request modifications
            $updateStmt = $pdo->prepare("
                UPDATE blood_requests 
                SET patient_name = :pname, blood_group = :bg, units_requested = :units, 
                    hospital_name = :hname, required_date = :reqdate, 
                    contact_number = :contact, status = :status
                WHERE id = :id
            ");

            $updateStmt->execute([
                ':pname' => $patient_name,
                ':bg' => $blood_group,
                ':units' => $newUnits,
                ':hname' => $hospital_name,
                ':reqdate' => $required_date,
                ':contact' => $contact_number,
                ':status' => $newStatus,
                ':id' => $id
            ]);

            $pdo->commit();
            $_SESSION['success_message'] = "Emergency request details updated successfully! Stock logs synced.";
            header("Location: requests.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

// Include header layout
require_once 'includes/header.php';
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-xl-9">
        <div class="premium-card">
            
            <!-- Card Header -->
            <div class="premium-card-header">
                <h3 class="premium-card-title text-primary">
                    <i class="fa-solid fa-ellipsis-vertical me-1"></i> Process Request #<?php echo $id; ?> (<?php echo htmlspecialchars($request['patient_name']); ?>)
                </h3>
                <a href="requests.php" class="btn btn-sm btn-premium-secondary border-0 py-1" style="font-size: 13px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Request Logs
                </a>
            </div>

            <!-- Card Body Form -->
            <div class="premium-card-body">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger p-3 mb-4 rounded-3">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i> Process Warning:</h5>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Stock Inventory Dynamic Analyzer Panel -->
                <div class="premium-card bg-dark border-secondary p-3 mb-4 rounded-3">
                    <div class="row align-items-center">
                        <div class="col-sm-8">
                            <h5 class="mb-1 text-light fw-bold" style="font-size: 15px;">
                                <i class="fa-solid fa-boxes-stacked text-warning me-1"></i> Inventory Stock Matching Analyzer
                            </h5>
                            <p class="mb-0 text-muted" style="font-size: 13px;">
                                Blood type required: <strong><?php echo htmlspecialchars($request['blood_group']); ?></strong> | Current stock available: <strong class="<?php echo $availableStock >= $request['units_requested'] ? 'text-success' : 'text-danger'; ?>"><?php echo $availableStock; ?> Units</strong>
                            </p>
                        </div>
                        <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                            <?php if ($availableStock >= $request['units_requested']): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-2 border border-success-subtle rounded-pill font-monospace" style="font-size: 12px;">
                                    <i class="fa-solid fa-circle-check me-1"></i> Stock Sufficient
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger px-3 py-2 border border-danger-subtle rounded-pill font-monospace" style="font-size: 12px;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Stock Deficit
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="request-edit.php?id=<?php echo $id; ?>" autocomplete="off">
                    
                    <!-- Row 1: Patient Name & Contact -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Patient Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-user"></i></span>
                                <input type="text" name="patient_name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($patient_name); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Emergency Contact Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="contact_number" class="form-control form-control-premium" value="<?php echo htmlspecialchars($contact_number); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Blood Type, Units Needed, Date Needed -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Required Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" class="form-control form-control-premium" required>
                                <option value="">-- Select Group --</option>
                                <?php 
                                $bgOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($bgOptions as $grp) {
                                    $selected = ($blood_group === $grp) ? 'selected' : '';
                                    echo "<option value=\"$grp\" $selected>$grp</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Units Needed <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-droplet"></i></span>
                                <input type="number" name="units_requested" class="form-control form-control-premium" min="1" max="20" value="<?php echo htmlspecialchars($units_requested); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Required Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" name="required_date" class="form-control form-control-premium" value="<?php echo htmlspecialchars($required_date); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Hospital Name & Address -->
                    <div class="mb-4">
                        <label class="form-label form-label-premium">Destination Hospital Name / Location <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-hospital"></i></span>
                            <input type="text" name="hospital_name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($hospital_name); ?>" required>
                        </div>
                    </div>

                    <!-- Row 4: Status Workflow selection -->
                    <div class="mb-4">
                        <label class="form-label form-label-premium text-warning fw-bold"><i class="fa-solid fa-heart-pulse"></i> Operational Fulfillment Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control form-control-premium border-warning text-warning" required>
                            <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending (Under Administrative Review)</option>
                            <option value="Approved" <?php echo $status === 'Approved' ? 'selected' : ''; ?>>Approved (Matched and Reserved)</option>
                            <option value="Fulfilled" <?php echo $status === 'Fulfilled' ? 'selected' : ''; ?>>Fulfilled (Dispatched & Deducted from Stock)</option>
                            <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled (Declined/Withdrawn)</option>
                        </select>
                        <span class="text-muted font-monospace" style="font-size: 11px; display: block; margin-top: 6px;">
                            💡 Setting status to <strong>Fulfilled</strong> automatically deducts requested units from active inventory, while switching AWAY from Fulfilled automatically refunds units.
                        </span>
                    </div>

                    <!-- Row 5: Action Buttons -->
                    <div class="d-flex justify-content-end gap-3 mt-5">
                        <a href="requests.php" class="btn btn-premium-secondary px-4 py-2">Cancel</a>
                        <button type="submit" class="btn btn-premium-primary px-4 py-2">
                            <i class="fa-solid fa-circle-check me-1"></i> Update Request Details
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
