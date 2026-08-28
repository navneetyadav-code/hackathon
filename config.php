<?php
session_start();

$host = 'sql100.infinityfree.com';
$dbUser = 'if0_41650456';
$dbPass = 'WrZyeiySYvUhlj';    
$dbName = 'if0_41650456_login_thikana';

try {
    $pdo = new PDO("mysql:host=$host", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => "Database error: " . $e->getMessage()]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'signup') {
        $role = $_POST['role'] ?? 'seeker';
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($first) || empty($last) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
        
        try {
            $insertStmt->execute([$role, $first, $last, $email, $phone, $hashedPassword]);
            echo json_encode(['status' => 'success', 'message' => 'Account created successfully!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
        }
        exit;
    }

    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Credentials required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            
            echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
        }
        exit;
    }
}
?>