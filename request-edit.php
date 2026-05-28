<?php
/**
 * Edit / Process Blood Request
 * Handles request updates, stock availability checks, and automatic inventory unit adjustments.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

check_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error_message'] = "Invalid blood request identification code.";
    header("Location: requests.php");
    exit();
}

$errors = [];
$request = null;
$availableStock = 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

    if (!$request) {
        $_SESSION['error_message'] = "Request not found.";
        header("Location: requests.php");
        exit();
    }

    $stockStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1");
    $stockStmt->execute([':bg' => $request['blood_group']]);
    $availableStock = (int)($stockStmt->fetchColumn() ?? 0);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error retrieving request details.";
    header("Location: requests.php");
    exit();
}

$patient_name = $request['patient_name'];
$blood_group  = $request['blood_group'];
$units_needed = $request['units_needed'];
$hospital     = $request['hospital'];
$requested_on = $request['requested_on'];
$contact      = $request['contact'];
$status       = $request['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $blood_group  = trim($_POST['blood_group']  ?? '');
    $units_needed = trim($_POST['units_needed'] ?? '');
    $hospital     = trim($_POST['hospital']     ?? '');
    $requested_on = trim($_POST['requested_on'] ?? '');
    $contact      = trim($_POST['contact']      ?? '');
    $newStatus    = trim($_POST['status']       ?? '');

    if (empty($patient_name)) $errors[] = "Patient name is required.";
    if (empty($blood_group))  $errors[] = "Blood group is required.";
    if (empty($hospital))     $errors[] = "Hospital name is required.";
    if (empty($requested_on)) $errors[] = "Date is required.";
    if (empty($contact))      $errors[] = "Contact is required.";
    if (empty($newStatus))    $errors[] = "Status is required.";

    if (empty($units_needed)) {
        $errors[] = "Units needed is required.";
    } else {
        $units = (int)$units_needed;
        if ($units <= 0 || $units > 20) $errors[] = "Units must be between 1 and 20.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $prevStatus = $request['status'];
            $prevUnits  = (int)$request['units_needed'];
            $newUnits   = (int)$units_needed;

            if ($newStatus === 'Fulfilled' && $prevStatus !== 'Fulfilled') {
                $checkStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1 FOR UPDATE");
                $checkStmt->execute([':bg' => $blood_group]);
                $currentStock = (int)($checkStmt->fetchColumn() ?? 0);
                if ($currentStock < $newUnits) {
                    throw new Exception("Insufficient stock of '$blood_group' ($currentStock available, $newUnits required).");
                }
                $pdo->prepare("UPDATE blood_stock SET units = units - :qty WHERE blood_group = :bg")
                    ->execute([':qty' => $newUnits, ':bg' => $blood_group]);

            } elseif ($newStatus !== 'Fulfilled' && $prevStatus === 'Fulfilled') {
                $pdo->prepare("UPDATE blood_stock SET units = units + :qty WHERE blood_group = :bg")
                    ->execute([':qty' => $prevUnits, ':bg' => $blood_group]);

            } elseif ($newStatus === 'Fulfilled' && $prevStatus === 'Fulfilled' && $prevUnits !== $newUnits) {
                $variance = $newUnits - $prevUnits;
                if ($variance > 0) {
                    $checkStmt = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg LIMIT 1 FOR UPDATE");
                    $checkStmt->execute([':bg' => $blood_group]);
                    $currentStock = (int)($checkStmt->fetchColumn() ?? 0);
                    if ($currentStock < $variance) {
                        throw new Exception("Insufficient additional stock ($currentStock available, $variance more required).");
                    }
                    $pdo->prepare("UPDATE blood_stock SET units = units - :qty WHERE blood_group = :bg")
                        ->execute([':qty' => $variance, ':bg' => $blood_group]);
                } else {
                    $pdo->prepare("UPDATE blood_stock SET units = units + :qty WHERE blood_group = :bg")
                        ->execute([':qty' => abs($variance), ':bg' => $blood_group]);
                }
            }

            $pdo->prepare("
                UPDATE requests
                SET patient_name = :pname,
                    blood_group  = :bg,
                    units_needed = :units,
                    hospital     = :hospital,
                    requested_on = :requested_on,
                    contact      = :contact,
                    status       = :status
                WHERE id = :id
            ")->execute([
                ':pname'        => $patient_name,
                ':bg'           => $blood_group,
                ':units'        => $newUnits,
                ':hospital'     => $hospital,
                ':requested_on' => $requested_on,
                ':contact'      => $contact,
                ':status'       => $newStatus,
                ':id'           => $id,
            ]);

            $pdo->commit();
            $_SESSION['success_message'] = "Request updated successfully!";
            header("Location: requests.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-xl-9">
        <div class="premium-card">

            <div class="premium-card-header">
                <h3 class="premium-card-title text-primary">
                    <i class="fa-solid fa-ellipsis-vertical me-1"></i>
                    Process Request #<?php echo $id; ?> (<?php echo htmlspecialchars($request['patient_name']); ?>)
                </h3>
                <a href="requests.php" class="btn btn-sm btn-premium-secondary border-0 py-1" style="font-size:13px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Request Logs
                </a>
            </div>

            <div class="premium-card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger p-3 mb-4 rounded-3">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i> Warning:</h5>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Stock Analyzer -->
                <div class="premium-card bg-dark border-secondary p-3 mb-4 rounded-3">
                    <div class="row align-items-center">
                        <div class="col-sm-8">
                            <h5 class="mb-1 text-light fw-bold" style="font-size:15px;">
                                <i class="fa-solid fa-boxes-stacked text-warning me-1"></i> Stock Analyzer
                            </h5>
                            <p class="mb-0 text-muted" style="font-size:13px;">
                                Blood type: <strong><?php echo htmlspecialchars($request['blood_group']); ?></strong> |
                                Stock available: <strong class="<?php echo $availableStock >= $request['units_needed'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $availableStock; ?> Units
                                </strong>
                            </p>
                        </div>
                        <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                            <?php if ($availableStock >= $request['units_needed']): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-2 border border-success-subtle rounded-pill" style="font-size:12px;">
                                    <i class="fa-solid fa-circle-check me-1"></i> Sufficient
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger px-3 py-2 border border-danger-subtle rounded-pill" style="font-size:12px;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Deficit
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="request-edit.php?id=<?php echo $id; ?>" autocomplete="off">

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Patient Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-user"></i></span>
                                <input type="text" name="patient_name" class="form-control form-control-premium"
                                       value="<?php echo htmlspecialchars($patient_name); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Contact <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="contact" class="form-control form-control-premium"
                                       value="<?php echo htmlspecialchars($contact); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" class="form-control form-control-premium" required>
                                <option value="">-- Select --</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $grp): ?>
                                    <option value="<?php echo $grp; ?>" <?php echo $blood_group === $grp ? 'selected' : ''; ?>>
                                        <?php echo $grp; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Units Needed <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-droplet"></i></span>
                                <input type="number" name="units_needed" class="form-control form-control-premium"
                                       min="1" max="20" value="<?php echo htmlspecialchars($units_needed); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Date Requested <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" name="requested_on" class="form-control form-control-premium"
                                       value="<?php echo htmlspecialchars($requested_on); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-premium">Hospital Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-hospital"></i></span>
                            <input type="text" name="hospital" class="form-control form-control-premium"
                                   value="<?php echo htmlspecialchars($hospital); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-premium text-warning fw-bold">
                            <i class="fa-solid fa-heart-pulse"></i> Fulfillment Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-control form-control-premium border-warning text-warning" required>
                            <option value="Pending"   <?php echo $status === 'Pending'   ? 'selected' : ''; ?>>Pending (Under Review)</option>
                            <option value="Approved"  <?php echo $status === 'Approved'  ? 'selected' : ''; ?>>Approved (Matched & Reserved)</option>
                            <option value="Fulfilled" <?php echo $status === 'Fulfilled' ? 'selected' : ''; ?>>Fulfilled (Dispatched & Stock Deducted)</option>
                            <option value="Rejected"  <?php echo $status === 'Rejected'  ? 'selected' : ''; ?>>Rejected (Declined/Withdrawn)</option>
                        </select>
                        <span class="text-muted font-monospace" style="font-size:11px;display:block;margin-top:6px;">
                            💡 Setting to <strong>Fulfilled</strong> auto-deducts units from stock. Switching away refunds them.
                        </span>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-5">
                        <a href="requests.php" class="btn btn-premium-secondary px-4 py-2">Cancel</a>
                        <button type="submit" class="btn btn-premium-primary px-4 py-2">
                            <i class="fa-solid fa-circle-check me-1"></i> Update Request
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>