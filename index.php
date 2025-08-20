<?php
require_once 'backend/auth/auth.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $role = $_SESSION['user_role'];
    switch ($role) {
        case 'pregnant_woman':
            header('Location: patient_dashboard.php');
            break;
        case 'doctor_asha':
            header('Location: doctor_dashboard.php');
            break;
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
    }
    exit();
}

$error = '';
$success = '';

if ($_POST) {
    $auth = new Auth();
    $result = $auth->login($_POST['username'], $_POST['password']);
    
    if ($result['success']) {
        $role = $result['user']['user_role'];
        switch ($role) {
            case 'pregnant_woman':
                header('Location: patient_dashboard.php');
                break;
            case 'doctor_asha':
                header('Location: doctor_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
        }
        exit();
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Healthcare Tracker - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card login-card">
                        <div class="card-header text-center">
                            <i class="fas fa-baby fa-3x mb-3"></i>
                            <h3>Maternal Healthcare Tracker</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username or Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center">
                                <p class="mb-2">New patient? <a href="register.php" class="text-decoration-none">Register here</a></p>
                                <p><small class="text-muted">Demo Accounts:</small></p>
                                <small class="text-muted">
                                    Admin: admin/admin123<br>
                                    Doctor: dr_sharma/doctor123
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
