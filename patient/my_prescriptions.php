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
$patient_query = "SELECT id FROM patients WHERE user_id = :user_id";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bindParam(':user_id', $user_id);
$patient_stmt->execute();
$patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// تحديد حالة الروشتة للفلترة (الافتراضي: جميع الروشتات)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// بناء استعلام SQL بناءً على الفلتر
$query = "SELECT p.id as prescription_id, p.prescription_date as date, p.status, p.notes, 
          d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization
          FROM prescriptions p
          JOIN doctors d ON p.doctor_id = d.id
          WHERE p.patient_id = :patient_id";

if ($status_filter == 'open') {
    $query .= " AND p.status = 'مفتوحة'";
} elseif ($status_filter == 'closed') {
    $query .= " AND p.status = 'مغلقة'";
}

$query .= " ORDER BY p.prescription_date DESC";

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
    .filter-btn {
        margin-right: 5px;
    }
    .filter-btn.active {
        background-color: #1e3c72;
        border-color: #1e3c72;
    }
    .prescription-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .prescription-card:hover {
        transform: translateY(-5px);
    }
    .prescription-card .card-body {
        padding: 15px;
    }
    .prescription-card .card-title {
        color: #2a5298;
        font-weight: bold;
    }
    .prescription-card .badge {
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
            <h2><i class="fas fa-prescription me-2"></i>روشتاتي الطبية</h2>
            <div>
                <a href="my_prescriptions.php" class="btn btn-primary filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    جميع الروشتات
                </a>
                <a href="my_prescriptions.php?status=open" class="btn btn-primary filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">
                    الروشتات المفتوحة
                </a>
                <a href="my_prescriptions.php?status=closed" class="btn btn-primary filter-btn <?php echo $status_filter == 'closed' ? 'active' : ''; ?>">
                    الروشتات المغلقة
                </a>
            </div>
        </div>

        <?php if (count($prescriptions) > 0): ?>
            <div class="row">
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card prescription-card" onclick="window.location.href='prescription_details.php?id=<?php echo $prescription['prescription_id']; ?>'">
                            <div class="card-body">
                                <h5 class="card-title">روشتة #<?php echo $prescription['prescription_id']; ?></h5>
                                <p class="card-text">
                                    <strong>الطبيب:</strong> د. <?php echo $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']; ?><br>
                                    <strong>التخصص:</strong> <?php echo $prescription['specialization']; ?><br>
                                    <strong>التاريخ:</strong> <?php echo date('Y-m-d', strtotime($prescription['date'])); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($prescription['status'] == 'مفتوحة'): ?>
                                        <span class="badge bg-warning">مفتوحة</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">مغلقة</span>
                                    <?php endif; ?>
                                    <a href="prescription_details.php?id=<?php echo $prescription['prescription_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i> عرض
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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