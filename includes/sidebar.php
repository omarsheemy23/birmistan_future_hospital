<?php
// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /birmistan_future_hospital/login.php');
    exit();
}

// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>مستشفى المستقبل</h3>
        <button class="close-btn" id="closeSidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/dashboard.php">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'doctors.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/doctors.php">
                <i class="fas fa-user-md"></i>
                <span>إدارة الأطباء</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'patients.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/patients.php">
                <i class="fas fa-users"></i>
                <span>المرضى</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'departments.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/departments.php">
                <i class="fas fa-building"></i>
                <span>إدارة الأقسام</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/appointments.php">
                <i class="fas fa-calendar-alt"></i>
                <span>المواعيد</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'vacations.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/vacations.php">
                <i class="fas fa-calendar-times"></i>
                <span>الإجازات</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'ambulance_requests.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/ambulance_requests.php">
                <i class="fas fa-ambulance"></i>
                <span>طلبات الإسعاف</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'ambulances.php' ? 'active' : ''; ?>" href="/birmistan_future_hospital/admin/ambulances.php">
                <i class="fas fa-car"></i>
                <span>إدارة سيارات الإسعاف</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/birmistan_future_hospital/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </li>
    </ul>
</div>

<style>
/* تنسيق القائمة الجانبية */
.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 250px;
    background-color: #343a40;
    color: #fff;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.sidebar.collapsed {
    right: -250px;
}

.sidebar-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #4b545c;
}

.sidebar-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.close-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.2rem;
    cursor: pointer;
}

.nav-link {
    color: #fff;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background-color: #4b545c;
    color: #fff;
    text-decoration: none;
}

.nav-link.active {
    background-color: #007bff;
}

.nav-link i {
    margin-left: 10px;
    width: 20px;
    text-align: center;
}

/* زر فتح القائمة */
.sidebar-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1001;
    background-color: #343a40;
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    display: none;
}

@media (max-width: 768px) {
    .sidebar {
        right: -250px;
    }
    
    .sidebar.show {
        right: 0;
    }
    
    .sidebar-toggle {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('closeSidebar');
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(sidebarToggle);

    // إضافة/إزالة class للطي
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
    }

    // إضافة/إزالة class للعرض في الشاشات الصغيرة
    function toggleMobileSidebar() {
        sidebar.classList.toggle('show');
    }

    // إضافة event listeners
    closeBtn.addEventListener('click', toggleSidebar);
    sidebarToggle.addEventListener('click', toggleMobileSidebar);

    // إغلاق القائمة عند النقر خارجها في الشاشات الصغيرة
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});
</script> 