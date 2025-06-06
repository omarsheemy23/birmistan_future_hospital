<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();

        // Update hospital settings
        $query = "UPDATE settings SET 
                  hospital_name = :hospital_name,
                  hospital_address = :hospital_address,
                  hospital_phone = :hospital_phone,
                  hospital_email = :hospital_email,
                  consultation_fee = :consultation_fee,
                  appointment_duration = :appointment_duration,
                  working_hours_start = :working_hours_start,
                  working_hours_end = :working_hours_end
                  WHERE id = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":hospital_name", $_POST['hospital_name']);
        $stmt->bindParam(":hospital_address", $_POST['hospital_address']);
        $stmt->bindParam(":hospital_phone", $_POST['hospital_phone']);
        $stmt->bindParam(":hospital_email", $_POST['hospital_email']);
        $stmt->bindParam(":consultation_fee", $_POST['consultation_fee']);
        $stmt->bindParam(":appointment_duration", $_POST['appointment_duration']);
        $stmt->bindParam(":working_hours_start", $_POST['working_hours_start']);
        $stmt->bindParam(":working_hours_end", $_POST['working_hours_end']);
        
        $stmt->execute();

        // Commit transaction
        $db->commit();

        $_SESSION['success_message'] = "Settings updated successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = "Error updating settings: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// Get current settings
$query = "SELECT * FROM settings WHERE id = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist, create default settings
if (!$settings) {
    $query = "INSERT INTO settings (hospital_name, hospital_address, hospital_phone, hospital_email, 
              consultation_fee, appointment_duration, working_hours_start, working_hours_end) 
              VALUES ('Birmistan Future Hospital', '123 Hospital Street', '1234567890', 
              'info@hospital.com', 100.00, 30, '09:00:00', '17:00:00')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Get the newly created settings
    $query = "SELECT * FROM settings WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Hospital Settings</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="settings.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hospital_name" class="form-label">Hospital Name</label>
                                <input type="text" class="form-control" id="hospital_name" name="hospital_name" 
                                       value="<?php echo htmlspecialchars($settings['hospital_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hospital_phone" class="form-label">Hospital Phone</label>
                                <input type="tel" class="form-control" id="hospital_phone" name="hospital_phone" 
                                       value="<?php echo htmlspecialchars($settings['hospital_phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hospital_address" class="form-label">Hospital Address</label>
                            <textarea class="form-control" id="hospital_address" name="hospital_address" 
                                      rows="2" required><?php echo htmlspecialchars($settings['hospital_address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hospital_email" class="form-label">Hospital Email</label>
                            <input type="email" class="form-control" id="hospital_email" name="hospital_email" 
                                   value="<?php echo htmlspecialchars($settings['hospital_email']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="consultation_fee" class="form-label">Default Consultation Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" 
                                           value="<?php echo htmlspecialchars($settings['consultation_fee']); ?>" 
                                           step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointment_duration" class="form-label">Default Appointment Duration (minutes)</label>
                                <input type="number" class="form-control" id="appointment_duration" name="appointment_duration" 
                                       value="<?php echo htmlspecialchars($settings['appointment_duration']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="working_hours_start" class="form-label">Working Hours Start</label>
                                <input type="time" class="form-control" id="working_hours_start" name="working_hours_start" 
                                       value="<?php echo htmlspecialchars($settings['working_hours_start']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="working_hours_end" class="form-label">Working Hours End</label>
                                <input type="time" class="form-control" id="working_hours_end" name="working_hours_end" 
                                       value="<?php echo htmlspecialchars($settings['working_hours_end']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 