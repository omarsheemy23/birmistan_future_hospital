<?php
session_start();
require_once '../config/database.php';
require_once 'header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get patient information
$query = "SELECT p.*, u.email, u.username 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error_message'] = "لم يتم العثور على سجل المريض.";
    header("Location: dashboard.php");
    exit();
}

// Set default values for missing fields
$patient['emergency_contact'] = isset($patient['emergency_contact']) ? $patient['emergency_contact'] : '';
$patient['emergency_phone'] = isset($patient['emergency_phone']) ? $patient['emergency_phone'] : '';
$patient['blood_type'] = isset($patient['blood_type']) ? $patient['blood_type'] : 'A+';
$patient['address'] = isset($patient['address']) ? $patient['address'] : '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'username', 'date_of_birth', 'gender', 'phone'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("جميع الحقول المطلوبة يجب ملؤها.");
            }
        }
        
        // Update users table
        $query = "UPDATE users SET 
                  email = :email,
                  username = :username,
                  updated_at = NOW()
                  WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $_POST['email']);
        $stmt->bindParam(":username", $_POST['username']);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        
        // Update patients table
        $query = "UPDATE patients SET 
                  first_name = :first_name,
                  last_name = :last_name,
                  date_of_birth = :date_of_birth,
                  gender = :gender,
                  phone = :phone,
                  address = :address,
                  emergency_contact = :emergency_contact,
                  emergency_phone = :emergency_phone,
                  blood_type = :blood_type,
                  updated_at = NOW()
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":first_name", $_POST['first_name']);
        $stmt->bindParam(":last_name", $_POST['last_name']);
        $stmt->bindParam(":date_of_birth", $_POST['date_of_birth']);
        $stmt->bindParam(":gender", $_POST['gender']);
        $stmt->bindParam(":phone", $_POST['phone']);
        $stmt->bindParam(":address", $_POST['address']);
        $stmt->bindParam(":emergency_contact", $_POST['emergency_contact']);
        $stmt->bindParam(":emergency_phone", $_POST['emergency_phone']);
        $stmt->bindParam(":blood_type", $_POST['blood_type']);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("فشل في تحديث معلومات المريض.");
        }
        
        $db->commit();
        $_SESSION['success_message'] = "تم تحديث الملف الشخصي بنجاح.";
        
        // Refresh patient data
        header("Location: profile.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">الملف الشخصي</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">الاسم الأول</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">الاسم الأخير</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="username" class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">تاريخ الميلاد</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo $patient['date_of_birth']; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="gender" class="form-label">الجنس</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="male" <?php echo $patient['gender'] === 'male' ? 'selected' : ''; ?>>ذكر</option>
                                    <option value="female" <?php echo $patient['gender'] === 'female' ? 'selected' : ''; ?>>أنثى</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="blood_type" class="form-label">فصيلة الدم</label>
                                <select class="form-select" id="blood_type" name="blood_type" required>
                                    <option value="A+" <?php echo $patient['blood_type'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo $patient['blood_type'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo $patient['blood_type'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo $patient['blood_type'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                    <option value="O+" <?php echo $patient['blood_type'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo $patient['blood_type'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                                    <option value="AB+" <?php echo $patient['blood_type'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo $patient['blood_type'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">العنوان</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($patient['address']); ?></textarea>
                        </div>
                        
                        <h5 class="mb-3">معلومات الاتصال في حالات الطوارئ</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">اسم الشخص للاتصال به</label>
                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                                       value="<?php echo htmlspecialchars($patient['emergency_contact']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="emergency_phone" class="form-label">رقم هاتف الطوارئ</label>
                                <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" 
                                       value="<?php echo htmlspecialchars($patient['emergency_phone']); ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>