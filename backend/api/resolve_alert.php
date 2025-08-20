<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../auth/auth.php';

$auth = new Auth();

// Check if user is logged in and has appropriate role
if (!$auth->isLoggedIn() || !($auth->hasRole('doctor_asha') || $auth->hasRole('admin'))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$user = $auth->getCurrentUser();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['alert_id']) || !is_numeric($input['alert_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid alert ID']);
    exit;
}

$alertId = (int)$input['alert_id'];
$db = getDBConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Check if alert exists and user has permission to resolve it
    $stmt = $db->prepare("SELECT ra.*, p.assigned_doctor_id 
                         FROM risk_alerts ra
                         JOIN pregnancies p ON ra.pregnancy_id = p.id
                         WHERE ra.id = ?");
    $stmt->execute([$alertId]);
    $alert = $stmt->fetch();
    
    if (!$alert) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Alert not found']);
        exit;
    }
    
    // Check if user has permission (doctor can only resolve their patients' alerts, admin can resolve all)
    if ($user['user_role'] === 'doctor_asha' && $alert['assigned_doctor_id'] != $user['id']) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'You can only resolve alerts for your patients']);
        exit;
    }
    
    // Update alert as resolved
    $stmt = $db->prepare("UPDATE risk_alerts 
                         SET is_resolved = 1, resolved_at = NOW(), resolved_by = ?
                         WHERE id = ?");
    $stmt->execute([$user['id'], $alertId]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Alert resolved successfully']);
    
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
