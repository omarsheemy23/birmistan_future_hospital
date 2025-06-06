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

// Handle video call deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $query = "DELETE FROM video_calls WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $_GET['delete']);
        $stmt->execute();
        $_SESSION['success_message'] = "Video call deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to delete video call.";
    }
    header("Location: video_calls.php");
    exit();
}

// Get all video calls with related information
$query = "SELECT vc.*, 
          a.appointment_date, a.appointment_time,
          d.first_name as doctor_first_name, d.last_name as doctor_last_name,
          p.first_name as patient_first_name, p.last_name as patient_last_name,
          p.email as patient_email
          FROM video_calls vc
          JOIN appointments a ON vc.appointment_id = a.id
          JOIN doctors d ON a.doctor_id = d.id
          JOIN patients p ON a.patient_id = p.id
          ORDER BY vc.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$video_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Video Calls Management</h1>
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
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Patient</th>
                                    <th>Date & Time</th>
                                    <th>Room ID</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($video_calls as $call): ?>
                                    <tr>
                                        <td><?php echo $call['id']; ?></td>
                                        <td>
                                            Dr. <?php echo $call['doctor_first_name'] . ' ' . $call['doctor_last_name']; ?>
                                        </td>
                                        <td>
                                            <?php echo $call['patient_first_name'] . ' ' . $call['patient_last_name']; ?>
                                            <br>
                                            <small class="text-muted"><?php echo $call['patient_email']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('F j, Y', strtotime($call['appointment_date'])); ?>
                                            <br>
                                            <?php echo date('h:i A', strtotime($call['appointment_time'])); ?>
                                        </td>
                                        <td><?php echo $call['room_id']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $call['status'] === 'completed' ? 'success' : 
                                                    ($call['status'] === 'in_progress' ? 'primary' : 
                                                    ($call['status'] === 'cancelled' ? 'danger' : 'warning')); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $call['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($call['start_time'] && $call['end_time']) {
                                                $start = new DateTime($call['start_time']);
                                                $end = new DateTime($call['end_time']);
                                                $duration = $start->diff($end);
                                                echo $duration->format('%H:%I:%S');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../doctor/video_call.php?appointment_id=<?php echo $call['appointment_id']; ?>" 
                                                   class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-video"></i>
                                                </a>
                                                <a href="../patient/video_call.php?appointment_id=<?php echo $call['appointment_id']; ?>" 
                                                   class="btn btn-sm btn-info" target="_blank">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <a href="?delete=<?php echo $call['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this video call?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 