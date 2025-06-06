<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if appointment_id is provided
if (!isset($_GET['appointment_id'])) {
    header("Location: appointments.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get appointment and payment details
$query = "SELECT a.*, p.amount, p.payment_status, p.payment_method, p.transaction_id,
          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
          d.specialization
          FROM appointments a
          JOIN payments p ON a.id = p.appointment_id
          JOIN doctors d ON a.doctor_id = d.id
          WHERE a.id = :appointment_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":appointment_id", $_GET['appointment_id']);
$stmt->execute();
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $_SESSION['error_message'] = "Appointment not found.";
    header("Location: appointments.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="mb-4">Payment Successful!</h2>
                    
                    <div class="alert alert-success">
                        Your payment has been processed successfully. Your appointment is now confirmed.
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Appointment Details</h5>
                            <p><strong>Doctor:</strong> Dr. <?php echo $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']; ?></p>
                            <p><strong>Specialization:</strong> <?php echo $appointment['specialization']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Details</h5>
                            <p><strong>Amount:</strong> $<?php echo number_format($appointment['amount'], 2); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $appointment['payment_method'])); ?></p>
                            <p><strong>Transaction ID:</strong> <?php echo $appointment['transaction_id']; ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="appointments.php" class="btn btn-primary me-2">View Appointments</a>
                        <a href="patient/dashboard.php" class="btn btn-outline-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 