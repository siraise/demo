<?php
session_start();

$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
'root', 
null, 
[PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// 1. Проверка наличия токена : локально ($_SESSION['token']) и сравнение с бд
//                  Если есть -> перекидываем на странцу пользователя / админа
//                  Если нет -> остаёмся на этой странице

$_SESSION['token'] = '';

// Проверка : существует ли токен и что он не пустой
if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
    $token = $_SESSION['token'];
    //
    $user = $db->query("SELECT id, type FROM users WHERE token = '$token'")->fetchALL();
    
    if (empty($user)) {
        $userType = $token[0]['type'];
        $isAdmin = $userType == 'admin';
        $isUser = $userType == 'user';
    }
    
    $isAdmin && header('Location: admin.php');
    $isUser && header('Location: user.php');
}
//  Проверака логина и пароля с БД , запись токена в БД, редирект
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Получить отправленные данные (логин и пароль)
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 2. Проверить переданы ли они, если нет вернуть ошибку
    // Если да -> ничего не делаем
    // Если нет -> ошибка : поля необходтмо заполнить
    if (empty($login) || empty($password)) {
        $error = 'Поля необходимо заполнить';
    } else {
        // 3. Сравнить с данными в БД
        // Если совпали -> генерируем токен, записываем в сессию и бд, редирект
        // Если нет -> Ошибка : неверный логин или пароль
        $user = $db->query("SELECT id, password, type FROM users WHERE login = '$login'")->fetch();
        
        if ($user && $user['password'] === $password) {
            // Генерируем токен
            $token = bin2hex(random_bytes(16));
            
            // Записываем токен в сессию
            $_SESSION['token'] = $token;
            
            // Записываем токен в БД
            $userId = $user['id'];
            $db->query("UPDATE users SET token = '$token' WHERE id = $userId");
            
            // Редирект в зависимости от типа пользователя
            if ($user['type'] === 'admin') {
                header('Location: admin.php');
                exit;
            } else {
                header('Location: user.php');
                exit;
            }
        } else {
            $error = 'Неверный логин или пароль';
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
         <?php if(isset($error)): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        </form>
    </div>
</body>
</html>