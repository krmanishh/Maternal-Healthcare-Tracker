<?php
session_start();
require_once '../../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, user_role, full_name, phone FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function register($userData) {
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, user_role, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['user_role'],
                $userData['full_name'],
                $userData['phone']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // If it's a pregnant woman, create pregnancy record
            if ($userData['user_role'] == 'pregnant_woman') {
                $pregnancyStmt = $this->db->prepare("INSERT INTO pregnancies (user_id, age, lmp_date, address, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?)");
                $pregnancyStmt->execute([
                    $userId,
                    $userData['age'],
                    $userData['lmp_date'],
                    $userData['address'],
                    $userData['emergency_contact_name'],
                    $userData['emergency_contact_phone']
                ]);
            }
            
            return ['success' => true, 'user_id' => $userId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'user_role' => $_SESSION['user_role'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email']
            ];
        }
        return null;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }
    }
    
    public function requireRole($requiredRole) {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== $requiredRole) {
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['user_role'] === $role;
    }
}
?>
