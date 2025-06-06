<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// استعلام لجلب جميع الروشتات الطبية
$query = "SELECT p.*, 
          CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
          CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
          ph.first_name as pharmacist_first_name,
          ph.last_name as pharmacist_last_name
          FROM prescriptions p 
          LEFT JOIN doctors d ON p.doctor_id = d.id 
          LEFT JOIN patients pt ON p.patient_id = pt.id 
          LEFT JOIN dispensed_medicines dm ON p.id = dm.prescription_id
          LEFT JOIN pharmacists ph ON dm.pharmacist_id = ph.id 
          ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// استعلام لجلب تفاصيل روشتة محددة
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $prescription_id = $_GET['view'];
    
    // استعلام لجلب بيانات الروشتة
    $prescription_query = "SELECT p.*, 
                          CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                          CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                          pt.phone as patient_phone,
                          ph.first_name as pharmacist_first_name,
                          ph.last_name as pharmacist_last_name
                          FROM prescriptions p 
                          LEFT JOIN doctors d ON p.doctor_id = d.id 
                          LEFT JOIN patients pt ON p.patient_id = pt.id 
                          LEFT JOIN dispensed_medicines dm ON p.id = dm.prescription_id
                          LEFT JOIN pharmacists ph ON dm.pharmacist_id = ph.id 
                          WHERE p.id = ?";
    $prescription_stmt = $pdo->prepare($prescription_query);
    $prescription_stmt->execute([$prescription_id]);
    $prescription_details = $prescription_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prescription_details) {
        // استعلام لجلب الأدوية في الروشتة
        $medicines_query = "SELECT pi.*, m.name as medicine_name, m.generic_name 
                          FROM prescription_items pi 
                          JOIN medicines m ON pi.medicine_id = m.id 
                          WHERE pi.prescription_id = ?";
        $medicines_stmt = $pdo->prepare($medicines_query);
        $medicines_stmt->execute([$prescription_id]);
        $prescription_medicines = $medicines_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// التعامل مع تحديث حالة الروشتة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $prescription_id = $_POST['prescription_id'];
    $new_status = $_POST['new_status'];
    $notes = trim($_POST['notes']);
    
    try {
        $update_query = "UPDATE prescriptions SET status = ?, notes = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$new_status, $notes, $prescription_id]);
        
        $success = "تم تحديث حالة الروشتة بنجاح";
        
        // إعادة تحميل الصفحة لتحديث القائمة
        header("Location: pharmacy_prescriptions.php?success=updated");
        exit;
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء تحديث حالة الروشتة: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الروشتات الطبية - مستشفى المستقبل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-action {
            margin: 0 5px;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-dispensed {
            color: #28a745;
            font-weight: bold;
        }
        .status-cancelled {
            color: #dc3545;
            font-weight: bold;
        }
        .prescription-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .medicine-item {
            background-color: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch ($_GET['success']) {
                        case 'updated':
                            echo "تم تحديث حالة الروشتة بنجاح";
                            break;
                        default:
                            echo "تمت العملية بنجاح";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($prescription_details)): ?>
            <!-- عرض تفاصيل الروشتة -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-prescription"></i> تفاصيل الروشتة #<?php echo $prescription_details['id']; ?></h4>
                            <a href="pharmacy_prescriptions.php" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right"></i> العودة للقائمة
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="prescription-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-user"></i> بيانات المريض</h5>
                                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($prescription_details['patient_name']); ?></p>
                                        <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($prescription_details['patient_phone']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-user-md"></i> بيانات الطبيب</h5>
                                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($prescription_details['doctor_name']); ?></p>
                                        <p><strong>تاريخ الروشتة:</strong> <?php echo date('Y-m-d', strtotime($prescription_details['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-info-circle"></i> حالة الروشتة</h5>
                                        <p>
                                            <strong>الحالة:</strong> 
                                            <?php if ($prescription_details['status'] === 'pending'): ?>
                                                <span class="status-pending"><i class="fas fa-clock"></i> قيد الانتظار</span>
                                            <?php elseif ($prescription_details['status'] === 'dispensed'): ?>
                                                <span class="status-dispensed"><i class="fas fa-check-circle"></i> تم صرفها</span>
                                                <p><strong>تم صرفها بواسطة:</strong> <?php echo htmlspecialchars($prescription_details['pharmacist_first_name'] . ' ' . $prescription_details['pharmacist_last_name']); ?></p>
                                            <?php elseif ($prescription_details['status'] === 'cancelled'): ?>
                                                <span class="status-cancelled"><i class="fas fa-times-circle"></i> ملغية</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-sticky-note"></i> ملاحظات</h5>
                                        <p><?php echo !empty($prescription_details['notes']) ? htmlspecialchars($prescription_details['notes']) : 'لا توجد ملاحظات'; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3"><i class="fas fa-pills"></i> الأدوية الموصوفة</h5>
                            <?php if (empty($prescription_medicines)): ?>
                                <div class="alert alert-info">لا توجد أدوية مسجلة في هذه الروشتة</div>
                            <?php else: ?>
                                <?php foreach ($prescription_medicines as $medicine): ?>
                                    <div class="medicine-item">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><?php echo htmlspecialchars($medicine['medicine_name']); ?></h6>
                                                <p class="text-muted"><?php echo htmlspecialchars($medicine['generic_name']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>الجرعة:</strong> <?php echo htmlspecialchars($medicine['dosage']); ?></p>
                                                <p><strong>التعليمات:</strong> <?php echo htmlspecialchars($medicine['instructions']); ?></p>
                                                <p><strong>الكمية:</strong> <?php echo $medicine['quantity']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if ($prescription_details['status'] === 'pending'): ?>
                                <div class="mt-4">
                                    <h5 class="mb-3"><i class="fas fa-edit"></i> تحديث حالة الروشتة</h5>
                                    <form method="POST" action="">
                                        <input type="hidden" name="prescription_id" value="<?php echo $prescription_details['id']; ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="new_status" class="form-label">الحالة الجديدة</label>
                                                <select class="form-select" id="new_status" name="new_status" required>
                                                    <option value="pending">قيد الانتظار</option>
                                                    <option value="dispensed">تم صرفها</option>
                                                    <option value="cancelled">ملغية</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="notes" class="form-label">ملاحظات</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($prescription_details['notes']); ?></textarea>
                                            </div>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary">
                                            <i class="fas fa-save"></i> تحديث الحالة
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- قائمة الروشتات -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-prescription"></i> إدارة الروشتات الطبية</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="searchPrescription" class="form-control" placeholder="البحث في الروشتات...">
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-outline-secondary filter-btn active" data-filter="all">الكل</button>
                                        <button class="btn btn-outline-warning filter-btn" data-filter="pending">قيد الانتظار</button>
                                        <button class="btn btn-outline-success filter-btn" data-filter="dispensed">تم صرفها</button>
                                        <button class="btn btn-outline-danger filter-btn" data-filter="cancelled">ملغية</button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (empty($prescriptions)): ?>
                                <div class="alert alert-info">لا توجد روشتات طبية مسجلة</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="prescriptionsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>المريض</th>
                                                <th>الطبيب</th>
                                                <th>تاريخ الروشتة</th>
                                                <th>الحالة</th>
                                                <th>صرفت بواسطة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prescriptions as $index => $prescription): ?>
                                                <tr data-status="<?php echo $prescription['status']; ?>">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescription['doctor_name']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($prescription['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($prescription['status'] === 'pending'): ?>
                                                            <span class="status-pending"><i class="fas fa-clock"></i> قيد الانتظار</span>
                                                        <?php elseif ($prescription['status'] === 'dispensed'): ?>
                                                            <span class="status-dispensed"><i class="fas fa-check-circle"></i> تم صرفها</span>
                                                        <?php elseif ($prescription['status'] === 'cancelled'): ?>
                                                            <span class="status-cancelled"><i class="fas fa-times-circle"></i> ملغية</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $prescription['status'] === 'dispensed' ? htmlspecialchars($prescription['pharmacist_first_name'] . ' ' . $prescription['pharmacist_last_name']) : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <a href="pharmacy_prescriptions.php?view=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-info btn-action">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // البحث في جدول الروشتات
            const searchPrescription = document.getElementById('searchPrescription');
            if (searchPrescription) {
                searchPrescription.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('prescriptionsTable');
                    const rows = table.getElementsByTagName('tr');
                    
                    for (let i = 1; i < rows.length; i++) {
                        const cells = rows[i].getElementsByTagName('td');
                        let found = false;
                        
                        for (let j = 1; j < 4; j++) { // البحث في اسم المريض والطبيب والتاريخ
                            if (cells.length > 0 && cells[j].textContent.toLowerCase().includes(searchValue)) {
                                found = true;
                                break;
                            }
                        }
                        
                        rows[i].style.display = found ? '' : 'none';
                    }
                });
            }
            
            // تصفية الروشتات حسب الحالة
            const filterButtons = document.querySelectorAll('.filter-btn');
            if (filterButtons.length > 0) {
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // إزالة الفلتر النشط
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        const filterValue = this.getAttribute('data-filter');
                        const table = document.getElementById('prescriptionsTable');
                        const rows = table.getElementsByTagName('tr');
                        
                        for (let i = 1; i < rows.length; i++) {
                            const status = rows[i].getAttribute('data-status');
                            if (filterValue === 'all' || status === filterValue) {
                                rows[i].style.display = '';
                            } else {
                                rows[i].style.display = 'none';
                            }
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>