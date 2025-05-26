<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$errors = $_SESSION['errors'] ?? [];
$old_data = $_SESSION['old_data'] ?? [];
unset($_SESSION['errors']);
unset($_SESSION['old_data']);
?>

<!DOCTYPE html>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SOUNDSCOUTS</title>
    <style>
    * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            background: url('https://pbs.twimg.com/media/F4FZIZebkAULKbo.jpg:large') no-repeat;
            background-size: cover;
            background-position: center;
            padding-top: 100px;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 100px;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(8.5px);
            -webkit-backdrop-filter: blur(8.5px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 99;
        }

        .logo {
            font-size: 2em;
            color: #fff;
            user-select: none;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .navigation a {
            position: relative;
            font-size: 1.1em;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            margin-left: 30px;
            padding-bottom: 5px;
        }

        .navigation a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: #fff;
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .navigation a:hover::after {
            width: 100%;
        }

        .navigation a.active {
            color: #1DA1F2;
        }

        .navigation a.active::after {
            background: #1DA1F2;
        }

        .navigation .divider {
            color: #ffffff;
            font-size: 26px;
            margin: 0px 20px;
            display: inline-block;
            margin-left: 30px;
            opacity: 0.6;
        }

        .btnLogin-popup {
            position: relative;
            padding: 12px 25px;
            font-size: 15px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: white;
            background: #1DA1F2;
            box-shadow: 
                inset 0 10px 5px rgba(0,0,0,0.1),
                0 5px 15px rgba(0,0,0,0.1),
                inset 0 -10px 15px rgba(255,255,255,0.2);
            border-radius: 50px;
            border: none;
            margin-left: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
            cursor: pointer;
        }

        .btnLogin-popup:hover {
            background: #1a8cd8;
            color: white;
            box-shadow: 
                0 0 10px rgba(29, 161, 242, 0.6),
                0 0 20px rgba(29, 161, 242, 0.4),
                0 0 30px rgba(29, 161, 242, 0.2);
        }

        .btnLogin-popup::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transform: translateX(-100%);
            transition: 0.6s;
        }

        .btnLogin-popup:hover::after {
            transform: translateX(100%);
        }

        .wrapper {
            position: relative;
            width: 400px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            backdrop-filter: blur(8.5px);
            -webkit-backdrop-filter: blur(8.5px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            z-index: 10;
            margin-top: 50px;
            margin-bottom: 50px;
        }

        .register-wrapper {
            height: 620px;
        }

        .form-box {
            width: 100%;
            padding: 40px;
            position: relative;
        }

        .form-box h2 {
            font-size: 2.2em;
            color: #fff;
            text-align: center;
            margin-bottom: 40px;
            text-shadow: 0 2px 8px rgba(0, 150, 255, 0.4);
            position: relative;
            letter-spacing: 1px;
        }

        .form-box h2::after {
            content: "Regístrate";
            position: absolute;
            left: 0;
            bottom: -15px;
            width: 100%;
            text-align: center;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.3);
            transform: scaleY(-1) translateY(5px);
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.1));
            -webkit-background-clip: text;
            background-clip: text;
            filter: blur(1px);
        }

        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 25px 0;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .input-box:hover {
            border-bottom-color: rgba(255, 255, 255, 0.8);
        }

        .input-box label {
            position: absolute;
            top: 50%;
            left: 40px;
            transform: translateY(-50%);
            color: #fff;
            font-weight: 500;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.2, 0, 0.2, 1);
        }

        .input-box input {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 1em;
            color: #fff;
            font-weight: 500;
            padding-left: 40px;
            transition: all 0.3s ease;
        }

        .input-box .icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-size: 1.2em;
            transition: all 0.3s ease;
        }

        .input-box input:focus ~ label,
        .input-box input:not(:placeholder-shown) ~ label {
            top: -5px;
            font-size: 0.8em;
            color: #1DA1F2;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .input-box input:focus ~ .icon,
        .input-box input:not(:placeholder-shown) ~ .icon {
            color: #1DA1F2;
        }

        .input-box input:focus {
            border-bottom-color: #1DA1F2;
        }

        .terms {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0 25px;
            color: #fff;
            font-size: 0.9em;
        }

        .terms input[type="checkbox"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 4px;
            outline: none;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .terms input[type="checkbox"]:checked {
            background-color: #1DA1F2;
            border-color: #1DA1F2;
        }

        .terms input[type="checkbox"]:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 12px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .terms a {
            color: #1DA1F2;
            text-decoration: none;
            font-weight: 500;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            height: 45px;
            background: #1DA1F2;
            border: none;
            outline: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1em;
            color: #fff;
            font-weight: 600;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background: #1a8cd8;
            box-shadow: 0 10px 20px rgba(29, 161, 242, 0.3);
            transform: translateY(-3px);
        }

        .btn:active {
            transform: translateY(-1px);
            box-shadow: 0 5px 10px rgba(29, 161, 242, 0.4);
        }

        .btn::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transform: translateX(-100%);
            transition: 0.6s;
        }

        .btn:hover::after {
            transform: translateX(100%);
        }

        .login-redirect {
            text-align: center;
            margin-top: 20px;
            color: #fff;
            font-size: 0.95em;
        }

        .login-link {
            color: #1DA1F2;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            position: relative;
        }

        .login-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: #1DA1F2;
            transition: width 0.3s ease;
        }

        .login-link:hover::after {
            width: 100%;
        }

        .login-link:hover {
            color: #1a8cd8;
        }

        .wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,150,255,0.1) 0%, transparent 70%);
            animation: waterEffect 8s linear infinite;
            opacity: 0.5;
            z-index: -1;
        }

        @keyframes waterEffect {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .wrapper::after {
            content: '';
            position: absolute;
            bottom: 20px;
            right: 30px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            box-shadow: 
                60px 80px 0 rgba(255,255,255,0.1),
                -40px 120px 0 rgba(255,255,255,0.1),
                80px -30px 0 rgba(255,255,255,0.1);
            z-index: -1;
        }

        /* Password strength indicator */
        .password-strength {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
            display: none;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            background: #ff4757;
            transition: all 0.3s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 900px) {
            header {
                padding: 15px 30px;
            }
            
            .navigation a {
                margin-left: 15px;
                font-size: 1em;
            }
            
            .divider {
                margin-left: 15px !important;
            }
            
            .btnLogin-popup {
                margin-left: 15px;
                padding: 10px 20px;
            }
            
            .wrapper {
                width: 90%;
                max-width: 400px;
            }
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
                padding: 15px;
                text-align: center;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .navigation {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .navigation a {
                margin: 0 10px 5px;
            }
            
            .divider {
                display: none !important;
            }
            
            .btnLogin-popup {
                margin: 10px 0 0;
            }
            
            .form-box {
                padding: 30px 20px;
            }
            
            .form-box h2 {
                font-size: 1.8em;
                margin-bottom: 30px;
            }
        }

        /* Floating animation for the wrapper */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .wrapper {
            animation: float 6s ease-in-out infinite;
        }

         .error-message {
            color: #ff4757;
            font-size: 0.8em;
            margin-top: 5px;
            display: none;
        }
        
        .input-box.error {
            border-bottom-color: #ff4757;
        }
        
        .input-box.error .icon {
            color: #ff4757;
        }
    </style>
</head>
<body>
    <header>
        <h2 class="logo">SOUNDSCOUTS</h2>
        <nav class="navigation">
            <a href="#">Premium</a>
            <a href="#">Ajustes</a>
            <a href="#">Descargar</a>
            <span class="divider">|</span>
            <a href="registro.php" class="active">Registrarse</a>
            <button class="btnLogin-popup">Iniciar Sesión</button>
        </nav>
    </header>

    <div class="wrapper register-wrapper">
        <div class="form-box register">
            <h2>Regístrate</h2>
            
            <form id="registerForm" action="procesar_registro.php" method="POST">
                <div class="input-box">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="nombre" name="nombre" required placeholder=" " value="<?php echo htmlspecialchars($old_data['nombre'] ?? ''); ?>">
                    <label>Nombre completo</label>
                    <div class="error-message" id="nombre-error"><?php echo $errors['nombre'] ?? ''; ?></div>
                </div>
                
                <div class="input-box">
                    <span class="icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" required placeholder=" " value="<?php echo htmlspecialchars($old_data['email'] ?? ''); ?>">
                    <label>E-mail</label>
                    <div class="error-message" id="email-error"><?php echo $errors['email'] ?? ''; ?></div>
                </div>
                
                <div class="input-box">
                    <span class="icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label>Contraseña</label>
                    <div class="password-strength">
                        <div class="strength-meter"></div>
                    </div>
                    <div class="error-message" id="password-error"><?php echo $errors['password'] ?? ''; ?></div>
                </div>
                
                <div class="input-box">
                    <span class="icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirmar" name="confirmar" required placeholder=" ">
                    <label>Confirmar contraseña</label>
                    <div class="error-message" id="confirmar-error"><?php echo $errors['confirmar'] ?? ''; ?></div>
                </div>
                
                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required <?php echo isset($old_data['terms']) ? 'checked' : ''; ?>>
                    <label for="terms">Acepto los <a href="terms.html">Términos y Condiciones</a></label>
                    <div class="error-message" id="terms-error"><?php echo $errors['terms'] ?? ''; ?></div>
                </div>
                
                <button type="submit" class="btn">Crear cuenta</button>
                
                <div class="login-redirect">
                    <p>¿Ya tienes una cuenta? <a href="login_soundscouts.php" class="login-link">Inicia Sesión</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
                document.addEventListener('DOMContentLoaded', function() {
            // Redirección al login
            const loginPopupBtn = document.querySelector('.btnLogin-popup');
            if (loginPopupBtn) {
                loginPopupBtn.addEventListener('click', function() {
                    window.location.href = 'login_soundscouts.php';
                });
            }

            // Validación del formulario
            const registerForm = document.getElementById('registerForm');
            const passwordInput = document.getElementById('password');
            const confirmarInput = document.getElementById('confirmar');
            const strengthMeter = document.querySelector('.strength-meter');
            const passwordStrength = document.querySelector('.password-strength');

            // Mostrar fuerza de la contraseña
            if (passwordInput && strengthMeter) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = calculatePasswordStrength(password);
                    
                    passwordStrength.style.display = password ? 'block' : 'none';
                    
                    if (strength < 3) {
                        strengthMeter.style.width = '30%';
                        strengthMeter.style.background = '#ff4757';
                    } else if (strength < 6) {
                        strengthMeter.style.width = '60%';
                        strengthMeter.style.background = '#ffa502';
                    } else {
                        strengthMeter.style.width = '100%';
                        strengthMeter.style.background = '#2ed573';
                    }
                });
            }

            // Validación al enviar el formulario
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Resetear errores
                clearErrors();
                
                // Obtener valores
                const nombre = document.getElementById('nombre').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = passwordInput.value;
                const confirmar = confirmarInput.value;
                const termsChecked = document.getElementById('terms').checked;
                
                let isValid = true;
                
                // Validar nombre
                if (nombre.length < 3) {
                    showError('nombre', 'El nombre debe tener al menos 3 caracteres');
                    isValid = false;
                }
                
                // Validar email
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError('email', 'Ingresa un email válido');
                    isValid = false;
                }
                
                // Validar contraseña
                if (password.length < 8) {
                    showError('password', 'La contraseña debe tener al menos 8 caracteres');
                    isValid = false;
                } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                    showError('password', 'Debe contener mayúsculas, minúsculas y números');
                    isValid = false;
                }
                
                // Validar confirmación
                if (password !== confirmar) {
                    showError('confirmar', 'Las contraseñas no coinciden');
                    isValid = false;
                }
                
                // Validar términos
                if (!termsChecked) {
                    showError('terms', 'Debes aceptar los términos y condiciones');
                    isValid = false;
                }
                
                // Enviar formulario si es válido
                if (isValid) {
                    this.submit();
                }
            });
            
            // Funciones auxiliares
            function calculatePasswordStrength(password) {
                let strength = 0;
                strength += Math.min(4, Math.floor(password.length / 2));
                if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
                if (password.match(/([0-9])/)) strength += 1;
                if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
                return strength;
            }
            
            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(`${fieldId}-error`);
                
                if (field && errorElement) {
                    field.closest('.input-box').classList.add('error');
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                }
            }
            
            function clearErrors() {
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                
                document.querySelectorAll('.input-box').forEach(el => {
                    el.classList.remove('error');
                });
            }
            
            // Efectos de los inputs
            const inputBoxes = document.querySelectorAll('.input-box');
            inputBoxes.forEach(box => {
                const input = box.querySelector('input');
                const label = box.querySelector('label');
                
                if (input.value) {
                    box.classList.add('active');
                }
                
                input.addEventListener('focus', () => {
                    box.classList.add('active');
                    box.classList.remove('error');
                    document.getElementById(`${input.id}-error`).style.display = 'none';
                });
                
                input.addEventListener('blur', () => {
                    if (!input.value) {
                        box.classList.remove('active');
                    }
                });
                
                input.addEventListener('input', () => {
                    if (input.value) {
                        box.classList.add('active');
                    } else {
                        box.classList.remove('active');
                    }
                });
            });
            
            // Efectos de los botones
            const buttons = document.querySelectorAll('.btn, .btnLogin-popup');
            buttons.forEach(button => {
                button.addEventListener('mousedown', function() {
                    this.style.transform = 'translateY(1px)';
                });
                button.addEventListener('mouseup', function() {
                    this.style.transform = '';
                });
            });
        });
    </script>
</body>
</html>