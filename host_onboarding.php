<?php
session_start();
require_once __DIR__ . '/config/app.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'host') {
    header('Location: ' . (($_SESSION['role'] ?? '') === 'seeker' ? 'dashboard_seek.php' : 'login.php'));
    exit;
}

$pdo = thikana_db();
if (thikana_host_onboarding_complete($_SESSION['user_id'])) {
    header('Location: dashboard_host.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection is not available.']);
        exit;
    }

    // Switched to standard POST payload to allow file handling
    $payload = $_POST;

    if (($payload['action'] ?? '') !== 'save_onboarding') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit;
    }

    $required = ['property_count', 'property_type', 'property_name', 'address', 'city', 'total_rooms', 'available_rooms', 'rent'];
    foreach ($required as $field) {
        if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
            echo json_encode(['status' => 'error', 'message' => 'Please complete all required property details.']);
            exit;
        }
    }

    // Process file upload if one was provided
    $imagePath = null;
    if (isset($_FILES['property_photo']) && $_FILES['property_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/assets/img/upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory securely if it doesn't exist
        }
        $fileExtension = pathinfo($_FILES['property_photo']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['property_photo']['tmp_name'], $targetFile)) {
            $imagePath = 'assets/img/upload/' . $fileName;
        }
    }

    try {
        $pdo->beginTransaction();

        $propertyCount = trim((string) $payload['property_count']);
        $propertyType = trim((string) $payload['property_type']);
        $propertyName = trim((string) $payload['property_name']);
        $address = trim((string) $payload['address']);
        $city = trim((string) $payload['city']);
        $totalRooms = (int) $payload['total_rooms'];
        $availableRooms = (int) $payload['available_rooms'];
        $rent = (float) $payload['rent'];
        $deposit = isset($payload['deposit']) && $payload['deposit'] !== '' ? (float) $payload['deposit'] : 0.0;
        $amenities = array_values(array_unique(array_filter(array_map('strval', $payload['amenities'] ?? []))));
        $rules = array_values(array_unique(array_filter(array_map('strval', $payload['rules'] ?? []))));

        $stmt = $pdo->prepare(
            'INSERT INTO properties (user_id, property_count, property_type, property_name, address, city, total_rooms, available_rooms, rent, deposit, amenities, house_rules, image_path, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $_SESSION['user_id'],
            $propertyCount,
            $propertyType,
            $propertyName,
            $address,
            $city,
            $totalRooms,
            $availableRooms,
            $rent,
            $deposit,
            json_encode($amenities, JSON_THROW_ON_ERROR),
            json_encode($rules, JSON_THROW_ON_ERROR),
            $imagePath,
            'active'
        ]);

        $updateUser = $pdo->prepare('UPDATE users SET onboarding_complete = 1 WHERE id = ?');
        $updateUser->execute([$_SESSION['user_id']]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Host profile saved successfully!',
            'redirect' => 'dashboard_host.php'
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Host onboarding save failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Unable to save your onboarding details. Please try again.']);
    }
    exit;
}

