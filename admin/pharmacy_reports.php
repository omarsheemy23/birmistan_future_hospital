<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// تحديد الفترة الزمنية للتقارير
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // بداية الشهر الحالي
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // اليوم الحالي

// تقرير الأدوية الأكثر صرفاً - تعديل الاستعلام ليتناسب مع هيكل قاعدة البيانات الحالي
$top_medicines_query = "SELECT m.name, m.generic_name, COUNT(pi.id) as total_quantity
                        FROM medicines m
                        JOIN prescription_items pi ON pi.medicine_id = m.id
                        JOIN prescriptions p ON pi.prescription_id = p.id
                        JOIN dispensed_medicines dm ON p.id = dm.prescription_id
                        WHERE dm.status = 'complete'
                        AND dm.dispense_date BETWEEN ? AND ?
                        GROUP BY m.id
                        ORDER BY total_quantity DESC
                        LIMIT 10";
$top_medicines_stmt = $pdo->prepare($top_medicines_query);
$top_medicines_stmt->execute([$start_date, $end_date . ' 23:59:59']);
$top_medicines = $top_medicines_stmt->fetchAll(PDO::FETCH_ASSOC);

// تقرير الأدوية منخفضة المخزون - تعديل الاستعلام ليتناسب مع هيكل قاعدة البيانات الحالي
$low_stock_query = "SELECT m.*, m.category as category_name
                    FROM medicines m
                    WHERE m.quantity_in_stock <= 10
                    ORDER BY m.quantity_in_stock ASC";
$low_stock_stmt = $pdo->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_medicines = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// تقرير الروشتات حسب الحالة
$prescriptions_status_query = "SELECT status, COUNT(*) as count
                              FROM prescriptions
                              WHERE created_at BETWEEN ? AND ?
                              GROUP BY status";
$prescriptions_status_stmt = $pdo->prepare($prescriptions_status_query);
$prescriptions_status_stmt->execute([$start_date, $end_date . ' 23:59:59']);
$prescriptions_status = $prescriptions_status_stmt->fetchAll(PDO::FETCH_ASSOC);

// تحويل نتائج تقرير الروشتات إلى مصفوفة أسهل للاستخدام
$status_counts = [
    'pending' => 0,
    'dispensed' => 0,
    'partially_dispensed' => 0,
    'cancelled' => 0,
    'مفتوحة' => 0,
    'مصروفة' => 0,
    'مصروفة جزئياً' => 0,
    'ملغية' => 0
];

foreach ($prescriptions_status as $status) {
    $status_counts[$status['status']] = $status['count'];
}

// تقرير الأطباء الأكثر وصفاً للأدوية
$top_doctors_query = "SELECT d.id, CONCAT(d.first_name, ' ', d.last_name) as doctor_name, 
                      COUNT(p.id) as prescription_count
                      FROM prescriptions p
                      JOIN doctors d ON p.doctor_id = d.id
                      WHERE p.created_at BETWEEN ? AND ?
                      GROUP BY p.doctor_id
                      ORDER BY prescription_count DESC
                      LIMIT 5";
$top_doctors_stmt = $pdo->prepare($top_doctors_query);
$top_doctors_stmt->execute([$start_date, $end_date . ' 23:59:59']);
$top_doctors = $top_doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

// تقرير الصيادلة الأكثر نشاطاً - تعديل الاستعلام ليتناسب مع هيكل قاعدة البيانات الحالي
$top_pharmacists_query = "SELECT ph.id, CONCAT(ph.first_name, ' ', ph.last_name) as pharmacist_name, 
                         COUNT(dm.prescription_id) as dispensed_count
                         FROM pharmacists ph
                         JOIN dispensed_medicines dm ON ph.id = dm.pharmacist_id
                         JOIN prescriptions p ON dm.prescription_id = p.id
                         WHERE (p.status = 'مصروفة' OR p.status = 'dispensed')
                         AND p.updated_at BETWEEN ? AND ?
                         GROUP BY ph.id
                         ORDER BY dispensed_count DESC
                         LIMIT 5";
