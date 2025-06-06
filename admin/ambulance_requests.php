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
$requests = []; // Initialize as empty array

// معالجة تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $ambulance_id = $_POST['ambulance_id'] ?? null;

    try {
        $stmt = $pdo->prepare("UPDATE ambulance_requests SET status = ?, assigned_ambulance_id = ? WHERE id = ?");
        $stmt->execute([$status, $ambulance_id, $request_id]);
        $success = 'تم تحديث حالة الطلب بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء تحديث حالة الطلب';
    }
}

// جلب جميع طلبات الإسعاف
try {
    $stmt = $pdo->query("
        SELECT ar.*, p.first_name, p.last_name, a.plate_number, a.driver_name, a.driver_phone
        FROM ambulance_requests ar
        LEFT JOIN patients p ON ar.patient_id = p.id
        LEFT JOIN ambulances a ON ar.assigned_ambulance_id = a.id
        ORDER BY ar.request_date DESC
    ");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'حدث خطأ أثناء جلب طلبات الإسعاف: ' . $e->getMessage();
}

// جلب سيارات الإسعاف المتاحة
try {
    $stmt = $pdo->query("SELECT * FROM ambulances WHERE status = 'available'");
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
    <title>إدارة طلبات الإسعاف - مستشفى المستقبل</title>
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
        .status-pending { color: #ffc107; }
        .status-accepted { color: #28a745; }
        .status-rejected { color: #dc3545; }
        .status-completed { color: #17a2b8; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">إدارة طلبات الإسعاف</h4>
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
                                        <th>رقم الطلب</th>
                                        <th>المريض</th>
                                        <th>موقع الاستلام</th>
                                        <th>الوجهة</th>
                                        <th>مستوى الطوارئ</th>
                                        <th>حالة المريض</th>
                                        <th>الحالة</th>
                                        <th>سيارة الإسعاف</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['pickup_location']); ?></td>
                                            <td><?php echo htmlspecialchars($request['destination']); ?></td>
                                            <td>
                                                <?php
                                                $emergency_levels = [
                                                    'low' => 'منخفض',
                                                    'medium' => 'متوسط',
                                                    'high' => 'عالي'
                                                ];
                                                echo $emergency_levels[$request['emergency_level']] ?? $request['emergency_level'];
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['patient_condition']); ?></td>
                                            <td class="status-<?php echo $request['status']; ?>">
                                                <?php
                                                $statuses = [
                                                    'pending' => 'قيد الانتظار',
                                                    'accepted' => 'مقبول',
                                                    'rejected' => 'مرفوض',
                                                    'completed' => 'مكتمل'
                                                ];
                                                echo $statuses[$request['status']] ?? $request['status'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($request['plate_number']): ?>
                                                    <?php echo htmlspecialchars($request['plate_number']); ?>
                                                    (<?php echo htmlspecialchars($request['driver_name']); ?>)
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm mb-2">
                                                        <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                        <option value="accepted" <?php echo $request['status'] === 'accepted' ? 'selected' : ''; ?>>مقبول</option>
                                                        <option value="rejected" <?php echo $request['status'] === 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
                                                        <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                                                    </select>
                                                    <select name="ambulance_id" class="form-select form-select-sm mb-2">
                                                        <option value="">اختر سيارة إسعاف</option>
                                                        <?php foreach ($ambulances as $ambulance): ?>
                                                            <option value="<?php echo $ambulance['id']; ?>" <?php echo $request['assigned_ambulance_id'] == $ambulance['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($ambulance['plate_number']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary">تحديث</button>
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