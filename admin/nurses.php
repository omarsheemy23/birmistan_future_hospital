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

// Handle nurse deletion
if (isset($_GET['delete'])) {
    try {
        $nurse_id = $_GET['delete'];
        
        // Start transaction
        $db->beginTransaction();
        
        // Get user_id for the nurse
        $query = "SELECT user_id FROM nurses WHERE id = :nurse_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nurse_id", $nurse_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Delete the nurse record
            $query = "DELETE FROM nurses WHERE id = :nurse_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nurse_id", $nurse_id);
            $stmt->execute();
            
            // Delete the user account
            $query = "DELETE FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $result['user_id']);
            $stmt->execute();
            
            // Commit transaction
            $db->commit();
            
            $_SESSION['success_message'] = "تم حذف الممرض بنجاح";
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error_message'] = "خطأ في حذف الممرض: " . $e->getMessage();
    }
    
    header("Location: nurses.php");
    exit();
}

// Get all nurses with their department and assigned doctor information
$query = "SELECT n.*, u.email, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
          FROM nurses n 
          LEFT JOIN users u ON n.user_id = u.id 
          LEFT JOIN doctors d ON n.assigned_doctor_id = d.id 
          ORDER BY n.first_name, n.last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get nurse shifts
$query = "SELECT ns.*, n.first_name as nurse_first_name, n.last_name as nurse_last_name,
          d.name as department_name
          FROM nurse_shifts ns
          JOIN nurses n ON ns.nurse_id = n.id
          LEFT JOIN departments d ON ns.department_id = d.id
          ORDER BY ns.shift_date DESC, ns.shift_type";
