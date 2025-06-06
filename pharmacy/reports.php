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

// تحديد نوع التقرير المطلوب (الافتراضي: الأدوية منخفضة المخزون)
$report_type = isset($_GET['type']) ? $_GET['type'] : 'low_stock';

// تاريخ البدء والانتهاء للتقارير التي تعتمد على فترة زمنية
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// استعلام للتقرير حسب النوع
$query = "";
$title = "";

switch ($report_type) {
    case 'low_stock':
        $title = "تقرير الأدوية منخفضة المخزون";
        $query = "SELECT * FROM medicines WHERE quantity_in_stock < 20 ORDER BY quantity_in_stock ASC";
        break;
    
    case 'expiring_soon':
        $title = "تقرير الأدوية التي توشك على انتهاء الصلاحية";
        $query = "SELECT * FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY expiry_date ASC";
        break;
    
    case 'dispensed':
        $title = "تقرير الأدوية المصروفة";
        $query = "SELECT m.name, m.type, COUNT(dm.id) as times_dispensed, SUM(pm.quantity) as total_quantity
                  FROM dispensed_medicines dm
                  JOIN prescriptions p ON dm.prescription_id = p.id
                  JOIN prescription_medicines pm ON pm.prescription_id = p.id
                  JOIN medicines m ON pm.medicine_id = m.id
                  WHERE dm.dispensed_date BETWEEN :start_date AND :end_date
                  GROUP BY m.id
                  ORDER BY times_dispensed DESC";
        break;
    
    case 'prescriptions':
        $title = "تقرير الروشتات الطبية";
        $query = "SELECT p.id, p.prescription_date, pt.first_name as patient_first_name, pt.last_name as patient_last_name, 
                  d.first_name as doctor_first_name, d.last_name as doctor_last_name, p.status,
                  COUNT(pm.id) as medicine_count
                  FROM prescriptions p
                  JOIN patients pt ON p.patient_id = pt.id
                  JOIN doctors d ON p.doctor_id = d.id
                  LEFT JOIN prescription_medicines pm ON p.id = pm.prescription_id
                  WHERE p.prescription_date BETWEEN :start_date AND :end_date
                  GROUP BY p.id
                  ORDER BY p.prescription_date DESC";
        break;
        
    default:
        $title = "تقرير الأدوية منخفضة المخزون";
        $query = "SELECT * FROM medicines WHERE quantity_in_stock < 20 ORDER BY quantity_in_stock ASC";
        break;
}

// تنفيذ الاستعلام
$stmt = $conn->prepare($query);

// ربط المعاملات إذا كان التقرير يعتمد على فترة زمنية
if ($report_type == 'dispensed' || $report_type == 'prescriptions') {
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// استعلام لإحصائيات عامة
$stats_query = "SELECT 
               (SELECT COUNT(*) FROM medicines) as total_medicines,
               (SELECT COUNT(*) FROM medicines WHERE quantity_in_stock < 20) as low_stock_count,
               (SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)) as expiring_soon_count,
               (SELECT COUNT(*) FROM prescriptions WHERE status = 'dispensed') as dispensed_count";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// تضمين ملف الهيدر
