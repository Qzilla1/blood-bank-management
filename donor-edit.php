<?php
/**
 * Edit / Update Donor Profile
 * Loads current values into the form and updates them with secure PDO binding.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

// Secure the page
check_login();

// Validate active ID parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error_message'] = "Invalid donor identification code.";
    header("Location: donors.php");
    exit();
}

$errors = [];
$donor = null;

// Fetch active donor details for form pre-filling
try {
    $stmt = $pdo->prepare("SELECT * FROM donors WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        $_SESSION['error_message'] = "Requested donor profile was not found.";
        header("Location: donors.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error retrieving donor data.";
    header("Location: donors.php");
    exit();
}

// Extract variables for form render (or from POST if validation fails)
$name = $donor['name'];
$age = $donor['age'];
$gender = $donor['gender'];
$blood_group = $donor['blood_group'];
$email = $donor['email'];
$phone = $donor['phone'];
$address = $donor['address'];
$medical_history = $donor['medical_history'] ?? '';
$last_donation_date = $donor['last_donation_date'] ?? '';

// Handle Form Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $medical_history = trim($_POST['medical_history'] ?? '');
    $last_donation_date = trim($_POST['last_donation_date'] ?? '');

    // Validations
    if (empty($name)) $errors[] = "Donor name is required.";
    
    if (empty($age)) {
        $errors[] = "Age is required.";
    } else {
        $ageInt = (int)$age;
        if ($ageInt < 17 || $ageInt > 65) {
            $errors[] = "Age must be between 17 and 65 years for standard blood donation.";
        }
    }

    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($blood_group)) $errors[] = "Blood group selection is required.";
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($phone)) $errors[] = "Contact phone number is required.";
    if (empty($address)) $errors[] = "Home/work physical address is required.";

    // If validation checks succeed, proceed to execute DB UPDATE
    if (empty($errors)) {
        try {
            $dbLastDonation = !empty($last_donation_date) ? $last_donation_date : null;
            $dbMedicalHistory = !empty($medical_history) ? $medical_history : null;

            $updateStmt = $pdo->prepare("
                UPDATE donors 
                SET name = :name, age = :age, gender = :gender, blood_group = :bg, 
                    email = :email, phone = :phone, address = :address, 
                    medical_history = :med, last_donation_date = :ldate
                WHERE id = :id
            ");

            $updateStmt->execute([
                ':name' => $name,
                ':age' => (int)$age,
                ':gender' => $gender,
                ':bg' => $blood_group,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':med' => $dbMedicalHistory,
                ':ldate' => $dbLastDonation,
                ':id' => $id
            ]);

            $_SESSION['success_message'] = "Donor profile for '" . htmlspecialchars($name) . "' has been updated successfully!";
            
            header("Location: donors.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile due to database exception: " . $e->getMessage();
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
                    <i class="fa-solid fa-user-pen me-1"></i> Edit Donor Profile: <?php echo htmlspecialchars($donor['name']); ?>
                </h3>
                <a href="donors.php" class="btn btn-sm btn-premium-secondary border-0 py-1" style="font-size: 13px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Directory
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

                <form method="POST" action="donor-edit.php?id=<?php echo $id; ?>" autocomplete="off">
                    
                    <!-- Row 1: Name & Email -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-user"></i></span>
                                <input type="text" name="name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-premium">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control form-control-premium" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Age, Gender, Blood Group, Phone -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <label class="form-label form-label-premium">Age (17 - 65) <span class="text-danger">*</span></label>
                            <input type="number" name="age" class="form-control form-control-premium" min="17" max="65" value="<?php echo htmlspecialchars($age); ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-premium">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control form-control-premium" required>
                                <option value="">-- Select --</option>
                                <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-premium">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" class="form-control form-control-premium" required>
                                <option value="">-- Group --</option>
                                <?php 
                                $bgOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($bgOptions as $grp) {
                                    $selected = ($blood_group === $grp) ? 'selected' : '';
                                    echo "<option value=\"$grp\" $selected>$grp</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-premium">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" name="phone" class="form-control form-control-premium" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Physical Address -->
                    <div class="mb-4">
                        <label class="form-label form-label-premium">Residential Physical Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-location-dot"></i></span>
                            <textarea name="address" class="form-control form-control-premium" rows="2" required><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </div>

                    <!-- Row 4: Medical History (Special Feature) & Last Donation Date -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <label class="form-label form-label-premium">Medical History / Health Notes <span class="badge bg-secondary-subtle text-muted ms-1 font-monospace">Special Feature</span></label>
                            <textarea name="medical_history" class="form-control form-control-premium" rows="2" placeholder="Record allergies, chronic conditions, hemoglobin trends..."><?php echo htmlspecialchars($medical_history); ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-premium">Last Blood Donation Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" name="last_donation_date" class="form-control form-control-premium" value="<?php echo htmlspecialchars($last_donation_date); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Action Buttons -->
                    <div class="d-flex justify-content-end gap-3 mt-5">
                        <a href="donors.php" class="btn btn-premium-secondary px-4 py-2">Cancel</a>
                        <button type="submit" class="btn btn-premium-primary px-4 py-2">
                            <i class="fa-solid fa-circle-check me-1"></i> Update Profile
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
