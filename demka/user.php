<?php
session_start();

$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
'root', 
null, 
[PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Проверка токена и редирект если не авторизован
if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$token = $_SESSION['token'];
// Выбираем все необходимые поля, включая новые
$user = $db->query("SELECT id, type, name, surname, isBlocked, login, token, blocked, amountAttempt, latest FROM users WHERE token = '$token'")->fetch();

if (empty($user)) {
    header('Location: login.php');
    exit;
}

// Обновляем дату последней активности при каждом посещении
$userId = $user['id'];
$db->prepare("UPDATE users SET latest = NOW() WHERE id = ?")->execute([$userId]);

// Обработка выхода из учетной записи
if (isset($_GET['logout'])) {
    // Сбрасываем токен в БД
    $db->prepare("UPDATE users SET token = NULL WHERE id = ?")->execute([$userId]);
    
    // Сбрасываем сессию
    unset($_SESSION['token']);
    session_destroy();
    
    // Редирект на страницу входа
    header('Location: login.php');
    exit;
}

// Обработка изменения пароля
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Проверяем, не заблокирован ли пользователь
    if ($user['blocked'] == 1) {
        $error = 'Пользователь заблокирован, обратитесь к администрации';
    } else {
        // Получаем данные из формы
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Проверка что поля переданы и не пустые
        if (empty($password) || empty($confirm_password)) {
            $error = 'Все поля необходимо заполнить';
        } 
        // Проверка что пароли совпадают
        elseif ($password !== $confirm_password) {
            $error = 'Пароли не совпадают';
        } 
        // Если все проверки пройдены - обновляем пароль
        else {
            // Хеширование пароля рекомендуется, но в вашем коде используется plain text
            $hashedPassword = $password; // В реальном проекте используйте password_hash()
            
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $success = 'Пароль успешно изменен';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Окно пользователя</title>
    <link rel="stylesheet" href="rename_password.css">
    <style>
        .user-info {
            margin: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .logout-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        .blocked-message {
            color: red;
            font-weight: bold;
            margin: 20px;
            padding: 15px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffebee;
        }
    </style>
</head>
<body>
    <header><h1>Окно пользователя</h1></header>
    
    <!-- Блок с информацией о пользователе -->
    <div class="user-info">
        <h2>Информация о пользователе</h2>
        <div class="user-details">
            <p><strong>ID:</strong> <?= htmlspecialchars($user['id']) ?></p>
            <p><strong>Фамилия:</strong> <?= htmlspecialchars($user['surname']) ?></p>
            <p><strong>Имя:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>Тип пользователя:</strong> <?= htmlspecialchars($user['type']) ?></p>
            <p><strong>Статус блокировки:</strong> <?= $user['blocked'] == 1 ? 'Заблокирован' : 'Активен' ?></p>
            <p><strong>Неудачных попыток входа:</strong> <?= htmlspecialchars($user['amountAttempt']) ?></p>
            <p><strong>Последняя активность:</strong> <?= $user['latest'] ? htmlspecialchars($user['latest']) : 'Никогда' ?></p>
            <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
            <a href="?logout=1" class="logout-btn">Выйти из учетной записи</a>
        </div>
    </div>
    
    <div class="login">
        <?php if ($user['blocked'] == 1): ?>
            <div class="blocked-message">
                Пользователь заблокирован, обратитесь к администрации
            </div>
        <?php else: ?>
            <form action="user.php" method="POST">
                <h1>Изменить пароль</h1>
                
                <label for="password">
                    Введите новый пароль
                    <?php if(isset($error) && empty($password)): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
                </label>
                <input type="password" name="password" id="password" required>
                
                <label for="confirm_password">
                    Подтвердите новый пароль
                    <?php if(isset($error) && empty($confirm_password)): ?><span class="error">Необходимо заполнить</span><?php endif; ?>
                </label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                
                <button type="submit">Изменить пароль</button>
                
                <?php if(isset($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
                <?php if(isset($success)): ?><p class="success"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>