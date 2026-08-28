<?php
session_start();

// ==========================================
// 1. DATABASE CONFIGURATION & AUTO-SETUP
// ==========================================
$host = 'sql100.infinityfree.com';
$dbUser = 'if0_41650456';
$dbPass = 'WrZyeiySYvUhlj';    
$dbName = 'if0_41650456_login_thikana';

try {
    // Connect to MySQL server to ensure DB exists
    $pdo = new PDO("mysql:host=$host", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Auto-create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // Auto-create users table if it doesn't exist
    $tableQuery = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role ENUM('seeker', 'host') NOT NULL DEFAULT 'seeker',
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($tableQuery);
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

// ==========================================
// 2. BACKEND API HANDLERS (Login & Signup)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // --- SIGNUP LOGIC ---
    if ($_POST['action'] === 'signup') {
        $role = $_POST['role'] ?? 'seeker';
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($first) || empty($last) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
            exit;
        }

        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
        
        try {
            $insertStmt->execute([$role, $first, $last, $email, $phone, $hashedPassword]);
            echo json_encode(['status' => 'success', 'message' => 'Account created successfully! You can now log in.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
        }
        exit;
    }

    // --- LOGIN LOGIC ---
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide both email and password.']);
            exit;
        }

        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables upon successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            
            echo json_encode(['status' => 'success', 'message' => 'Login successful! Redirecting to dashboard...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Thikana - Rent Affordability & Roommate Finder</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

:root {
    --brand-primary: #4F46E5; 
    --brand-primary-hover: #4338CA;
    --brand-secondary: #F97316; 
    --bg-color: #F8FAFC; 
    --surface-color: #FFFFFF;
    --text-main: #0F172A; 
    --text-muted: #64748B; 
    --border-color: #E2E8F0; 
    --error-color: #EF4444; 
    --success-color: #10B981; 
    --font-family: 'Plus Jakarta Sans', sans-serif;
}

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family);
    background-color: var(--bg-color);
    color: var(--text-main);
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.app-wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
    min-height: 100svh; 
    flex-direction: row;
}

.hero-section {
    width: 42%;
    position: relative;
    background-color: #000;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: flex-start;
    padding: clamp(32px, 5vw, 64px);
    color: #FFF;
}

.hero-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.1) 0%, rgba(15, 23, 42, 0.95) 100%);
    z-index: 2;
}

.slideshow {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.slide {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    animation: crossfade 18s infinite ease-in-out;
}

.slide:nth-child(1) { animation-delay: 0s; }
.slide:nth-child(2) { animation-delay: 6s; }
.slide:nth-child(3) { animation-delay: 12s; }

@keyframes crossfade {
    0%, 20% { opacity: 1; transform: scale(1.05); }
    33.33%, 86.66% { opacity: 0; transform: scale(1.08); }
    100% { opacity: 1; transform: scale(1); }
}

.hero-content {
    position: relative;
    z-index: 3;
    max-width: 100%;
}

.hero-content h2 {
    font-size: clamp(2rem, 3vw, 3rem);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 16px;
    letter-spacing: -0.02em;
}

.hero-content h2 span {
    color: var(--brand-secondary);
}

.hero-content p {
    font-size: clamp(1rem, 1.2vw, 1.1rem);
    line-height: 1.6;
    opacity: 0.9;
    max-width: 420px;
}

.form-section {
    width: 58%;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    padding: clamp(24px, 5vw, 48px);
    overflow-y: auto;
    background: var(--bg-color);
}

.form-container {
    width: 100%;
    max-width: 520px;
    background: var(--surface-color);
    padding: clamp(24px, 4vw, 48px);
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.02);
    border: 1px solid rgba(226, 232, 240, 0.8);
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-main);
    text-decoration: none;
    margin-bottom: 32px;
    letter-spacing: -0.01em;
}

.brand-logo svg {
    width: 32px;
    height: 32px;
    fill: var(--brand-primary);
}

.auth-tabs {
    display: flex;
    background: var(--bg-color);
    padding: 6px;
    border-radius: 16px;
    margin-bottom: 32px;
    position: relative;
}