include_once 'header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-chart-bar me-2"></i><?php echo $title; ?></h2>
    
    <!-- نموذج اختيار نوع التقرير والفترة الزمنية -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>خيارات التقرير
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">نوع التقرير</label>
                    <select class="form-select" id="type" name="type" onchange="toggleDateFields()">
                        <option value="low_stock" <?php echo $report_type == 'low_stock' ? 'selected' : ''; ?>>الأدوية منخفضة المخزون</option>
                        <option value="expiring_soon" <?php echo $report_type == 'expiring_soon' ? 'selected' : ''; ?>>الأدوية التي توشك على انتهاء الصلاحية</option>
                        <option value="dispensed" <?php echo $report_type == 'dispensed' ? 'selected' : ''; ?>>الأدوية المصروفة</option>
                        <option value="prescriptions" <?php echo $report_type == 'prescriptions' ? 'selected' : ''; ?>>الروشتات الطبية</option>
                    </select>
                </div>
                
                <div class="col-md-3 date-field" <?php echo ($report_type != 'dispensed' && $report_type != 'prescriptions') ? 'style="display:none;"' : ''; ?>>
                    <label for="start_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="col-md-3 date-field" <?php echo ($report_type != 'dispensed' && $report_type != 'prescriptions') ? 'style="display:none;"' : ''; ?>>
                    <label for="end_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ملخص الإحصائيات -->
    <div class="row mb-4">
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
                    <h5 class="card-title"><?php echo $stats['low_stock_count']; ?></h5>
                    <p class="card-text">منخفضة المخزون</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-times fa-2x mb-2 text-danger"></i>
                    <h5 class="card-title"><?php echo $stats['expiring_soon_count']; ?></h5>
                    <p class="card-text">توشك على انتهاء الصلاحية</p>
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
    </div>
    
    <!-- نتائج التقرير -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-2"></i>نتائج التقرير
        </div>
        <div class="card-body">
            <?php if (count($results) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <?php if ($report_type == 'low_stock' || $report_type == 'expiring_soon'): ?>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الدواء</th>
                                    <th>النوع</th>
                                    <th>الكمية المتوفرة</th>
                                    <th>تاريخ انتهاء الصلاحية</th>
                                    <th>السعر</th>
                                </tr>
                            <?php elseif ($report_type == 'dispensed'): ?>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الدواء</th>
                                    <th>النوع</th>
                                    <th>عدد مرات الصرف</th>
                                    <th>إجمالي الكمية</th>
                                </tr>
                            <?php elseif ($report_type == 'prescriptions'): ?>
                                <tr>
                                    <th>رقم الروشتة</th>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>عدد الأدوية</th>
                                    <th>الحالة</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach ($results as $row): 
                            ?>
                                <?php if ($report_type == 'low_stock' || $report_type == 'expiring_soon'): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['type'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($row['quantity_in_stock'] ?? 0) < 10): ?>
                                                <span class="badge bg-danger"><?php echo $row['quantity_in_stock']; ?></span>
                                            <?php elseif (($row['quantity_in_stock'] ?? 0) < 20): ?>
                                                <span class="badge bg-warning"><?php echo $row['quantity_in_stock']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $row['quantity_in_stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['expiry_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['price'] ?? ''); ?></td>
                                    </tr>
                                <?php elseif ($report_type == 'dispensed'): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['times_dispensed'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_quantity'] ?? ''); ?></td>
                                    </tr>
                                <?php elseif ($report_type == 'prescriptions'): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['prescription_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(($row['patient_first_name'] ?? '') . ' ' . ($row['patient_last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars(($row['doctor_first_name'] ?? '') . ' ' . ($row['doctor_last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($row['medicine_count'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($row['status'] ?? '') == 'pending'): ?>
                                                <span class="badge bg-warning">قيد الانتظار</span>
                                            <?php elseif (($row['status'] ?? '') == 'dispensed'): ?>
                                                <span class="badge bg-success">تم الصرف</span>
                                            <?php elseif (($row['status'] ?? '') == 'cancelled'): ?>
                                                <span class="badge bg-danger">ملغاة</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['status'] ?? ''); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- خيارات طباعة التقرير وتصديره -->
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-secondary me-2" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> طباعة
                    </button>
                    <button type="button" class="btn btn-success me-2" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i> تصدير إلى Excel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i> تصدير إلى PDF
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>لا توجد بيانات متاحة لهذا التقرير
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // دالة لإظهار/إخفاء حقول التاريخ حسب نوع التقرير
    function toggleDateFields() {
        const reportType = document.getElementById('type').value;
        const dateFields = document.querySelectorAll('.date-field');
        
        if (reportType === 'dispensed' || reportType === 'prescriptions') {
            dateFields.forEach(field => field.style.display = 'block');
        } else {
            dateFields.forEach(field => field.style.display = 'none');
        }
    }
    
    // دالة لتصدير البيانات إلى Excel (مثال)
    function exportToExcel() {
        alert('جاري تصدير البيانات إلى Excel...');
        // يمكن استخدام مكتبة مثل SheetJS هنا للتصدير الفعلي
    }
    
    // دالة لتصدير البيانات إلى PDF (مثال)
    function exportToPDF() {
        alert('جاري تصدير البيانات إلى PDF...');
        // يمكن استخدام مكتبة مثل jsPDF هنا للتصدير الفعلي
    }
</script>

<?php
// تضمين ملف الفوتر إذا كان موجودًا
if (file_exists('footer.php')) {
    include_once 'footer.php';
}
?> 