$top_pharmacists_stmt = $pdo->prepare($top_pharmacists_query);
$top_pharmacists_stmt->execute([$start_date, $end_date . ' 23:59:59']);
$top_pharmacists = $top_pharmacists_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير الصيدلية - مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-bar"></i> تقارير الصيدلية</h4>
                    </div>
                    <div class="card-body">
                        <!-- نموذج تصفية التقارير -->
                        <form method="GET" action="" class="filter-form">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter"></i> تصفية
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- ملخص التقارير -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-prescription"></i> إجمالي الروشتات</h5>
                                        <h2><?php echo array_sum($status_counts); ?></h2>
                                        <p>خلال الفترة المحددة</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-check-circle"></i> الروشتات المصروفة</h5>
                                        <h2><?php echo isset($status_counts['مصروفة']) ? $status_counts['مصروفة'] : 0; ?></h2>
                                        <p>خلال الفترة المحددة</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-exclamation-triangle"></i> الأدوية منخفضة المخزون</h5>
                                        <h2><?php echo count($low_stock_medicines); ?></h2>
                                        <p>تحتاج إلى إعادة تعبئة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- تقرير حالة الروشتات -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> حالة الروشتات</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="prescriptionsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- تقرير الأدوية الأكثر صرفاً -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-pills"></i> الأدوية الأكثر صرفاً</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($top_medicines)): ?>
                                            <div class="alert alert-info">لا توجد بيانات متاحة للفترة المحددة</div>
                                        <?php else: ?>
                                            <div class="chart-container">
                                                <canvas id="topMedicinesChart"></canvas>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- تقرير الأطباء الأكثر وصفاً للأدوية -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-user-md"></i> الأطباء الأكثر وصفاً للأدوية</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($top_doctors)): ?>
                                            <div class="alert alert-info">لا توجد بيانات متاحة للفترة المحددة</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>اسم الطبيب</th>
                                                            <th>عدد الروشتات</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($top_doctors as $index => $doctor): ?>
                                                            <tr>
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                                                <td><?php echo $doctor['prescription_count']; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- تقرير الصيادلة الأكثر نشاطاً -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-user-nurse"></i> الصيادلة الأكثر نشاطاً</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($top_pharmacists)): ?>
                                            <div class="alert alert-info">لا توجد بيانات متاحة للفترة المحددة</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>اسم الصيدلي</th>
                                                            <th>عدد الروشتات المصروفة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($top_pharmacists as $index => $pharmacist): ?>
                                                            <tr>
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($pharmacist['pharmacist_name']); ?></td>
                                                                <td><?php echo $pharmacist['dispensed_count']; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- تقرير الأدوية منخفضة المخزون -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> الأدوية منخفضة المخزون</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($low_stock_medicines)): ?>
                                            <div class="alert alert-success">لا توجد أدوية منخفضة المخزون</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>اسم الدواء</th>
                                                            <th>الاسم العلمي</th>
                                                            <th>الفئة</th>
                                                            <th>الكمية المتبقية</th>
                                                            <th>السعر</th>
                                                            <th>تاريخ انتهاء الصلاحية</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($low_stock_medicines as $index => $medicine): ?>
                                                            <tr>
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($medicine['generic_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($medicine['category_name']); ?></td>
                                                                <td class="low-stock"><?php echo $medicine['quantity_in_stock']; ?></td>
                                                                <td><?php echo number_format($medicine['selling_price'], 2); ?> ر.س</td>
                                                                <td>
                                                                    <?php 
                                                                        $expiry = new DateTime($medicine['expiry_date']);
                                                                        $now = new DateTime();
                                                                        $expired = $expiry < $now;
                                                                        
                                                                        if ($expired) {
                                                                            echo '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> منتهي الصلاحية</span>';
                                                                        } else {
                                                                            echo $medicine['expiry_date'];
                                                                        }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // رسم مخطط حالة الروشتات
            const prescriptionsCtx = document.getElementById('prescriptionsChart').getContext('2d');
            new Chart(prescriptionsCtx, {
                type: 'pie',
                data: {
                    labels: ['قيد الانتظار', 'تم صرفها', 'ملغية'],
                    datasets: [{
                        data: [
                            <?php echo $status_counts['pending']; ?>,
                            <?php echo $status_counts['dispensed']; ?>,
                            <?php echo $status_counts['cancelled']; ?>
                        ],
                        backgroundColor: [
                            '#ffc107',
                            '#28a745',
                            '#dc3545'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            <?php if (!empty($top_medicines)): ?>
            // رسم مخطط الأدوية الأكثر صرفاً
            const topMedicinesCtx = document.getElementById('topMedicinesChart').getContext('2d');
            new Chart(topMedicinesCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php foreach ($top_medicines as $medicine): ?>
                            '<?php echo addslashes($medicine['name']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'الكمية المصروفة',
                        data: [
                            <?php foreach ($top_medicines as $medicine): ?>
                                <?php echo $medicine['total_quantity']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>