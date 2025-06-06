<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_error.log');

// البيانات الوهمية لعرض المرضى
$patients = [
    [
        'id' => 1,
        'name' => 'أحمد محمد',
        'medical_id' => 'PAT-00001',
        'phone' => '0512345678',
        'pending_prescriptions' => 2,
        'last_visit' => '2023-10-15'
    ],
    [
        'id' => 2,
        'name' => 'فاطمة علي',
        'medical_id' => 'PAT-00002',
        'phone' => '0598765432',
        'pending_prescriptions' => 0,
        'last_visit' => '2023-10-18'
    ],
    [
        'id' => 3,
        'name' => 'محمد عبدالله',
        'medical_id' => 'PAT-00003',
        'phone' => '0556789012',
        'pending_prescriptions' => 1,
        'last_visit' => '2023-10-10'
    ],
    [
        'id' => 4,
        'name' => 'سارة خالد',
        'medical_id' => 'PAT-00004',
        'phone' => '0567890123',
        'pending_prescriptions' => 0,
        'last_visit' => '2023-09-25'
    ],
    [
        'id' => 5,
        'name' => 'عمر سعد',
        'medical_id' => 'PAT-00005',
        'phone' => '0534567890',
        'pending_prescriptions' => 3,
        'last_visit' => '2023-10-19'
    ]
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المرضى - نظام الصيدلية - مستشفى بيرمستان المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #1e3c72 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
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
        .alert-fix {
            margin-bottom: 0;
            border-radius: 0;
        }
        .patient-row:hover {
            background-color: #f1f5fe;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <!-- تنبيه وضع العرض المستقل -->
    <div class="alert alert-warning text-center alert-fix" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        هذه نسخة مستقلة للعرض فقط - لا تتطلب تسجيل دخول أو اتصال بقاعدة البيانات
    </div>
    
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="standalone.php">
                <i class="fas fa-clinic-medical me-2"></i>مستشفى بيرمستان المستقبل - نظام الصيدلية
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="standalone.php">
                            <i class="fas fa-home me-1"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="patients.php">
                            <i class="fas fa-users me-1"></i> المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="standalone.php">
                            <i class="fas fa-prescription me-1"></i> الروشتات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="standalone.php">
                            <i class="fas fa-pills me-1"></i> صرف الدواء
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="standalone.php">
                            <i class="fas fa-boxes me-1"></i> إدارة المخزون
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-arrow-left me-1"></i> العودة للنظام
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- روابط التصفح السريع -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>المرضى</h5>
                <div>
                    <a href="../setup_pharmacy_tables.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-database me-1"></i> إصلاح قاعدة البيانات
                    </a>
                    <a href="standalone.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-home me-1"></i> العودة للوحة الرئيسية
                    </a>
                </div>
            </div>
        </div>

        <!-- بحث عن المريض -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-search me-2"></i>بحث عن مريض
            </div>
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-4">
                        <label for="patient_name" class="form-label">اسم المريض</label>
                        <input type="text" class="form-control" id="patient_name" placeholder="ادخل اسم المريض">
                    </div>
                    <div class="col-md-4">
                        <label for="medical_id" class="form-label">الرقم الطبي</label>
                        <input type="text" class="form-control" id="medical_id" placeholder="ادخل الرقم الطبي">
                    </div>
                    <div class="col-md-4">
                        <label for="phone" class="form-label">رقم الهاتف</label>
                        <input type="text" class="form-control" id="phone" placeholder="ادخل رقم الهاتف">
                    </div>
                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                        <button type="reset" class="btn btn-secondary px-4">
                            <i class="fas fa-redo me-2"></i>إعادة تعيين
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة المرضى -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><i class="fas fa-list me-2"></i>قائمة المرضى</div>
                <span class="badge bg-primary"><?php echo count($patients); ?> مريض</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم المريض</th>
                                <th>الرقم الطبي</th>
                                <th>رقم الهاتف</th>
                                <th>آخر زيارة</th>
                                <th>الروشتات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $index => $patient): ?>
                            <tr class="patient-row">
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    <?php echo htmlspecialchars($patient['name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($patient['medical_id']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['last_visit']); ?></td>
                                <td>
                                    <?php if ($patient['pending_prescriptions'] > 0): ?>
                                        <span class="badge badge-pending">
                                            <?php echo $patient['pending_prescriptions']; ?> روشتة معلقة
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">لا توجد روشتات معلقة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-outline-primary" title="عرض السجل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-success" title="صرف أدوية">
                                            <i class="fas fa-pills"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-info" title="تاريخ الصرف">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">السابق</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">التالي</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- معلومات تشخيصية -->
        <div class="card mt-4 mb-5">
            <div class="card-header bg-info text-white">
                <i class="fas fa-info-circle me-2"></i>معلومات تشخيصية
            </div>
            <div class="card-body">
                <div class="alert alert-light">
                    <h5>خطأ قاعدة البيانات:</h5>
                    <p>قد تواجه خطأ عند محاولة الوصول إلى صفحة الصيدلي/لوحة التحكم بسبب جدول مفقود. الخطأ المتوقع:</p>
                    <div class="bg-danger text-white p-2 rounded mb-3">
                        <code>SQLSTATE[42S02]: Base table or view not found: 1146 Table 'hospital_db.prescription_medicines' doesn't exist</code>
                    </div>
                    
                    <h5>الحل:</h5>
                    <p>لحل هذه المشكلة، يمكنك استخدام "إصلاح قاعدة البيانات" لإنشاء الجداول المفقودة. باتباع الخطوات التالية:</p>
                    <ol>
                        <li>انقر على زر <strong>إصلاح قاعدة البيانات</strong> أعلاه</li>
                        <li>انتظر حتى تظهر رسالة نجاح</li>
                        <li>حاول الوصول إلى لوحة تحكم الصيدلي مرة أخرى</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- تذييل الصفحة -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> جميع الحقوق محفوظة - مستشفى بيرمستان المستقبل
            </p>
            <p class="small text-muted mb-0">
                نظام إدارة الصيدلية المستقل - الإصدار 1.0
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 