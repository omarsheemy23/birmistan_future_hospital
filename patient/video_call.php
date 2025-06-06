<?php
session_start();

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// التحقق من وجود معرف الموعد أو الطبيب
$appointment_id = null;
$doctor_id = null;
$error = null;
$appointment = null;
$doctor = [
    'first_name' => '',
    'last_name' => '',
    'department_name' => '',
    'email' => '',
    'phone' => ''
];

if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
} elseif (isset($_GET['doctor_id'])) {
    $doctor_id = $_GET['doctor_id'];
} else {
    $error = "لم يتم تحديد الطبيب أو الموعد";
    $_SESSION['error'] = $error;
    header('Location: appointments.php');
    exit();
}

try {
    // جلب بيانات المريض
    $patient_query = "SELECT p.* FROM patients p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE u.id = ?";
    $patient_stmt = $db->prepare($patient_query);
    $patient_stmt->execute([$_SESSION['user_id']]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // إذا تم تحديد معرف الموعد، تحقق من حالة الموعد والدفع
    if ($appointment_id) {
        // طباعة معلومات تصحيح للتطوير
        error_log("Checking appointment ID: " . $appointment_id . " for patient ID: " . $patient['id']);
        
        $appointment_query = "SELECT a.*, d.first_name, d.last_name, d.user_id as doctor_user_id, 
                              dep.name as department_name, u.email, u.phone
                              FROM appointments a
                              JOIN doctors d ON a.doctor_id = d.id
                              JOIN departments dep ON d.department_id = dep.id
                              JOIN users u ON d.user_id = u.id
                              WHERE a.id = ? AND a.patient_id = ? 
                              AND a.status = 'confirmed' AND a.payment_status = 'paid'";
                              // تم إزالة شرط التاريخ (CURDATE) للسماح بالمكالمات للمواعيد المؤكدة بغض النظر عن التاريخ
        
        $appointment_stmt = $db->prepare($appointment_query);
        $appointment_stmt->execute([$appointment_id, $patient['id']]);
        $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
        
        // سجل نتائج الاستعلام للتصحيح
        error_log("Appointment query result: " . ($appointment ? "Found" : "Not found"));
        if ($appointment) {
            error_log("Appointment details: Date=" . $appointment['appointment_date'] . ", Status=" . $appointment['status'] . ", Payment=" . $appointment['payment_status']);
        }

        if (!$appointment) {
            // تحقق من وجود الموعد بغض النظر عن الحالة للمساعدة في التصحيح
            $check_query = "SELECT a.id, a.status, a.payment_status, a.appointment_date 
                           FROM appointments a 
                           WHERE a.id = ? AND a.patient_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$appointment_id, $patient['id']]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($check_result) {
                error_log("Appointment exists but not confirmed/paid: Status=" . $check_result['status'] . ", Payment=" . $check_result['payment_status'] . ", Date=" . $check_result['appointment_date']);
                throw new Exception("لا يمكن بدء المكالمة - الموعد موجود ولكن حالته (" . $check_result['status'] . ") أو حالة الدفع (" . $check_result['payment_status'] . ")");
            } else {
                error_log("No appointment found with ID: " . $appointment_id . " for patient ID: " . $patient['id']);
                throw new Exception("لا يمكن بدء المكالمة - لا يوجد موعد مؤكد ومدفوع لهذا المريض");
            }
        }

        $doctor_id = $appointment['doctor_id'];
    } else {
        // إذا تم تحديد معرف الطبيب فقط، تحقق من وجود موعد مؤكد ومدفوع لهذا اليوم
        $appointment_query = "SELECT a.*, d.first_name, d.last_name, d.user_id as doctor_user_id, 
                              dep.name as department_name, u.email, u.phone
                              FROM appointments a
                              JOIN doctors d ON a.doctor_id = d.id
                              JOIN departments dep ON d.department_id = dep.id
                              JOIN users u ON d.user_id = u.id
                              WHERE a.doctor_id = ? AND a.patient_id = ? 
                              AND a.status = 'confirmed' AND a.payment_status = 'paid'
                              ORDER BY a.appointment_date DESC LIMIT 1";
                              // تم تغيير الاستعلام للحصول على أحدث موعد مؤكد ومدفوع
        
        $appointment_stmt = $db->prepare($appointment_query);
        $appointment_stmt->execute([$doctor_id, $patient['id']]);
        $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception("لا يمكن بدء المكالمة - لا يوجد موعد مؤكد ومدفوع مع هذا الطبيب");
        }
    }

    // جلب بيانات الطبيب
    $doctor_query = "SELECT d.*, u.email, u.phone, dep.name as department_name
                     FROM doctors d
                     JOIN users u ON d.user_id = u.id
                     JOIN departments dep ON d.department_id = dep.id
                     WHERE d.id = ?";
    $doctor_stmt = $db->prepare($doctor_query);
    $doctor_stmt->execute([$doctor_id]);
    $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);

    if ($doctor_result) {
        $doctor = $doctor_result;
    } else {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // إنشاء معرف فريد للغرفة
    $room_id = 'room_' . ($doctor['id'] ?? 'unknown') . '_' . ($patient['id'] ?? 'unknown') . '_' . time();

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// الآن بعد التحقق من كل شيء، قم باستدعاء ملف header.php
require_once 'header.php';
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
                            <i class="fas fa-calendar-alt"></i> مواعيدي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> سجلاتي الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="doctors.php">
                            <i class="fas fa-user-md"></i> الأطباء
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
                <h1 class="h2">مكالمة فيديو مع <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="doctors.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> العودة إلى الأطباء
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <h5 class="alert-heading">حدث خطأ!</h5>
                    <p><?php echo $error; ?></p>
                    <hr>
                    <p class="mb-0">
                        <a href="doctors.php" class="btn btn-outline-danger">
                            <i class="fas fa-arrow-right"></i> العودة إلى قائمة الأطباء
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h5 class="mb-3">معلومات الطبيب:</h5>
                            <p class="mb-2">الاسم: <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?></p>
                            <p class="mb-2">التخصص: <?php echo htmlspecialchars($doctor['department_name'] ?? ''); ?></p>
                            <p class="mb-2">البريد الإلكتروني: <?php echo htmlspecialchars($doctor['email'] ?? ''); ?></p>
                            <p class="mb-2">رقم الهاتف: <?php echo htmlspecialchars($doctor['phone'] ?? ''); ?></p>
                        </div>

                        <div id="video-container" class="row">
                            <div class="col-md-6">
                                <div class="video-box">
                                    <h4>أنت</h4>
                                    <video id="localVideo" autoplay muted playsinline class="img-fluid"></video>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="video-box">
                                    <h4>الطبيب</h4>
                                    <video id="remoteVideo" autoplay playsinline class="img-fluid"></video>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button id="startCall" class="btn btn-success btn-lg">
                                <i class="fas fa-phone"></i> بدء المكالمة
                            </button>
                            <button id="endCall" class="btn btn-danger btn-lg" style="display: none;">
                                <i class="fas fa-phone-slash"></i> إنهاء المكالمة
                            </button>
                        </div>
                    </div>
                </div>

                <style>
                    .video-box {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                    }
                    video {
                        width: 100%;
                        max-height: 400px;
                        background: #000;
                        border-radius: 4px;
                    }
                    .controls {
                        margin-top: 15px;
                    }
                    .btn-lg {
                        padding: 15px 30px;
                        font-size: 1.2rem;
                    }
                </style>

                <script>
                    // تخزين معرف الغرفة
                    const roomId = '<?php echo $room_id; ?>';
                    
                    // إعداد WebRTC
                    let localStream;
                    let remoteStream;
                    let peerConnection;

                    // بدء المكالمة
                    document.getElementById('startCall').addEventListener('click', async () => {
                        try {
                            localStream = await navigator.mediaDevices.getUserMedia({ 
                                video: true, 
                                audio: true 
                            });
                            document.getElementById('localVideo').srcObject = localStream;
                            
                            // إخفاء زر البدء وإظهار زر الإنهاء
                            document.getElementById('startCall').style.display = 'none';
                            document.getElementById('endCall').style.display = 'inline-block';
                            
                            // إعداد اتصال WebRTC
                            initializeWebRTC();
                        } catch (error) {
                            console.error('Error accessing media devices:', error);
                            alert('حدث خطأ في الوصول إلى الكاميرا والميكروفون');
                        }
                    });

                    // إنهاء المكالمة
                    document.getElementById('endCall').addEventListener('click', () => {
                        if (localStream) {
                            localStream.getTracks().forEach(track => track.stop());
                        }
                        if (peerConnection) {
                            peerConnection.close();
                        }
                        document.getElementById('localVideo').srcObject = null;
                        document.getElementById('remoteVideo').srcObject = null;
                        document.getElementById('startCall').style.display = 'inline-block';
                        document.getElementById('endCall').style.display = 'none';
                    });

                    function initializeWebRTC() {
                        // هنا يتم إضافة كود WebRTC
                        // يمكن استخدام مكتبة مثل PeerJS أو إعداد خادم STUN/TURN
                    }
                </script>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'footer.php'; ?>