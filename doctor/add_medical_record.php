<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// التحقق من وجود معرف المريض
if (!isset($_GET['patient_id'])) {
    header('Location: appointments.php');
    exit();
}

$patient_id = $_GET['patient_id'];

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("
        SELECT d.*, dep.name as department_name
        FROM doctors d
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب بيانات المريض
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM patients p
        WHERE p.id = ?
    ");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // معالجة إرسال النموذج
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $diagnosis = $_POST['diagnosis'] ?? '';
        $prescription = $_POST['prescription'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if (empty($diagnosis)) {
            throw new Exception("يرجى إدخال التشخيص");
        }

        // إضافة السجل الطبي
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (
                patient_id, 
                doctor_id, 
                diagnosis, 
                prescription, 
                notes, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $patient_id,
            $doctor['id'],
            $diagnosis,
            $prescription,
            $notes
        ]);

        header('Location: medical_records.php?success=1');
        exit();
    }

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// تضمين ملف الهيدر بعد عمليات إعادة التوجيه
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">إضافة سجل طبي</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">بيانات المريض</h5>
                    <p class="card-text">
                        <strong>الاسم:</strong> <?php echo htmlspecialchars($patient['name'] ?? ''); ?><br>
                        <strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($patient['email'] ?? ''); ?><br>
                        <strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($patient['phone'] ?? ''); ?>
                    </p>
                </div>
            </div>

            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="diagnosis" class="form-label">التشخيص</label>
                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="prescription" class="form-label">الوصفة الطبية</label>
                    <textarea class="form-control" id="prescription" name="prescription" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">ملاحظات إضافية</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">حفظ السجل الطبي</button>
                    <a href="medical_records.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 