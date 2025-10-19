<?php

session_start();

require_once 'bootstrap.php';

$error_message = '';

if(isset($_SESSION['user'])) {
    return redirectTo('dashboard.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error_message = 'Username dan password harus diisi.';
    } else {
        $query = "SELECT * FROM admin WHERE username = ?";
        $connection = Database::getConnection();

        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                ];
                return redirectTo('dashboard.php');
            } else {
                $error_message = 'Password salah.';
            }
        } else {
            $error_message = 'Username tidak ditemukan.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #f5f7fa;
            --color-container: #ffffff;
            --color-shadow: rgba(0, 0, 0, 0.1);
            --color-heading: #20354b;
            --color-label: #495057;
            --color-border: #ced4da;
            --color-btn: #007bff;
            --color-btn-hover: #0056b3;
            --color-error: #dc3545;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            transition: background 0.3s;
        }
        .login-container {
            background-color: var(--color-container);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--color-shadow);
            width: 100%;
            max-width: 350px;
        }
        .logo-pemda {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        .logo-pemda img {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }
        h2 {
            text-align: center;
            color: var(--color-heading);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-label);
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            background: transparent;
            color: inherit;
            transition: background 0.3s, color 0.3s, border-color 0.3s;
        }
        button {
            width: 100%;
            background-color: var(--color-btn);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: var(--color-btn-hover);
        }
        .error-message {
            color: var(--color-error);
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }

        /* Dark Mode Styles */
        body.darkmode {
            --color-bg: #191c20;
            --color-container: #23272f;
            --color-shadow: rgba(0,0,0,0.38);
            --color-heading: #e8eaee;
            --color-label: #c9ced7;
            --color-border: #454a51;
            --color-btn: #1e90ff;
            --color-btn-hover: #1569c7;
            --color-error: #ff6363;
            color: #e8eaee;
        }
        body.darkmode .login-container {
            color: #e8eaee;
        }
        body.darkmode input[type="text"], 
        body.darkmode input[type="password"] {
            background: #252932;
            color: #e8eaee;
            border-color: #454a51;
        }
        body.darkmode h2 {
            color: var(--color-heading);
        }
        body.darkmode label {
            color: var(--color-label);
        }
        body.darkmode .error-message {
            color: var(--color-error);
        }

        .toggle-darkmode-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: #555;
            border: none;
            font-size: 16px;
            cursor: pointer;
            z-index: 99;
            transition: color 0.2s;
        }
        .toggle-darkmode-btn .icon {
            font-size: 20px;
        }
        body.darkmode .toggle-darkmode-btn {
            color: #eee;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px 8px;
            }
            .toggle-darkmode-btn {
                top: 15px;
                right: 10px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-pemda">
            <img src="./asset/pemda.png" alt="Pemda Lotim Logo">
            <img src="./asset/kkn-logo.jpg" alt="Pemda Lotim Logo">
        </div>
        <h2>🔑 Admin Login</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

</body>
</html>