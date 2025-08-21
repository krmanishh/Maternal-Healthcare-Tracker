<?php
require_once 'backend/auth/auth.php';

$error = '';
$success = '';

if ($_POST) {
    $auth = new Auth();
    
    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'full_name', 'phone', 'age', 'lmp_date'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = "Please fill in all required fields: " . implode(', ', $missing_fields);
    } else {
        $userData = [
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'user_role' => 'pregnant_woman',
            'full_name' => trim($_POST['full_name']),
            'phone' => trim($_POST['phone']),
            'age' => (int)$_POST['age'],
            'lmp_date' => $_POST['lmp_date'],
            'address' => trim($_POST['address']),
            'emergency_contact_name' => trim($_POST['emergency_contact_name']),
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'])
        ];
        
        $result = $auth->register($userData);
        
        if ($result['success']) {
            $success = "Registration successful! Please login to continue.";
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Maternal Healthcare Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card">
                        <div class="card-header text-center">
                            <i class="fas fa-user-plus fa-2x mb-2"></i>
                            <h3>Patient Registration</h3>
                            <p class="mb-0">Join our maternal healthcare program</p>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($success); ?>
                                    <br><a href="index.php" class="alert-link">Click here to login</a>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="age" class="form-label">Age *</label>
                                        <input type="number" class="form-control" id="age" name="age" min="18" max="50"
                                               value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                        <div class="form-text">Password must be at least 6 characters long</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lmp_date" class="form-label">Last Menstrual Period (LMP) Date *</label>
                                    <input type="date" class="form-control" id="lmp_date" name="lmp_date"
                                           value="<?php echo isset($_POST['lmp_date']) ? htmlspecialchars($_POST['lmp_date']) : ''; ?>" required>
                                    <div class="form-text">This helps us calculate your pregnancy timeline</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                                               value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                        <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"
                                               value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus"></i> Register Now
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <p>Already have an account? <a href="index.php" class="text-decoration-none">Login here</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set maximum date for LMP to today
        document.getElementById('lmp_date').max = new Date().toISOString().split('T')[0];
        
        // Set minimum date for LMP to 300 days ago
        const minDate = new Date();
        minDate.setDate(minDate.getDate() - 300);
        document.getElementById('lmp_date').min = minDate.toISOString().split('T')[0];
    </script>
</body>
</html>
