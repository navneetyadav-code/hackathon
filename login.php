<?php
session_start();
require_once 'config/config.php';

// Handle Backend API Requests (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    try {
        // -- SIGNUP LOGIC --
        if ($action === 'signup') {
            // Whitelist valid roles
            $role = in_array($data['role'] ?? '', ['seeker', 'host']) ? $data['role'] : 'seeker';
            
            // Sanitize & validate inputs
            $first = trim(htmlspecialchars($data['first_name'] ?? ''));
            $last = trim(htmlspecialchars($data['last_name'] ?? ''));
            $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $phone = trim(htmlspecialchars($data['phone'] ?? ''));
            $password = $data['password'] ?? '';

            if (empty($first) || empty($last) || empty($email) || empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
                exit;
            }

            if (strlen($password) < 8) {
                echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
                exit;
            }

            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
                exit;
            }

            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$role, $first, $last, $email, $phone, $hashedPassword])) {
                echo json_encode(['status' => 'success', 'message' => 'Account created successfully! Please log in.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
            }
            exit;
        }

        // -- LOGIN LOGIC --
        if ($action === 'login') {
            $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify user exists and password matches
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Set secure session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                
                // Role-Based Access Control routing
                $redirect = ($user['role'] === 'host') ? 'dashboard_host.php' : 'dashboard_seek.php';
                
                echo json_encode(['status' => 'success', 'message' => 'Login successful!', 'redirect' => $redirect]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
            }
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'A server error occurred. Please try again later.']);
        exit;
    }
}
?>
<!-- Frontend View (GET) -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

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

    .slideshow { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; }
    .slide {
        position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0;
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

    .hero-content { position: relative; z-index: 3; max-width: 100%; }
    .hero-content h2 { font-size: clamp(2rem, 3vw, 3rem); font-weight: 700; line-height: 1.15; margin-bottom: 16px; letter-spacing: -0.02em; }
    .hero-content h2 span { color: var(--brand-secondary); }
    .hero-content p { font-size: clamp(1rem, 1.2vw, 1.1rem); line-height: 1.6; opacity: 0.9; max-width: 420px; }

    .form-section {
        width: 58%; display: flex; justify-content: center; align-items: center; position: relative;
        padding: clamp(24px, 5vw, 48px); overflow-y: auto; background: var(--bg-color);
    }
    .form-container {
        width: 100%; max-width: 520px; background: var(--surface-color); padding: clamp(24px, 4vw, 48px);
        border-radius: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.02);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .brand-logo {
        display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; color: var(--text-main);
        text-decoration: none; margin-bottom: 32px; letter-spacing: -0.01em;
    }
    .brand-logo svg { width: 32px; height: 32px; fill: var(--brand-primary); }

    .auth-tabs { display: flex; background: var(--bg-color); padding: 6px; border-radius: 16px; margin-bottom: 32px; position: relative; }
    .auth-tab {
        flex: 1; text-align: center; padding: 12px 20px; font-size: 0.95rem; font-weight: 600;
        color: var(--text-muted); cursor: pointer; border-radius: 12px; transition: all 0.3s ease; position: relative; z-index: 2;
    }
    .auth-tab.active { color: var(--brand-primary); background: var(--surface-color); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); }
    
    .auth-view { display: none; animation: fadeIn 0.4s ease forwards; }
    .auth-view.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-header { margin-bottom: 24px; }
    .form-header h3 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 8px; font-weight: 700; }
    .form-header p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; }

    .input-group { position: relative; margin-bottom: 16px; }
    .input-row { display: flex; gap: 16px; }
    .input-row .input-group { flex: 1; }

    .input-field {
        width: 100%; padding: 24px 16px 8px; border: 1.5px solid var(--border-color); border-radius: 14px;
        background-color: var(--surface-color); font-size: 1rem; font-family: inherit; color: var(--text-main); transition: all 0.2s ease;
    }
    .input-field:focus { outline: none; border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); background-color: #FFFFFF; }
    
    .input-label {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 1rem; color: var(--text-muted);
        pointer-events: none; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .input-field:focus ~ .input-label, .input-field:not(:placeholder-shown) ~ .input-label { top: 14px; font-size: 0.75rem; font-weight: 600; color: var(--brand-primary); }
    .input-field:not(:placeholder-shown):not(:focus) ~ .input-label { color: var(--text-muted); }

    .pref-group { margin-bottom: 20px; }
    .pref-label-main { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 12px; }
    .pref-options { display: flex; gap: 12px; }
    .pref-option { flex: 1; position: relative; }
    .pref-option input[type="radio"] { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 2; }
    .pref-option label {
        display: block; text-align: center; padding: 14px 10px; border: 1.5px solid var(--border-color); border-radius: 12px;
        font-size: 0.95rem; font-weight: 600; color: var(--text-muted); transition: all 0.2s ease; background: var(--surface-color); position: relative; z-index: 1;
    }
    .pref-option input[type="radio"]:checked + label { border-color: var(--brand-primary); background-color: rgba(79, 70, 229, 0.04); color: var(--brand-primary); }

    .pwd-toggle {
        position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted);
        cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;
    }
    .pwd-toggle:hover { color: var(--brand-primary); }

    .btn-primary {
        width: 100%; padding: 16px; background-color: var(--brand-primary); color: #FFFFFF; border: none; border-radius: 14px;
        font-size: 1rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.3s ease; margin-top: 8px; display: flex; justify-content: center;
    }
    .btn-primary:hover { background-color: var(--brand-primary-hover); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25); }
    .form-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; font-size: 0.95rem; }
    .forgot-link { color: var(--brand-primary); text-decoration: none; font-weight: 600; }
    .forgot-link:hover { text-decoration: underline; }
    
    .checkbox-container { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.9rem; color: var(--text-muted); }
    .checkbox-container input { width: 18px; height: 18px; accent-color: var(--brand-primary); cursor: pointer; }
    
    .alert-message { margin-top: 16px; padding: 14px 16px; border-radius: 10px; font-size: 0.95rem; font-weight: 500; display: none; text-align: center; }
    .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #047857; border: 1px solid rgba(16, 185, 129, 0.2); }
    .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #B91C1C; border: 1px solid rgba(239, 68, 68, 0.2); }

    @media (max-width: 1024px) {
        .hero-section { width: 45%; padding: 40px; }
        .form-section { width: 55%; padding: 32px; }
    }
    @media (max-width: 768px) {
        .app-wrapper { flex-direction: column; min-height: auto; }
        .hero-section { width: 100%; height: 32vh; min-height: 280px; padding: 32px 24px; justify-content: center; }
        .hero-content h2 { font-size: clamp(2rem, 7vw, 2.5rem); margin-bottom: 8px; }
        .form-section { width: 100%; height: auto; min-height: 68vh; padding: 24px 16px 40px; align-items: flex-start; }
        .form-container { max-width: 100%; padding: 28px 20px; margin-top: -30px; z-index: 10; position: relative; }
    }
    @media (max-width: 480px) {
        .input-row { flex-direction: column; gap: 0; }
        .pref-options { flex-direction: column; }
        .form-footer { flex-direction: column; gap: 16px; align-items: flex-start; }
    }
