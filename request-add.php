<?php
/**
 * Create Blood Request
 * Form handling and insertion for emergency requests.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Secure the page
check_login();

$errors = [];
$patient_name = $blood_group = $units_requested = $hospital_name = $required_date = $contact_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input values
    $patient_name = trim($_POST['patient_name'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $units_requested = trim($_POST['units_requested'] ?? '');
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $required_date = trim($_POST['required_date'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    // Server side validations
    if (empty($patient_name)) $errors[] = "Patient name is required.";
    if (empty($blood_group)) $errors[] = "Required blood group is required.";
    
    if (empty($units_requested)) {
        $errors[] = "Number of units requested is required.";
    } else {
        $units = (int)$units_requested;
        if ($units <= 0 || $units > 20) {
            $errors[] = "Requested blood units must be a positive integer (max 20 units per request).";
        }
    }

    if (empty($hospital_name)) $errors[] = "Hospital or delivery location name is required.";
    if (empty($required_date)) $errors[] = "Date required is required.";
    if (empty($contact_number)) $errors[] = "Emergency contact phone number is required.";

    // If no validation errors, proceed with PDO insertion
    if (empty($errors)) {
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO blood_requests (patient_name, blood_group, units_requested, hospital_name, required_date, contact_number, status)
                VALUES (:pname, :bg, :units, :hname, :reqdate, :contact, 'Pending')
            ");

            $insertStmt->execute([
                ':pname' => $patient_name,
                ':bg' => $blood_group,
                ':units' => (int)$units_requested,
                ':hname' => $hospital_name,
                ':reqdate' => $required_date,
                ':contact' => $contact_number
            ]);

            $_SESSION['success_message'] = "Emergency blood request for '" . htmlspecialchars($patient_name) . "' has been filed successfully.";
            
            header("Location: requests.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to submit request due to a database exception: " . $e->getMessage();
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-xl-9">
        <div class="premium-card">
            
            <!-- Card Header -->
            <div class="premium-card-header">
                <h3 class="premium-card-title text-success">
                    <i class="fa-solid fa-square-plus me-1"></i> Add Emergency Blood Request
                </h3>
                <a href="requests.php" class="btn btn-sm btn-premium-secondary border-0 py-1" style="font-size: 13px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Request Logs
                </a>
            </div>

            <!-- Card Body Form -->
            <div class="premium-card-body">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-danger-subtle bg-danger-subtle text-danger p-3 mb-4 rounded-3">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i> Please resolve the following errors:</h5>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="request-add.php" autocomplete="off">
                    
                    <!-- Row 1: Patient Name & Contact -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Patient Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-user"></i></span>
                                <input type="text" name="patient_name" class="form-control form-control-premium" placeholder="e.g. Muhammad Rizwan" value="<?php echo htmlspecialchars($patient_name); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Emergency Contact Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="contact_number" class="form-control form-control-premium" placeholder="e.g. +92 300 7654321" value="<?php echo htmlspecialchars($contact_number); ?>" required>
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
                                <input type="number" name="units_requested" class="form-control form-control-premium" placeholder="Quantity" min="1" max="20" value="<?php echo htmlspecialchars($units_requested); ?>" required>
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
                            <input type="text" name="hospital_name" class="form-control form-control-premium" placeholder="e.g. Shifa International Hospital, Islamabad" value="<?php echo htmlspecialchars($hospital_name); ?>" required>
                        </div>
                    </div>

                    <!-- Note Alert: Initial State is Pending -->
                    <div class="alert alert-dark border-secondary bg-transparent p-3 mb-4 rounded-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                        <i class="fa-solid fa-circle-info text-warning"></i>
                        <span class="text-muted">New emergency blood requests are automatically filed in <strong>Pending</strong> status, ready for administrative validation and stock matching.</span>
                    </div>

                    <!-- Row 4: Action Buttons -->
                    <div class="d-flex justify-content-end gap-3 mt-5">
                        <a href="requests.php" class="btn btn-premium-secondary px-4 py-2">Cancel</a>
                        <button type="submit" class="btn btn-premium-primary px-4 py-2" style="background: linear-gradient(135deg, var(--accent-emerald), #25a18e); box-shadow: 0 4px 12px rgba(46, 196, 182, 0.25);">
                            <i class="fa-solid fa-share-from-square me-1"></i> File Emergency Request
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
