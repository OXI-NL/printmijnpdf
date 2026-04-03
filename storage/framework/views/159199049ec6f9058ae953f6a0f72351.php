<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Admin Login | PrintMijnPDF</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 24px rgba(230, 57, 70, 0.3);
        }
        .logo-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .logo-text span {
            color: #e63946;
        }
        .login-title {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            font-family: inherit;
            font-size: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }
        .form-group input:focus {
            outline: none;
            border-color: #e63946;
            background: white;
            box-shadow: 0 0 0 4px rgba(230, 57, 70, 0.1);
        }
        .form-group input::placeholder {
            color: #adb5bd;
        }
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-message svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 1.5rem;
        }
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
            margin-top: 0.5rem;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn svg {
            width: 20px;
            height: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }
        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
        }
        .footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="logo-text">Print<span>Mijn</span>PDF</div>
            </div>

            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle">Log in om bestellingen te beheren</p>

            <?php if($errors->has('login')): ?>
                <div class="error-message">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <?php echo e($errors->first('login')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('success')): ?>
                <div class="success-message"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('admin.login.submit')); ?>">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="username">Gebruikersnaam</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo e(old('username')); ?>"
                        placeholder="Voer je gebruikersnaam in"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Voer je wachtwoord in"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Inloggen
                </button>
            </form>
        </div>

        <div class="footer">
            <a href="/">← Terug naar PrintMijnPDF.nl</a>
        </div>
    </div>
</body>
</html>
<?php /**PATH /Users/elcoroest/Documents/GitHub/printmijnpdf/resources/views/admin/login.blade.php ENDPATH**/ ?>