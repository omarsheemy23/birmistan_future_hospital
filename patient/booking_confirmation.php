<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من تسجيل الدخول وأن المستخدم مريض
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// الحصول على معرف الموعد من الرابط أو من الجلسة
$appointment_id = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : 
                 (isset($_SESSION['appointment_id']) ? $_SESSION['appointment_id'] : null);

if (!$appointment_id) {
    $_SESSION['error_message'] = "لم يتم تحديد معرف الموعد";
    header("Location: appointments.php");
    exit();
}

// إذا لم يكن هناك رسالة نجاح، قم بتعيينها
if (!isset($_SESSION['success_message'])) {
    $_SESSION['success_message'] = "تم حجز الموعد وبانتظار عملية الدفع";
}

// نتأكد من وجود رسوم
if (!isset($_SESSION['consultation_fee'])) {
    $_SESSION['consultation_fee'] = 300; // قيمة افتراضية
}

require_once 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5 text-center">
                    <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                    
                    <h2 class="text-center mb-3">تم حجز الموعد بنجاح!</h2>
                    
                    <div class="alert alert-success mb-4">
                        <h4 class="alert-heading">تم حجز الموعد وبانتظار عملية الدفع</h4>
                        <p>سيتم تحويلك إلى صفحة الدفع خلال ثوانٍ... أو يمكنك النقر على الزر أدناه للانتقال مباشرة</p>
                    </div>
                    
                    <div class="progress mb-4">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                    </div>

                    <div class="mt-4">
                        <a href="payment_new.php?appointment_id=<?php echo $appointment_id; ?>" class="btn btn-primary btn-lg">
                            الانتقال إلى صفحة الدفع الآن
                        </a>
                        <a href="appointments.php" class="btn btn-outline-secondary btn-lg ms-2">
                            العودة إلى المواعيد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // تقدم شريط التحميل تدريجياً
    let width = 0;
    const interval = 50; // سرعة التقدم
    const progressBar = document.getElementById('progressBar');
    
    const progress = setInterval(function() {
        width += 1;
        progressBar.style.width = width + '%';
        
        if (width >= 100) {
            clearInterval(progress);
            // الانتقال إلى صفحة الدفع
            window.location.href = 'payment_new.php?appointment_id=<?php echo $appointment_id; ?>';
        }
    }, interval);
    
    // توقف العد عند وضع المؤشر على الأزرار
    document.querySelectorAll('a.btn').forEach(button => {
        button.addEventListener('mouseenter', function() {
            clearInterval(progress);
        });
    });
</script>

<?php require_once 'footer.php'; ?> 