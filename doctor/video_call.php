<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // التحقق من وجود معرف الموعد أو المريض
    if (empty($_GET['appointment_id']) && empty($_GET['patient_id'])) {
        throw new Exception("يرجى تحديد الموعد أو المريض للبدء في مكالمة الفيديو. الرجاء العودة واختيار موعد من قائمة المواعيد.");
    }

    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT d.* FROM doctors d 
                          JOIN users u ON d.user_id = u.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب في النظام. يرجى التواصل مع إدارة النظام.");
    }

    if (!empty($_GET['appointment_id'])) {
        // جلب بيانات الموعد والمريض
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                p.id as patient_id,
                p.first_name,
                p.last_name,
                u.email,
                u.phone
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE a.id = ? AND a.doctor_id = ?
        ");
        $stmt->execute([$_GET['appointment_id'], $doctor['id']]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception("لم يتم العثور على الموعد المحدد. يرجى التأكد من صحة رقم الموعد والمحاولة مرة أخرى.");
        }

        // التحقق من حالة الدفع
        if ($appointment['payment_status'] !== 'paid') {
            throw new Exception("لا يمكن بدء المكالمة - لم يتم دفع رسوم الموعد بعد.");
        }

        // التحقق من حالة الموعد
        if ($appointment['status'] !== 'confirmed') {
            throw new Exception("لا يمكن بدء المكالمة - الموعد غير مؤكد. يجب تأكيد الموعد أولاً.");
        }

        $patient_id = $appointment['patient_id'];
        $patient_name = $appointment['first_name'] . ' ' . $appointment['last_name'];
    } else {
        // جلب بيانات المريض مباشرة
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                u.email,
                u.phone
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$_GET['patient_id']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            throw new Exception("لم يتم العثور على بيانات المريض المحدد. يرجى التأكد من صحة رقم المريض والمحاولة مرة أخرى.");
        }

        // التحقق من وجود موعد مؤكد ومدفوع لهذا المريض
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_valid_appointment
            FROM appointments 
            WHERE patient_id = ? 
            AND doctor_id = ? 
            AND payment_status = 'paid' 
            AND status = 'confirmed'
        ");
        $stmt->execute([$patient['id'], $doctor['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // إزالة شرط التحقق من وجود موعد لنفس اليوم
        // السماح بإجراء مكالمة فيديو مع أي مريض له سجل موعد مع هذا الطبيب
        if ($result['has_valid_appointment'] == 0) {
            // بدلاً من رفض طلب المكالمة، نعرض تحذيراً
            $warning = "تنبيه: لا يوجد موعد مؤكد ومدفوع لهذا المريض اليوم. سيتم السماح بإجراء المكالمة.";
        }

        $patient_id = $patient['id'];
        $patient_name = $patient['first_name'] . ' ' . $patient['last_name'];
    }

    // إنشاء معرف فريد للغرفة
    $room_id = 'room_' . $doctor['id'] . '_' . $patient_id . '_' . time();

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكالمة فيديو - مستشفى بارمستان المستقبل</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar removed since there's already a navbar -->
            <!-- Main Content -->
            <main class="col-12 px-md-4 pt-3">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                    <h1 class="h2">مكالمة فيديو مع <?php echo htmlspecialchars($patient_name ?? ''); ?></h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">حدث خطأ!</h5>
                        <p><?php echo $error; ?></p>
                        <hr>
                        <p class="mb-0">
                            <a href="appointments.php" class="btn btn-outline-danger">
                                <i class="fas fa-arrow-right"></i> العودة إلى قائمة المواعيد
                            </a>
                        </p>
                    </div>
                <?php elseif (isset($warning)): ?>
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">تنبيه!</h5>
                        <p><?php echo $warning; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!isset($error)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div id="video-container" class="row">
                                <div class="col-md-6">
                                    <div class="video-box">
                                        <h4>أنت</h4>
                                        <video id="localVideo" autoplay muted playsinline class="img-fluid"></video>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="video-box">
                                        <h4>المريض</h4>
                                        <video id="remoteVideo" autoplay playsinline class="img-fluid"></video>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button id="startCall" class="btn btn-success">
                                    <i class="fas fa-phone"></i> بدء المكالمة
                                </button>
                                <button id="endCall" class="btn btn-danger" style="display: none;">
                                    <i class="fas fa-phone-slash"></i> إنهاء المكالمة
                                </button>
                            </div>
                        </div>
                    </div>

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
                                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
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
                    </style>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 