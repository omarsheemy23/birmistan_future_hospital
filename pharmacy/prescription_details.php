<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تسجيل الأخطاء في ملف
ini_set('log_errors', 1);
ini_set('error_log', '../logs/pharmacy_error.log');

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل دخول الصيدلي
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit();
}

// التحقق من وجود معرف الروشتة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_prescriptions.php");
    exit();
}

$prescription_id = $_GET['id'];

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// استعلام لجلب معلومات الروشتة
$prescription_query = "SELECT p.*, 
                        pt.first_name as patient_first_name, pt.last_name as patient_last_name, pt.id as patient_id, pt.date_of_birth, pt.gender, pt.phone,
                        d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.id as doctor_id, d.specialization
                        FROM prescriptions p
                        JOIN patients pt ON p.patient_id = pt.id
                        JOIN doctors d ON p.doctor_id = d.id
                        WHERE p.id = :prescription_id";
$prescription_stmt = $conn->prepare($prescription_query);
$prescription_stmt->bindParam(':prescription_id', $prescription_id);
$prescription_stmt->execute();

if ($prescription_stmt->rowCount() == 0) {
    header("Location: view_prescriptions.php");
    exit();
}

$prescription = $prescription_stmt->fetch(PDO::FETCH_ASSOC);

// استعلام لجلب عناصر الروشتة (الأدوية)
try {
    $items_query = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type, m.quantity_in_stock, m.price
                    FROM prescription_medicines pm
                    JOIN medicines m ON pm.medicine_id = m.id
                    WHERE pm.prescription_id = :prescription_id";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bindParam(':prescription_id', $prescription_id);
    $items_stmt->execute();
    $prescription_medicines = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching prescription items: " . $e->getMessage());
    // الحصول على الأدوية من جدول prescription_medicines إذا كان موجودًا
    try {
        $alt_items_query = "SELECT pm.*, m.name as medicine_name, m.type as medicine_type, m.quantity_in_stock, m.price
                        FROM prescription_medicines pm
                        JOIN medicines m ON pm.medicine_id = m.id
                        WHERE pm.prescription_id = :prescription_id";
        $alt_items_stmt = $conn->prepare($alt_items_query);
        $alt_items_stmt->bindParam(':prescription_id', $prescription_id);
        $alt_items_stmt->execute();
        $prescription_medicines = $alt_items_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        error_log("Error fetching from alternative table: " . $e2->getMessage());
        $prescription_medicines = [];
    }
}

// استعلام لجلب معلومات الصرف إذا تم صرف الروشتة
try {
    $dispense_query = "SELECT dm.*, p.name as pharmacist_name
                   FROM dispensed_medicines dm
                   JOIN pharmacists p ON dm.pharmacist_id = p.pharmacist_id
                   WHERE dm.prescription_id = :prescription_id";
    $dispense_stmt = $conn->prepare($dispense_query);
    $dispense_stmt->bindParam(':prescription_id', $prescription_id);
    $dispense_stmt->execute();
    $dispense_info = $dispense_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching dispense info: " . $e->getMessage());
    $dispense_info = false;
}

// معالجة طلب صرف الدواء
if (isset($_POST['dispense'])) {
    // الحصول على معرف الصيدلي
    $pharmacist_query = "SELECT pharmacist_id FROM pharmacists WHERE user_id = :user_id";
    $pharmacist_stmt = $conn->prepare($pharmacist_query);
    $pharmacist_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $pharmacist_stmt->execute();
    $pharmacist = $pharmacist_stmt->fetch(PDO::FETCH_ASSOC);
    $pharmacist_id = $pharmacist['pharmacist_id'];
    
    // التحقق من توفر الأدوية في المخزون
    $all_available = true;
    $unavailable_medicines = [];
    
    foreach ($prescription_medicines as $item) {
        if ($item['quantity_in_stock'] <= 0) {
            $all_available = false;
            $unavailable_medicines[] = $item['medicine_name'];
        }
    }
    
    if ($all_available) {
        try {
            // بدء المعاملة
            $conn->beginTransaction();
            
            // إضافة سجل صرف الدواء
            $dispense_insert = "INSERT INTO dispensed_medicines (prescription_id, pharmacist_id, status, notes) 
                               VALUES (:prescription_id, :pharmacist_id, 'تم الصرف', :notes)";
            $dispense_stmt = $conn->prepare($dispense_insert);
            $dispense_stmt->bindParam(':prescription_id', $prescription_id);
            $dispense_stmt->bindParam(':pharmacist_id', $pharmacist_id);
            $dispense_stmt->bindParam(':notes', $_POST['notes']);
            $dispense_stmt->execute();
            
            // تحديث حالة الروشتة
            $update_prescription = "UPDATE prescriptions SET status = 'مغلقة' WHERE id = :prescription_id";
            $update_stmt = $conn->prepare($update_prescription);
            $update_stmt->bindParam(':prescription_id', $prescription_id);
            $update_stmt->execute();
            
            // تحديث كمية الأدوية في المخزون
            foreach ($prescription_medicines as $item) {
                $update_stock = "UPDATE medicines SET quantity_in_stock = quantity_in_stock - 1 WHERE id = :medicine_id";
                $update_stock_stmt = $conn->prepare($update_stock);
                $update_stock_stmt->bindParam(':medicine_id', $item['medicine_id']);
                $update_stock_stmt->execute();
            }
            
            // تأكيد المعاملة
            $conn->commit();
            
            // إعادة التوجيه مع رسالة نجاح
            header("Location: prescription_details.php?id=$prescription_id&success=1");
            exit();
            
        } catch (PDOException $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollBack();
            $error_message = "حدث خطأ أثناء صرف الدواء: " . $e->getMessage();
        }
    } else {
        $error_message = "لا يمكن صرف الدواء. الأدوية التالية غير متوفرة: " . implode(", ", $unavailable_medicines);
    }
}

