<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات الممرض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// معالجة إضافة سجل طبي جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_id = $_POST['appointment_id'] ?? null;
    $diagnosis = $_POST['diagnosis'] ?? '';
    $prescription = $_POST['prescription'] ?? '';
    $notes = $_POST['notes'] ?? '';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$patient_id, $doctor_id, $appointment_id, $diagnosis, $prescription, $notes]);
        $success = 'تم إضافة السجل الطبي بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء إضافة السجل الطبي';
    }
}

// جلب السجلات الطبية
try {
    $stmt = $pdo->query("
        SELECT mr.*, p.first_name as patient_first_name, p.last_name as patient_last_name,
               d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.id
        JOIN doctors d ON mr.doctor_id = d.id
        ORDER BY mr.created_at DESC
    ");
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'حدث خطأ أثناء جلب السجلات الطبية';
}

// جلب قائمة المرضى
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name");
    $patients = $stmt->fetchAll();
} catch (PDOException $e) {
    $patients = [];
}

// جلب قائمة الأطباء
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM doctors ORDER BY first_name");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $doctors = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة السجلات الطبية - مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">إضافة سجل طبي جديد</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">المريض</label>
                                    <select name="patient_id" class="form-select" required>
                                        <option value="">اختر المريض</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id']; ?>">
                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الطبيب</label>
                                    <select name="doctor_id" class="form-select" required>
                                        <option value="">اختر الطبيب</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['id']; ?>">
                                                <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رقم الموعد (اختياري)</label>
                                    <input type="number" name="appointment_id" class="form-control">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">التشخيص</label>
                                    <textarea name="diagnosis" class="form-control" rows="3" required></textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">الوصفة الطبية</label>
                                    <textarea name="prescription" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">ملاحظات</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>إضافة سجل طبي
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">السجلات الطبية</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>التاريخ</th>
                                        <th>التشخيص</th>
                                        <th>الوصفة الطبية</th>
                                        <th>ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                            <td><?php echo htmlspecialchars($record['prescription'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 