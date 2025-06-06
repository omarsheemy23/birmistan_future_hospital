<?php
session_start();
require_once '../includes/config.php';

// استدعاء ملف الهيدر
include('header.php');

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT d.*, u.*, dep.name as department_name 
                          FROM doctors d 
                          JOIN users u ON d.user_id = u.id 
                          JOIN departments dep ON d.department_id = dep.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب السجلات الطبية للمرضى
    $query = "
        SELECT DISTINCT p.*, u.email, u.phone,
               COUNT(mr.id) as total_records,
               MAX(mr.created_at) as last_record_date
        FROM patients p
        JOIN users u ON p.user_id = u.id
        JOIN appointments a ON a.patient_id = p.id
        LEFT JOIN medical_records mr ON mr.patient_id = p.id
        WHERE a.doctor_id = ?
        GROUP BY p.id
        ORDER BY last_record_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$doctor['id']]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
    $patients = [];
} catch (Exception $e) {
    $error = $e->getMessage();
    $patients = [];
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar removed since there's already a navbar -->
        <!-- Main Content -->
        <main class="col-12 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                <h1 class="h2">السجلات الطبية</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($patients)): ?>
                <div class="alert alert-info">لا توجد سجلات طبية متاحة</div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم المريض</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>رقم الهاتف</th>
                                        <th>عدد السجلات</th>
                                        <th>آخر سجل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['email'] ?? 'غير متوفر'); ?></td>
                                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'غير متوفر'); ?></td>
                                            <td><?php echo $patient['total_records']; ?></td>
                                            <td><?php echo $patient['last_record_date'] ? date('Y-m-d', strtotime($patient['last_record_date'])) : 'لا يوجد'; ?></td>
                                            <td>
                                                <a href="view_medical_records.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> عرض السجلات
                                                </a>
                                                <a href="add_medical_record.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i> إضافة سجل
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>