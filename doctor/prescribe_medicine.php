<?php
session_start();
require_once '../includes/config.php';

// التحقق من تسجيل الدخول وأن المستخدم طبيب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// التحقق من وجود معرف المريض
if (!isset($_GET['patient_id'])) {
    header('Location: patients.php');
    exit();
}

$patient_id = $_GET['patient_id'];
$record_id = $_GET['record_id'] ?? null;
$success_message = null;
$error_message = null;

try {
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $doctor['id'] ?? 0;

    if (!$doctor_id) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // جلب بيانات المريض
    $stmt = $pdo->prepare("
        SELECT p.*, u.email, u.phone, u.created_at as registration_date
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("لم يتم العثور على بيانات المريض");
    }

    // جلب السجل الطبي إذا تم تحديده
    $medical_record = null;
    if ($record_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM medical_records WHERE id = ? AND patient_id = ? AND doctor_id = ?
        ");
        $stmt->execute([$record_id, $patient_id, $doctor_id]);
        $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // جلب قائمة الأدوية المتوفرة في المخزون
    $stmt = $pdo->prepare("
        SELECT m.*, 
               COALESCE(SUM(pi.quantity), 0) as total_quantity
        FROM medicines m
        LEFT JOIN pharmacy_inventory pi ON m.id = pi.medicine_id AND pi.status = 'active'
        WHERE pi.quantity > 0
        GROUP BY m.id
        ORDER BY m.name ASC
    ");
    $stmt->execute();
    $available_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // معالجة إرسال النموذج لصرف الأدوية
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_prescription'])) {
        $pdo->beginTransaction();

        try {
            // إنشاء روشتة جديدة
            $stmt = $pdo->prepare("
                INSERT INTO prescriptions (patient_id, doctor_id, medical_record_id, prescription_date, diagnosis, notes, status)
                VALUES (?, ?, ?, CURRENT_DATE, ?, ?, 'pending')
            ");
            $stmt->execute([
                $patient_id,
                $doctor_id,
                $record_id,
                $_POST['diagnosis'],
                $_POST['notes']
            ]);
            $prescription_id = $pdo->lastInsertId();

            // تخزين معلومات الأدوية في ملاحظات الروشتة مؤقتًا بدلاً من استخدام جدول prescription_medicines
            if (isset($_POST['medicine_id']) && is_array($_POST['medicine_id'])) {
                $medicine_info = [];

                for ($i = 0; $i < count($_POST['medicine_id']); $i++) {
                    // التحقق من وجود الدواء والكمية
                    if (empty($_POST['medicine_id'][$i]) || empty($_POST['quantity'][$i])) {
                        continue;
                    }

                    $medicine_id = $_POST['medicine_id'][$i];
                    $quantity = $_POST['quantity'][$i];
                    $dosage = $_POST['dosage'][$i] ?? '';
                    $frequency = $_POST['frequency'][$i] ?? '';
                    $duration = $_POST['duration'][$i] ?? '';
                    $instructions = $_POST['instructions'][$i] ?? '';

                    // التحقق من توفر الكمية في المخزون
                    $stmt = $pdo->prepare("
                        SELECT m.name, SUM(pi.quantity) as available_quantity
                        FROM medicines m 
                        JOIN pharmacy_inventory pi ON m.id = pi.medicine_id 
                        WHERE m.id = ? AND pi.status = 'active'
                        GROUP BY m.id
                    ");
                    $stmt->execute([$medicine_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $available_quantity = $result['available_quantity'] ?? 0;
                    $medicine_name = $result['name'] ?? 'دواء غير معروف';

                    if ($available_quantity < $quantity) {
                        throw new Exception("الكمية المطلوبة من الدواء ($medicine_name) غير متوفرة في المخزون");
                    }

                    // تخزين معلومات الدواء في المصفوفة
                    $medicine_info[] = [
                        'id' => $medicine_id,
                        'name' => $medicine_name,
                        'quantity' => $quantity,
                        'dosage' => $dosage,
                        'frequency' => $frequency,
                        'duration' => $duration,
                        'instructions' => $instructions
                    ];

                    // إضافة الدواء إلى جدول prescription_medicines
                    $insert_medicine = $pdo->prepare("
                        INSERT INTO prescription_medicines 
                        (prescription_id, medicine_id, quantity, dosage_instructions) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    // تجميع تعليمات الجرعة في نص واحد
                    $dosage_instructions = "الجرعة: $dosage، التكرار: $frequency، المدة: $duration، تعليمات: $instructions";
                    
                    $insert_medicine->execute([
                        $prescription_id,
                        $medicine_id,
                        $quantity,
                        $dosage_instructions
                    ]);
                }

                // إضافة معلومات الأدوية إلى ملاحظات الوصفة
                if (!empty($medicine_info)) {
                    $medicine_notes = "الأدوية المصروفة:\n";
                    foreach ($medicine_info as $med) {
                        $medicine_notes .= "- {$med['name']} (الكمية: {$med['quantity']})\n";
                        $medicine_notes .= "  الجرعة: {$med['dosage']}, التكرار: {$med['frequency']}, المدة: {$med['duration']}, تعليمات: {$med['instructions']}\n";
                    }

                    // تحديث ملاحظات الوصفة لتشمل معلومات الأدوية
                    $update_notes = $pdo->prepare("
                        UPDATE prescriptions 
                        SET notes = CONCAT(notes, '\n\n', ?) 
                        WHERE id = ?
                    ");
                    $update_notes->execute([$medicine_notes, $prescription_id]);
                }
            }

            $pdo->commit();
            $success_message = "تم حفظ الوصفة الطبية بنجاح وإضافة الأدوية";
            
            // إعادة التوجيه بعد الإنشاء الناجح
            header('Location: view_patient.php?id=' . $patient_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "حدث خطأ أثناء صرف الأدوية: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    $error_message = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// استدعاء ملف الهيدر بعد معالجة البيانات
include('header.php');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <!-- Hospital Logo and Name -->
                <div class="text-center mb-4">
                    <h4 class="text-primary">مستشفى بارمستان المستقبل</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="dashboard.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="appointments.php">
                            <i class="fas fa-calendar-alt"></i> المواعيد
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="patients.php">
                            <i class="fas fa-users"></i> المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="view_prescriptions.php">
                            <i class="fas fa-prescription"></i> الروشتات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="pharmacy_inventory.php">
                            <i class="fas fa-pills"></i> مخزون الأدوية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="profile.php">
                            <i class="fas fa-user-md"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">صرف أدوية للمريض</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> العودة إلى صفحة المريض
                    </a>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($patient)): ?>
                <!-- Patient Info Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">بيانات المريض</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>الاسم:</strong> <?php echo htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')); ?></p>
                                <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($patient['email'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($patient['phone'] ?? ''); ?></p>
                                <p><strong>تاريخ الميلاد:</strong> <?php echo isset($patient['birth_date']) ? date('Y-m-d', strtotime($patient['birth_date'])) : 'غير محدد'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($medical_record): ?>
                    <!-- Medical Record Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">بيانات السجل الطبي المحدد</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>تاريخ الكشف:</strong> <?php echo date('Y-m-d', strtotime($medical_record['created_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>التشخيص:</strong> <?php echo htmlspecialchars($medical_record['diagnosis'] ?? ''); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($medical_record['prescription'])): ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><strong>الوصفة الطبية الموصى بها:</strong></p>
                                        <p><?php echo nl2br(htmlspecialchars($medical_record['prescription'] ?? '')); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Prescription Form -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">صرف الأدوية</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="prescriptionForm">
                            <div class="mb-3">
                                <label for="diagnosis" class="form-label">التشخيص</label>
                                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="2" required><?php echo $medical_record['diagnosis'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الأدوية المصروفة</label>
                                <div id="medicinesList">
                                    <div class="medicine-item card mb-2">
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">اختر الدواء</label>
                                                    <select class="form-select medicine-select" name="medicine_id[]" required>
                                                        <option value="">-- اختر دواء --</option>
                                                        <?php foreach ($available_medicines as $medicine): ?>
                                                            <option value="<?php echo $medicine['id']; ?>" data-available="<?php echo $medicine['total_quantity']; ?>">
                                                                <?php echo htmlspecialchars($medicine['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">الكمية</label>
                                                    <input type="number" class="form-control medicine-quantity" name="quantity[]" min="1" value="1" required>
                                                    <small class="available-text text-muted"></small>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger remove-medicine">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">الجرعة</label>
                                                    <input type="text" class="form-control" name="dosage[]" placeholder="مثال: قرص واحد">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">التكرار</label>
                                                    <input type="text" class="form-control" name="frequency[]" placeholder="مثال: 3 مرات يومياً">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">المدة</label>
                                                    <input type="text" class="form-control" name="duration[]" placeholder="مثال: 5 أيام">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">قبل/بعد الطعام</label>
                                                    <select class="form-select" name="instructions[]">
                                                        <option value="قبل الطعام">قبل الطعام</option>
                                                        <option value="بعد الطعام">بعد الطعام</option>
                                                        <option value="مع الطعام">مع الطعام</option>
                                                        <option value="حسب الحاجة">حسب الحاجة</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-2">
                                    <button type="button" id="addMedicine" class="btn btn-secondary">
                                        <i class="fas fa-plus-circle"></i> إضافة دواء آخر
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">ملاحظات إضافية</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="submit_prescription" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ وصرف الأدوية
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const medicinesList = document.getElementById('medicinesList');
    const addMedicineBtn = document.getElementById('addMedicine');

    // إضافة دواء جديد للقائمة
    addMedicineBtn.addEventListener('click', function() {
        const firstItem = medicinesList.querySelector('.medicine-item');
        const newItem = firstItem.cloneNode(true);
        
        // إعادة تعيين القيم
        newItem.querySelectorAll('input').forEach(input => {
            if (input.name === 'quantity[]') {
                input.value = 1;
            } else {
                input.value = '';
            }
        });
        newItem.querySelector('select.medicine-select').selectedIndex = 0;
        
        // إضافة العنصر الجديد للقائمة
        medicinesList.appendChild(newItem);
        
        // إعادة تعيين مستمعي الأحداث
        setupEventListeners();
    });

    // إعداد مستمعي الأحداث لجميع عناصر الأدوية
    function setupEventListeners() {
        // أزرار حذف الدواء
        document.querySelectorAll('.remove-medicine').forEach(button => {
            button.addEventListener('click', function() {
                if (medicinesList.querySelectorAll('.medicine-item').length > 1) {
                    this.closest('.medicine-item').remove();
                } else {
                    alert('يجب إضافة دواء واحد على الأقل');
                }
            });
        });

        // مراقبة تغيير الدواء المحدد
        document.querySelectorAll('.medicine-select').forEach(select => {
            select.addEventListener('change', function() {
                const item = this.closest('.medicine-item');
                const quantityInput = item.querySelector('.medicine-quantity');
                const availableText = item.querySelector('.available-text');
                
                if (this.selectedIndex > 0) {
                    const option = this.options[this.selectedIndex];
                    const availableQuantity = option.getAttribute('data-available');
                    
                    quantityInput.max = availableQuantity;
                    availableText.textContent = `الكمية المتوفرة: ${availableQuantity}`;
                    
                    if (parseInt(quantityInput.value) > parseInt(availableQuantity)) {
                        quantityInput.value = availableQuantity;
                    }
                } else {
                    availableText.textContent = '';
                }
            });
        });

        // مراقبة تغيير الكمية
        document.querySelectorAll('.medicine-quantity').forEach(input => {
            input.addEventListener('input', function() {
                const item = this.closest('.medicine-item');
                const select = item.querySelector('.medicine-select');
                
                if (select.selectedIndex > 0) {
                    const option = select.options[select.selectedIndex];
                    const availableQuantity = parseInt(option.getAttribute('data-available'));
                    
                    if (parseInt(this.value) > availableQuantity) {
                        this.value = availableQuantity;
                        alert(`الكمية المتوفرة من هذا الدواء هي ${availableQuantity} فقط`);
                    }
                }
            });
        });
    }

    // إعداد مستمعي الأحداث عند تحميل الصفحة
    setupEventListeners();

    // التحقق من صحة النموذج قبل الإرسال
    document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
        let isValid = true;
        const medicines = document.querySelectorAll('.medicine-select');
        const selectedIds = [];

        medicines.forEach(select => {
            if (select.value) {
                // التحقق من عدم تكرار نفس الدواء
                if (selectedIds.includes(select.value)) {
                    isValid = false;
                    alert('لا يمكن إضافة نفس الدواء مرتين. يرجى زيادة الكمية بدلاً من ذلك.');
                } else {
                    selectedIds.push(select.value);
                }
            } else {
                isValid = false;
                alert('يرجى اختيار دواء لكل عنصر');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 