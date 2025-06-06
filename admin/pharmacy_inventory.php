<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// استعلام لجلب جميع الأدوية في المخزون
$query = "SELECT m.*, c.name as category_name 
          FROM medicines m 
          LEFT JOIN medicine_categories c ON m.category = c.name
          ORDER BY m.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// استعلام لجلب جميع فئات الأدوية
$category_query = "SELECT * FROM medicine_categories ORDER BY name ASC";
$category_stmt = $pdo->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// التعامل مع إضافة دواء جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $generic_name = trim($_POST['generic_name']);
    $category = $_POST['category_id']; // استخدام قيمة category_id ولكن تخزينها في عمود category
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $manufacturer = trim($_POST['manufacturer']);
    $expiry_date = $_POST['expiry_date'];
    
    // التحقق من البيانات
    if (empty($name) || empty($price) || empty($quantity)) {
        $error = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        try {
            // التحقق من وجود الدواء مسبقاً
            $check_query = "SELECT id FROM medicines WHERE name = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$name]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "هذا الدواء موجود بالفعل في المخزون";
            } else {
                // الحصول على اسم الفئة من معرف الفئة
                $category_name_query = "SELECT name FROM medicine_categories WHERE id = ?";
                $category_name_stmt = $pdo->prepare($category_name_query);
                $category_name_stmt->execute([$category]);
                $category_name = $category_name_stmt->fetch(PDO::FETCH_COLUMN);
                
                // إضافة دواء جديد
                $medicine_query = "INSERT INTO medicines (name, generic_name, category, description, selling_price, quantity_in_stock, 
                                  manufacturer, expiry_date, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $medicine_stmt = $pdo->prepare($medicine_query);
                $medicine_stmt->execute([
                    $name, $generic_name, $category_name, $description, $price, $quantity, 
                    $manufacturer, $expiry_date
                ]);
                
                $success = "تمت إضافة الدواء بنجاح";
                
                // إعادة تحميل الصفحة لتحديث القائمة
                header("Location: pharmacy_inventory.php?success=added");
                exit;
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء إضافة الدواء: " . $e->getMessage();
        }
    }
}

// التعامل مع إضافة فئة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    if (empty($category_name)) {
        $error = "يرجى إدخال اسم الفئة";
    } else {
        try {
            // التحقق من وجود الفئة مسبقاً
            $check_query = "SELECT id FROM medicine_categories WHERE name = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$category_name]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = "هذه الفئة موجودة بالفعل";
            } else {
                // إضافة فئة جديدة
                $category_query = "INSERT INTO medicine_categories (name, description, created_at) 
                                  VALUES (?, ?, NOW())";
                $category_stmt = $pdo->prepare($category_query);
                $category_stmt->execute([$category_name, $category_description]);
                
                $success = "تمت إضافة الفئة بنجاح";
                
                // إعادة تحميل الصفحة لتحديث القائمة
                header("Location: pharmacy_inventory.php?success=category_added");
                exit;
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء إضافة الفئة: " . $e->getMessage();
        }
    }
}

// التعامل مع تحديث كمية الدواء
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $medicine_id = $_POST['medicine_id'];
    $new_quantity = intval($_POST['new_quantity']);
    
    if ($new_quantity < 0) {
        $error = "يجب أن تكون الكمية أكبر من أو تساوي صفر";
    } else {
        try {
            $update_query = "UPDATE medicines SET quantity_in_stock = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$new_quantity, $medicine_id]);
            
            $success = "تم تحديث الكمية بنجاح";
            
            // إعادة تحميل الصفحة لتحديث القائمة
            header("Location: pharmacy_inventory.php?success=updated");
            exit;
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء تحديث الكمية: " . $e->getMessage();
        }
    }
}

