<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all departments
$query = "SELECT * FROM departments";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 class="mb-4">Our Departments</h1>
    
    <div class="row">
        <?php foreach ($departments as $department): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $department['name']; ?></h5>
                        <p class="card-text"><?php echo $department['description']; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 