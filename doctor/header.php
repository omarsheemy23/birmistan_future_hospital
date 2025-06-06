<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستشفى بارمستان المستقبل - لوحة تحكم الطبيب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 50px; /* تقليل المساحة في الأعلى بشكل أكبر */
            margin-top: 0; /* إزالة أي هوامش إضافية */
        }
        .navbar {
            background-color: #2c3e50;
            padding: 0.3rem 1rem; /* تقليل الحشو أكثر */
            position: fixed; /* تثبيت الهيدر */
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000; /* ضمان ظهور الهيدر فوق جميع العناصر */
            min-height: auto; /* السماح للناف بار بأن يكون بالارتفاع الطبيعي */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* إضافة ظل خفيف للتمييز */
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: bold;
            font-size: 1.25rem; /* تقليل حجم الخط */
            padding: 0.2rem 0; /* تقليل البادينج */
        }
        .nav-link {
            color: #ecf0f1 !important;
            margin: 0 4px; /* تقليل الهوامش */
            transition: color 0.3s;
            font-size: 0.95rem; /* تقليل حجم الخط */
            padding: 0.3rem 0.5rem; /* تقليل البادينج */
        }
        .nav-link:hover {
            color: #3498db !important;
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        .btn-logout {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 4px 10px; /* تقليل البادينج */
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 0.95rem; /* تقليل حجم الخط */
        }
        .btn-logout:hover {
            background-color: #c0392b;
            color: white;
        }
        .dropdown-menu {
            background-color: #34495e;
        }
        .dropdown-item {
            color: #ecf0f1;
        }
        .dropdown-item:hover {
            background-color: #2c3e50;
            color: #3498db;
        }
        
        /* تضمين المحتوى الرئيسي مباشرة تحت الناف بار */
        .container-fluid {
            padding-top: 1rem;
            clear: both;
        }
        
        /* تعديل أحجام الأيقونات */
        .fas, .far, .fab {
            font-size: 0.9rem;
        }
        
        /* إضافة صف وهمي يتم عرضه فقط لضمان وجود مساحة كافية بعد الناف بار */
        .navbar-spacer {
            height: 10px;
            width: 100%;
            clear: both;
        }
    </style>
</head>
<body>
    <?php
    // تحديد الصفحة الحالية
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital me-1"></i>مستشفى بارمستان المستقبل
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
                            <i class="fas fa-calendar-check me-1"></i>المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'patients.php' ? 'active' : ''; ?>" href="patients.php">
                            <i class="fas fa-users me-1"></i>المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'medical_records.php' ? 'active' : ''; ?>" href="medical_records.php">
                            <i class="fas fa-file-medical me-1"></i>السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'schedule.php' ? 'active' : ''; ?>" href="schedule.php">
                            <i class="fas fa-calendar-alt me-1"></i>جدول العمل
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fas fa-user-md me-1"></i>الملف الشخصي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'view_prescriptions.php' ? 'active' : ''; ?>" href="view_prescriptions.php">
                            <i class="fas fa-prescription me-1"></i>عرض الروشتات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'pharmacy_inventory.php' ? 'active' : ''; ?>" href="pharmacy_inventory.php">
                            <i class="fas fa-pills me-1"></i>مخزون الأدوية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dispense_medicine.php' ? 'active' : ''; ?>" href="dispense_medicine.php">
                            <i class="fas fa-capsules me-1"></i>صرف الدواء
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
    <div class="navbar-spacer"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تفعيل القوائم المنسدلة
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            })
        });
    </script>
<!-- تم إزالة وسوم الإغلاق body و html لضمان عرض محتوى الصفحات بشكل صحيح -->