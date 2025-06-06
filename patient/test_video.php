<?php
session_start();
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

// معرف الطبيب (يمكن تحديده من خلال الرابط)
$doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : 1; // استخدم معرف الطبيب 1 افتراضيًا

// معلومات افتراضية للطبيب
$doctor = [
    'id' => $doctor_id,
    'first_name' => 'الطبيب',
    'last_name' => 'المعالج',
    'department_name' => 'قسم الطب العام',
    'email' => 'doctor@example.com',
    'phone' => '0123456789'
];

// محاولة جلب بيانات الطبيب إذا كان متاحًا
try {
    $doctor_query = "SELECT d.*, u.email, u.phone, dep.name as department_name
                     FROM doctors d
                     LEFT JOIN users u ON d.user_id = u.id
                     LEFT JOIN departments dep ON d.department_id = dep.id
                     WHERE d.id = ?";
    $doctor_stmt = $db->prepare($doctor_query);
    $doctor_stmt->execute([$doctor_id]);
    $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doctor_result) {
        $doctor = $doctor_result;
    }
} catch (Exception $e) {
    // استخدم البيانات الافتراضية في حالة حدوث خطأ
}

// جلب بيانات المريض
try {
    $patient_query = "SELECT p.* FROM patients p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE u.id = ?";
    $patient_stmt = $db->prepare($patient_query);
    $patient_stmt->execute([$_SESSION['user_id']]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // استمر دون بيانات المريض
}

// إنشاء معرف فريد للغرفة
$room_id = 'test_room_' . ($doctor['id'] ?? 'unknown') . '_' . (isset($patient['id']) ? $patient['id'] : 'unknown') . '_' . time();

// استدعاء ملف header.php
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
                <h1 class="h2">اختبار مكالمة فيديو مع <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="appointments.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> العودة إلى المواعيد
                    </a>
                </div>
            </div>

            <div class="alert alert-warning">
                <h5 class="alert-heading">وضع الاختبار!</h5>
                <p>هذه صفحة اختبار للمكالمات المرئية. تم تجاوز التحققات العادية للمواعيد المؤكدة والمدفوعة.</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5 class="mb-3">معلومات الطبيب:</h5>
                        <p class="mb-2">الاسم: <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?></p>
                        <p class="mb-2">التخصص: <?php echo htmlspecialchars($doctor['department_name'] ?? 'غير متوفر'); ?></p>
                        <p class="mb-2">البريد الإلكتروني: <?php echo htmlspecialchars($doctor['email'] ?? 'غير متوفر'); ?></p>
                        <p class="mb-2">رقم الهاتف: <?php echo htmlspecialchars($doctor['phone'] ?? 'غير متوفر'); ?></p>
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
        </main>
    </div>
</div>

<?php require_once 'footer.php'; ?> 