<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
// Запуск функции
authorization($_POST['method']);
// Второй этап авторизации, если $code получен на первом этапе с помощью перехода по ссылке https://accounts.zoho.com/oauth/v2/auth...
function authorization($method)
{
	if (isset($_COOKIE['access_token'])) {
		// Первый этап авторизации уже был осуществлен ранее и уже имеется access_token
		// Необходима проверка валидности токена, например тестовый запрос по API
		if ($method == 'loginPage') {
			echo json_encode(array(
					'success' => true
				)
			);
		}
		if ($method == 'zohoHandler') {
			return true;
		}
	} else {
		// При первом этапе авторизации, после перехода по ссылке авторизации https://accounts.zoho.com/oauth/v2/auth?...
		$code = $_GET['code'];
		if ($code) {
			// Second stage of authorization
			$accountsServer = $_GET['accounts-server'];

			if ($curl = curl_init()) {
				curl_setopt($curl, CURLOPT_URL, "$accountsServer/oauth/v2/token");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS,
					"grant_type=" . GRANT_TYPE . "&client_id=" . CLIENT_ID . "&client_secret=" . CLIENT_SECRET . "&redirect_uri=" . REDIRECT_URI . "&code=$code");
				$request = json_decode(curl_exec($curl));
				curl_close($curl);

				$access_token = $request->access_token;

				if ($access_token) {
					setcookie('access_token', $access_token, time() + 3600, '/');
				}

				// Успешная авторизация

				if ($method == 'loginPage' || !$method) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: /");
					echo json_encode(array(
							'success' => true
						)
					);
				}
				if ($method == 'zohoHandler') {
					return true;
				}
			}
		} else {
			// Необходимо пройти первый этап авторизации (переход по ссылке на https://accounts.zoho.com/oauth/v2/auth?...

			if ($method == 'loginPage') {
				echo json_encode(array(
					'success' => false,
					'message' => 'Необходимо пройти по ссылке первого этапа авторизации https://accounts.zoho.com/oauth/v2/auth?...'
				), JSON_UNESCAPED_UNICODE);
			}
			if ($method == 'zohoHandler') {
				return false;
			}
		}
	}
}