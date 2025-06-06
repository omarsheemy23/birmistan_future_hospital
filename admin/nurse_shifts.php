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

// Handle shift deletion
if (isset($_GET['delete'])) {
    try {
        $shift_id = $_GET['delete'];
        $query = "DELETE FROM nurse_shifts WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $shift_id);
        $stmt->execute();
        $_SESSION['success_message'] = "Shift deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting shift: " . $e->getMessage();
    }
    header("Location: nurse_shifts.php");
    exit();
}

// Get all shifts with nurse and department information
$query = "SELECT ns.*, 
          n.first_name as nurse_first_name, n.last_name as nurse_last_name,
          d.name as department_name
          FROM nurse_shifts ns
          JOIN nurses n ON ns.nurse_id = n.id
          JOIN departments d ON ns.department_id = d.id
          ORDER BY ns.shift_date DESC, ns.shift_type";
$stmt = $db->prepare($query);
$stmt->execute();
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all nurses for the add shift form
$query = "SELECT n.*, u.email 
          FROM nurses n 
          JOIN users u ON n.user_id = u.id 
          WHERE n.status = 'active'
          ORDER BY n.first_name, n.last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all departments for the add shift form
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Nurse Shifts</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                    <i class="fas fa-plus"></i> Add New Shift
                </button>
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
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Nurse</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><?php echo date('F j, Y', strtotime($shift['shift_date'])); ?></td>
                                        <td><?php echo ucfirst($shift['shift_type']); ?></td>
                                        <td><?php echo $shift['nurse_first_name'] . ' ' . $shift['nurse_last_name']; ?></td>
                                        <td><?php echo $shift['department_name']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $shift['status'] == 'scheduled' ? 'primary' : 
                                                    ($shift['status'] == 'completed' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($shift['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $shift['notes']; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-shift" 
                                                    data-id="<?php echo $shift['id']; ?>"
                                                    data-nurse-id="<?php echo $shift['nurse_id']; ?>"
                                                    data-date="<?php echo $shift['shift_date']; ?>"
                                                    data-type="<?php echo $shift['shift_type']; ?>"
                                                    data-department="<?php echo $shift['department_id']; ?>"
                                                    data-status="<?php echo $shift['status']; ?>"
                                                    data-notes="<?php echo htmlspecialchars($shift['notes']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="nurse_shifts.php?delete=<?php echo $shift['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this shift?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_shift.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nurse_id" class="form-label">Nurse</label>
                        <select class="form-select" id="nurse_id" name="nurse_id" required>
                            <option value="">Select Nurse</option>
                            <?php foreach ($nurses as $nurse): ?>
                                <option value="<?php echo $nurse['id']; ?>">
                                    <?php echo $nurse['first_name'] . ' ' . $nurse['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="shift_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="shift_date" name="shift_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="shift_type" class="form-label">Shift Type</label>
                        <select class="form-select" id="shift_type" name="shift_type" required>
                            <option value="">Select Shift Type</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shift Modal -->
<div class="modal fade" id="editShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_shift.php" method="POST">
                <input type="hidden" id="edit_shift_id" name="shift_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nurse_id" class="form-label">Nurse</label>
                        <select class="form-select" id="edit_nurse_id" name="nurse_id" required>
                            <option value="">Select Nurse</option>
                            <?php foreach ($nurses as $nurse): ?>
                                <option value="<?php echo $nurse['id']; ?>">
                                    <?php echo $nurse['first_name'] . ' ' . $nurse['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_shift_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit_shift_date" name="shift_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_shift_type" class="form-label">Shift Type</label>
                        <select class="form-select" id="edit_shift_type" name="shift_type" required>
                            <option value="">Select Shift Type</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_department_id" class="form-label">Department</label>
                        <select class="form-select" id="edit_department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit shift button click
    document.querySelectorAll('.edit-shift').forEach(button => {
        button.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('editShiftModal'));
            document.getElementById('edit_shift_id').value = this.dataset.id;
            document.getElementById('edit_nurse_id').value = this.dataset.nurseId;
            document.getElementById('edit_shift_date').value = this.dataset.date;
            document.getElementById('edit_shift_type').value = this.dataset.type;
            document.getElementById('edit_department_id').value = this.dataset.department;
            document.getElementById('edit_status').value = this.dataset.status;
            document.getElementById('edit_notes').value = this.dataset.notes;
            modal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 