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

    // معالجة تحديث جدول العمل
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'update_schedule') {
            $day = $_POST['day'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $is_working = isset($_POST['is_working']) ? 1 : 0;

            // التحقق من وجود جدول لهذا اليوم
            $stmt = $pdo->prepare("SELECT id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?");
            $stmt->execute([$doctor['id'], $day]);
            $existing = $stmt->fetch();

            if ($existing) {
                // تحديث الجدول الموجود
                $stmt = $pdo->prepare("
                    UPDATE doctor_schedule 
                    SET start_time = ?, end_time = ?, is_working = ?
                    WHERE doctor_id = ? AND day_of_week = ?
                ");
                $stmt->execute([$start_time, $end_time, $is_working, $doctor['id'], $day]);
            } else {
                // إضافة جدول جديد
                $stmt = $pdo->prepare("
                    INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, is_working)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$doctor['id'], $day, $start_time, $end_time, $is_working]);
            }

            $_SESSION['success'] = "تم تحديث جدول العمل بنجاح";
        } 
        elseif ($_POST['action'] === 'add_vacation') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $reason = $_POST['reason'];

            $stmt = $pdo->prepare("
                INSERT INTO doctor_vacations (doctor_id, start_date, end_date, reason)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$doctor['id'], $start_date, $end_date, $reason]);

            $_SESSION['success'] = "تم إضافة طلب الإجازة بنجاح";
        }

        header('Location: schedule.php');
        exit();
    }

    // جلب جدول العمل الحالي
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedule WHERE doctor_id = ?");
    $stmt->execute([$doctor['id']]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الإجازات
    $stmt = $pdo->prepare("SELECT * FROM doctor_vacations WHERE doctor_id = ? ORDER BY start_date DESC");
    $stmt->execute([$doctor['id']]);
    $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// أيام الأسبوع
$days = [
    'sunday' => 'الأحد',
    'monday' => 'الإثنين',
    'tuesday' => 'الثلاثاء',
    'wednesday' => 'الأربعاء',
    'thursday' => 'الخميس',
    'friday' => 'الجمعة',
    'saturday' => 'السبت'
];
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <!-- Hospital Logo and Name -->
                <div class="text-center mb-4">
                    <h4 class="text-primary">مستشفى بارمستان المستقبل</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="patients.php">
                            <i class="fas fa-users"></i> المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="schedule.php">
                            <i class="fas fa-clock"></i> جدول العمل
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="profile.php">
                            <i class="fas fa-user-md"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">جدول العمل</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- جدول العمل -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">تحديد أوقات العمل</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_schedule">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>اليوم</th>
                                        <th>وقت البدء</th>
                                        <th>وقت الانتهاء</th>
                                        <th>حالة العمل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($days as $day_key => $day_name): 
                                        $day_schedule = array_filter($schedule, function($item) use ($day_key) {
                                            return $item['day_of_week'] === $day_key;
                                        });
                                        $day_schedule = !empty($day_schedule) ? reset($day_schedule) : null;
                                    ?>
                                        <tr>
                                            <td><?php echo $day_name; ?></td>
                                            <td>
                                                <input type="time" class="form-control" name="start_time" 
                                                       value="<?php echo $day_schedule ? $day_schedule['start_time'] : '09:00'; ?>" required>
                                            </td>
                                            <td>
                                                <input type="time" class="form-control" name="end_time" 
                                                       value="<?php echo $day_schedule ? $day_schedule['end_time'] : '17:00'; ?>" required>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_working" 
                                                           <?php echo (!$day_schedule || $day_schedule['is_working']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">متاح للعمل</label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="hidden" name="day" value="<?php echo $day_key; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>

            <!-- طلبات الإجازة -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">طلبات الإجازة</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="add_vacation">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">تاريخ بداية الإجازة</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">تاريخ نهاية الإجازة</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                            <div class="col-md-4">
                                <label for="reason" class="form-label">سبب الإجازة</label>
                                <input type="text" class="form-control" id="reason" name="reason" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">إضافة إجازة</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>تاريخ البداية</th>
                                    <th>تاريخ النهاية</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacations as $vacation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vacation['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['end_date']); ?></td>
                                        <td><?php echo htmlspecialchars($vacation['reason']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($vacation['status']) {
                                                case 'approved':
                                                    $status_class = 'text-success';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'text-danger';
                                                    break;
                                                default:
                                                    $status_class = 'text-warning';
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <?php
                                                switch ($vacation['status']) {
                                                    case 'approved':
                                                        echo 'موافق عليه';
                                                        break;
                                                    case 'rejected':
                                                        echo 'مرفوض';
                                                        break;
                                                    default:
                                                        echo 'قيد الانتظار';
                                                }
                                                ?>
                                            </span>
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