<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once 'header.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// معالجة طلب الإسعاف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // الحصول على معرف المريض من جدول المرضى
    try {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            $error = 'لم يتم العثور على بيانات المريض';
        } else {
            $patient_id = $patient['id'];
            $pickup_location = $_POST['pickup_location'] ?? '';
            $destination = $_POST['destination'] ?? '';
            $emergency_level = $_POST['emergency_level'] ?? '';
            $patient_condition = $_POST['patient_condition'] ?? '';
            $additional_notes = $_POST['additional_notes'] ?? '';

            if (empty($pickup_location) || empty($destination) || empty($emergency_level)) {
                $error = 'الرجاء ملء جميع الحقول المطلوبة';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO ambulance_requests (patient_id, pickup_location, destination, emergency_level, patient_condition, additional_notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$patient_id, $pickup_location, $destination, $emergency_level, $patient_condition, $additional_notes]);
                    $success = 'تم إرسال طلب الإسعاف بنجاح';
                } catch (PDOException $e) {
                    $error = 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage();
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء جلب بيانات المريض: ' . $e->getMessage();
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">طلب سيارة إسعاف</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="pickup_location" class="form-label">موقع الاستلام</label>
                            <input type="text" class="form-control" id="pickup_location" name="pickup_location" required>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">الوجهة</label>
                            <input type="text" class="form-control" id="destination" name="destination" required>
                        </div>

                        <div class="mb-3">
                            <label for="emergency_level" class="form-label">مستوى الطوارئ</label>
                            <select class="form-select" id="emergency_level" name="emergency_level" required>
                                <option value="low">منخفض</option>
                                <option value="medium">متوسط</option>
                                <option value="high">عالي</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="patient_condition" class="form-label">حالة المريض</label>
                            <textarea class="form-control" id="patient_condition" name="patient_condition" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="additional_notes" class="form-label">ملاحظات إضافية</label>
                            <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3"></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-ambulance me-2"></i>إرسال الطلب
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>