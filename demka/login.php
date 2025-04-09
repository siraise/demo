<?php
session_start();

$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
'root', 
null, 
[PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Проверка наличия токена
if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
    $token = $_SESSION['token'];
    $user = $db->query("SELECT id, type FROM users WHERE token = '$token'")->fetch();
    
    if (!empty($user)) {
        $userType = $user['type'];
        $isAdmin = $userType == 'admin';
        $isUser = $userType == 'user';
        
        if ($isAdmin) {
            header('Location: admin.php');
            exit;
        } elseif ($isUser) {
            header('Location: user.php');
            exit;
        }
    }
}

// Проверка логина и пароля с БД
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Поля необходимо заполнить';
    } else {
        $user = $db->query("SELECT id, password, type, blocked, amountAttempt, latest FROM users WHERE login = '$login'")->fetch();
        
        // Проверяем, не заблокирован ли пользователь
        if ($user && $user['blocked'] == 1) {
            $error = 'Пользователь заблокирован, обратитесь к администрации';
        }
        // Проверяем, не истекла ли дата последней активности (больше месяца)
        elseif ($user && $user['latest'] && strtotime($user['latest']) < strtotime('-1 month')) {
            $db->query("UPDATE users SET blocked = 1 WHERE id = {$user['id']}");
            $error = 'Пользователь заблокирован из-за долгого отсутствия активности';
        }
        // Проверяем правильность пароля
        elseif ($user && $user['password'] === $password) {
            // Сбрасываем счетчик попыток
            $db->query("UPDATE users SET amountAttempt = 0 WHERE id = {$user['id']}");
            
            // Генерируем токен
            $token = bin2hex(random_bytes(16));
            
            // Записываем токен в сессию
            $_SESSION['token'] = $token;
            
            // Записываем токен и обновляем дату активности в БД
            $userId = $user['id'];
            $db->query("UPDATE users SET token = '$token', latest = NOW() WHERE id = $userId");
            
            // Редирект в зависимости от типа пользователя
            if ($user['type'] === 'admin') {
                header('Location: admin.php');
                exit;
            } else {
                header('Location: user.php');
                exit;
            }
        } else {
            // Увеличиваем счетчик неудачных попыток
            if ($user) {
                $newAttempts = $user['amountAttempt'] + 1;
                $db->query("UPDATE users SET amountAttempt = $newAttempts WHERE id = {$user['id']}");
                
                // Если попыток >= 3 - блокируем пользователя
                if ($newAttempts >= 3) {
                    $db->query("UPDATE users SET blocked = 1 WHERE id = {$user['id']}");
                    $error = 'Превышено количество попыток входа. Пользователь заблокирован.';
                } else {
                    $remaining = 3 - $newAttempts;
                    $error = "Неверный логин или пароль. Осталось попыток: $remaining";
                }
            } else {
                $error = 'Неверный логин или пароль';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login">
        <form method='POST' action='login.php'>
             <h1>Авторизация</h1>
        <label for="login">
            Введите логин
            <?php if(isset($error) && empty($login)): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
        </label>
        <input type="text" name="login" id="login" required>
         <label for="password">
            Введите пароль
            <?php if(isset($error) && empty($password)): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
        </label>
        <input type="password" name="password" id="password" required>
         <button type = "submit">Вход</button>
         <?php if(isset($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
        </form>
    </div>
</body>
</html>