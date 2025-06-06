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
    // جلب بيانات الطبيب
    $stmt = $pdo->prepare("
        SELECT d.*, dep.name as department_name
        FROM doctors d
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("لم يتم العثور على بيانات الطبيب");
    }

    // معالجة تحديث البيانات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $department_id = $_POST['department_id'] ?? null;
        $specialization = $_POST['specialization'] ?? '';
        $experience = $_POST['experience'] ?? '';
        $qualification = $_POST['qualification'] ?? '';

        // معالجة صورة الملف الشخصي
        $profile_picture = $doctor['profile_picture'] ?? 'default-profile.png';
        
        // معالجة طلب حذف الصورة
        if (isset($_POST['delete_profile_picture']) && $_POST['delete_profile_picture'] === '1') {
            // حذف الصورة الحالية إذا كانت موجودة وليست الصورة الافتراضية
            if ($doctor['profile_picture'] && $doctor['profile_picture'] !== 'default-profile.png') {
                $old_image_path = '../uploads/doctors/' . $doctor['profile_picture'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
                // تعيين صورة افتراضية
                $profile_picture = 'default-profile.png';
            }
        } 
        // معالجة تحميل صورة جديدة
        elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/doctors/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // حذف الصورة القديمة إذا كانت موجودة
                    if ($doctor['profile_picture'] && $doctor['profile_picture'] !== 'default-profile.png') {
                        $old_image_path = $upload_dir . $doctor['profile_picture'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    $profile_picture = $new_filename;
                }
            }
        }

        // تحديث بيانات الطبيب
        $stmt = $pdo->prepare("
            UPDATE doctors 
            SET first_name = ?, 
                last_name = ?, 
                email = ?, 
                phone = ?, 
                department_id = ?, 
                specialization = ?, 
                experience = ?, 
                qualification = ?,
                profile_picture = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone,
            $department_id,
            $specialization,
            $experience,
            $qualification,
            $profile_picture,
            $_SESSION['user_id']
        ]);

        $_SESSION['success'] = "تم تحديث البيانات بنجاح";
        header('Location: profile.php');
        exit();
    }

} catch (PDOException $e) {
    $error = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<style>
    /* تنسيق إضافي لصفحة الملف الشخصي */
    .profile-image-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .profile-image-container:hover {
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    
    .profile-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s ease;
    }
    
    .profile-image-container:hover img {
        transform: scale(1.05);
    }
    
    .file-name-display {
        background-color: #f8f9fa;
        transition: all 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    .file-name-display.text-success {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .photo-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .photo-controls .btn {
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .photo-controls .btn:hover {
        transform: translateY(-2px);
    }
    
    .photo-controls .btn-danger:hover {
        background-color: #dc3545;
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
    
    .photo-controls .btn-primary:hover {
        background-color: #007bff;
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }
</style>

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
                        <a class="nav-link text-dark" href="patients.php">
                            <i class="fas fa-users"></i> المرضى
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="medical_records.php">
                            <i class="fas fa-file-medical"></i> السجلات الطبية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="schedule.php">
                            <i class="fas fa-clock"></i> جدول العمل
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="profile.php">
                            <i class="fas fa-user-md"></i> الملف الشخصي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">الملف الشخصي</h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <div class="profile-image-container">
                                <img src="../uploads/doctors/<?php echo htmlspecialchars($doctor['profile_picture'] ?? 'default-profile.png'); ?>" 
                                     alt="صورة الملف الشخصي"
                                     id="profileImagePreview">
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?></p>
                            <p class="text-muted"><?php echo htmlspecialchars($doctor['department_name'] ?? ''); ?></p>
                            
                            <!-- أزرار تغيير وحذف الصورة -->
                            <div class="mt-3 photo-controls">
                                <button type="button" class="btn btn-sm btn-primary" id="changePhotoBtn">
                                    <i class="fas fa-camera"></i> تغيير الصورة
                                </button>
                                <?php if ($doctor['profile_picture'] && $doctor['profile_picture'] !== 'default-profile.png'): ?>
                                <button type="button" class="btn btn-sm btn-danger" id="deletePhotoBtn">
                                    <i class="fas fa-trash"></i> حذف الصورة
                                </button>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">صورة الملف الشخصي</label>
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <div class="file-name-display border rounded p-2 me-2 flex-grow-1" id="fileNameDisplay">
                                            لم يتم اختيار ملف
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="browseFileBtn">
                                            استعراض
                                        </button>
                                    </div>
                                    <small class="text-muted">الملفات المسموح بها: JPG, JPEG, PNG, GIF</small>
                                    <!-- إضافة حقل خفي للتحكم في حذف الصورة -->
                                    <input type="hidden" name="delete_profile_picture" id="delete_profile_picture" value="0">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">الاسم الأول</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($doctor['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">الاسم الأخير</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($doctor['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="department_id" class="form-label">القسم</label>
                                        <select class="form-select" id="department_id" name="department_id">
                                            <option value="">اختر القسم</option>
                                            <?php
                                            $stmt = $pdo->query("SELECT * FROM departments");
                                            while ($department = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($department['id'] == ($doctor['department_id'] ?? '')) ? 'selected' : '';
                                                echo "<option value=\"{$department['id']}\" {$selected}>" . htmlspecialchars($department['name']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="specialization" class="form-label">التخصص</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="experience" class="form-label">سنوات الخبرة</label>
                                        <input type="number" class="form-control" id="experience" name="experience" value="<?php echo htmlspecialchars($doctor['experience'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="qualification" class="form-label">المؤهلات العلمية</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($doctor['qualification'] ?? ''); ?>">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- JavaScript للتحكم في تغيير وحذف الصورة -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // زر تغيير الصورة
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const photoInput = document.getElementById('profile_picture');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const browseFileBtn = document.getElementById('browseFileBtn');
    const profileImagePreview = document.getElementById('profileImagePreview');
    
    // دالة لعرض اسم الملف المحدد
    function displayFileName() {
        if (photoInput.files.length > 0) {
            fileNameDisplay.textContent = photoInput.files[0].name;
            fileNameDisplay.classList.add('text-success');
            
            // عرض معاينة للصورة
            showImagePreview(photoInput.files[0]);
        } else {
            fileNameDisplay.textContent = 'لم يتم اختيار ملف';
            fileNameDisplay.classList.remove('text-success');
        }
    }
    
    // دالة لعرض معاينة الصورة المختارة
    function showImagePreview(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImagePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
    
    if (changePhotoBtn) {
        changePhotoBtn.addEventListener('click', function() {
            photoInput.click();
        });
    }
    
    if (browseFileBtn) {
        browseFileBtn.addEventListener('click', function() {
            photoInput.click();
        });
    }
    
    if (photoInput) {
        photoInput.addEventListener('change', displayFileName);
    }
    
    // زر حذف الصورة
    const deletePhotoBtn = document.getElementById('deletePhotoBtn');
    const deletePhotoInput = document.getElementById('delete_profile_picture');
    
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function() {
            if (confirm('هل أنت متأكد من حذف صورة الملف الشخصي؟')) {
                deletePhotoInput.value = "1";
                document.querySelector('form').submit();
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>