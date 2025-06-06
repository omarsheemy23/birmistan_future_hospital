<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستشفى بارمستان المستقبل - لوحة تحكم المريض</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital me-2"></i>مستشفى بارمستان المستقبل
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../doctors.php">
                            <i class="fas fa-user-md me-1"></i>أطباؤنا
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_appointment.php">
                            <i class="fas fa-calendar-plus me-1"></i>حجز موعد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fas fa-calendar-check me-1"></i>مواعيدي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php">
                            <i class="fas fa-file-medical me-1"></i>السجل الطبي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_prescriptions.php">
                            <i class="fas fa-prescription me-1"></i>روشتاتي الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track_dispense.php">
                            <i class="fas fa-pills me-1"></i>متابعة صرف الأدوية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_ambulance.php">
                            <i class="fas fa-ambulance me-1"></i>طلب سيارة إسعاف
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i>الملف الشخصي
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="../logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt me-1"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <div class="container mt-4">
        <!-- محتوى الصفحة سيتم إضافته هنا -->
    </div>
</body>
</html>