</style>
</head>
<body>
<div class="app-wrapper">
    <aside class="hero-section">
        <div class="slideshow">
            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&q=80&w=1200" alt="Space 1" class="slide">
            <img src="https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=1200" alt="Space 2" class="slide">
            <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=1200" alt="Space 3" class="slide">
        </div>
        <div class="hero-content">
            <h2>Find your perfect space.<br><span>Split the cost.</span></h2>
            <p>Join Thikana today to discover affordable spaces and compatible roommates.</p>
        </div>
    </aside>

    <main class="form-section">
        <div class="form-container">
            <a href="#" class="brand-logo">
                <svg viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h5v-6h4v6h5v-8h3L12 3zm0 2.5l5.5 4.95V18h-1v-6H7.5v6h-1v-7.55L12 5.5z"/><path d="M12 11a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/></svg>
                Thikana
            </a>

            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')" id="tab-login">Log In</div>
                <div class="auth-tab" onclick="switchTab('signup')" id="tab-signup">Sign Up</div>
            </div>

            <!-- LOGIN VIEW -->
            <div class="auth-view active" id="view-login">
                <div class="form-header"><h3>Welcome back!</h3><p>Log in to manage your spaces.</p></div>
                <form id="loginForm" onsubmit="handleAuth(event, 'login')">
                    <div class="input-group">
                        <input type="email" id="logEmail" name="email" class="input-field" placeholder=" " required>
                        <label for="logEmail" class="input-label">Email Address</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="logPassword" name="password" class="input-field" placeholder=" " required>
                        <label for="logPassword" class="input-label">Password</label>
                        <button type="button" class="pwd-toggle" onclick="togglePwd('logPassword')">👁</button>
                    </div>
                    <div class="form-footer" style="margin-bottom: 24px;">
                        <label class="checkbox-container"><input type="checkbox"><span>Remember me</span></label>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn-primary" id="btn-login">Sign In</button>
                    <div id="login-alert" class="alert-message"></div>
                </form>
            </div>

            <!-- SIGNUP VIEW -->
            <div class="auth-view" id="view-signup">
                <div class="form-header"><h3>Create account</h3><p>Start your journey today.</p></div>
                <form id="signupForm" onsubmit="handleAuth(event, 'signup')">
                    <div class="pref-group">
                        <span class="pref-label-main">I am a</span>
                        <div class="pref-options">
                            <div class="pref-option"><input type="radio" id="role-seeker" name="role" value="seeker" checked><label for="role-seeker">Seeker</label></div>
                            <div class="pref-option"><input type="radio" id="role-host" name="role" value="host"><label for="role-host">Host</label></div>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group"><input type="text" id="regFirst" class="input-field" placeholder=" " required><label for="regFirst" class="input-label">First Name</label></div>
                        <div class="input-group"><input type="text" id="regLast" class="input-field" placeholder=" " required><label for="regLast" class="input-label">Last Name</label></div>
                    </div>
                    <div class="input-group"><input type="email" id="regEmail" class="input-field" placeholder=" " required><label for="regEmail" class="input-label">Email</label></div>
                    <div class="input-group"><input type="tel" id="regPhone" class="input-field" placeholder=" " required><label for="regPhone" class="input-label">Mobile</label></div>
                    <div class="input-row">
                        <div class="input-group"><input type="password" id="regPwd" class="input-field" placeholder=" " required><label for="regPwd" class="input-label">Password</label><button type="button" class="pwd-toggle" onclick="togglePwd('regPwd')">👁</button></div>
                        <div class="input-group"><input type="password" id="regConfPwd" class="input-field" placeholder=" " required><label for="regConfPwd" class="input-label">Confirm</label><button type="button" class="pwd-toggle" onclick="togglePwd('regConfPwd')">👁</button></div>
                    </div>
                    <button type="submit" class="btn-primary" id="btn-signup">Create Account</button>
                    <div id="signup-alert" class="alert-message"></div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.auth-tab, .auth-view').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('view-' + tab).classList.add('active');
    document.querySelectorAll('.alert-message').forEach(el => el.style.display = 'none');
}

