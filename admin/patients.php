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

// Handle patient deletion
if (isset($_GET['delete'])) {
    try {
        $patient_id = $_GET['delete'];
        
        // Start transaction
        $db->beginTransaction();
        
        // First, delete associated appointments
        $query = "DELETE FROM appointments WHERE patient_id = :patient_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id);
        $stmt->execute();
        
        // Then, delete associated medical reports
        $query = "DELETE FROM medical_reports WHERE patient_id = :patient_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id);
        $stmt->execute();
        
        // Finally, delete the patient
        $query = "DELETE FROM patients WHERE id = :patient_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success_message'] = "Patient deleted successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = "Error deleting patient: " . $e->getMessage();
    }
    
    header("Location: patients.php");
    exit();
}

// Get all patients
$query = "SELECT p.*, u.email 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.first_name, p.last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Patients</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                    <i class="fas fa-plus"></i> Add New Patient
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

            <!-- Patients List -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($patients)): ?>
                        <div class="alert alert-info">
                            No patients found. Please add a new patient.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($patients as $patient): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="card-title mb-0">
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                </h5>
                                                <span class="badge bg-<?php echo isset($patient['status']) && $patient['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo isset($patient['status']) && $patient['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                </span>
                                            </div>
                                            <p class="card-text">
                                                <i class="fas fa-envelope"></i> <?php echo isset($patient['email']) && $patient['email'] !== null ? htmlspecialchars($patient['email']) : ''; ?><br>
                                                <i class="fas fa-phone"></i> <?php echo isset($patient['phone']) && $patient['phone'] !== null ? htmlspecialchars($patient['phone']) : ''; ?><br>
                                                <i class="fas fa-calendar"></i> <?php echo isset($patient['date_of_birth']) && $patient['date_of_birth'] !== null ? htmlspecialchars($patient['date_of_birth']) : ''; ?><br>
                                                <i class="fas fa-venus-mars"></i> <?php echo isset($patient['gender']) && $patient['gender'] !== null ? ($patient['gender'] === 'male' ? 'ذكر' : 'أنثى') : ''; ?><br>
                                                <i class="fas fa-map-marker-alt"></i> <?php echo isset($patient['address']) && $patient['address'] !== null ? htmlspecialchars($patient['address']) : ''; ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-patient" 
                                                        data-id="<?php echo $patient['id']; ?>">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </button>
                                                <a href="?delete=<?php echo $patient['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا المريض؟')">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                                <a href="medical_history.php?patient_id=<?php echo $patient['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-history"></i> السجل الطبي
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_patient.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_patient.php" method="POST">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_gender" class="form-label">Gender</label>
                        <select class="form-select" id="edit_gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit patient button click
    document.querySelectorAll('.edit-patient').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.dataset.id;
            fetch(`get_patient.php?id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_patient_id').value = data.id;
                    document.getElementById('edit_first_name').value = data.first_name;
                    document.getElementById('edit_last_name').value = data.last_name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_phone').value = data.phone;
                    document.getElementById('edit_date_of_birth').value = data.date_of_birth;
                    document.getElementById('edit_gender').value = data.gender;
                    document.getElementById('edit_address').value = data.address;
                    
                    new bootstrap.Modal(document.getElementById('editPatientModal')).show();
                })
                .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 