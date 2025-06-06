<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// معالجة إضافة سيارة إسعاف جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ambulance'])) {
    $plate_number = $_POST['plate_number'] ?? '';
    $driver_name = $_POST['driver_name'] ?? '';
    $driver_phone = $_POST['driver_phone'] ?? '';

    if (empty($plate_number) || empty($driver_name) || empty($driver_phone)) {
        $error = 'الرجاء ملء جميع الحقول المطلوبة';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ambulances (plate_number, driver_name, driver_phone) VALUES (?, ?, ?)");
            $stmt->execute([$plate_number, $driver_name, $driver_phone]);
            $success = 'تم إضافة سيارة الإسعاف بنجاح';
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء إضافة سيارة الإسعاف';
        }
    }
}

// معالجة تحديث حالة سيارة الإسعاف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ambulance_id = $_POST['ambulance_id'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE ambulances SET status = ? WHERE id = ?");
        $stmt->execute([$status, $ambulance_id]);
        $success = 'تم تحديث حالة سيارة الإسعاف بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء تحديث حالة سيارة الإسعاف';
    }
}

// جلب جميع سيارات الإسعاف
try {
    $stmt = $pdo->query("SELECT * FROM ambulances ORDER BY status, plate_number");
    $ambulances = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'حدث خطأ أثناء جلب سيارات الإسعاف';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة سيارات الإسعاف - مستشفى المستقبل</title>
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
        .status-available { color: #28a745; }
        .status-busy { color: #dc3545; }
        .status-maintenance { color: #ffc107; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">إضافة سيارة إسعاف جديدة</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="plate_number" class="form-label">رقم اللوحة</label>
                                <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                            </div>

                            <div class="mb-3">
                                <label for="driver_name" class="form-label">اسم السائق</label>
                                <input type="text" class="form-control" id="driver_name" name="driver_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="driver_phone" class="form-label">رقم هاتف السائق</label>
                                <input type="text" class="form-control" id="driver_phone" name="driver_phone" required>
                            </div>

                            <button type="submit" name="add_ambulance" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>إضافة سيارة إسعاف
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">قائمة سيارات الإسعاف</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>رقم اللوحة</th>
                                        <th>اسم السائق</th>
                                        <th>رقم الهاتف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ambulances as $ambulance): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ambulance['plate_number']); ?></td>
                                            <td><?php echo htmlspecialchars($ambulance['driver_name']); ?></td>
                                            <td><?php echo htmlspecialchars($ambulance['driver_phone']); ?></td>
                                            <td class="status-<?php echo $ambulance['status']; ?>">
                                                <?php
                                                $statuses = [
                                                    'available' => 'متاحة',
                                                    'busy' => 'مشغولة',
                                                    'maintenance' => 'صيانة'
                                                ];
                                                echo $statuses[$ambulance['status']] ?? $ambulance['status'];
                                                ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="ambulance_id" value="<?php echo $ambulance['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm">
                                                        <option value="available" <?php echo $ambulance['status'] === 'available' ? 'selected' : ''; ?>>متاحة</option>
                                                        <option value="busy" <?php echo $ambulance['status'] === 'busy' ? 'selected' : ''; ?>>مشغولة</option>
                                                        <option value="maintenance" <?php echo $ambulance['status'] === 'maintenance' ? 'selected' : ''; ?>>صيانة</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary mt-1">تحديث</button>
                                                </form>
                                            </td>
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