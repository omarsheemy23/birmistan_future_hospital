<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// تخطي التحقق من الجلسة للاختبار
$_SESSION['user_id'] = 1; // قيمة وهمية
$_SESSION['role'] = 'pharmacist'; // تعيين دور الصيدلي للاختبار

require_once '../config/database.php';
require_once '../includes/functions.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// جلب معلومات الصيدلي
try {
    $stmt = $conn->prepare("SELECT * FROM pharmacists WHERE user_id = ? LIMIT 1");
    $stmt->execute([1]); // استخدام قيمة وهمية
    $pharmacist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pharmacist) {
        // إذا لم يتم العثور على صيدلي، أنشئ بيانات وهمية للعرض
        $pharmacist = [
            'first_name' => 'صيدلي',
            'last_name' => 'اختبار'
        ];
    }
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px; margin:20px; background:#ffeeee; border:1px solid red;'>";
    echo "<h3>خطأ في الاتصال بقاعدة البيانات:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    // تعيين بيانات وهمية للاستمرار في العرض
    $pharmacist = [
        'first_name' => 'صيدلي',
        'last_name' => 'اختبار'
    ];
}

// الإحصائيات - استخدام بيانات وهمية للاختبار
$stats = [
    'pending_prescriptions' => 5,
    'dispensed_prescriptions' => 10,
    'low_stock_medicines' => 3,
    'expiring_medicines' => 2
];

// بيانات وهمية للروشتات الأخيرة
$recent_prescriptions = [
    [
        'id' => 1,
        'patient_first_name' => 'أحمد',
        'patient_last_name' => 'محمد',
        'doctor_first_name' => 'سمير',
        'doctor_last_name' => 'علي',
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'patient_first_name' => 'فاطمة',
        'patient_last_name' => 'أحمد',
        'doctor_first_name' => 'نورا',
        'doctor_last_name' => 'حسن',
        'status' => 'dispensed'
    ]
];

// بيانات وهمية للأدوية منخفضة المخزون
$low_stock_medicines = [
    [
        'id' => 1,
        'name' => 'باراسيتامول',
        'type' => 'مسكن',
        'quantity_in_stock' => 5
    ],
    [
        'id' => 2,
        'name' => 'أموكسيسيلين',
        'type' => 'مضاد حيوي',
        'quantity_in_stock' => 3
    ]
];

