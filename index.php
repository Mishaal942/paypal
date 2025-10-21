<!DOCTYPE html>
<html>
<head>
    <title>PayNow - Digital Payments</title>
    <style>
        body {
            margin:0;
            padding:0;
            font-family: Arial, sans-serif;
            background:#f4f6f9;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .container {
            text-align:center;
            background:white;
            padding:40px;
            border-radius:10px;
            box-shadow:0 0 15px rgba(0,0,0,0.1);
            width:350px;
        }
        h1 {
            margin-bottom:15px;
            font-size:28px;
            color:#0070ba;
        }
        p {
            font-size:14px;
            color:#555;
            margin-bottom:25px;
        }
        button {
            width:100%;
            padding:12px;
            margin:8px 0;
            background:#0070ba;
            color:white;
            border:none;
            border-radius:5px;
            font-size:16px;
            cursor:pointer;
        }
        button:hover {
            background:#005fa3;
        }
    </style>
    <script>
        function goTo(page){
            window.location.href = page;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>PayNow</h1>
        <p>Send, receive, and manage your money securely.</p>
        <button onclick="goTo('signup.php')">Create Account</button>
        <button onclick="goTo('login.php')">Login</button>
    </div>
</body>
</html>
