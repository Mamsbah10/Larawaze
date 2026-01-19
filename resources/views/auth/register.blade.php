<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inscription - YATRAFFIC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #2d3748;
            --text-light: #718096;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            --input-border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
            pointer-events: none;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .auth-container {
            position: relative;
            width: 100%;
            max-width: 440px;
            z-index: 1;
        }

        .auth-card {
            background: white;
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-logo {
            width: 72px;
            height: 72px;
            background: var(--bg-gradient);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .auth-logo i {
            font-size: 2rem;
            color: white;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .form-control-wrapper {
            position: relative;
        }

        .form-control-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        }

        .form-control::placeholder {
            color: #cbd5e0;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 8px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-weak { width: 33%; background: #f56565; }
        .strength-medium { width: 66%; background: #ed8936; }
        .strength-strong { width: 100%; background: #48bb78; }

        .password-hint {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 6px;
        }

        .btn-primary-custom {
            width: 100%;
            padding: 14px;
            background: var(--bg-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            margin-top: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-primary-custom:active {
            transform: translateY(0);
        }

        .btn-primary-custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: none;
            font-size: 0.9rem;
            animation: shake 0.5s;
        }

        .alert-danger {
            background: #fee;
            color: #c53030;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            color: var(--primary-dark);
        }

        .terms {
            font-size: 0.85rem;
            color: var(--text-light);
            text-align: center;
            margin-top: 16px;
            line-height: 1.5;
        }

        .terms a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .btn-primary-custom.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-primary-custom.loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 10px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 32px 24px;
            }

            .auth-title {
                font-size: 1.5rem;
            }

            .auth-logo {
                width: 64px;
                height: 64px;
            }

            .auth-logo i {
                font-size: 1.75rem;
            }
        }

        .form-control:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        button:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fa-solid fa-car-side"></i>
                </div>
                <h1 class="auth-title">Rejoignez-nous</h1>
                <p class="auth-subtitle">Créez votre compte et commencez l'aventure</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf
                
                <div class="form-group">
                    <label for="name" class="form-label">Nom complet</label>
                    <div class="form-control-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input 
                            type="text" 
                            id="name"
                            name="name" 
                            class="form-control" 
                            placeholder="Jean Dupont"
                            value="{{ old('name') }}"
                            required 
                            autocomplete="name"
                            autofocus
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <div class="form-control-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            class="form-control" 
                            placeholder="votre@email.com"
                            value="{{ old('email') }}"
                            required 
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="form-control-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-control" 
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                            oninput="checkPasswordStrength(this.value)"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fa-solid fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint" id="strengthText">
                        Minimum 8 caractères
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="form-control-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input 
                            type="password" 
                            id="password_confirmation"
                            name="password_confirmation" 
                            class="form-control" 
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <i class="fa-solid fa-eye" id="password_confirmation-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom" id="submitBtn">
                    Créer mon compte
                </button>

                <div class="terms">
                    En vous inscrivant, vous acceptez nos <a href="#">Conditions d'utilisation</a> 
                    et notre <a href="#">Politique de confidentialité</a>
                </div>
            </form>

            <div class="auth-footer">
                Vous avez déjà un compte ? <a href="{{ route('login.form') }}">Se connecter</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (strength === 0 || strength === 1) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Mot de passe faible';
                strengthText.style.color = '#f56565';
            } else if (strength === 2 || strength === 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Mot de passe moyen';
                strengthText.style.color = '#ed8936';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Mot de passe fort';
                strengthText.style.color = '#48bb78';
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            
            if (password !== confirmation) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères');
                return;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.textContent = 'Création du compte';
        });

        // Auto-hide error after 5 seconds
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>