.auth-tab {
    flex: 1;
    text-align: center;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.auth-tab.active {
    color: var(--brand-primary);
    background: var(--surface-color);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
}

.auth-view {
    display: none;
    animation: fadeIn 0.4s ease forwards;
}

.auth-view.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-header {
    margin-bottom: 24px;
}

.form-header h3 {
    font-size: 1.5rem;
    color: var(--text-main);
    margin-bottom: 8px;
    font-weight: 700;
}

.form-header p {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.5;
}

.input-group {
    position: relative;
    margin-bottom: 16px;
}

.input-row {
    display: flex;
    gap: 16px;
}

.input-row .input-group {
    flex: 1;
}

.input-field {
    width: 100%;
    padding: 24px 16px 8px;
    border: 1.5px solid var(--border-color);
    border-radius: 14px;
    background-color: var(--surface-color);
    font-size: 1rem;
    font-family: inherit;
    color: var(--text-main);
    transition: all 0.2s ease;
}

.input-field:focus {
    outline: none;
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    background-color: #FFFFFF;
}

.input-label {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: var(--text-muted);
    pointer-events: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.input-field:focus ~ .input-label,
.input-field:not(:placeholder-shown) ~ .input-label {
    top: 14px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--brand-primary);
}

.input-field:not(:placeholder-shown):not(:focus) ~ .input-label {
    color: var(--text-muted);
}

.pref-group { margin-bottom: 20px; }

.pref-label-main {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 12px;
}

.pref-options {
    display: flex;
    gap: 12px;
}

.pref-option {
    flex: 1;
    position: relative;
}

.pref-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 2;
}

.pref-option label {
    display: block;
    text-align: center;
    padding: 14px 10px;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-muted);
    transition: all 0.2s ease;
    background: var(--surface-color);
    position: relative;
    z-index: 1;
}

.pref-option input[type="radio"]:checked + label {
    border-color: var(--brand-primary);
    background-color: rgba(79, 70, 229, 0.04);
    color: var(--brand-primary);
}

.pref-option input[type="radio"]:focus-visible + label {
    outline: 2px solid var(--brand-primary);
    outline-offset: 2px;
}

.pwd-toggle {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pwd-toggle:hover { color: var(--brand-primary); }

.btn-primary {
    width: 100%;
    padding: 16px;
    background-color: var(--brand-primary);
    color: #FFFFFF;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background-color: var(--brand-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
}

.btn-primary:active { transform: translateY(1px); }

.form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    font-size: 0.95rem;
}

