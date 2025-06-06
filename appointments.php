<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all doctors
$query = "SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.id";
$stmt = $db->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    // Get patient_id from patients table
    $query = "SELECT id FROM patients WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        $_SESSION['error_message'] = "Patient record not found. Please contact support.";
        header("Location: appointments.php");
        exit();
    }
    
    $patient_id = $patient['id'];
    
    // Check if the time slot is available
    $query = "SELECT id FROM appointments WHERE doctor_id = :doctor_id AND appointment_date = :appointment_date AND appointment_time = :appointment_time";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":doctor_id", $doctor_id);
    $stmt->bindParam(":appointment_date", $appointment_date);
    $stmt->bindParam(":appointment_time", $appointment_time);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Get doctor's consultation fee
        $query = "SELECT consultation_fee FROM doctors WHERE id = :doctor_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->execute();
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create appointment
        $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) 
                 VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":appointment_date", $appointment_date);
        $stmt->bindParam(":appointment_time", $appointment_time);
        
        if ($stmt->execute()) {
            $appointment_id = $db->lastInsertId();
            
            // Create payment record
            $query = "INSERT INTO payments (appointment_id, amount, payment_status) 
                     VALUES (:appointment_id, :amount, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":appointment_id", $appointment_id);
            $stmt->bindParam(":amount", $doctor['consultation_fee']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Appointment booked successfully! Please complete the payment.";
                header("Location: payment.php?appointment_id=" . $appointment_id);
                exit();
            }
        }
    } else {
        $error = "This time slot is already booked. Please select another time.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5">
                <div class="card-body">
                    <h2 class="text-center mb-4">Book an Appointment</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="appointmentForm">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">Choose a doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>" data-fee="<?php echo $doctor['consultation_fee']; ?>">
                                        Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?> 
                                        (<?php echo $doctor['specialization']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Select Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                <option value="">Choose a time</option>
                                <?php
                                $start_time = strtotime('09:00');
                                $end_time = strtotime('17:00');
                                $interval = 30 * 60; // 30 minutes
                                
                                for ($time = $start_time; $time <= $end_time; $time += $interval) {
                                    echo '<option value="' . date('H:i:s', $time) . '">' . date('h:i A', $time) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Consultation Fee</label>
                            <p id="consultation_fee" class="form-control-static">Please select a doctor to see the consultation fee.</p>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Book Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('doctor_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const fee = selectedOption.dataset.fee;
    document.getElementById('consultation_fee').textContent = fee ? `$${fee}` : 'Please select a doctor to see the consultation fee.';
});
</script>

<?php include 'includes/footer.php'; ?> 