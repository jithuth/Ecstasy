<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            // In a real app, use password_verify($password, $row['password'])
            // For this demo, we are using a known hash or simple check if you didn't set up hashes correctly.
            // Let's assume standard password_verify.
            if (password_verify($password, $row['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("location: index.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Invalid username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --glass-bg: rgba(17, 34, 64, 0.7);
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --neon-accent: #64ffda;
        }

        body {
            background-color: #020c1b;
            background-image: radial-gradient(circle at 10% 20%, rgba(100, 255, 218, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(0, 112, 243, 0.05) 0%, transparent 20%);
            min-height: 100vh;
            color: #8892b0;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .login-box {
            background: var(--glass-bg);
            padding: 40px;
            border-radius: 16px;
            border: var(--glass-border);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #ccd6f6;
            font-size: 24px;
            margin: 0 0 10px;
        }

        .login-header p {
            font-size: 14px;
            color: var(--neon-accent);
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccd6f6;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(2, 12, 27, 0.5);
            color: #ccd6f6;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--neon-accent);
            box-shadow: 0 0 10px rgba(100, 255, 218, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #8892b0;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--neon-accent);
        }

        .btn-submit {
            background: var(--neon-accent);
            color: #020c1b;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: block;
            width: 100%;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #4cdbb3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
        }

        .error {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #8892b0;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: var(--neon-accent);
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="login-header">
            <h2>Admin Login</h2>
            <p>Enter your credentials to access</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required placeholder="Enter password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eye-icon"></i>
                        <i class="fas fa-eye-slash" id="eye-off-icon" style="display: none;"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>
        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
</body>

</html>