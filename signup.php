<?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $plain_password = $_POST['password'];

    // Basic validation
    if (empty($name) || empty($email) || empty($plain_password)) {
        $message = "Please fill all fields.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // User exists -> check password
            if (password_verify($plain_password, $existingUser['password'])) {
                // Password correct -> auto-login
                $_SESSION['user_id'] = $existingUser['id'];

                // Ensure wallet exists (in case DB was created separately)
                $w = $conn->prepare("SELECT * FROM wallets WHERE user_id = ?");
                $w->execute([$existingUser['id']]);
                if (!$w->fetch(PDO::FETCH_ASSOC)) {
                    $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$existingUser['id']]);
                }

                echo "<script>alert('Account exists — logging you in.'); window.location='dashboard.php';</script>";
                exit();
            } else {
                // Password incorrect -> ask to login or reset
                echo "<script>alert('Email already registered. Password incorrect. Please login or reset your password.'); window.location='login.php';</script>";
                exit();
            }
        } else {
            // New user -> create account
            $password_hashed = password_hash($plain_password, PASSWORD_BCRYPT);

            $stmtIns = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmtIns->execute([$name, $email, $password_hashed]);

            $userId = $conn->lastInsertId();

            // create wallet
            $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);

            // auto-login new user
            $_SESSION['user_id'] = $userId;

            echo "<script>alert('Account created and logged in successfully!'); window.location='dashboard.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signup</title>
    <meta charset="utf-8">
    <style>
        body {
            margin:0; padding:0;
            display:flex; justify-content:center; align-items:center;
            height:100vh; background:#eef3f7; font-family:Arial, sans-serif;
        }
        .box {
            background:white; padding:30px; border-radius:8px;
            box-shadow:0 0 20px rgba(0,0,0,0.07); width:380px;
        }
        input {
            width:100%; padding:12px; margin:10px 0;
            border:1px solid #d0d7de; border-radius:8px; font-size:14px;
        }
        button {
            width:100%; padding:12px; margin-top:6px;
            background:#0070ba; color:white; border:none; border-radius:8px;
            font-size:16px; cursor:pointer;
        }
        button:hover { background:#005fa3; }
        h2 { text-align:center; color:#0070ba; margin:0 0 10px 0; }
        .small { text-align:center; margin-top:12px; font-size:14px; }
        .msg { text-align:center; color:#b00020; margin-bottom:8px; }
        a { color:#0070ba; text-decoration:none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Create Account</h2>
        <?php if ($message) echo "<div class='msg'>{$message}</div>"; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="full_name" placeholder="Full Name" required value="<?php echo isset($_POST['full_name'])?htmlspecialchars($_POST['full_name']):''; ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Signup / Continue</button>
        </form>
        <p class="small">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