.forgot-link {
    color: var(--brand-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.forgot-link:hover {
    color: var(--brand-primary-hover);
    text-decoration: underline;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.4;
}

.checkbox-container input {
    width: 18px;
    height: 18px;
    accent-color: var(--brand-primary);
    cursor: pointer;
    border-radius: 4px;
}

.alert-message {
    margin-top: 16px;
    padding: 14px 16px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    display: none;
    text-align: center;
    animation: fadeIn 0.3s ease;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-error {
    background-color: rgba(239, 68, 68, 0.1);
    color: #B91C1C;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.auth-divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 24px 0;
    color: #718096;
    font-size: 0.875rem;
}
.auth-divider::before, .auth-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #E2E8F0;
}
.auth-divider span { padding: 0 12px; }

.social-login-group {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}
.btn-social {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    background: #fff;
    color: #1A202C;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-social:hover {
    background: #F7FAFC;
    border-color: #CBD5E0;
}

@media (max-width: 1024px) {
    .hero-section { width: 45%; padding: 40px; }
    .form-section { width: 55%; padding: 32px; }
    .hero-content h2 { font-size: 2.2rem; }
    .hero-content p { font-size: 1rem; }
}

@media (max-width: 768px) {
    .app-wrapper { flex-direction: column; min-height: auto; }
    .hero-section {
        width: 100%;
        height: 32vh;
        min-height: 280px;
        padding: 32px 24px;
        justify-content: center;
    }
    .hero-content { max-width: 100%; text-align: left; }
    .hero-content h2 { font-size: clamp(2rem, 7vw, 2.5rem); margin-bottom: 8px; }
    .hero-content p { font-size: 1rem; line-height: 1.5; max-width: 90%; }
    .form-section {
        width: 100%;
        height: auto;
        min-height: 68vh;
        padding: 24px 16px 40px;
        align-items: flex-start;
        background: var(--bg-color);
    }
    .form-container {
        max-width: 100%;
        padding: 28px 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        border: 1px solid rgba(226, 232, 240, 0.9);
        margin-top: -30px; 
        z-index: 10;
        position: relative;
    }
}

@media (max-width: 480px) {
    .hero-section { height: 35vh; min-height: 260px; padding: 24px 20px; }
    .brand-logo { font-size: 1.35rem; margin-bottom: 24px; }
    .brand-logo svg { width: 28px; height: 28px; }
    .auth-tabs { margin-bottom: 24px; }
    .auth-tab { padding: 10px 12px; font-size: 0.9rem; }
    .form-header h3 { font-size: 1.35rem; }
    .input-row { flex-direction: column; gap: 0; }
    .pref-options { gap: 8px; flex-direction: column; }
    .pref-option label { padding: 12px 10px; font-size: 0.9rem; }
    .form-footer { flex-direction: column; gap: 16px; align-items: flex-start; }
}
</style>
</head>
<body>
<div class="app-wrapper">
    <aside class="hero-section">
        <div class="slideshow">
            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&q=80&w=1200" alt="Modern Apartment" class="slide">
            <img src="https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=1200" alt="Bunk beds" class="slide">
            <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=1200" alt="Shared Living" class="slide">
        </div>
        <div class="hero-content">
            <h2>Find your perfect space.<br><span>Split the cost.</span></h2>
            <p>Join Thikana today to discover affordable shared PGs, beautiful flats, and compatible roommates matching your vibe.</p>
        </div>
    </aside>

    <main class="form-section">
        <div class="form-container">
            <a href="#" class="brand-logo">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 3L2 12h3v8h5v-6h4v6h5v-8h3L12 3zm0 2.5l5.5 4.95V18h-1v-6H7.5v6h-1v-7.55L12 5.5z"/><path d="M12 11a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/></svg>
                Thikana
            </a>

            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')" id="tab-login">Log In</div>
                <div class="auth-tab" onclick="switchTab('signup')" id="tab-signup">Sign Up</div>
            </div>

            <!-- LOGIN VIEW -->
            <div class="auth-view active" id="view-login">
                <div class="form-header"><h3>Welcome back!</h3><p>Log in to manage your spaces and roommate requests.</p></div>
                <form id="loginForm" onsubmit="handleLogin(event)">
                    <div class="input-group">
                        <input type="email" id="logEmail" name="email" class="input-field" placeholder=" " required>
                        <label for="logEmail" class="input-label">Email Address</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="logPassword" name="password" class="input-field" placeholder=" " required>
                        <label for="logPassword" class="input-label">Password</label>
                        <button type="button" class="pwd-toggle" onclick="togglePassword('logPassword')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
                    </div>
                    <div class="form-footer" style="margin-bottom: 24px;">
                        <label class="checkbox-container"><input type="checkbox" id="rememberMe"><span>Remember me</span></label>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn-primary" id="btn-login">Sign In</button>
                    
                    <div class="auth-divider"><span>or continue with</span></div>
                    <div class="social-login-group">
                        <button type="button" class="btn-social"><svg viewBox="0 0 48 48" width="20" height="20"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2c-2 1.5-4.5 2.4-7.2 2.4-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C41 35.1 44 29.9 44 24c0-1.3-.1-2.6-.4-3.9z"/></svg>Google</button>
                        <button type="button" class="btn-social"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.1 20.3c-1 .9-2.1.8-3.1.3-1.1-.5-2.1-.5-3.2 0-1.4.6-2.2.4-3.1-.3C2.8 15.3 3.5 7.6 9.1 7.3c1.3.1 2.3.7 3.1.8 1.2-.2 2.3-.9 3.5-.8 1.5.1 2.8.7 3.5 1.8-3 1.8-2.6 5.9.4 7-.7 1.7-1.6 3.3-2.5 4.2zM12 7.3c-.1-2.2 1.7-4.1 3.7-4.3.3 2.3-1.9 4.3-3.7 4.3z"/></svg>Apple</button>
                    </div>
                    <div id="login-alert" class="alert-message"></div>
                </form>
            </div>

            <!-- SIGNUP VIEW -->
            <div class="auth-view" id="view-signup">
                <div class="form-header"><h3>Create an account</h3><p>Start your journey to affordable living today.</p></div>
                <form id="signupForm" onsubmit="handleSignup(event)">
                    <div class="pref-group">
                        <span class="pref-label-main">I am a</span>
                        <div class="pref-options">
                            <div class="pref-option"><input type="radio" id="role-seeker" name="role" value="seeker" checked required><label for="role-seeker">Seeker</label></div>
                            <div class="pref-option"><input type="radio" id="role-host" name="role" value="host" required><label for="role-host">Host</label></div>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><input type="text" id="regFirst" class="input-field" placeholder=" " required><label for="regFirst" class="input-label">First Name</label></div>
                        <div class="input-group"><input type="text" id="regLast" class="input-field" placeholder=" " required><label for="regLast" class="input-label">Last Name</label></div>
                    </div>
                    <div class="input-group"><input type="email" id="regEmail" class="input-field" placeholder=" " required><label for="regEmail" class="input-label">Email</label></div>
                    <div class="input-group"><input type="tel" id="regPhone" class="input-field" placeholder=" " pattern="[0-9]{10}" required><label for="regPhone" class="input-label">Mobile</label></div>
                    <div class="input-row">
                        <div class="input-group">
                            <input type="password" id="regPwd" class="input-field" placeholder=" " minlength="8" required><label for="regPwd" class="input-label">Password</label>
                            <button type="button" class="pwd-toggle" onclick="togglePassword('regPwd')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                        <div class="input-group">
                            <input type="password" id="regConfPwd" class="input-field" placeholder=" " required><label for="regConfPwd" class="input-label">Confirm</label>
                            <button type="button" class="pwd-toggle" onclick="togglePassword('regConfPwd')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>
                    <label class="checkbox-container" style="margin:16px 0 24px"><input type="checkbox" required><span>I agree to the <a href="#" class="forgot-link">Terms</a></span></label>
                    <button type="submit" class="btn-primary" id="btn-signup">Create Account</button>
                    
                    <div class="auth-divider"><span>or sign up with</span></div>
                    <div class="social-login-group">
                        <button type="button" class="btn-social"><svg viewBox="0 0 48 48" width="20" height="20"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2c-2 1.5-4.5 2.4-7.2 2.4-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C41 35.1 44 29.9 44 24c0-1.3-.1-2.6-.4-3.9z"/></svg>Google</button>
                        <button type="button" class="btn-social"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.1 20.3c-1 .9-2.1.8-3.1.3-1.1-.5-2.1-.5-3.2 0-1.4.6-2.2.4-3.1-.3C2.8 15.3 3.5 7.6 9.1 7.3c1.3.1 2.3.7 3.1.8 1.2-.2 2.3-.9 3.5-.8 1.5.1 2.8.7 3.5 1.8-3 1.8-2.6 5.9.4 7-.7 1.7-1.6 3.3-2.5 4.2zM12 7.3c-.1-2.2 1.7-4.1 3.7-4.3.3 2.3-1.9 4.3-3.7 4.3z"/></svg>Apple</button>
                    </div>
                    <div id="signup-alert" class="alert-message"></div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// Tab Switching Logic
function switchTab(tab) {
    document.getElementById('tab-login').classList.remove('active');
    document.getElementById('tab-signup').classList.remove('active');
    document.getElementById('tab-' + tab).classList.add('active');

    document.getElementById('view-login').classList.remove('active');
    document.getElementById('view-signup').classList.remove('active');
    document.getElementById('view-' + tab).classList.add('active');
    
    document.getElementById('login-alert').style.display = 'none';
    document.getElementById('signup-alert').style.display = 'none';
}

// Password Visibility Toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}

// Display Alert Message Helper
function showAlert(elementId, message, type) {
    const alertEl = document.getElementById(elementId);
    alertEl.textContent = message;
    alertEl.className = `alert-message ${type === 'error' ? 'alert-error' : 'alert-success'}`;
    alertEl.style.display = 'block';
}

// Actual Login API Call
async function handleLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-login');
    const alertId = 'login-alert';
    
    btn.innerHTML = 'Authenticating...';
    btn.disabled = true;
    document.getElementById(alertId).style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('email', document.getElementById('logEmail').value);
    formData.append('password', document.getElementById('logPassword').value);

    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            body: formData 
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            showAlert(alertId, data.message, 'success');
            // Redirect to a secure dashboard after success
            setTimeout(() => {
                window.location.href = 'dashboard.php'; // Update redirect as needed
            }, 1500);
        } else {
            showAlert(alertId, data.message, 'error');
        }
    } catch (error) {
        showAlert(alertId, 'An error occurred. Please check your connection.', 'error');
    } finally {
        btn.innerHTML = 'Sign In';
        btn.disabled = false;
    }
}

