<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل دخول الصيدلي
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit();
}

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

$success_message = '';
$error_message = '';

// إضافة دواء جديد
if (isset($_POST['add_medicine'])) {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $dosage_form = $_POST['dosage_form'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
    $manufacturer = $_POST['manufacturer'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name) || empty($type) || $quantity <= 0 || $price <= 0) {
        $error_message = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO medicines (name, type, dosage_form, quantity_in_stock, price, expiry_date, manufacturer, description, created_at, updated_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$name, $type, $dosage_form, $quantity, $price, $expiry_date, $manufacturer, $description]);
            
            $success_message = "تمت إضافة الدواء بنجاح";
        } catch (PDOException $e) {
            $error_message = "حدث خطأ: " . $e->getMessage();
        }
    }
}

// تحديث كمية الدواء
if (isset($_POST['update_quantity'])) {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $new_quantity = intval($_POST['new_quantity'] ?? 0);
    
    if ($medicine_id <= 0 || $new_quantity < 0) {
        $error_message = "يرجى إدخال قيم صحيحة";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE medicines SET quantity_in_stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_quantity, $medicine_id]);
            
            $success_message = "تم تحديث الكمية بنجاح";
        } catch (PDOException $e) {
            $error_message = "حدث خطأ: " . $e->getMessage();
        }
    }
}

