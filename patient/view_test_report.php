<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Check if report ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid test report.";
    header("Location: medical_records.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get test report details
$query = "SELECT tr.*, 
          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
          d.specialization, d.email as doctor_email,
          dep.name as department_name
          FROM test_reports tr
          JOIN doctors d ON tr.doctor_id = d.id
          JOIN departments dep ON d.department_id = dep.id
          WHERE tr.id = :id AND tr.patient_id = :patient_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id']);
$stmt->bindParam(":patient_id", $_SESSION['user_id']);
$stmt->execute();
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    $_SESSION['error_message'] = "Test report not found.";
    header("Location: medical_records.php");
    exit();
}

// Check if report is completed
if ($report['status'] !== 'completed') {
    $_SESSION['error_message'] = "Test report is not yet available.";
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
                <h1 class="h2">Test Report Details</h1>
                <a href="medical_records.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Medical Records
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="card-title">Test Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($report['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Doctor:</th>
                                    <td>
                                        Dr. <?php echo $report['doctor_first_name'] . ' ' . $report['doctor_last_name']; ?>
                                        <br>
                                        <small class="text-muted"><?php echo $report['specialization']; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td><?php echo $report['department_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Test Name:</th>
                                    <td><?php echo $report['test_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-success">Completed</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Test Results</h5>
                            <p class="card-text"><?php echo nl2br($report['results']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($report['interpretation'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Interpretation</h5>
                            <p class="card-text"><?php echo nl2br($report['interpretation']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($report['recommendations'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="card-title">Recommendations</h5>
                            <p class="card-text"><?php echo nl2br($report['recommendations']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($report['attachments'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Attachments</h5>
                            <div class="list-group">
                                <?php 
                                $attachments = json_decode($report['attachments'], true);
                                foreach ($attachments as $attachment): 
                                ?>
                                    <a href="<?php echo $attachment['url']; ?>" 
                                       class="list-group-item list-group-item-action"
                                       target="_blank">
                                        <i class="fas fa-file-pdf"></i> <?php echo $attachment['name']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?> 