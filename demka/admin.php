<?php
session_start();

// Проверка авторизации и прав администратора
if (!isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost; dbname=module; charset=utf8', 
              'root', 
              null, 
              [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Проверка что пользователь администратор
$token = $_SESSION['token'];
$currentUser = $db->query("SELECT * FROM users WHERE token = '$token'")->fetch();
if ($currentUser['type'] !== 'admin') {
    header('Location: user.php');
    exit;
}

// Обработка выхода из учетной записи
if (isset($_GET['logout'])) {
    $db->prepare("UPDATE users SET token = NULL WHERE token = ?")->execute([$token]);
    unset($_SESSION['token']);
    session_destroy();
    header('Location: login.php');
    exit;
}

// Обработка блокировки/разблокировки
if (isset($_GET['block'])) {
    $id = $_GET['block'];
    $action = $_GET['action']; // 'block' или 'unblock'
    
    $blockStatus = ($action === 'block') ? 1 : 0;
    $db->prepare("UPDATE users SET blocked = ? WHERE id = ?")->execute([$blockStatus, $id]);
    
    header('Location: admin.php');
    exit;
}

// Обработка добавления/редактирования пользователя
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Добавление нового пользователя
        $name = $_POST['name'] ?? '';
        $surname = $_POST['surname'] ?? '';
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'user';
        
        $stmt = $db->prepare("INSERT INTO users (name, surname, login, password, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $surname, $login, $password, $type]);
        
        header('Location: admin.php');
        exit;
    } elseif (isset($_POST['edit_user'])) {
        // Редактирование пользователя
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $surname = $_POST['surname'] ?? '';
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'user';
        
        if (!empty($password)) {
            $stmt = $db->prepare("UPDATE users SET name = ?, surname = ?, login = ?, password = ?, type = ? WHERE id = ?");
            $stmt->execute([$name, $surname, $login, $password, $type, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, surname = ?, login = ?, type = ? WHERE id = ?");
            $stmt->execute([$name, $surname, $login, $type, $id]);
        }
        
        header('Location: admin.php');
        exit;
    }
}

// Получение списка всех пользователей
$users = $db->query("SELECT * FROM users")->fetchAll();

// Проверка режима редактирования/добавления
$editMode = isset($_GET['edit']);
$addMode = isset($_GET['add']);
$editUserId = $editMode ? $_GET['edit'] : 0;
$userToEdit = $editMode ? $db->query("SELECT * FROM users WHERE id = $editUserId")->fetch() : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователей</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        .user-list {
            margin: 1rem;
        }
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
        }
        .user-actions button {
            margin-left: 0.5rem;
            padding: 0.3rem 0.6rem;
            cursor: pointer;
        }
        .block-btn {
            background-color: <?= isset($user['blocked']) && $user['blocked'] ? '#4caf50' : '#ff9800' ?>;
            color: white;
            border: none;
            border-radius: 4px;
        }
        .edit-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            border-radius: 4px;
        }
        .add-btn {
            margin: 1rem;
            padding: 0.5rem 1rem;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .user-form {
            margin: 1rem;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 500px;
        }
        .user-form label {
            display: block;
            margin: 0.5rem 0;
        }
        .user-form input, .user-form select {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
        }
        .user-form button {
            padding: 0.5rem 1rem;
            background-color: #2196f3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .blocked {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <h1>Окно администратора - <?= htmlspecialchars($currentUser['name']) ?> <?= htmlspecialchars($currentUser['surname']) ?></h1>
        <form action="admin.php" method="GET">
            <button type="submit" name="logout" class="logout-btn">Выйти из учетной записи</button>
        </form>
    </header>

    <?php if ($addMode || $editMode): ?>
        <div class="user-form">
            <h2><?= $editMode ? 'Редактирование пользователя' : 'Добавление пользователя' ?></h2>
            <form method="POST" action="admin.php">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?= $userToEdit['id'] ?>">
                <?php endif; ?>
                
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" value="<?= $editMode ? htmlspecialchars($userToEdit['name']) : '' ?>" required>
                
                <label for="surname">Фамилия:</label>
                <input type="text" id="surname" name="surname" value="<?= $editMode ? htmlspecialchars($userToEdit['surname']) : '' ?>" required>
                
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" value="<?= $editMode ? htmlspecialchars($userToEdit['login']) : '' ?>" required>
                
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" <?= $editMode ? 'placeholder="Оставьте пустым, чтобы не изменять"' : 'required' ?>>
                
                <label for="type">Тип пользователя:</label>
                <select id="type" name="type">
                    <option value="user" <?= $editMode && $userToEdit['type'] === 'user' ? 'selected' : '' ?>>Обычный пользователь</option>
                    <option value="admin" <?= $editMode && $userToEdit['type'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                </select>
                
                <button type="submit" name="<?= $editMode ? 'edit_user' : 'add_user' ?>">
                    <?= $editMode ? 'Сохранить изменения' : 'Добавить пользователя' ?>
                </button>
                <a href="admin.php" style="margin-left: 1rem;">Отмена</a>
            </form>
        </div>
    <?php else: ?>
        <div class="user-list">
            <h2>Список пользователей</h2>
            <a href="admin.php?add=1" class="add-btn">Добавить пользователя</a>
            
            <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div>
                        <span><?= htmlspecialchars($user['surname']) ?> <?= htmlspecialchars($user['name']) ?></span>
                        <span> (<?= htmlspecialchars($user['login']) ?>)</span>
                        <span class="<?= $user['blocked'] ? 'blocked' : '' ?>">
                            - <?= $user['type'] === 'admin' ? 'Администратор' : 'Пользователь' ?>
                            <?= $user['blocked'] ? '(Заблокирован)' : '' ?>
                        </span>
                    </div>
                    <div class="user-actions">
                        <?php if ($user['id'] != $currentUser['id']): ?>
                            <a href="admin.php?block=<?= $user['id'] ?>&action=<?= $user['blocked'] ? 'unblock' : 'block' ?>">
                                <button class="block-btn" style="background-color: <?= $user['blocked'] ? '#4caf50' : '#ff9800' ?>;">
                                    <?= $user['blocked'] ? 'Разблокировать' : 'Блокировать' ?>
                                </button>
                            </a>
                        <?php endif; ?>
                        <a href="admin.php?edit=<?= $user['id'] ?>">
                            <button class="edit-btn">Редактировать</button>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>