$firstName = $_SESSION['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thikana - Host Onboarding</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { primary: '#4F46E5', hover: '#4338CA', secondary: '#F97316', light: '#EEF2FF', dark: '#1E1B4B' }
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    boxShadow: { soft: '0 4px 20px -2px rgba(0, 0, 0, 0.05)', floating: '0 10px 30px -5px rgba(79, 70, 229, 0.15)' }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .btn-primary { @apply px-6 py-3 bg-brand-primary hover:bg-brand-hover text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-floating; }
            .btn-secondary { @apply px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300; }
            .input-field { @apply w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-primary focus:border-brand-primary dark:bg-slate-900/50 dark:border-slate-700 dark:text-white dark:focus:ring-brand-primary outline-none transition-all; }
            .label-text { @apply block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2; }
            
            /* Custom Scrollbar */
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #94A3B8; border-radius: 4px; opacity: 0.5; }
            .dark ::-webkit-scrollbar-thumb { background: #475569; }
        }

        /* Step Transitions */
        .step-container { display: none; opacity: 0; transform: translateX(20px); transition: opacity 0.4s ease, transform 0.4s ease; }
        .step-container.active { display: block; animation: slideIn 0.4s forwards; }
        
        @keyframes slideIn {
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Selectable Card Styling via peer checking */
        .card-radio:checked + div {
            @apply border-brand-primary bg-brand-light dark:bg-indigo-900/20 dark:border-brand-primary ring-1 ring-brand-primary;
        }
        .card-radio:checked + div svg { @apply text-brand-primary; }
        
        .card-checkbox:checked + div {
            @apply border-brand-primary bg-brand-light dark:bg-indigo-900/20 dark:border-brand-primary;
        }
        .card-checkbox:checked + div .check-icon { @apply opacity-100 scale-100; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100 min-h-screen flex flex-col">

    <!-- Top Navigation -->
    <header class="w-full px-6 py-4 flex items-center justify-between bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50">
        <div class="flex items-center gap-2 text-2xl font-bold text-brand-primary">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h5v-6h4v6h5v-8h3L12 3zm0 2.5l5.5 4.95V18h-1v-6H7.5v6h-1v-7.55L12 5.5z"/></svg>
            Thikana
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400 hidden sm:block">Welcome, <?= htmlspecialchars($firstName) ?></span>
            <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full max-w-4xl mx-auto px-4 py-8 sm:py-12 flex flex-col">
        
        <!-- Progress Indicator -->
        <div class="mb-10 w-full max-w-2xl mx-auto">
            <div class="flex justify-between items-center relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 dark:bg-slate-800 -z-10 rounded-full"></div>
                <div id="progress-bar" class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand-primary -z-10 rounded-full transition-all duration-500" style="width: 0%;"></div>
                
                <div class="step-indicator flex flex-col items-center gap-2" data-step="1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-primary text-white shadow-md border-4 border-slate-50 dark:border-slate-950 transition-colors">1</div>
                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Portfolio</span>
                </div>
                <div class="step-indicator flex flex-col items-center gap-2" data-step="2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-4 border-slate-50 dark:border-slate-950 transition-colors">2</div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Details</span>
                </div>
                <div class="step-indicator flex flex-col items-center gap-2" data-step="3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-4 border-slate-50 dark:border-slate-950 transition-colors">3</div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Media</span>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-soft border border-slate-200 dark:border-slate-800 p-6 sm:p-10 w-full">
            <form id="onboarding-form" onsubmit="submitOnboarding(event)">
                
                <!-- STEP 1: Portfolio -->
                <div id="step-1" class="step-container active">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-3">Let's set up your Host Profile 👋</h2>
                        <p class="text-slate-500 dark:text-slate-400">To give you the best experience, tell us about your hosting portfolio.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                        <label class="cursor-pointer relative group">
                            <input type="radio" name="property_count" value="single" class="card-radio sr-only" required checked>
                            <div class="h-full border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-8 flex flex-col items-center text-center transition-all hover:border-brand-primary/50 hover:shadow-md">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-3xl">🏠</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">I manage 1 property</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Perfect for single homeowners, a flat, or one PG building.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer relative group">
                            <input type="radio" name="property_count" value="multiple" class="card-radio sr-only">
                            <div class="h-full border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-8 flex flex-col items-center text-center transition-all hover:border-brand-primary/50 hover:shadow-md">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <span class="text-3xl">🏢</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Multiple properties</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">I manage several buildings, hostels, or real estate listings.</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-10 flex justify-end">
                        <button type="button" onclick="nextStep(2)" class="btn-primary flex items-center gap-2">
                            Next Step
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Tell us about your property -->
                <div id="step-2" class="step-container">
                    <div class="mb-8">
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-2">🏡 Tell us about your property</h2>
                        <p class="text-slate-500 dark:text-slate-400">Let's get your first listing ready. You can edit this anytime.</p>
                    </div>

                    <div class="space-y-8">
                        <!-- Property Type (Cards) -->
                        <div>
                            <span class="label-text">Property Type</span>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php
                                $prop_types = [
                                    'pg' => ['icon' => '🛏️', 'label' => 'PG'],
                                    'flat' => ['icon' => '🔑', 'label' => 'Flat'],
                                    'room' => ['icon' => '🚪', 'label' => 'Room'],
                                    'hostel' => ['icon' => '🏫', 'label' => 'Hostel']
                                ];
                                foreach($prop_types as $val => $data):
                                ?>
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="property_type" value="<?= $val ?>" class="card-radio sr-only" required>
                                    <div class="border-2 border-slate-200 dark:border-slate-700 rounded-xl p-4 flex flex-col items-center justify-center gap-2 transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <span class="text-2xl"><?= $data['icon'] ?></span>
                                        <span class="font-semibold text-sm text-slate-700 dark:text-slate-300"><?= $data['label'] ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="prop_name" class="label-text">Property Name</label>
                                <input type="text" id="prop_name" name="property_name" class="input-field" placeholder="e.g. Sunrise Premium PG" required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="label-text">Full Address</label>
                                <textarea id="address" name="address" rows="2" class="input-field resize-none" placeholder="House/Building No, Street, Landmark" required></textarea>
                            </div>
                            <div>
                                <label for="city" class="label-text">City</label>
                                <input type="text" id="city" name="city" class="input-field" placeholder="e.g. Phagwara" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="total_rooms" class="label-text">Total Rooms</label>
                                    <input type="number" id="total_rooms" name="total_rooms" min="1" class="input-field" placeholder="0" required>
                                </div>
                                <div>
                                    <label for="avail_rooms" class="label-text">Available</label>
                                    <input type="number" id="avail_rooms" name="available_rooms" min="0" class="input-field" placeholder="0" required>
                                </div>
                            </div>
                            <div>
                                <label for="rent" class="label-text">Monthly Rent (₹)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">₹</span>
                                    <input type="number" id="rent" name="rent" class="input-field pl-8" placeholder="5000" required>
                                </div>
                            </div>
                            <div>
                                <label for="deposit" class="label-text">Security Deposit (₹)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">₹</span>
                                    <input type="number" id="deposit" name="deposit" class="input-field pl-8" placeholder="5000">
                                </div>
                            </div>
                        </div>

                        <!-- Amenities (Clickable Toggle Cards) -->
                        <div>
                            <span class="label-text">Amenities</span>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <?php
                                $amenities = [
                                    'wifi' => '📶 Wi-Fi', 'ac' => '❄️ AC', 'parking' => '🚗 Parking', 
                                    'washing_machine' => '🧺 Washing Machine', 'attached_bath' => '🚿 Attached Bath', 
                                    'kitchen' => '🍳 Kitchen', 'cctv' => '📹 CCTV', 'power_backup' => '🔋 Power Backup'
                                ];
                                foreach($amenities as $key => $label):
                                ?>
                                <label class="cursor-pointer relative">
                                    <input type="checkbox" name="amenities[]" value="<?= $key ?>" class="card-checkbox sr-only">
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 flex items-center justify-between transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= $label ?></span>
                                        <div class="w-5 h-5 rounded border border-slate-300 dark:border-slate-600 flex items-center justify-center bg-white dark:bg-slate-900 transition-all check-icon opacity-50 scale-90">
                                            <svg class="w-3.5 h-3.5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-between items-center">
                        <button type="button" onclick="prevStep(1)" class="text-slate-500 hover:text-slate-900 dark:hover:text-white font-semibold transition-colors flex items-center gap-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back
                        </button>
                        <button type="button" onclick="if(validateStep(2)) nextStep(3)" class="btn-primary flex items-center gap-2">
                            Next Step
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Photos & Rules -->
                <div id="step-3" class="step-container">
                    <div class="mb-8">
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-2">📸 Final Touches</h2>
                        <p class="text-slate-500 dark:text-slate-400">Upload a nice photo and set the house rules.</p>
                    </div>

                    <div class="space-y-8">
                        <!-- Photo Upload -->
                        <div>
                            <span class="label-text">Upload Property Photo</span>
                            <div class="mt-2 w-full flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 dark:border-slate-700 border-dashed rounded-xl hover:border-brand-primary hover:bg-brand-light/30 dark:hover:bg-indigo-900/10 transition-colors relative cursor-pointer" id="dropzone">
                                <input type="file" id="property_photo" name="property_photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/">
                                <div class="space-y-2 text-center pointer-events-none">
                                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-slate-600 dark:text-slate-400 justify-center">
                                        <span class="relative font-semibold text-brand-primary">Upload a file</span>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-slate-500">PNG, JPG, GIF up to 5MB</p>
                                </div>
                            </div>
                            <div id="file-name" class="mt-2 text-sm text-green-600 dark:text-green-400 font-medium hidden">Photo selected!</div>
                        </div>

                        <!-- House Rules (Clickable Toggle Cards) -->
                        <div>
                            <span class="label-text">House Rules</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php
                                $rules = [
                                    'smoking' => ['icon' => '🚬', 'label' => 'Smoking Allowed'],
                                    'visitors' => ['icon' => '👥', 'label' => 'Visitors Allowed'],
                                    'cooking' => ['icon' => '🍳', 'label' => 'Cooking Allowed'],
                                    'pets' => ['icon' => '🐾', 'label' => 'Pets Allowed']
                                ];
                                foreach($rules as $key => $data):
                                ?>
                                <label class="cursor-pointer relative">
                                    <input type="checkbox" name="rules[]" value="<?= $key ?>" class="card-checkbox sr-only">
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 flex items-center justify-between transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl"><?= $data['icon'] ?></span>
                                            <span class="font-semibold text-sm text-slate-700 dark:text-slate-300"><?= $data['label'] ?></span>
                                        </div>
                                        <div class="w-10 h-6 bg-slate-200 dark:bg-slate-700 rounded-full relative transition-colors toggle-bg">
                                            <div class="w-4 h-4 bg-white rounded-full absolute top-1 left-1 transition-transform toggle-dot"></div>
                                        </div>
                                    </div>
                                    <style>
                                        .card-checkbox:checked + div .toggle-bg { @apply bg-brand-primary; }
                                        .card-checkbox:checked + div .toggle-dot { transform: translateX(16px); }
                                    </style>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-between items-center border-t border-slate-200 dark:border-slate-800 pt-6">
                        <button type="button" onclick="prevStep(2)" class="text-slate-500 hover:text-slate-900 dark:hover:text-white font-semibold transition-colors flex items-center gap-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back
                        </button>
                        <button type="submit" id="submit-btn" class="btn-primary px-8 flex items-center gap-2">
                            Complete Setup 🚀
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Dark Mode Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        });

        // Multi-step form logic
        function updateProgressBar(step) {
            const progress = document.getElementById('progress-bar');
            const indicators = document.querySelectorAll('.step-indicator');
            
            // Width calc: step 1 = 0%, step 2 = 50%, step 3 = 100%
            let width = ((step - 1) / 2) * 100;
            progress.style.width = `${width}%`;

            indicators.forEach((ind) => {
                const indStep = parseInt(ind.getAttribute('data-step'));
                const circle = ind.querySelector('div');
                const text = ind.querySelector('span');
                
                if (indStep <= step) {
                    circle.className = "w-10 h-10 rounded-full flex items-center justify-center font-bold bg-brand-primary text-white shadow-md border-4 border-slate-50 dark:border-slate-950 transition-colors";
                    text.className = "text-xs font-semibold text-slate-900 dark:text-white";
                } else {
                    circle.className = "w-10 h-10 rounded-full flex items-center justify-center font-bold bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-4 border-slate-50 dark:border-slate-950 transition-colors";
                    text.className = "text-xs font-semibold text-slate-500 dark:text-slate-400";
                }
            });
        }

        function nextStep(step) {
            // Hide all
            document.querySelectorAll('.step-container').forEach(el => {
                el.classList.remove('active');
            });
            // Show target
            document.getElementById(`step-${step}`).classList.add('active');
            updateProgressBar(step);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function prevStep(step) {
            nextStep(step);
        }

        // Basic HTML5 validation trigger for step 2 before moving to step 3
        function validateStep(stepNum) {
            if (stepNum === 2) {
                const requiredFields = document.getElementById('step-2').querySelectorAll('[required]');
                let isValid = true;
                requiredFields.forEach(field => {
                    if (!field.value || (field.type === 'radio' && !document.querySelector(`input[name="${field.name}"]:checked`))) {
                        isValid = false;
                        field.reportValidity();
                    }
                });
                return isValid;
            }
            return true;
        }

        // Handle File Input UI change
        document.getElementById('property_photo').addEventListener('change', function(e) {
            const fileNameDisplay = document.getElementById('file-name');
            if (e.target.files.length > 0) {
                fileNameDisplay.textContent = '✅ Selected: ' + e.target.files[0].name;
                fileNameDisplay.classList.remove('hidden');
            } else {
                fileNameDisplay.classList.add('hidden');
            }
        });

        // Form Submit Simulation
        async function submitOnboarding(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            btn.innerHTML = 'Saving Profile...';
            btn.disabled = true;

            const form = document.getElementById('onboarding-form');
            const formData = new FormData(form);
            formData.append('action', 'save_onboarding');

            try {
                // Not stringifying as JSON anymore to let the file go through correctly via multipart/form-data
                const response = await fetch('host_onboarding.php', {
                    method: 'POST',
                    body: formData 
                });

                const data = await response.json();

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Unable to save onboarding details.');
                }

                window.location.href = data.redirect || 'dashboard_host.php';
            } catch (error) {
                btn.innerHTML = 'Complete Setup 🚀';
                btn.disabled = false;
                alert(error.message || 'Something went wrong. Please try again.');
            }
        }
    </script>
</body>
</html>