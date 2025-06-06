<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login and role before any output
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/config.php';

// Handle vacation approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $vacation_id = $_POST['vacation_id'];
    $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE doctor_vacations 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $vacation_id]);
        
        $_SESSION['success'] = "تم تحديث حالة الإجازة بنجاح";
    } catch (PDOException $e) {
        $_SESSION['error'] = "حدث خطأ في تحديث حالة الإجازة: " . $e->getMessage();
    }
    
    header('Location: vacations.php');
    exit();
}

// Get all vacation requests
try {
    $stmt = $pdo->prepare("
        SELECT v.*, d.first_name, d.last_name, dep.name as department_name
        FROM doctor_vacations v
        JOIN doctors d ON v.doctor_id = d.id
        LEFT JOIN departments dep ON d.department_id = dep.id
        ORDER BY v.start_date DESC
    ");
    $stmt->execute();
    $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Now include the header and start HTML output
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">إدارة إجازات الأطباء</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الطبيب</th>
                                    <th>القسم</th>
                                    <th>تاريخ البداية</th>
                                    <th>تاريخ النهاية</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacations as $vacation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vacation['first_name'] . ' ' . $vacation['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['department_name'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['end_date']); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['reason']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($vacation['status']) {
                                                case 'approved':
                                                    $status_class = 'text-success';
                                                    $status_text = 'موافق عليه';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'text-danger';
                                                    $status_text = 'مرفوض';
                                                    break;
                                                default:
                                                    $status_class = 'text-warning';
                                                    $status_text = 'قيد الانتظار';
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($vacation['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="vacation_id" value="<?php echo $vacation['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> موافقة
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> رفض
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">تمت المعالجة</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 