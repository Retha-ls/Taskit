<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$errors = [];
$debug = false; // Set to true only when debugging

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"]) && isset($_POST["password"])) {
    require_once "connection.php";

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($debug) {
        echo "<pre>Submitted Email: $email\n";
    }

    $stmt = $conn->prepare("SELECT user_id, name, surname, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($debug) {
        echo "Rows found: " . $stmt->num_rows . "\n";
    }

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $name, $surname, $hashed_password);
        $stmt->fetch();

        if ($debug) {
            echo "DB Hash: $hashed_password\n";
        }

        if (password_verify($password, $hashed_password)) {
            if ($debug) {
                echo "✅ Password matches\n</pre>";
            }

            $_SESSION["user_id"] = $user_id;
            $_SESSION["name"] = $name;
            $_SESSION["surname"] = $surname;

            // REDIRECT before any HTML
            header("Location: dashboard.php");
            exit;
        } else {
            if ($debug) {
                echo "❌ Password does NOT match\n</pre>";
            }
            $errors[] = "Invalid email or password.";
        }
    } else {
        if ($debug) {
            echo "❌ No user found with that email\n</pre>";
        }
        $errors[] = "Invalid email or password.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - TaskIt</title>
</head>
<body>
<h2>Login</h2>

<?php foreach ($errors as $error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required
        value="<?php echo isset($_POST["email"]) ? htmlspecialchars($_POST["email"]) : ''; ?>"><br>

    <input type="password" name="password" placeholder="Password" required><br>

    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="signup.php">Signup</a></p>
</body>
</html>
