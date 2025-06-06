<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Appointment ID is required.";
    header("Location: appointments.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get appointment details with patient and doctor information
$query = "SELECT a.*, 
          p.first_name as patient_first_name, p.last_name as patient_last_name,
          u.email as patient_email, p.phone as patient_phone,
          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
          d.email as doctor_email, d.phone as doctor_phone,
          dept.name as department_name
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN users u ON p.user_id = u.id
          JOIN doctors d ON a.doctor_id = d.id
          LEFT JOIN departments dept ON d.department_id = dept.id
          WHERE a.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id']);
$stmt->execute();
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $_SESSION['error_message'] = "Appointment not found.";
    header("Location: appointments.php");
    exit();
}

// Get payment information if exists
$query = "SELECT * FROM payments WHERE appointment_id = :appointment_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":appointment_id", $_GET['id']);
$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">
                            <i class="fas fa-user-md"></i> Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="fas fa-users"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointments.php">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="departments.php">
                            <i class="fas fa-hospital"></i> Departments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Appointment Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="appointments.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Appointments
                    </a>
                    <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Appointment
                    </a>
                </div>
            </div>

            <!-- Appointment Information -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Appointment Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Date & Time:</th>
                                    <td>
                                        <?php 
                                        echo date('F j, Y', strtotime($appointment['appointment_date'])) . ' ' . 
                                             date('h:i A', strtotime($appointment['appointment_time'])); 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $appointment['status'] === 'completed' ? 'success' : 
                                                ($appointment['status'] === 'cancelled' ? 'danger' : 
                                                ($appointment['status'] === 'confirmed' ? 'primary' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td><?php echo htmlspecialchars($appointment['department_name'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Name:</th>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($appointment['patient_phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Doctor Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Name:</th>
                                    <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($appointment['doctor_email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($appointment['doctor_phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($payment): ?>
                                <table class="table">
                                    <tr>
                                        <th>Amount:</th>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge bg-<?php echo $payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Payment Date:</th>
                                        <td><?php echo date('F j, Y', strtotime($payment['created_at'])); ?></td>
                                    </tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">No payment information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 