// Actual Signup API Call
async function handleSignup(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-signup');
    const alertId = 'signup-alert';
    
    const pwd = document.getElementById('regPwd').value;
    const confirmPwd = document.getElementById('regConfPwd').value;

    if (pwd !== confirmPwd) {
        showAlert(alertId, 'Passwords do not match!', 'error');
        return;
    }

    btn.innerHTML = 'Creating Account...';
    btn.disabled = true;
    document.getElementById(alertId).style.display = 'none';

    // Collect data using FormData
    const formData = new FormData();
    formData.append('action', 'signup');
    formData.append('role', document.querySelector('input[name="role"]:checked').value);
    formData.append('first_name', document.getElementById('regFirst').value);
    formData.append('last_name', document.getElementById('regLast').value);
    formData.append('email', document.getElementById('regEmail').value);
    formData.append('phone', document.getElementById('regPhone').value);
    formData.append('password', pwd);

    try {
        const response = await fetch(window.location.href, { 
            method: 'POST', 
            body: formData 
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            showAlert(alertId, data.message, 'success');
            document.getElementById('signupForm').reset();
            // Auto switch to login view
            setTimeout(() => switchTab('login'), 2000);
        } else {
            showAlert(alertId, data.message, 'error');
        }
    } catch (error) {
        showAlert(alertId, 'An error occurred. Please try again.', 'error');
    } finally {
        btn.innerHTML = 'Create Account';
        btn.disabled = false;
    }
}
</script>
</body>
</html>