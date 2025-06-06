<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
            color: #222;
            margin: 0;
            padding: 20px 0;
            min-height: 100vh;
        }
        .navbar {
            background-color: #343a40 !important;
            padding: 0.5rem 1rem;
        }
        .navbar .nav-link, .navbar .navbar-brand {
            color: #fff !important;
            padding: 0.25rem 1rem;
        }
        .navbar .nav-link.active, .navbar .nav-link:focus, .navbar .nav-link:hover {
            color: #007bff !important;
            background: rgba(255,255,255,0.05);
            border-radius: 5px;
        }
        .navbar .navbar-brand {
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .navbar-collapse {
            flex-grow: 0;
        }
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: #343a40;
                padding: 1rem;
                border-radius: 0.5rem;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/birmistan_future_hospital/admin/dashboard.php">
      <i class="fas fa-hospital me-2"></i> مستشفى المستقبل
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/dashboard.php"><i class="fas fa-home"></i> الرئيسية</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='doctors.php') echo ' active'; ?>" href="/birmistan_future_hospital/doctors.php"><i class="fas fa-user-md"></i> أطباؤنا</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='doctors.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/doctors.php"><i class="fas fa-user-md"></i> إدارة الأطباء</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='nurses.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/nurses.php"><i class="fas fa-user-nurse"></i> إدارة الممرضين</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='patients.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/patients.php"><i class="fas fa-users"></i> المرضى</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='departments.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/departments.php"><i class="fas fa-building"></i> إدارة الأقسام</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='appointments.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/appointments.php"><i class="fas fa-calendar-alt"></i> المواعيد</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='vacations.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/vacations.php"><i class="fas fa-calendar-times"></i> الإجازات</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='ambulance_requests.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/ambulance_requests.php"><i class="fas fa-ambulance"></i> طلبات الإسعاف</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='ambulances.php') echo ' active'; ?>" href="/birmistan_future_hospital/admin/ambulances.php"><i class="fas fa-car"></i> إدارة سيارات الإسعاف</a>
        </li>
        <!-- قسم الصيدلية -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="pharmacyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-pills"></i> نظام الصيدلية
          </a>
          <ul class="dropdown-menu" aria-labelledby="pharmacyDropdown">
            <li><a class="dropdown-item" href="/birmistan_future_hospital/admin/pharmacy_inventory.php"><i class="fas fa-boxes"></i> إدارة المخزون</a></li>
            <li><a class="dropdown-item" href="/birmistan_future_hospital/admin/pharmacy_prescriptions.php"><i class="fas fa-prescription"></i> الروشتات</a></li>
            <li><a class="dropdown-item" href="/birmistan_future_hospital/admin/pharmacists.php"><i class="fas fa-user-md"></i> إدارة الصيادلة</a></li>
            <li><a class="dropdown-item" href="/birmistan_future_hospital/admin/pharmacy_reports.php"><i class="fas fa-chart-bar"></i> تقارير الصيدلية</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/birmistan_future_hospital/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/birmistan_future_hospital/">
      <i class="fas fa-hospital"></i> مستشفى المستقبل
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo ' active'; ?>" href="/birmistan_future_hospital/"><i class="fas fa-home"></i> الرئيسية</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='doctors.php') echo ' active'; ?>" href="/birmistan_future_hospital/doctors.php"><i class="fas fa-user-md"></i> أطباؤنا</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='departments.php') echo ' active'; ?>" href="/birmistan_future_hospital/departments.php"><i class="fas fa-building"></i> الأقسام</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='contact.php') echo ' active'; ?>" href="/birmistan_future_hospital/contact.php"><i class="fas fa-envelope"></i> اتصل بنا</a>
        </li>
      </ul>
      <div class="d-flex">
        <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="/birmistan_future_hospital/login.php" class="btn btn-light"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a>
        <?php else: ?>
        <a href="/birmistan_future_hospital/logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container-fluid mt-4">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // تفعيل القوائم المنسدلة
  document.addEventListener('DOMContentLoaded', function() {
    // تفعيل جميع القوائم المنسدلة
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
      new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // تفعيل قائمة الصيدلية بشكل خاص
    var pharmacyDropdown = document.getElementById('pharmacyDropdown');
    if (pharmacyDropdown) {
      new bootstrap.Dropdown(pharmacyDropdown);
      
      // إضافة مستمع حدث للنقر
      pharmacyDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        var dropdown = bootstrap.Dropdown.getInstance(pharmacyDropdown);
        if (dropdown) {
          dropdown.toggle();
        } else {
          new bootstrap.Dropdown(pharmacyDropdown).toggle();
        }
      });
    }
  });
</script>