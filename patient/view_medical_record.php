<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid medical record.";
    header("Location: medical_records.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get medical record details
$query = "SELECT mr.*, 
          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
          d.specialization, d.email as doctor_email,
          dep.name as department_name
          FROM medical_records mr
          JOIN doctors d ON mr.doctor_id = d.id
          JOIN departments dep ON d.department_id = dep.id
          WHERE mr.id = :id AND mr.patient_id = :patient_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id']);
$stmt->bindParam(":patient_id", $_SESSION['user_id']);
$stmt->execute();
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    $_SESSION['error_message'] = "Medical record not found.";
    header("Location: medical_records.php");
    exit();
}
?>

<?php include 'header.php'; ?>

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
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> Medical Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Medical Record Details</h1>
                <a href="medical_records.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Medical Records
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="card-title">Visit Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($record['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Doctor:</th>
                                    <td>
                                        Dr. <?php echo $record['doctor_first_name'] . ' ' . $record['doctor_last_name']; ?>
                                        <br>
                                        <small class="text-muted"><?php echo $record['specialization']; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td><?php echo $record['department_name']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Symptoms</h5>
                            <p class="card-text"><?php echo nl2br($record['symptoms']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Diagnosis</h5>
                            <p class="card-text"><?php echo nl2br($record['diagnosis']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Treatment</h5>
                            <p class="card-text"><?php echo nl2br($record['treatment']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($record['prescription'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Prescription</h5>
                            <p class="card-text"><?php echo nl2br($record['prescription']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record['notes'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Additional Notes</h5>
                            <p class="card-text"><?php echo nl2br($record['notes']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?> 