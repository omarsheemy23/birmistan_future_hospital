<?php
// تفعيل عرض الأخطاء لتسهيل تشخيص المشكلات
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// التحقق من تسجيل دخول المريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// الحصول على معرف المريض
$user_id = $_SESSION['user_id'];
$patient_query = "SELECT id, first_name, last_name FROM patients WHERE user_id = :user_id";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bindParam(':user_id', $user_id);
$patient_stmt->execute();
$patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// استعلام لجلب الروشتات مع حالة الصرف
$query = "SELECT p.id as prescription_id, p.prescription_date as date, p.status, 
          d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization,
          CASE WHEN dm.id IS NULL THEN 'قيد المراجعة' ELSE 'تم الصرف' END as dispense_status,
          dm.dispense_date, CONCAT(ph.first_name, ' ', ph.last_name) as pharmacist_name
          FROM prescriptions p
          JOIN doctors d ON p.doctor_id = d.id
          LEFT JOIN dispensed_medicines dm ON p.id = dm.prescription_id
          LEFT JOIN pharmacists ph ON dm.pharmacist_id = ph.id
          WHERE p.patient_id = :patient_id
          ORDER BY p.prescription_date DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':patient_id', $patient_id);
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>
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
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    .timeline-item {
        position: relative;
        padding-left: 40px;
        margin-bottom: 30px;
    }
    .timeline-item:before {
        content: '';
        position: absolute;
        top: 0;
        left: 10px;
        height: 100%;
        width: 2px;
        background-color: #ddd;
    }
    .timeline-item:last-child:before {
        height: 0;
    }
    .timeline-badge {
        position: absolute;
        top: 0;
        left: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        text-align: center;
        color: white;
        background-color: #2a5298;
        z-index: 1;
    }
    .timeline-badge.pending {
        background-color: #ffc107;
    }
    .timeline-badge.completed {
        background-color: #28a745;
    }
    .timeline-content {
        padding: 15px;
        border-radius: 10px;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .timeline-title {
        margin-top: 0;
        color: #2a5298;
    }
    .timeline-date {
        color: #666;
        font-size: 0.9rem;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
    .empty-state {
        text-align: center;
        padding: 50px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 20px;
    }
    .empty-state h4 {
        color: #666;
        margin-bottom: 15px;
    }
</style>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-pills me-2"></i>متابعة صرف العلاج</h2>
            <a href="my_prescriptions.php" class="btn btn-primary">
                <i class="fas fa-prescription me-1"></i> عرض جميع الروشتات
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user me-2"></i>معلومات المريض
            </div>
            <div class="card-body">
                <h5><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></h5>
                <p><strong>رقم المريض:</strong> <?php echo $patient_id; ?></p>
            </div>
        </div>

        <?php if (count($prescriptions) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-2"></i>حالة صرف الأدوية
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($prescriptions as $prescription): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge <?php echo $prescription['dispense_status'] == 'تم الصرف' ? 'completed' : 'pending'; ?>">
                                    <i class="fas <?php echo $prescription['dispense_status'] == 'تم الصرف' ? 'fa-check' : 'fa-hourglass-half'; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="timeline-title">روشتة #<?php echo $prescription['prescription_id']; ?></h5>
                                        <?php if ($prescription['dispense_status'] == 'تم الصرف'): ?>
                                            <span class="badge bg-success status-badge">تم الصرف</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning status-badge">قيد المراجعة</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="timeline-date">تاريخ الروشتة: <?php echo date('Y-m-d', strtotime($prescription['date'])); ?></p>
                                    <p><strong>الطبيب:</strong> د. <?php echo $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']; ?></p>
                                    <p><strong>التخصص:</strong> <?php echo $prescription['specialization']; ?></p>
                                    
                                    <?php if ($prescription['dispense_status'] == 'تم الصرف'): ?>
                                        <div class="alert alert-success">
                                            <p><strong>تاريخ الصرف:</strong> <?php echo date('Y-m-d H:i', strtotime($prescription['dispense_date'])); ?></p>
                                            <p><strong>الصيدلي:</strong> <?php echo $prescription['pharmacist_name']; ?></p>
                                            <p>يمكنك استلام الدواء من صيدلية المستشفى بإظهار رقم الروشتة أو بطاقة الهوية.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <p>الروشتة قيد المراجعة من قبل الصيدلي. سيتم إشعارك عند صرف الدواء.</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-end">
                                        <a href="prescription_details.php?id=<?php echo $prescription['prescription_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> عرض التفاصيل
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body empty-state">
                    <i class="fas fa-prescription"></i>
                    <h4>لا توجد روشتات طبية</h4>
                    <p>لم يقم الطبيب بإضافة أي روشتات طبية لك بعد.</p>
                    <a href="appointments.php" class="btn btn-primary">
                        <i class="fas fa-calendar-check me-1"></i> حجز موعد جديد
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>