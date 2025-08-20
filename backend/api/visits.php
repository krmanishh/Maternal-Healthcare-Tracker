<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../auth/auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$db = getDBConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetVisits($db, $user);
            break;
        case 'POST':
            handleCreateVisit($db, $user);
            break;
        case 'PUT':
            handleUpdateVisit($db, $user);
            break;
        case 'DELETE':
            handleDeleteVisit($db, $user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetVisits($db, $user) {
    $pregnancyId = $_GET['pregnancy_id'] ?? null;
    $visitId = $_GET['visit_id'] ?? null;
    
    if ($visitId) {
        // Get specific visit
        $stmt = $db->prepare("
            SELECT v.*, p.user_id, u1.full_name as patient_name, u2.full_name as doctor_name
            FROM visits v
            JOIN pregnancies p ON v.pregnancy_id = p.id
            JOIN users u1 ON p.user_id = u1.id
            JOIN users u2 ON v.doctor_id = u2.id
            WHERE v.id = ?
        ");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();
        
        if (!$visit) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Visit not found']);
            return;
        }
        
        // Check access permissions
        if ($user['user_role'] === 'pregnant_woman' && $visit['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        if ($user['user_role'] === 'doctor_asha' && $visit['doctor_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        echo json_encode(['success' => true, 'visit' => $visit]);
        
    } elseif ($pregnancyId) {
        // Get visits for a specific pregnancy
        $baseQuery = "
            SELECT v.*, u.full_name as doctor_name
            FROM visits v
            JOIN users u ON v.doctor_id = u.id
            WHERE v.pregnancy_id = ?
        ";
        
        // Check access permissions
        if ($user['user_role'] === 'pregnant_woman') {
            $stmt = $db->prepare("SELECT user_id FROM pregnancies WHERE id = ?");
            $stmt->execute([$pregnancyId]);
            $pregnancy = $stmt->fetch();
            
            if (!$pregnancy || $pregnancy['user_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        } elseif ($user['user_role'] === 'doctor_asha') {
            $baseQuery .= " AND v.doctor_id = ?";
        }
        
        $baseQuery .= " ORDER BY v.visit_date DESC";
        
        if ($user['user_role'] === 'doctor_asha') {
            $stmt = $db->prepare($baseQuery);
            $stmt->execute([$pregnancyId, $user['id']]);
        } else {
            $stmt = $db->prepare($baseQuery);
            $stmt->execute([$pregnancyId]);
        }
        
        $visits = $stmt->fetchAll();
        echo json_encode(['success' => true, 'visits' => $visits]);
        
    } else {
        // Get visits based on user role
        if ($user['user_role'] === 'pregnant_woman') {
            $stmt = $db->prepare("
                SELECT v.*, p.id as pregnancy_id, u.full_name as doctor_name
                FROM visits v
                JOIN pregnancies p ON v.pregnancy_id = p.id
                JOIN users u ON v.doctor_id = u.id
                WHERE p.user_id = ?
                ORDER BY v.visit_date DESC
            ");
            $stmt->execute([$user['id']]);
            
        } elseif ($user['user_role'] === 'doctor_asha') {
            $stmt = $db->prepare("
                SELECT v.*, p.id as pregnancy_id, u.full_name as patient_name
                FROM visits v
                JOIN pregnancies p ON v.pregnancy_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE v.doctor_id = ?
                ORDER BY v.visit_date DESC
            ");
            $stmt->execute([$user['id']]);
            
        } else {
            // Admin can see all visits
            $stmt = $db->prepare("
                SELECT v.*, p.id as pregnancy_id, u1.full_name as patient_name, u2.full_name as doctor_name
                FROM visits v
                JOIN pregnancies p ON v.pregnancy_id = p.id
                JOIN users u1 ON p.user_id = u1.id
                JOIN users u2 ON v.doctor_id = u2.id
                ORDER BY v.visit_date DESC
            ");
            $stmt->execute();
        }
        
        $visits = $stmt->fetchAll();
        echo json_encode(['success' => true, 'visits' => $visits]);
    }
}

function handleCreateVisit($db, $user) {
    // Only doctors can create visits
    if ($user['user_role'] !== 'doctor_asha' && $user['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only doctors can create visits']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['pregnancy_id', 'visit_date', 'gestational_week'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Validate pregnancy exists and doctor has access
    $stmt = $db->prepare("SELECT * FROM pregnancies WHERE id = ? AND is_active = 1");
    $stmt->execute([$input['pregnancy_id']]);
    $pregnancy = $stmt->fetch();
    
    if (!$pregnancy) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pregnancy not found']);
        return;
    }
    
    if ($user['user_role'] === 'doctor_asha' && $pregnancy['assigned_doctor_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only create visits for your assigned patients']);
        return;
    }
    
    $db->beginTransaction();
    
    try {
        // Insert visit
        $stmt = $db->prepare("
            INSERT INTO visits (
                pregnancy_id, doctor_id, visit_date, visit_type, gestational_week,
                weight, blood_pressure_systolic, blood_pressure_diastolic,
                hemoglobin, sugar_level, protein_urine, fundal_height,
                fetal_heart_rate, complaints, examination_notes, advice,
                next_visit_date, risk_factors, medications
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['pregnancy_id'],
            $user['id'],
            $input['visit_date'],
            $input['visit_type'] ?? 'routine',
            $input['gestational_week'],
            $input['weight'] ?? null,
            $input['blood_pressure_systolic'] ?? null,
            $input['blood_pressure_diastolic'] ?? null,
            $input['hemoglobin'] ?? null,
            $input['sugar_level'] ?? null,
            $input['protein_urine'] ?? 'nil',
            $input['fundal_height'] ?? null,
            $input['fetal_heart_rate'] ?? null,
            $input['complaints'] ?? null,
            $input['examination_notes'] ?? null,
            $input['advice'] ?? null,
            $input['next_visit_date'] ?? null,
            $input['risk_factors'] ?? null,
            $input['medications'] ?? null
        ]);
        
        $visitId = $db->lastInsertId();
        
        // The database trigger will automatically create risk alerts if needed
        
        $db->commit();
        echo json_encode(['success' => true, 'visit_id' => $visitId, 'message' => 'Visit created successfully']);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleUpdateVisit($db, $user) {
    // Only doctors can update visits
    if ($user['user_role'] !== 'doctor_asha' && $user['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only doctors can update visits']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['visit_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing visit_id']);
        return;
    }
    
    // Check visit exists and user has permission
    $stmt = $db->prepare("SELECT * FROM visits WHERE id = ?");
    $stmt->execute([$input['visit_id']]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visit not found']);
        return;
    }
    
    if ($user['user_role'] === 'doctor_asha' && $visit['doctor_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only update your own visits']);
        return;
    }
    
    // Update visit
    $updateFields = [];
    $updateValues = [];
    
    $allowedFields = [
        'visit_date', 'visit_type', 'gestational_week', 'weight',
        'blood_pressure_systolic', 'blood_pressure_diastolic',
        'hemoglobin', 'sugar_level', 'protein_urine', 'fundal_height',
        'fetal_heart_rate', 'complaints', 'examination_notes', 'advice',
        'next_visit_date', 'risk_factors', 'medications'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    $updateValues[] = $input['visit_id'];
    
    $stmt = $db->prepare("UPDATE visits SET " . implode(', ', $updateFields) . " WHERE id = ?");
    $stmt->execute($updateValues);
    
    echo json_encode(['success' => true, 'message' => 'Visit updated successfully']);
}

function handleDeleteVisit($db, $user) {
    // Only doctors and admins can delete visits
    if ($user['user_role'] !== 'doctor_asha' && $user['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only doctors can delete visits']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['visit_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing visit_id']);
        return;
    }
    
    // Check visit exists and user has permission
    $stmt = $db->prepare("SELECT * FROM visits WHERE id = ?");
    $stmt->execute([$input['visit_id']]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visit not found']);
        return;
    }
    
    if ($user['user_role'] === 'doctor_asha' && $visit['doctor_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own visits']);
        return;
    }
    
    // Delete visit
    $stmt = $db->prepare("DELETE FROM visits WHERE id = ?");
    $stmt->execute([$input['visit_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Visit deleted successfully']);
}
?>
