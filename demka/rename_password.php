<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Окно пользователя</title>
    <link rel="stylesheet" href="rename_password.css">
</head>
<body>
    <header><h1>Окно пользователя</h1></header>
    <div class="login">
        <form action="">
             <h1>Изменить пароль</h1>
        <label for="login">
            Введите пароль
            <span class="error">Необходимо заполнить</span>
        </label>
         <input type="text" name="password" id="login">
         <label for="password">
            Подтвердите пароль
            <span class="error">Необходимо заполнить</span>
        </label>
         <input type="text" name="password" id="login">
         <button type = "submit">Вход</button>
         <p class="error">Пароли не совпадают</p>
        </form>
    </div>
</body>
</html>