// بيانات وهمية للأدوية التي توشك على انتهاء الصلاحية
$expiring_medicines = [
    [
        'id' => 3,
        'name' => 'إيبوبروفين',
        'type' => 'مضاد التهاب',
        'expiry_date' => date('Y-m-d', strtotime('+20 days'))
    ],
    [
        'id' => 4,
        'name' => 'لوراتادين',
        'type' => 'مضاد حساسية',
        'expiry_date' => date('Y-m-d', strtotime('+45 days'))
    ]
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الصيدلية - اختبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .navbar {
            background-color: #1e3c72 !important;
            color: white;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2a5298;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل المبسط -->
    <nav class="navbar navbar-dark p-3">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-clinic-medical me-2"></i>مستشفى بيرمستان المستقبل - نظام الصيدلية (وضع الاختبار)
            </span>
        </div>
    </nav>

    <div class="container">
        <!-- ترحيب وملخص -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">أهلاً بك، <?php echo htmlspecialchars($pharmacist['first_name'] ?? '') . ' ' . htmlspecialchars($pharmacist['last_name'] ?? ''); ?></h4>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            هذه صفحة اختبار لعرض لوحة تحكم الصيدلية بدون الحاجة لتسجيل الدخول.
                        </div>
                        <p class="card-text">لوحة تحكم نظام الصيدلية تعرض ملخصاً للأنشطة والمهام.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['pending_prescriptions']; ?></h3>
                        <p class="card-text">روشتات بانتظار الصرف</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['dispensed_prescriptions']; ?></h3>
                        <p class="card-text">روشتات تم صرفها</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['low_stock_medicines']; ?></h3>
                        <p class="card-text">أدوية منخفضة المخزون</p>
                        <a href="#" class="btn btn-dark btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="display-4"><?php echo $stats['expiring_medicines']; ?></h3>
                        <p class="card-text">أدوية توشك على الانتهاء</p>
                        <a href="#" class="btn btn-light btn-sm mt-2">عرض</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- المهام السريعة -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tasks me-2"></i>المهام السريعة
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-pills me-2"></i>صرف دواء
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-boxes me-2"></i>إدارة المخزون
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-warning btn-lg w-100">
                                    <i class="fas fa-clipboard-list me-2"></i>عرض الروشتات
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-chart-bar me-2"></i>التقارير
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- الروشتات الأخيرة -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-2"></i>أحدث الروشتات
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_prescriptions)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>رقم</th>
                                            <th>المريض</th>
                                            <th>الطبيب</th>
                                            <th>الحالة</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_prescriptions as $prescription): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prescription['id']); ?></td>
                                                <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                                <td>
                                                    <?php if ($prescription['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">قيد الانتظار</span>
                                                    <?php elseif ($prescription['status'] === 'dispensed'): ?>
                                                        <span class="badge bg-success">تم الصرف</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($prescription['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="#" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i> عرض
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-outline-primary">عرض الكل</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>لا توجد روشتات حديثة
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- الأدوية منخفضة المخزون -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle me-2"></i>الأدوية منخفضة المخزون
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_medicines)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>اسم الدواء</th>
                                            <th>النوع</th>
                                            <th>الكمية المتبقية</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($low_stock_medicines as $medicine): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['type']); ?></td>
                                                <td>
                                                    <?php if ($medicine['quantity_in_stock'] == 0): ?>
                                                        <span class="badge bg-danger">نفذ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning"><?php echo $medicine['quantity_in_stock']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit me-1"></i> تحديث
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-outline-warning">عرض الكل</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>لا توجد أدوية منخفضة المخزون
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- الأدوية التي توشك على انتهاء الصلاحية -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-times me-2"></i>الأدوية التي توشك على انتهاء الصلاحية
                    </div>
                    <div class="card-body">
                        <?php if (!empty($expiring_medicines)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>اسم الدواء</th>
                                            <th>النوع</th>
                                            <th>تاريخ انتهاء الصلاحية</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiring_medicines as $medicine): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                <td><?php echo htmlspecialchars($medicine['type']); ?></td>
                                                <td>
                                                    <?php 
                                                    $expiry_date = new DateTime($medicine['expiry_date']);
                                                    $current_date = new DateTime();
                                                    $diff = $current_date->diff($expiry_date);
                                                    $days_until_expiry = $expiry_date > $current_date ? $diff->days : -$diff->days;
                                                    
                                                    if ($days_until_expiry < 30): 
                                                    ?>
                                                        <span class="badge bg-danger"><?php echo htmlspecialchars($medicine['expiry_date']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning"><?php echo htmlspecialchars($medicine['expiry_date']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-outline-danger">عرض الكل</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>لا توجد أدوية توشك على انتهاء الصلاحية
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- روابط الاختبار -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-link me-2"></i>روابط الاختبار
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>صفحات النظام:</h5>
                        <ul>
                            <li><a href="../login.php">صفحة تسجيل الدخول</a></li>
                            <li><a href="../pharmacist_login.php">تسجيل دخول تلقائي كصيدلي</a></li>
                            <li><a href="../setup_check.php">فحص إعداد النظام</a></li>
                            <li><a href="../test_pharmacy.php">اختبار صفحات الصيدلية</a></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>صفحات تشخيصية:</h5>
                        <ul>
                            <li><a href="error.php">عرض الأخطاء</a></li>
                            <li><a href="../debug.php">معلومات تشخيصية</a></li>
                            <li><a href="../symlink.php">إعادة مزامنة المجلدات</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- تذييل الصفحة -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> جميع الحقوق محفوظة - مستشفى بيرمستان المستقبل
                    </p>
                    <p class="small text-muted mb-0">
                        نظام إدارة الصيدلية - الإصدار 1.0 (وضع الاختبار)
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- تضمين مكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 