// تحديث معلومات الروشتة بعد الصرف
if (isset($_GET['success'])) {
    $prescription_stmt->execute();
    $prescription = $prescription_stmt->fetch(PDO::FETCH_ASSOC);
    
    $dispense_stmt->execute();
    $dispense_info = $dispense_stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الروشتة - مستشفى بيرمستان المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
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
        .navbar {
            background-color: #1e3c72 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #4facfe !important;
        }
        .btn-primary {
            background-color: #2a5298;
            border-color: #2a5298;
        }
        .btn-primary:hover {
            background-color: #1e3c72;
            border-color: #1e3c72;
        }
        .patient-info, .doctor-info {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .patient-info {
            background-color: #e8f4f8;
        }
        .doctor-info {
            background-color: #f0f5ff;
        }
        .medicine-item {
            border-left: 4px solid #2a5298;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .medicine-name {
            font-weight: bold;
            color: #2a5298;
        }
        .medicine-details {
            color: #666;
            font-size: 0.9rem;
        }
        .dispense-form {
            background-color: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .dispense-info {
            background-color: #e8f8e8;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .ai-suggestion {
            background-color: #fff8e8;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-clinic-medical me-2"></i>مستشفى بيرمستان المستقبل - نظام الصيدلية
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> لوحة التحكم</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_prescriptions.php"><i class="fas fa-prescription me-1"></i> الروشتات الجديدة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dispense_medicine.php"><i class="fas fa-pills me-1"></i> صرف دواء</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php"><i class="fas fa-boxes me-1"></i> إدارة الأدوية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../setup_pharmacy_tables.php"><i class="fas fa-database me-1"></i> إصلاح قاعدة البيانات</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> تسجيل الخروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (empty($prescription)): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>خطأ في عرض تفاصيل الروشتة</h4>
            <p>لم يتم العثور على الروشتة المطلوبة أو هناك مشكلة في قاعدة البيانات.</p>
            <div class="mt-3">
                <a href="view_prescriptions.php" class="btn btn-primary me-2">
                    <i class="fas fa-arrow-left me-1"></i> العودة إلى قائمة الروشتات
                </a>
                <a href="../setup_pharmacy_tables.php" class="btn btn-success">
                    <i class="fas fa-database me-1"></i> إصلاح قاعدة البيانات
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-prescription me-2"></i>تفاصيل الروشتة #<?php echo $prescription_id; ?></h2>
            <a href="view_prescriptions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للروشتات
            </a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>تم صرف الدواء بنجاح!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i>معلومات المريض
                    </div>
                    <div class="card-body">
                        <div class="patient-info">
                            <h5><?php echo $prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']; ?></h5>
                            <p><strong>رقم المريض:</strong> <?php echo $prescription['patient_id']; ?></p>
                            <p><strong>تاريخ الميلاد:</strong> <?php echo $prescription['date_of_birth']; ?></p>
                            <p><strong>الجنس:</strong> <?php echo $prescription['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?></p>
                            <p><strong>رقم الهاتف:</strong> <?php echo $prescription['phone']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-md me-2"></i>معلومات الطبيب
                    </div>
                    <div class="card-body">
                        <div class="doctor-info">
                            <h5>د. <?php echo $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']; ?></h5>
                            <p><strong>رقم الطبيب:</strong> <?php echo $prescription['doctor_id']; ?></p>
                            <p><strong>التخصص:</strong> <?php echo $prescription['specialization']; ?></p>
                            <p><strong>تاريخ الروشتة:</strong> <?php echo date('Y-m-d', strtotime($prescription['date'])); ?></p>
                            <p><strong>حالة الروشتة:</strong> 
                                <?php if ($prescription['status'] == 'مفتوحة'): ?>
                                    <span class="badge bg-warning">مفتوحة</span>
                                <?php else: ?>
                                    <span class="badge bg-success">مغلقة</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-pills me-2"></i>الأدوية الموصوفة
            </div>
            <div class="card-body">
                <?php if (count($prescription_medicines) > 0): ?>
                    <?php foreach ($prescription_medicines as $item): ?>
                        <div class="medicine-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="medicine-name"><?php echo $item['medicine_name']; ?> (<?php echo $item['medicine_type']; ?>)</div>
                                    <div class="medicine-details">
                                        <p><strong>الجرعة:</strong> <?php echo $item['dosage']; ?></p>
                                        <p><strong>المدة:</strong> <?php echo $item['duration']; ?></p>
                                        <p><strong>ملاحظات:</strong> <?php echo $item['notes'] ? $item['notes'] : 'لا توجد ملاحظات'; ?></p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p><strong>السعر:</strong> <?php echo $item['price']; ?> ريال</p>
                                    <p>
                                        <strong>المخزون:</strong> 
                                        <?php if ($item['quantity_in_stock'] > 20): ?>
                                            <span class="text-success"><?php echo $item['quantity_in_stock']; ?> (متوفر)</span>
                                        <?php elseif ($item['quantity_in_stock'] > 0): ?>
                                            <span class="text-warning"><?php echo $item['quantity_in_stock']; ?> (منخفض)</span>
                                        <?php else: ?>
                                            <span class="text-danger">0 (غير متوفر)</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- اقتراحات الذكاء الاصطناعي -->
                    <div class="ai-suggestion">
                        <h5><i class="fas fa-robot me-2"></i>اقتراحات Trea AI</h5>
                        <ul>
                            <?php foreach ($prescription_medicines as $item): ?>
                                <?php if ($item['quantity_in_stock'] <= 0): ?>
                                    <li>الدواء <strong><?php echo $item['medicine_name']; ?></strong> غير متوفر. يمكن استبداله بـ: 
                                        <?php 
                                            // هنا يمكن إضافة منطق لاقتراح البدائل من قاعدة البيانات
                                            echo "أدفيل، بروفين (استشر الطبيب قبل التغيير)"; 
                                        ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <li>تأكد من عدم وجود تفاعلات دوائية بين الأدوية الموصوفة.</li>
                            <li>تذكير: يجب إبلاغ المريض بالآثار الجانبية المحتملة.</li>
                        </ul>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد أدوية مضافة لهذه الروشتة
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($prescription['status'] == 'مفتوحة'): ?>
            <!-- نموذج صرف الدواء -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-hand-holding-medical me-2"></i>صرف الدواء
                </div>
                <div class="card-body">
                    <form method="post" action="process_dispensing.php" class="dispense-form">
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات الصرف</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="أضف أي ملاحظات أو تعليمات للمريض"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm" required>
                            <label class="form-check-label" for="confirm">
                                أؤكد أنني قمت بمراجعة جميع الأدوية والتأكد من توفرها
                            </label>
                        </div>
                        <input type="hidden" name="prescription_id" value="<?php echo $prescription_id; ?>">
                        <button type="submit" name="dispense" class="btn btn-primary">
                            <i class="fas fa-pills me-1"></i> صرف الدواء
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($dispense_info): ?>
            <!-- معلومات الصرف -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-check-circle me-2"></i>معلومات الصرف
                </div>
                <div class="card-body">
                    <div class="dispense-info">
                        <h5>تم صرف الدواء</h5>
                        <p><strong>تاريخ الصرف:</strong> <?php echo date('Y-m-d H:i', strtotime($dispense_info['dispense_date'])); ?></p>
                        <p><strong>الصيدلي:</strong> <?php echo $dispense_info['pharmacist_name']; ?></p>
                        <p><strong>ملاحظات:</strong> <?php echo $dispense_info['notes'] ? $dispense_info['notes'] : 'لا توجد ملاحظات'; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>