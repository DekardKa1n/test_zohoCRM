<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
?>
<html>
<head>
</head>
<body>

<? if ($_COOKIE['access_token']) { ?>
	<form id="formCreateLead" method="POST" onsubmit="return createLead();">
		<input value="" placeholder="Имя" name="name" required><br><br>
		<input value="" placeholder="Телефон" name="phone" type="number" required><br><br>
		<input value="" placeholder="email" name="email" type="email" required><br><br>
		<input value="" placeholder="Сумма сделки" name="budget"><br><br>
		<input value="" placeholder="Источник" name="source"><br><br>
		<input type="submit" value="Создать лид"/>
	</form>
	<?
} else {
	echo 'Переадресация на страницу авторизации';
} ?>
</body>
</html>


<script>
    // При загрузке страницы
    window.onload = function () {
        // Авторизуемся
        authorization();
    }

    function authorization() {
        // Асинхронный запрос на обработчик авторизации
        var formData = new FormData();
        formData.append('method', 'loginPage');
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                // Ответ сервера
                responce = xhr.responseText;
                try {
                    responce = JSON.parse(responce);
                } catch (exc) {
                    console.log('Ошибка парсинга');
                    console.log(exc);
                    console.log('---------');
                    console.log(responce);
                }

                if (responce.success) {
                    // Успешная авторизация
                    formCreateLead = document.getElementById('formCreateLead');
                    formCreateLead.setAttribute("style", "");
                } else {
                    // Переход на ссылку первого этапа авторизации
                    window.location.href = "https://accounts.zoho.com/oauth/v2/auth?scope=ZohoCRM.modules.all&client_id=<? echo CLIENT_ID; ?>" +
                        "&response_type=code&access_type=offline&redirect_uri=<? echo REDIRECT_URI; ?>"
                }
            }
        };
        xhr.open('POST', '/api/zohoAuthorization.php');
        xhr.send(formData);
    }

    function createLead() {
        // Асинхронный запрос на обработчик создания лида
        form = document.getElementById('formCreateLead');
        var formData = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                // Ответ сервера
                responce = xhr.responseText;
                try {
                    responce = JSON.parse(responce);
                } catch (exc) {
                    console.log('Ошибка парсинга');
                    console.log(exc);
                    console.log('---------');
                    console.log(responce);
                }

                if (responce.success) {
                    // Лид создан
                    alert('Лид создан');
                    // Очистка данных в форме
                    //form.reset();
                } else {
                    // Лид не создан
                    alert(responce.message);
                    if(responce.error == "authorization"){
                        // Переход на ссылку первого этапа авторизации
                        window.location.href = "https://accounts.zoho.com/oauth/v2/auth?scope=ZohoCRM.modules.all&client_id=<? echo CLIENT_ID; ?>" +
                            "&response_type=code&access_type=offline&redirect_uri=<? echo REDIRECT_URI; ?>"
                    }
                }
            }
        };
        xhr.open('POST', '/api/zohoHandler.php');
        xhr.send(formData);

        // Отмена перезагрузки страницы формой
        return false;
    }
</script>