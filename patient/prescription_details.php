<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل دخول المريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// التحقق من وجود معرف الروشتة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_prescriptions.php");
    exit();
}

$prescription_id = $_GET['id'];

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// الحصول على معرف المريض
$user_id = $_SESSION['user_id'];
$patient_query = "SELECT id FROM patients WHERE user_id = :user_id";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bindParam(':user_id', $user_id);
$patient_stmt->execute();
$patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// استعلام لجلب معلومات الروشتة
$prescription_query = "SELECT p.*, 
                        d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.id as doctor_id, d.specialization
                        FROM prescriptions p
                        JOIN doctors d ON p.doctor_id = d.id
                        WHERE p.prescription_id = :prescription_id AND p.patient_id = :patient_id";
$prescription_stmt = $conn->prepare($prescription_query);
$prescription_stmt->bindParam(':prescription_id', $prescription_id);
$prescription_stmt->bindParam(':patient_id', $patient_id);
$prescription_stmt->execute();

if ($prescription_stmt->rowCount() == 0) {
    header("Location: my_prescriptions.php");
    exit();
}

$prescription = $prescription_stmt->fetch(PDO::FETCH_ASSOC);

// استعلام لجلب عناصر الروشتة (الأدوية)
$items_query = "SELECT pi.*, m.name as medicine_name, m.type as medicine_type, m.price
                FROM prescription_items pi
                JOIN medicines m ON pi.medicine_id = m.medicine_id
                WHERE pi.prescription_id = :prescription_id";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bindParam(':prescription_id', $prescription_id);
$items_stmt->execute();
$prescription_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// استعلام لجلب معلومات الصرف إذا تم صرف الروشتة
$dispense_query = "SELECT dm.*, p.name as pharmacist_name
                   FROM dispensed_medicines dm
                   JOIN pharmacists p ON dm.pharmacist_id = p.pharmacist_id
                   WHERE dm.prescription_id = :prescription_id";
$dispense_stmt = $conn->prepare($dispense_query);
$dispense_stmt->bindParam(':prescription_id', $prescription_id);
$dispense_stmt->execute();
$dispense_info = $dispense_stmt->fetch(PDO::FETCH_ASSOC);
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
        .btn-primary {
            background-color: #2a5298;
            border-color: #2a5298;
        }
        .btn-primary:hover {
            background-color: #1e3c72;
            border-color: #1e3c72;
        }
        .doctor-info {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
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
        .dispense-info {
            background-color: #e8f8e8;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .pending-info {
            background-color: #fff8e8;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-prescription me-2"></i>تفاصيل الروشتة #<?php echo $prescription_id; ?></h2>
            <a href="my_prescriptions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للروشتات
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-md me-2"></i>معلومات الطبيب والروشتة
            </div>
            <div class="card-body">
                <div class="doctor-info">
                    <h5>د. <?php echo $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']; ?></h5>
                    <p><strong>التخصص:</strong> <?php echo $prescription['specialization']; ?></p>
                    <p><strong>تاريخ الروشتة:</strong> <?php echo date('Y-m-d', strtotime($prescription['date'])); ?></p>
                    <p><strong>حالة الروشتة:</strong> 
                        <?php if ($prescription['status'] == 'مفتوحة'): ?>
                            <span class="badge bg-warning">مفتوحة</span>
                        <?php else: ?>
                            <span class="badge bg-success">مغلقة</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($prescription['notes']): ?>
                        <p><strong>ملاحظات الطبيب:</strong> <?php echo $prescription['notes']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-pills me-2"></i>الأدوية الموصوفة
            </div>
            <div class="card-body">
                <?php if (count($prescription_items) > 0): ?>
                    <?php foreach ($prescription_items as $item): ?>
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
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد أدوية مضافة لهذه الروشتة
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-hand-holding-medical me-2"></i>حالة صرف الدواء
            </div>
            <div class="card-body">
                <?php if ($dispense_info): ?>
                    <div class="dispense-info">
                        <h5><i class="fas fa-check-circle me-2"></i>تم صرف الدواء</h5>
                        <p><strong>تاريخ الصرف:</strong> <?php echo date('Y-m-d H:i', strtotime($dispense_info['dispense_date'])); ?></p>
                        <p><strong>الصيدلي:</strong> <?php echo $dispense_info['pharmacist_name']; ?></p>
                        <?php if ($dispense_info['notes']): ?>
                            <p><strong>ملاحظات الصيدلي:</strong> <?php echo $dispense_info['notes']; ?></p>
                        <?php endif; ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-info-circle me-2"></i>يمكنك استلام الدواء من صيدلية المستشفى بإظهار رقم الروشتة أو بطاقة الهوية.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="pending-info">
                        <h5><i class="fas fa-hourglass-half me-2"></i>في انتظار صرف الدواء</h5>
                        <p>لم يتم صرف الدواء بعد. سيقوم الصيدلي بمراجعة الروشتة وصرف الدواء قريباً.</p>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-info-circle me-2"></i>يمكنك متابعة حالة صرف الدواء من خلال هذه الصفحة أو من خلال صفحة "متابعة صرف العلاج".
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>