// حذف دواء
if (isset($_POST['delete_medicine'])) {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    
    if ($medicine_id <= 0) {
        $error_message = "معرف الدواء غير صالح";
    } else {
        try {
            // التحقق من وجود روشتات تستخدم هذا الدواء
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescription_medicines WHERE medicine_id = ?");
            $check_stmt->execute([$medicine_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
                $error_message = "لا يمكن حذف هذا الدواء لأنه مستخدم في روشتات طبية";
    } else {
                $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
                $stmt->execute([$medicine_id]);
                
                $success_message = "تم حذف الدواء بنجاح";
            }
        } catch (PDOException $e) {
            $error_message = "حدث خطأ: " . $e->getMessage();
        }
    }
}

// البحث عن الأدوية
$search_query = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_stock = $_GET['filter_stock'] ?? '';

$params = [];
$where_conditions = [];

if (!empty($search_query)) {
    $where_conditions[] = "(name LIKE ? OR manufacturer LIKE ? OR description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if (!empty($filter_type)) {
    $where_conditions[] = "type = ?";
    $params[] = $filter_type;
}

if ($filter_stock === 'low') {
    $where_conditions[] = "quantity_in_stock < 20";
} elseif ($filter_stock === 'out') {
    $where_conditions[] = "quantity_in_stock = 0";
} elseif ($filter_stock === 'expiring') {
    $where_conditions[] = "expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// جلب أنواع الأدوية المتاحة
$type_stmt = $conn->prepare("SELECT DISTINCT type FROM medicines ORDER BY type");
$type_stmt->execute();
$medicine_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);

// جلب قائمة الأدوية
$query = "SELECT * FROM medicines $where_clause ORDER BY name";
$stmt = $conn->prepare($query);
foreach ($params as $key => $param) {
    $stmt->bindValue($key + 1, $param);
}
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تضمين ملف الهيدر
include_once 'header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-boxes me-2"></i>إدارة المخزون الدوائي</h2>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

    <!-- إحصائيات المخزون -->
        <div class="row mb-4">
        <?php
        // الإحصائيات
        $stats_query = "SELECT 
                        COUNT(*) as total_medicines,
                        COUNT(CASE WHEN quantity_in_stock < 20 THEN 1 END) as low_stock,
                        COUNT(CASE WHEN quantity_in_stock = 0 THEN 1 END) as out_of_stock,
                        COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_soon
                       FROM medicines";
        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-pills fa-2x mb-2 text-primary"></i>
                    <h5 class="card-title"><?php echo $stats['total_medicines']; ?></h5>
                    <p class="card-text">إجمالي الأدوية</p>
                </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
                    <h5 class="card-title"><?php echo $stats['low_stock']; ?></h5>
                    <p class="card-text">منخفضة المخزون</p>
                </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-2x mb-2 text-danger"></i>
                    <h5 class="card-title"><?php echo $stats['out_of_stock']; ?></h5>
                    <p class="card-text">نفذت من المخزون</p>
                </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-times fa-2x mb-2 text-info"></i>
                    <h5 class="card-title"><?php echo $stats['expiring_soon']; ?></h5>
                    <p class="card-text">تنتهي صلاحيتها قريباً</p>
                </div>
                </div>
            </div>
        </div>

    <!-- أزرار الإجراءات -->
    <div class="row mb-4">
        <div class="col-12">
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                <i class="fas fa-plus-circle me-1"></i> إضافة دواء جديد
            </button>
            <a href="inventory.php?filter_stock=low" class="btn btn-warning me-2">
                <i class="fas fa-exclamation-triangle me-1"></i> عرض الأدوية منخفضة المخزون
            </a>
            <a href="inventory.php?filter_stock=expiring" class="btn btn-info me-2">
                <i class="fas fa-calendar-times me-1"></i> عرض الأدوية التي تنتهي صلاحيتها قريباً
            </a>
            <a href="inventory.php" class="btn btn-secondary">
                <i class="fas fa-sync me-1"></i> عرض الكل
            </a>
        </div>
    </div>
    
    <!-- نموذج البحث والفلترة -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search me-2"></i>بحث وفلترة
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="اسم الدواء، الشركة المصنعة...">
                </div>
                <div class="col-md-3">
                    <label for="filter_type" class="form-label">نوع الدواء</label>
                    <select class="form-select" id="filter_type" name="filter_type">
                        <option value="">الكل</option>
                        <?php foreach ($medicine_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_stock" class="form-label">حالة المخزون</label>
                    <select class="form-select" id="filter_stock" name="filter_stock">
                        <option value="">الكل</option>
                        <option value="low" <?php echo $filter_stock === 'low' ? 'selected' : ''; ?>>منخفض المخزون</option>
                        <option value="out" <?php echo $filter_stock === 'out' ? 'selected' : ''; ?>>نفذ من المخزون</option>
                        <option value="expiring" <?php echo $filter_stock === 'expiring' ? 'selected' : ''; ?>>ينتهي قريباً</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                </div>
            </form>
        </div>
        </div>

        <!-- جدول الأدوية -->
        <div class="card">
            <div class="card-header">
            <i class="fas fa-table me-2"></i>قائمة الأدوية
            </div>
            <div class="card-body">
                <?php if (count($medicines) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                <th>#</th>
                                    <th>اسم الدواء</th>
                                    <th>النوع</th>
                                <th>الشكل الدوائي</th>
                                    <th>الكمية المتوفرة</th>
                                <th>السعر</th>
                                    <th>تاريخ انتهاء الصلاحية</th>
                                <th>الشركة المصنعة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $count = 1;
                            foreach ($medicines as $medicine): 
                            ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($medicine['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['dosage_form'] ?? ''); ?></td>
                                    <td>
                                        <?php if (($medicine['quantity_in_stock'] ?? 0) == 0): ?>
                                            <span class="badge bg-danger">نفذ</span>
                                        <?php elseif (($medicine['quantity_in_stock'] ?? 0) < 10): ?>
                                            <span class="badge bg-warning"><?php echo $medicine['quantity_in_stock']; ?></span>
                                            <?php else: ?>
                                            <span class="badge bg-success"><?php echo $medicine['quantity_in_stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <td><?php echo htmlspecialchars($medicine['price'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                        $expiry_date = new DateTime($medicine['expiry_date'] ?? 'now');
                                        $current_date = new DateTime();
                                        $diff = $current_date->diff($expiry_date);
                                        $days_until_expiry = $expiry_date > $current_date ? $diff->days : -$diff->days;
                                        
                                        if ($days_until_expiry < 0): 
                                        ?>
                                            <span class="badge bg-danger">منتهي الصلاحية</span>
                                        <?php elseif ($days_until_expiry <= 90): ?>
                                            <span class="badge bg-warning"><?php echo htmlspecialchars($medicine['expiry_date']); ?></span>
                                                <?php else: ?>
                                            <?php echo htmlspecialchars($medicine['expiry_date']); ?>
                                                <?php endif; ?>
                                        </td>
                                    <td><?php echo htmlspecialchars($medicine['manufacturer'] ?? ''); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateQuantityModal" 
                                                data-medicine-id="<?php echo $medicine['id']; ?>"
                                                data-medicine-name="<?php echo htmlspecialchars($medicine['name']); ?>"
                                                data-current-quantity="<?php echo $medicine['quantity_in_stock']; ?>">
                                            <i class="fas fa-edit me-1"></i> تحديث الكمية
                                            </button>
                                        
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteMedicineModal" 
                                                data-medicine-id="<?php echo $medicine['id']; ?>"
                                                data-medicine-name="<?php echo htmlspecialchars($medicine['name']); ?>">
                                            <i class="fas fa-trash me-1"></i> حذف
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>لا توجد أدوية متطابقة مع معايير البحث
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- مودال إضافة دواء جديد -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إضافة دواء جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                    <div class="modal-body">
                <form method="post" id="addMedicineForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">اسم الدواء *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">نوع الدواء *</label>
                            <input type="text" class="form-control" id="type" name="type" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dosage_form" class="form-label">الشكل الدوائي</label>
                            <select class="form-select" id="dosage_form" name="dosage_form">
                                <option value="أقراص">أقراص</option>
                                <option value="كبسولات">كبسولات</option>
                                    <option value="شراب">شراب</option>
                                    <option value="حقن">حقن</option>
                                    <option value="مرهم">مرهم</option>
                                <option value="قطرات">قطرات</option>
                                <option value="آخر">آخر</option>
                                </select>
                            </div>
                        <div class="col-md-6">
                            <label for="manufacturer" class="form-label">الشركة المصنعة</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">الكمية *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                            </div>
                        <div class="col-md-4">
                            <label for="price" class="form-label">السعر *</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="expiry_date" class="form-label">تاريخ انتهاء الصلاحية *</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                        </div>
                    </div>
                    
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف الدواء</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    
                    <div class="text-center">
                        <button type="submit" name="add_medicine" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> حفظ
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- مودال تحديث الكمية -->
<div class="modal fade" id="updateQuantityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تحديث كمية الدواء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="updateQuantityForm">
                    <input type="hidden" id="update_medicine_id" name="medicine_id">
                    
                    <div class="mb-3">
                        <label class="form-label">اسم الدواء</label>
                        <input type="text" class="form-control" id="medicine_name_display" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_quantity" class="form-label">الكمية الحالية</label>
                        <input type="number" class="form-control" id="current_quantity" readonly>
                    </div>
                    
                        <div class="mb-3">
                        <label for="new_quantity" class="form-label">الكمية الجديدة *</label>
                        <input type="number" class="form-control" id="new_quantity" name="new_quantity" min="0" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="update_quantity" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> تحديث
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- مودال حذف دواء -->
<div class="modal fade" id="deleteMedicineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>حذف دواء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
            <div class="modal-body">
                <form method="post" id="deleteMedicineForm">
                    <input type="hidden" id="delete_medicine_id" name="medicine_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>هل أنت متأكد من حذف الدواء: <span id="delete_medicine_name"></span>؟
                    </div>
                    <p>هذا الإجراء لا يمكن التراجع عنه. لن تتمكن من حذف الدواء إذا كان مستخدمًا في أي روشتة طبية.</p>
                    
                    <div class="text-center">
                        <button type="submit" name="delete_medicine" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> نعم، حذف
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // تهيئة التاريخ
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const nextYear = new Date(today);
        nextYear.setFullYear(today.getFullYear() + 1);
        
        document.getElementById('expiry_date').valueAsDate = nextYear;
        
        // تهيئة مودال تحديث الكمية
        const updateQuantityModal = document.getElementById('updateQuantityModal');
        updateQuantityModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const medicineId = button.getAttribute('data-medicine-id');
            const medicineName = button.getAttribute('data-medicine-name');
            const currentQuantity = button.getAttribute('data-current-quantity');
            
            updateQuantityModal.querySelector('#update_medicine_id').value = medicineId;
            updateQuantityModal.querySelector('#medicine_name_display').value = medicineName;
            updateQuantityModal.querySelector('#current_quantity').value = currentQuantity;
            updateQuantityModal.querySelector('#new_quantity').value = currentQuantity;
        });
        
        // تهيئة مودال حذف دواء
        const deleteMedicineModal = document.getElementById('deleteMedicineModal');
        deleteMedicineModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const medicineId = button.getAttribute('data-medicine-id');
            const medicineName = button.getAttribute('data-medicine-name');
            
            deleteMedicineModal.querySelector('#delete_medicine_id').value = medicineId;
            deleteMedicineModal.querySelector('#delete_medicine_name').textContent = medicineName;
        });
    });
</script>

<?php
// تضمين ملف الفوتر إذا كان موجودًا
if (file_exists('footer.php')) {
    include_once 'footer.php';
}
?>