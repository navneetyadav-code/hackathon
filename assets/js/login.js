        // Tab Switching Logic
        function switchTab(tab) {
            // Update Tab Styles
            document.getElementById('tab-login').classList.remove('active');
            document.getElementById('tab-signup').classList.remove('active');
            document.getElementById('tab-' + tab).classList.add('active');

            // Update View Visibility
            document.getElementById('view-login').classList.remove('active');
            document.getElementById('view-signup').classList.remove('active');
            document.getElementById('view-' + tab).classList.add('active');
            
            // Clear alerts on switch
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

        // Mock Login Handler
        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-login');
            const alertId = 'login-alert';
            
            btn.innerHTML = 'Authenticating...';
            btn.disabled = true;
            document.getElementById(alertId).style.display = 'none';

            // Simulate API Request
            setTimeout(() => {
                const email = document.getElementById('logEmail').value;
                if(email.includes('@')) {
                    showAlert(alertId, 'Login successful! Redirecting to dashboard...', 'success');
                } else {
                    showAlert(alertId, 'Invalid email or password. Please try again.', 'error');
                }
                
                btn.innerHTML = 'Sign In';
                btn.disabled = false;
            }, 1500);
        }

        // Mock Signup Handler
        async function handleSignup(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-signup');
            const alertId = 'signup-alert';
            
            const pwd = document.getElementById('regPassword').value;
            const confirmPwd = document.getElementById('regConfirmPassword').value;

            // Basic Client-side validation
            if (pwd !== confirmPwd) {
                showAlert(alertId, 'Passwords do not match!', 'error');
                return;
            }

            btn.innerHTML = 'Creating Account...';
            btn.disabled = true;
            document.getElementById(alertId).style.display = 'none';

            // Simulate API Request
            setTimeout(() => {
                showAlert(alertId, 'Account created successfully! You can now log in.', 'success');
                document.getElementById('signupForm').reset();
                
                btn.innerHTML = 'Create Account';
                btn.disabled = false;
                
                // Auto switch to login after 2 seconds
                setTimeout(() => switchTab('login'), 2000);
            }, 2000);
        }