// التعامل مع حذف دواء
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $medicine_id = $_GET['delete'];
    
    try {
        $delete_query = "DELETE FROM medicines WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$medicine_id]);
        
        $success = "تم حذف الدواء بنجاح";
        header("Location: pharmacy_inventory.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء حذف الدواء: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مخزون الصيدلية - مستشفى المستقبل</title>
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
        .low-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .good-stock {
            color: #28a745;
            font-weight: bold;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            padding: 10px 20px;
            font-weight: bold;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
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
                        case 'added':
                            echo "تمت إضافة الدواء بنجاح";
                            break;
                        case 'updated':
                            echo "تم تحديث المخزون بنجاح";
                            break;
                        case 'deleted':
                            echo "تم حذف الدواء بنجاح";
                            break;
                        case 'category_added':
                            echo "تمت إضافة الفئة بنجاح";
                            break;
                        default:
                            echo "تمت العملية بنجاح";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-pills"></i> إدارة مخزون الصيدلية</h4>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="true">
                                    <i class="fas fa-boxes"></i> المخزون
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-medicine-tab" data-bs-toggle="tab" data-bs-target="#add-medicine" type="button" role="tab" aria-controls="add-medicine" aria-selected="false">
                                    <i class="fas fa-plus-circle"></i> إضافة دواء
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="false">
                                    <i class="fas fa-tags"></i> الفئات
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="myTabContent">
                            <!-- قسم المخزون -->
                            <div class="tab-pane fade show active" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <input type="text" id="searchMedicine" class="form-control" placeholder="البحث عن دواء...">
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-outline-danger" id="lowStockBtn">
                                            <i class="fas fa-exclamation-triangle"></i> عرض الأدوية منخفضة المخزون
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="medicinesTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>اسم الدواء</th>
                                                <th>الاسم العلمي</th>
                                                <th>الفئة</th>
                                                <th>السعر</th>
                                                <th>الكمية</th>
                                                <th>تاريخ انتهاء الصلاحية</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($medicines)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">لا توجد أدوية في المخزون</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($medicines as $index => $medicine): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($medicine['generic_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($medicine['category_name']); ?></td>
                                                        <td><?php echo number_format($medicine['selling_price'] ?? 0, 2); ?> ر.س</td>
                                                        <td>
                                                            <?php if (($medicine['quantity_in_stock'] ?? 0) <= 10): ?>
                                                                <span class="low-stock"><?php echo $medicine['quantity_in_stock'] ?? 0; ?></span>
                                                            <?php else: ?>
                                                                <span class="good-stock"><?php echo $medicine['quantity_in_stock'] ?? 0; ?></span>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#updateQuantityModal" data-id="<?php echo $medicine['id']; ?>" data-name="<?php echo htmlspecialchars($medicine['name']); ?>" data-quantity="<?php echo $medicine['quantity_in_stock'] ?? 0; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                $expiry = new DateTime($medicine['expiry_date']);
                                                                $now = new DateTime();
                                                                $interval = $now->diff($expiry);
                                                                $expired = $expiry < $now;
                                                                
                                                                if ($expired) {
                                                                    echo '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> منتهي الصلاحية</span>';
                                                                } elseif ($interval->days < 30) {
                                                                    echo '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' . $medicine['expiry_date'] . '</span>';
                                                                } else {
                                                                    echo $medicine['expiry_date'];
                                                                }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="pharmacy_inventory.php?delete=<?php echo $medicine['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('هل أنت متأكد من حذف هذا الدواء؟')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- قسم إضافة دواء -->
                            <div class="tab-pane fade" id="add-medicine" role="tabpanel" aria-labelledby="add-medicine-tab">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">اسم الدواء</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="generic_name" class="form-label">الاسم العلمي</label>
                                            <input type="text" class="form-control" id="generic_name" name="generic_name">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="category_id" class="form-label">الفئة</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">-- اختر الفئة --</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="manufacturer" class="form-label">الشركة المصنعة</label>
                                            <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="price" class="form-label">السعر</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                                <span class="input-group-text">ر.س</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="quantity" class="form-label">الكمية</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_date" class="form-label">تاريخ انتهاء الصلاحية</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">الوصف</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" name="add_medicine" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> إضافة الدواء
                                    </button>
                                </form>
                            </div>
                            
                            <!-- قسم الفئات -->
                            <div class="tab-pane fade" id="categories" role="tabpanel" aria-labelledby="categories-tab">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0">إضافة فئة جديدة</h5>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST" action="">
                                                    <div class="mb-3">
                                                        <label for="category_name" class="form-label">اسم الفئة</label>
                                                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="category_description" class="form-label">الوصف</label>
                                                        <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                                                    </div>
                                                    <button type="submit" name="add_category" class="btn btn-primary w-100">
                                                        <i class="fas fa-plus-circle"></i> إضافة فئة
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0">قائمة الفئات</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php if (empty($categories)): ?>
                                                    <div class="alert alert-info">لا توجد فئات مسجلة</div>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>اسم الفئة</th>
                                                                    <th>الوصف</th>
                                                                    <th>تاريخ الإضافة</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($categories as $index => $category): ?>
                                                                    <tr>
                                                                        <td><?php echo $index + 1; ?></td>
                                                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                                                        <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
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
        </div>
    </div>
    
    <!-- Modal تحديث الكمية -->
    <div class="modal fade" id="updateQuantityModal" tabindex="-1" aria-labelledby="updateQuantityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateQuantityModalLabel">تحديث كمية الدواء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="medicine_id" name="medicine_id">
                        <div class="mb-3">
                            <label for="medicine_name" class="form-label">اسم الدواء</label>
                            <input type="text" class="form-control" id="medicine_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="current_quantity" class="form-label">الكمية الحالية</label>
                            <input type="text" class="form-control" id="current_quantity" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="new_quantity" class="form-label">الكمية الجديدة</label>
                            <input type="number" class="form-control" id="new_quantity" name="new_quantity" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_quantity" class="btn btn-primary">تحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحديث بيانات المودال عند فتحه
        document.addEventListener('DOMContentLoaded', function() {
            const updateQuantityModal = document.getElementById('updateQuantityModal');
            if (updateQuantityModal) {
                updateQuantityModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const quantity = button.getAttribute('data-quantity');
                    
                    const modalMedicineId = this.querySelector('#medicine_id');
                    const modalMedicineName = this.querySelector('#medicine_name');
                    const modalCurrentQuantity = this.querySelector('#current_quantity');
                    const modalNewQuantity = this.querySelector('#new_quantity');
                    
                    modalMedicineId.value = id;
                    modalMedicineName.value = name;
                    modalCurrentQuantity.value = quantity;
                    modalNewQuantity.value = quantity;
                });
            }
            
            // البحث في جدول الأدوية
            const searchMedicine = document.getElementById('searchMedicine');
            if (searchMedicine) {
                searchMedicine.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('medicinesTable');
                    const rows = table.getElementsByTagName('tr');
                    
                    for (let i = 1; i < rows.length; i++) {
                        const cells = rows[i].getElementsByTagName('td');
                        let found = false;
                        
                        for (let j = 1; j < 4; j++) { // البحث في اسم الدواء والاسم العلمي والفئة
                            if (cells.length > 0 && cells[j].textContent.toLowerCase().includes(searchValue)) {
                                found = true;
                                break;
                            }
                        }
                        
                        rows[i].style.display = found ? '' : 'none';
                    }
                });
            }
            
            // عرض الأدوية منخفضة المخزون
            const lowStockBtn = document.getElementById('lowStockBtn');
            if (lowStockBtn) {
                lowStockBtn.addEventListener('click', function() {
                    const table = document.getElementById('medicinesTable');
                    const rows = table.getElementsByTagName('tr');
                    const showingLowStock = this.classList.contains('active');
                    
                    if (showingLowStock) {
                        // إظهار جميع الأدوية
                        for (let i = 1; i < rows.length; i++) {
                            rows[i].style.display = '';
                        }
                        this.classList.remove('active');
                        this.classList.remove('btn-danger');
                        this.classList.add('btn-outline-danger');
                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> عرض الأدوية منخفضة المخزون';
                    } else {
                        // إظهار الأدوية منخفضة المخزون فقط
                        for (let i = 1; i < rows.length; i++) {