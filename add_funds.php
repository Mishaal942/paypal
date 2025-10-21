<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}
include "db.php";

$userId = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = floatval($_POST['amount']);

    $stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);

    $message = "Funds added successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Funds</title>
    <style>
        body {
            margin:0; padding:0; display:flex;
            justify-content:center; align-items:center;
            height:100vh; background:#eef3f7; font-family:Arial;
        }
        .box {
            background:white; padding:30px; border-radius:8px;
            box-shadow:0 0 15px rgba(0,0,0,0.1); width:350px;
        }
        input {
            width:100%; padding:10px; margin:8px 0;
            border:1px solid #ccc; border-radius:5px;
        }
        button {
            width:100%; padding:12px; background:#0070ba;
            color:white; border:none; border-radius:5px; cursor:pointer;
        }
        button:hover { background:#005fa3; }
        h2 { text-align:center; color:#0070ba; }
        a { text-decoration:none; font-size:14px; color:#0070ba; }
        .msg { color:green; text-align:center; }
    </style>
    <script>
        function backDash(){
            window.location = 'dashboard.php';
        }
    </script>
</head>
<body>
<div class="box">
    <h2>Add Funds</h2>
    <?php if ($message) echo "<p class='msg'>$message</p>"; ?>
    <form method="POST">
        <input type="number" name="amount" placeholder="Amount" step="0.01" required>
        <button type="submit">Add</button>
    </form>
    <p><a href="#" onclick="backDash()">Back to Dashboard</a></p>
</div>
</body>
</html>
