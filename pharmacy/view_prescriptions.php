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

// تحديد الفلتر (الافتراضي: جميع الروشتات)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// بناء استعلام SQL حسب الفلتر
$query = "
    SELECT p.id, p.prescription_date, p.status, 
           pt.first_name as patient_first_name, pt.last_name as patient_last_name,
           d.first_name as doctor_first_name, d.last_name as doctor_last_name,
           COUNT(pm.id) as medicine_count
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.id
    JOIN doctors d ON p.doctor_id = d.id
    LEFT JOIN prescription_medicines pm ON p.id = pm.prescription_id
";

$params = [];

if ($filter === 'pending') {
    $query .= " WHERE p.status = 'pending'";
} elseif ($filter === 'dispensed') {
    $query .= " WHERE p.status = 'dispensed'";
} elseif ($filter === 'cancelled') {
    $query .= " WHERE p.status = 'cancelled'";
}

if (!empty($search)) {
    if (strpos($query, 'WHERE') !== false) {
        $query .= " AND (pt.first_name LIKE ? OR pt.last_name LIKE ? OR d.first_name LIKE ? OR d.last_name LIKE ? OR p.id LIKE ?)";
    } else {
        $query .= " WHERE (pt.first_name LIKE ? OR pt.last_name LIKE ? OR d.first_name LIKE ? OR d.last_name LIKE ? OR p.id LIKE ?)";
    }
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
}

$query .= " GROUP BY p.id
            ORDER BY p.prescription_date DESC";

$stmt = $conn->prepare($query);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param);
}
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات الروشتات
$stats_query = "SELECT 
                COUNT(*) as total_prescriptions,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'dispensed' THEN 1 END) as dispensed_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
                FROM prescriptions";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// تضمين ملف الهيدر
include_once 'header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-prescription me-2"></i>الروشتات الطبية</h2>
    
    <!-- إحصائيات الروشتات -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clipboard-list fa-2x mb-2 text-primary"></i>
                    <h5 class="card-title"><?php echo $stats['total_prescriptions']; ?></h5>
                    <p class="card-text">إجمالي الروشتات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-hourglass-half fa-2x mb-2 text-warning"></i>
                    <h5 class="card-title"><?php echo $stats['pending_count']; ?></h5>
                    <p class="card-text">بانتظار الصرف</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                    <h5 class="card-title"><?php echo $stats['dispensed_count']; ?></h5>
                    <p class="card-text">تم صرفها</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-2x mb-2 text-danger"></i>
                    <h5 class="card-title"><?php echo $stats['cancelled_count']; ?></h5>
                    <p class="card-text">ملغاة</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- فلترة وبحث -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>فلترة وبحث
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="اسم المريض، اسم الطبيب، رقم الروشتة...">
                </div>
                <div class="col-md-4">
                    <label for="filter" class="form-label">فلترة حسب الحالة</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>جميع الروشتات</option>
                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>بانتظار الصرف</option>
                        <option value="dispensed" <?php echo $filter === 'dispensed' ? 'selected' : ''; ?>>تم صرفها</option>
                        <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- قائمة الروشتات -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-clipboard-list me-2"></i>قائمة الروشتات
        </div>
        <div class="card-body">
            <?php if (count($prescriptions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الروشتة</th>
                                <th>تاريخ الروشتة</th>
                                <th>المريض</th>
                                <th>الطبيب</th>
                                <th>عدد الأدوية</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prescription['id']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['prescription_date']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['medicine_count']); ?></td>
                                    <td>
                                        <?php if ($prescription['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">قيد الانتظار</span>
                                        <?php elseif ($prescription['status'] === 'dispensed'): ?>
                                            <span class="badge bg-success">تم الصرف</span>
                                        <?php elseif ($prescription['status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">ملغاة</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($prescription['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="prescription_details.php?id=<?php echo $prescription['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> عرض
                                        </a>
                                        
                                        <?php if ($prescription['status'] === 'pending'): ?>
                                            <a href="dispense_medicine.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-pills me-1"></i> صرف
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>لا توجد روشتات متطابقة مع معايير البحث
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر إذا كان موجودًا
if (file_exists('footer.php')) {
    include_once 'footer.php';
}
?>