<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get appointment details if appointment_id is provided
$appointment = null;
if (isset($_GET['appointment_id'])) {
    $stmt = $db->prepare("
        SELECT a.*, d.consultation_fee, p.first_name as patient_first_name, p.last_name as patient_last_name,
               doc.first_name as doctor_first_name, doc.last_name as doctor_last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors doc ON a.doctor_id = doc.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.id = ? AND a.patient_id = ?
    ");
    $stmt->execute([$_GET['appointment_id'], $_SESSION['patient_id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $db->beginTransaction();
        
        // Validate card number (basic validation)
        $card_number = str_replace(' ', '', $_POST['card_number']);
        if (!preg_match('/^[0-9]{16}$/', $card_number)) {
            throw new Exception('رقم البطاقة غير صالح');
        }
        
        // Process payment (in a real system, this would connect to a payment gateway)
        $stmt = $db->prepare("
            INSERT INTO payments (appointment_id, amount, payment_status, payment_method, transaction_id)
            VALUES (?, ?, 'completed', 'credit_card', ?)
        ");
        
        $transaction_id = 'TRX-' . time() . '-' . rand(1000, 9999);
        $stmt->execute([
            $_POST['appointment_id'],
            $appointment['consultation_fee'],
            $transaction_id
        ]);
        
        // Update appointment status
        $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$_POST['appointment_id']]);
        
        $db->commit();
        $_SESSION['success'] = "تم الدفع بنجاح! رقم المعاملة: " . $transaction_id;
        header("Location: patient/dashboard.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "خطأ في عملية الدفع: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفع رسوم الكشف</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            background-color: #f8f9fa;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-input {
            letter-spacing: 2px;
        }
        .payment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="payment-container">
            <h2 class="text-center mb-4">دفع رسوم الكشف</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($appointment): ?>
                <div class="payment-details">
                    <h5>تفاصيل الكشف</h5>
                    <p><strong>الطبيب:</strong> د. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></p>
                    <p><strong>المريض:</strong> <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></p>
                    <p><strong>التاريخ:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                    <p><strong>الوقت:</strong> <?php echo htmlspecialchars($appointment['appointment_time']); ?></p>
                    <p><strong>رسوم الكشف:</strong> <?php echo htmlspecialchars($appointment['consultation_fee']); ?> جنيه مصري</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">رقم البطاقة</label>
                        <input type="text" name="card_number" class="form-control card-input" 
                               placeholder="1234 5678 9012 3456" maxlength="19" required
                               pattern="[0-9\s]{19}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تاريخ الانتهاء</label>
                            <input type="text" name="expiry_date" class="form-control" 
                                   placeholder="MM/YY" maxlength="5" required
                                   pattern="(0[1-9]|1[0-2])\/([0-9]{2})">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CVV</label>
                            <input type="text" name="cvv" class="form-control" 
                                   placeholder="123" maxlength="3" required
                                   pattern="[0-9]{3}">
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="process_payment" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i> ادفع الآن
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    لم يتم العثور على تفاصيل الكشف. يرجى التأكد من صحة الرابط.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format card number input
        document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formatted = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = formatted;
        });

        // Format expiry date input
        document.querySelector('input[name="expiry_date"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });
    </script>
</body>
</html> 