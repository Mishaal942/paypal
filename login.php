<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill all fields');</script>";
    } else {
        // Check user
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Password correct -> login
                $_SESSION['user_id'] = $user['id'];

                // Ensure wallet exists
                $w = $conn->prepare("SELECT * FROM wallets WHERE user_id = ?");
                $w->execute([$user['id']]);
                if (!$w->fetch(PDO::FETCH_ASSOC)) {
                    $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$user['id']]);
                }

                echo "<script>alert('Login successful!'); window.location='dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Incorrect password. Try again.');</script>";
            }
        } else {
            // If user does not exist -> create account automatically (optional)
            // Comment below block if you don't want auto-create on login
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt2->execute([$email, $email, $hashed]); // Name as email for auto-create
            $userId = $conn->lastInsertId();
            $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);
            $_SESSION['user_id'] = $userId;
            echo "<script>alert('Account created automatically!'); window.location='dashboard.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
        a { color:#0070ba; text-decoration:none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Login</h2>
        <form method="POST" autocomplete="off">
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login / Continue</button>
        </form>
        <p class="small">Don't have an account? <a href="signup.php">Signup here</a></p>
    </div>
</body>
</html>
