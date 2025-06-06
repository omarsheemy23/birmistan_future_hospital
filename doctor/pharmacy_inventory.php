<?php
session_start();
require_once '../includes/config.php';

// استدعاء ملف الهيدر
include('header.php');

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

try {
    // جلب قائمة الأدوية المتوفرة في المخزون
    $stmt = $pdo->prepare("SELECT m.*, c.name as category_name,
                          CASE 
                            WHEN m.quantity_in_stock <= m.min_stock_level THEN 'منخفض'
                            WHEN m.quantity_in_stock > m.min_stock_level AND m.quantity_in_stock <= (m.min_stock_level * 2) THEN 'متوسط'
                            ELSE 'جيد'
                          END as stock_status
                          FROM medicines m
                          LEFT JOIN medicine_categories c ON m.category = c.id
                          ORDER BY m.name ASC");
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // التأكد من عدم وجود قيم null في البيانات
    foreach ($medicines as &$medicine) {
        // التحقق من وجود القيم المطلوبة وتعيين قيم افتراضية إذا لزم الأمر
        $medicine['name'] = $medicine['name'] ?? 'دواء غير معروف';
        $medicine['category'] = $medicine['category'] ?? '';
        $medicine['stock_status'] = $medicine['stock_status'] ?? 'غير معروف';
        $medicine['quantity_in_stock'] = $medicine['quantity_in_stock'] ?? 0;
        $medicine['min_stock_level'] = $medicine['min_stock_level'] ?? 0;
        $medicine['expiry_date'] = $medicine['expiry_date'] ?? date('Y-m-d');
        $medicine['unit_price'] = $medicine['unit_price'] ?? 'غير محدد';
        $medicine['manufacturer'] = $medicine['manufacturer'] ?? 'غير محدد';
        $medicine['description'] = $medicine['description'] ?? 'لا يوجد وصف';
        $medicine['dosage_form'] = $medicine['dosage_form'] ?? 'غير محدد';
    }

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">مخزون الأدوية</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="searchMedicine" class="form-control" placeholder="ابحث عن دواء...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        <select id="filterCategory" class="form-select me-2" style="max-width: 200px;">
                            <option value="">جميع الفئات</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM medicine_categories ORDER BY name");
                            while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$category['id']}\">{$category['name']}</option>";
                            }
                            ?>
                        </select>
                        <select id="filterStock" class="form-select" style="max-width: 200px;">
                            <option value="">جميع المستويات</option>
                            <option value="منخفض">مخزون منخفض</option>
                            <option value="متوسط">مخزون متوسط</option>
                            <option value="جيد">مخزون جيد</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Medicines Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>اسم الدواء</th>
                            <th>الفئة</th>
                            <th>الشكل الدوائي</th>
                            <th>الكمية المتوفرة</th>
                            <th>حالة المخزون</th>
                            <th>تاريخ انتهاء الصلاحية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($medicines) && count($medicines) > 0): ?>
                            <?php foreach ($medicines as $index => $medicine): ?>
                                <tr class="medicine-row" 
                                    data-name="<?php echo strtolower(htmlspecialchars($medicine['name'])); ?>" 
                                    data-category="<?php echo htmlspecialchars($medicine['category']); ?>"
                                    data-stock="<?php echo htmlspecialchars($medicine['stock_status']); ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['category_name'] ?? 'غير مصنف'); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['dosage_form'] ?? 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['quantity_in_stock']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $medicine['stock_status'] === 'منخفض' ? 'bg-danger' : 
                                                ($medicine['stock_status'] === 'متوسط' ? 'bg-warning' : 'bg-success'); 
                                        ?>">
                                            <?php echo htmlspecialchars($medicine['stock_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $expiry_date = new DateTime($medicine['expiry_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($expiry_date);
                                        $days_remaining = $expiry_date > $today ? $interval->days : -$interval->days;
                                        
                                        $badge_class = 'bg-success';
                                        if ($days_remaining < 0) {
                                            $badge_class = 'bg-danger';
                                        } elseif ($days_remaining <= 30) {
                                            $badge_class = 'bg-warning';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo date('Y-m-d', strtotime($medicine['expiry_date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#medicineModal<?php echo $medicine['id']; ?>">
                                            <i class="fas fa-eye"></i> التفاصيل
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for Medicine Details -->
                                <div class="modal fade" id="medicineModal<?php echo $medicine['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">تفاصيل الدواء</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>اسم الدواء:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>الفئة:</strong> <?php echo htmlspecialchars($medicine['category_name'] ?? 'غير مصنف'); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>الشكل الدوائي:</strong> <?php echo htmlspecialchars($medicine['dosage_form'] ?? 'غير محدد'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>الكمية المتوفرة:</strong> <?php echo htmlspecialchars($medicine['quantity_in_stock']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>الحد الأدنى للمخزون:</strong> <?php echo htmlspecialchars($medicine['min_stock_level']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>تاريخ انتهاء الصلاحية:</strong> <?php echo date('Y-m-d', strtotime($medicine['expiry_date'])); ?></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>سعر الوحدة:</strong> <?php echo htmlspecialchars($medicine['unit_price'] ?? 'غير محدد'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>الشركة المصنعة:</strong> <?php echo htmlspecialchars($medicine['manufacturer'] ?? 'غير محدد'); ?></p>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <p><strong>الوصف:</strong></p>
                                                    <p><?php echo nl2br(htmlspecialchars($medicine['description'] ?? 'لا يوجد وصف')); ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">لا توجد أدوية في المخزون</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
// وظيفة البحث والتصفية
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchMedicine');
    const filterCategory = document.getElementById('filterCategory');
    const filterStock = document.getElementById('filterStock');
    const medicineRows = document.querySelectorAll('.medicine-row');

    function filterMedicines() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryFilter = filterCategory.value;
        const stockFilter = filterStock.value;

        medicineRows.forEach(row => {
            const medicineName = row.getAttribute('data-name');
            const medicineCategory = row.getAttribute('data-category');
            const medicineStock = row.getAttribute('data-stock');

            const matchesSearch = medicineName.includes(searchTerm);
            const matchesCategory = categoryFilter === '' || medicineCategory === categoryFilter;
            const matchesStock = stockFilter === '' || medicineStock === stockFilter;

            if (matchesSearch && matchesCategory && matchesStock) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterMedicines);
    filterCategory.addEventListener('change', filterMedicines);
    filterStock.addEventListener('change', filterMedicines);
});
</script>

<?php require_once '../includes/footer.php'; ?>