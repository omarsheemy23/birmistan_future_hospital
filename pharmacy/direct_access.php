<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الوصول السريع - مستشفى بيرمستان المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap');
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
            padding: 40px 0;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: #1e3c72;
            color: white;
            font-weight: bold;
            padding: 15px 20px;
            font-size: 1.2rem;
        }
        .option-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .option-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .bg-gradient-primary {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
        }
        .text-info {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm mb-5">
                    <div class="card-header bg-gradient-primary">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clinic-medical me-2"></i>
                            <span>مستشفى بيرمستان المستقبل - الوصول السريع لنظام الصيدلية</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>مرحباً!</strong> يمكنك استخدام أحد الخيارات أدناه للوصول إلى نظام الصيدلية.
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card option-card h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="option-icon text-primary">
                                            <i class="fas fa-desktop"></i>
                                        </div>
                                        <h5 class="card-title">النسخة المستقلة</h5>
                                        <p class="card-text text-muted mb-4">واجهة كاملة بدون الحاجة لتسجيل الدخول أو اتصال بقاعدة البيانات</p>
                                        <a href="standalone.php" class="btn btn-primary px-4">
                                            <i class="fas fa-arrow-right me-2"></i>فتح النسخة المستقلة
                                        </a>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-info">الأفضل للاستخدام: مناسب للعرض وتجاوز أخطاء الجلسات</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card option-card h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="option-icon text-success">
                                            <i class="fas fa-vial"></i>
                                        </div>
                                        <h5 class="card-title">وضع الاختبار</h5>
                                        <p class="card-text text-muted mb-4">يستخدم البيانات الحقيقية مع تجاوز عملية تسجيل الدخول</p>
                                        <a href="dashboard_test.php" class="btn btn-success px-4">
                                            <i class="fas fa-arrow-right me-2"></i>فتح وضع الاختبار
                                        </a>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-info">الأفضل للاستخدام: عرض البيانات الحقيقية دون الحاجة للتسجيل</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card option-card h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="option-icon text-danger">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                        <h5 class="card-title">تسجيل الدخول</h5>
                                        <p class="card-text text-muted mb-4">المسار الطبيعي للنظام مع تسجيل الدخول والتحقق</p>
                                        <a href="../pharmacist_login.php" class="btn btn-danger px-4">
                                            <i class="fas fa-arrow-right me-2"></i>تسجيل دخول تلقائي
                                        </a>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-info">الأفضل للاستخدام: للوصول للوظائف الكاملة مع التحقق</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>ملاحظة هامة</h5>
                            <p class="mb-0">إذا واجهتك الشاشة البيضاء عند استخدام الطريقة الطبيعية، فإن ذلك يرجع غالبًا إلى مشكلة في الجلسات أو التحقق من المستخدم. استخدم النسخة المستقلة لتجاوز هذه المشكلة.</p>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            <a href="../index.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-home me-1"></i>الصفحة الرئيسية
                            </a>
                            <a href="../debug.php" class="btn btn-outline-primary">
                                <i class="fas fa-bug me-1"></i>صفحة التشخيص
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 