<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// استعلام لجلب جميع الأطباء مع أقسامهم ومعلوماتهم الكاملة
$sql = "SELECT d.*, dep.name as department_name, 
       CONCAT(d.first_name, ' ', d.last_name) as name,
       d.qualification, d.specialization, d.experience,
       d.profile_picture as image, d.consultation_fee
FROM doctors d 
LEFT JOIN departments dep ON d.department_id = dep.id 
WHERE d.status = 'active'
ORDER BY d.first_name ASC, d.last_name ASC";
$stmt = $pdo->query($sql);
$result = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستشفى بارمستان المستقبل - أطباؤنا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3">
            <a class="navbar-brand text-white" href="index.php">
                <img src="assets/images/hospital-logo.svg" alt="شعار المستشفى" height="40">
                مستشفى بارمستان المستقبل
            </a>
            <a href="login.php" class="btn btn-outline-light">
                <i class="fas fa-sign-in-alt me-1"></i>تسجيل الدخول
            </a>
        </div>
    </div>

    <div class="container my-5">
    <h1 class="text-center mb-5">أطباؤنا المتميزون</h1>
    
    <div class="row g-4">
        <?php foreach($result as $doctor): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 doctor-card">
                    <img src="<?php echo !empty($doctor['image']) ? 'uploads/doctors/' . $doctor['image'] : 'assets/images/default-doctor.png'; ?>" 
                         class="card-img-top doctor-image" 
                         alt="<?php echo htmlspecialchars($doctor['name'] ?? ''); ?>">
                    
                    <div class="card-body text-center">
                        <h3 class="card-title h4 mb-3">د. <?php echo htmlspecialchars($doctor['name'] ?? ''); ?></h3>
                        <p class="card-text text-primary mb-2">
                            <i class="fas fa-stethoscope me-2"></i>
                            <?php echo htmlspecialchars($doctor['department_name'] ?? ''); ?>
                            <?php if (!empty($doctor['specialization'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($doctor['specialization']); ?></small>
                            <?php endif; ?>
                        </p>
                        <div class="doctor-info mb-3">
                            <?php if (!empty($doctor['qualification'])): ?>
                                <p><i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($doctor['qualification']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($doctor['experience'])): ?>
                                <p><i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($doctor['experience']); ?> سنوات خبرة</p>
                            <?php endif; ?>
                            <?php if (!empty($doctor['consultation_fee'])): ?>
                                <p><i class="fas fa-money-bill-wave me-2"></i>رسوم الاستشارة: <?php echo htmlspecialchars($doctor['consultation_fee']); ?> د.ك</p>
                            <?php endif; ?>
                        </div>
                        <a href="patient/book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-calendar-plus me-2"></i>حجز موعد
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
:root {
    --card-gradient-start: #1a759f;
    --card-gradient-end: #34a0a4;
    --card-shadow: rgba(26, 117, 159, 0.2);
}

.doctor-card {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    box-shadow: 0 10px 25px var(--card-shadow);
    overflow: hidden;
    border-radius: 25px;
    background: linear-gradient(145deg, var(--card-gradient-start) 0%, var(--card-gradient-end) 100%);
    padding: 3px;
}

.doctor-card > div {
    background: #fff;
    border-radius: 22px;
    height: 100%;
}

.doctor-card:hover {
    transform: translateY(-12px) scale(1.03);
    box-shadow: 0 20px 35px var(--card-shadow);
}

.doctor-image {
    width: 250px;
    height: 250px;
    object-fit: contain;
    border-radius: 50%;
    margin: 20px auto;
    border: 5px solid var(--card-gradient-end);
    padding: 5px;
    background: #fff;
    display: block;
    box-shadow: 0 8px 20px var(--card-shadow);
    transition: transform 0.4s ease;
    text-indent: -9999px;
    overflow: hidden;
}

.doctor-card:hover .doctor-image {
    transform: scale(1.08) rotate(5deg);
}

.doctor-info {
    font-size: 1rem;
    color: #444;
    padding: 1rem;
    background: rgba(52, 160, 164, 0.1);
    border-radius: 15px;
    margin: 1.2rem 0;
    box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
}

.doctor-info p {
    margin-bottom: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.doctor-info i {
    color: var(--card-gradient-start);
    font-size: 1.1rem;
}

.card-title {
    color: var(--card-gradient-start);
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 1.2rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: linear-gradient(145deg, var(--card-gradient-start) 0%, var(--card-gradient-end) 100%);
    border: none;
    padding: 14px 28px;
    transition: all 0.4s ease;
    border-radius: 30px;
    font-weight: 600;
    letter-spacing: 0.6px;
    box-shadow: 0 4px 15px var(--card-shadow);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px var(--card-shadow);
    opacity: 0.95;
}

.text-primary {
    color: var(--card-gradient-start) !important;
}
</style>

<!-- Custom Footer without glowing banner -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>مستشفى بيرمستان المستقبلية</h5>
                <p>نقدم خدمات رعاية صحية عالية الجودة لمجتمعنا.</p>
            </div>
            <div class="col-md-4">
                <h5>روابط سريعة</h5>
                <ul class="list-unstyled">
                    <li><a href="/birmistan_future_hospital/about.php" class="text-white">من نحن</a></li>
                    <li><a href="/birmistan_future_hospital/services.php" class="text-white">خدماتنا</a></li>
                    <li><a href="/birmistan_future_hospital/contact.php" class="text-white">اتصل بنا</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>معلومات الاتصال</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-phone"></i> +1234567890</li>
                    <li><i class="fas fa-envelope"></i> info@hospital.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> شارع المستشفى 123، المدينة</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; <?php echo date('Y'); ?> مستشفى بيرمستان المستقبلية. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>