$stmt = $db->prepare($query);
$stmt->execute();
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for the add nurse form
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get doctors for the add nurse form
$query = "SELECT * FROM doctors WHERE status = 'active' ORDER BY first_name, last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">إدارة الممرضين</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNurseModal">
                    <i class="fas fa-plus"></i> إضافة ممرض جديد
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

            <!-- Nurses List -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">قائمة الممرضين</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNurseModal">
                        <i class="fas fa-plus"></i> إضافة ممرض جديد
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($nurses)): ?>
                        <div class="alert alert-info">
                            لا يوجد ممرضين. الرجاء إضافة ممرض جديد.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($nurses as $nurse): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="card-title mb-0">
                                                    <?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?>
                                                </h5>
                                                <span class="badge bg-<?php echo isset($nurse['status']) && $nurse['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo isset($nurse['status']) && $nurse['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                </span>
                                            </div>
                                            <p class="card-text">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($nurse['email']); ?><br>
                                                <i class="fas fa-hospital"></i> <?php echo htmlspecialchars($nurse['department'] ?: 'غير محدد'); ?><br>
                                                <?php if ($nurse['assigned_doctor_id']): ?>
                                                    <i class="fas fa-user-md"></i> د. <?php echo htmlspecialchars($nurse['doctor_first_name'] . ' ' . $nurse['doctor_last_name']); ?>
                                                <?php else: ?>
                                                    <i class="fas fa-user-md"></i> لا يوجد طبيب مسؤول
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-nurse" 
                                                        data-id="<?php echo $nurse['id']; ?>">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </button>
                                                <a href="?delete=<?php echo $nurse['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الممرض؟')">
                                                    <i class="fas fa-trash"></i> حذف
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

            <!-- Nurse Shifts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">نوبات الممرضين</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                        <i class="fas fa-plus"></i> إضافة نوبة جديدة
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الممرض</th>
                                    <th>نوع النوبة</th>
                                    <th>القسم</th>
                                    <th>الحالة</th>
                                    <th>ملاحظات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($shift['shift_date'])); ?></td>
                                        <td><?php echo $shift['nurse_first_name'] . ' ' . $shift['nurse_last_name']; ?></td>
                                        <td>
                                            <?php
                                            $shift_types = [
                                                'morning' => 'صباحي',
                                                'afternoon' => 'مسائي',
                                                'night' => 'ليلي'
                                            ];
                                            echo $shift_types[$shift['shift_type']] ?? $shift['shift_type'];
                                            ?>
                                        </td>
                                        <td><?php echo $shift['department_name']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $shift['status'] === 'scheduled' ? 'primary' : 
                                                    ($shift['status'] === 'completed' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php
                                                $status_text = [
                                                    'scheduled' => 'مجدول',
                                                    'completed' => 'مكتمل',
                                                    'cancelled' => 'ملغي'
                                                ];
                                                echo $status_text[$shift['status']] ?? $shift['status'];
                                                ?>
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
                                            <a href="?delete_shift=<?php echo $shift['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('هل أنت متأكد من حذف هذه النوبة؟')">
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

<!-- Add Nurse Modal -->
<div class="modal fade" id="addNurseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة ممرض جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_nurse.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">الاسم الأول</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">الاسم الأخير</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">القسم</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['name']; ?>">
                                    <?php echo $department['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_doctor_id" class="form-label">الطبيب المسؤول</label>
                        <select class="form-select" id="assigned_doctor_id" name="assigned_doctor_id">
                            <option value="">اختر الطبيب</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    د. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة نوبة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_shift.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nurse_id" class="form-label">الممرض</label>
                        <select class="form-select" id="nurse_id" name="nurse_id" required>
                            <option value="">اختر الممرض</option>
                            <?php foreach ($nurses as $nurse): ?>
                                <option value="<?php echo $nurse['id']; ?>">
                                    <?php echo $nurse['first_name'] . ' ' . $nurse['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="shift_date" class="form-label">التاريخ</label>
                        <input type="date" class="form-control" id="shift_date" name="shift_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="shift_type" class="form-label">نوع النوبة</label>
                        <select class="form-select" id="shift_type" name="shift_type" required>
                            <option value="">اختر نوع النوبة</option>
                            <option value="morning">صباحي</option>
                            <option value="afternoon">مسائي</option>
                            <option value="night">ليلي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">القسم</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
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
                <h5 class="modal-title">تعديل النوبة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_shift.php" method="POST">
                <input type="hidden" name="shift_id" id="edit_shift_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nurse_id" class="form-label">الممرض</label>
                        <select class="form-select" id="edit_nurse_id" name="nurse_id" required>
                            <option value="">اختر الممرض</option>
                            <?php foreach ($nurses as $nurse): ?>
                                <option value="<?php echo $nurse['id']; ?>">
                                    <?php echo $nurse['first_name'] . ' ' . $nurse['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_shift_date" class="form-label">التاريخ</label>
                        <input type="date" class="form-control" id="edit_shift_date" name="shift_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_shift_type" class="form-label">نوع النوبة</label>
                        <select class="form-select" id="edit_shift_type" name="shift_type" required>
                            <option value="">اختر نوع النوبة</option>
                            <option value="morning">صباحي</option>
                            <option value="afternoon">مسائي</option>
                            <option value="night">ليلي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_department_id" class="form-label">القسم</label>
                        <select class="form-select" id="edit_department_id" name="department_id" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">الحالة</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="scheduled">مجدول</option>
                            <option value="completed">مكتمل</option>
                            <option value="cancelled">ملغي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit nurse button click
    document.querySelectorAll('.edit-nurse').forEach(button => {
        button.addEventListener('click', function() {
            const nurseId = this.dataset.id;
            fetch(`get_nurse.php?id=${nurseId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_nurse_id').value = data.id;
                    document.getElementById('edit_first_name').value = data.first_name;
                    document.getElementById('edit_last_name').value = data.last_name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_department').value = data.department;
                    document.getElementById('edit_assigned_doctor_id').value = data.assigned_doctor_id;
                    
                    new bootstrap.Modal(document.getElementById('editNurseModal')).show();
                })
                .catch(error => console.error('Error:', error));
        });
    });

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