function togglePwd(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function showAlert(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.className = `alert-message alert-${type}`;
    el.style.display = 'block';
}

async function handleAuth(e, action) {
    e.preventDefault();
    const btn = document.getElementById(`btn-${action}`);
    const alertId = `${action}-alert`;
    
    if(action === 'signup' && document.getElementById('regPwd').value !== document.getElementById('regConfPwd').value) {
        return showAlert(alertId, 'Passwords do not match!', 'error');
    }

    btn.disabled = true; 
    btn.innerHTML = 'Processing...';
    document.getElementById(alertId).style.display = 'none';

    let payload = { action: action };

    if (action === 'login') {
        payload.email = document.getElementById('logEmail').value.trim();
        payload.password = document.getElementById('logPassword').value;
    } else if (action === 'signup') {
        payload.role = document.querySelector('input[name="role"]:checked').value;
        payload.first_name = document.getElementById('regFirst').value.trim();
        payload.last_name = document.getElementById('regLast').value.trim();
        payload.email = document.getElementById('regEmail').value.trim();
        payload.phone = document.getElementById('regPhone').value.trim();
        payload.password = document.getElementById('regPwd').value;
    }

    try {
        const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();

        if (data.status === 'success') {
            showAlert(alertId, data.message, 'success');
            
            if (action === 'login') {
                setTimeout(() => window.location.href = data.redirect, 1000); 
            } else {
                e.target.reset(); 
                setTimeout(() => switchTab('login'), 1500); 
            }
        } else {
            showAlert(alertId, data.message, 'error');
        }
    } catch (error) {
        showAlert(alertId, 'A server error occurred. Please try again.', 'error');
        console.error("Auth Error:", error);
    } finally {
        btn.disabled = false; 
        btn.innerHTML = action === 'login' ? 'Sign In' : 'Create Account';
    }
}
</script>
</body>
</html>