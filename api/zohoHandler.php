<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/zohoAuthorization.php');

// Точка входа
if (authorization('zohoHandler')) {
	// Получение данных из формы
	$name = $_POST['name'];
	$phone = $_POST['phone'];
	$email = $_POST['email'];
	$budget = $_POST['budget'];
	$source = $_POST['source'];

	if (checkForm($name, $phone, $email)) {
		if ($idLead = findLead($phone)) {
			// Lead exists
			$idAccount = findAccount($phone);
			$idContact = findContact($phone);
			convertLeadToDeal($idAccount, $idLead, $idContact);
		} else {
			// Lead doesn't exist
			createLead($name, $phone, $email, $budget, $source);

			$idContact = findContact($phone);
			if (!$idContact) {
				createContact($name, $phone, $email, $source);
			} else {
				// Контакт уже существует
				echo json_encode(array(
					'success' => false,
					'message' => "Контакт уже существует: $idContact"
				), JSON_UNESCAPED_UNICODE);
			}
		}
	}
} else {
	echo json_encode(array(
		'success' => false,
		'message' => "Не авторизован",
		'error' => 'authorization'
	), JSON_UNESCAPED_UNICODE);
}

// Check form for valid required field
function checkForm($name, $phone, $email)
{
	// Check if field not empty
	if ($name && $phone && $email) {
		return true;
	} else {
		return false;
	}
}

// Check lead for exists
function findLead($phone)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads/search?criteria=Phone:equals:$phone");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, false);
		$response = curl_exec($curl);
		$id = str_replace('"', "", json_encode(json_decode($response)->data[0]->id)); // убираем кавычки для последующих запросов
		curl_close($curl);

		if ($response) {
			// Лид найден
			return $id;
		} else {
			return false;
		}
	}
}

function findContact($phone)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Contacts/search?criteria=Phone:equals:$phone");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, false);
		$response = curl_exec($curl);
		$id = str_replace('"', "", json_encode(json_decode($response)->data[0]->id));
		curl_close($curl);


		if ($response) {
			// Контакт найден
			return $id;
		} else {
			return false;
		}
	}
}

function findAccount($phone)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads/search?criteria=Phone:equals:$phone");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, false);
		$response = curl_exec($curl);
		$id = str_replace('"', "", json_encode(json_decode($response)->data[0]->id)); // убираем кавычки для последующих запросов
		curl_close($curl);


		if ($response) {
			// Аккаунт найден
			return $id;
		} else {
			return false;
		}
	}
}

function createLead($name, $phone, $email, $budget, $source)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));

		$postData = json_encode(array(
				'data' => array(
					array(
						'Last_Name' => $name,
						'Phone' => $phone,
						'Email' => $email,
						'Annual_Revenue' => $budget,
						'Lead_Source' => $source,
					)
				)
			)
		);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		$response = json_encode(json_decode(curl_exec($curl)));
		curl_close($curl);

		$idLead = str_replace('"', "", json_encode(json_decode($response)->data[0]->details->id));
        
		// Лид создан

		echo json_encode(array(
			'success' => true,
			'message' => "Лид создан: $idLead"
		), JSON_UNESCAPED_UNICODE);
	}
}

function createContact($name, $phone, $email, $source)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Contacts");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));

		$postData = json_encode(array(
				'data' => array(
					array(
						'Last_Name' => $name,
						'Phone' => $phone,
						'Email' => $email,
						'Lead_Source' => $source,
					)
				)
			)
		);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		$response = json_encode(json_decode(curl_exec($curl)));
		curl_close($curl);

		$id = str_replace('"', "", json_encode(json_decode($response)->data[0]->details->id));
		// Контакт создан
		return $id;
	}
}

function convertLeadToDeal($idAccount, $idLead, $idContact)
{
	$url = "https://www.zohoapis.eu/crm/v2/Leads/$idLead/actions/convert";
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, $url); // Адрес обработчика API
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token'],
			'Content-Type: application/json'
		));

		$postData = json_encode(array(
				'data' => array(
					array(
						'overwrite' => true,
						'Account' => $idAccount,
						'Contact' => $idContact,
						"Deals" => array(
							"Deal_Name" => "Robert",
							"Closing_Date" => "2016-03-30",
							"Stage" => "Closed Won",
							"Amount" => 56.6
						)
					)
				)
			)
		); // Переменная данных в json для запроса

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Получать ли ответ с запроса
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POST, true); // Обозначаем, что запрос будет с помощью метода POST
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData); // Заполняем данные в теле запроса
		$response = curl_exec($curl); // Запускаем запрос

		if ($response) {
			// Запрос не пустой
			// Лид уже существует, существующий лид конвертирован в сделку
			echo json_encode(array(
				'success' => true,
				'message' => "Лид сконвертирован в сделку: $idLead",
				'response' => $response,
				'url' => $url,
				'idLead' => $idLead,
			), JSON_UNESCAPED_UNICODE);
		}else{
			// Запрос пустой (Так не должно быть даже в случае ошибки в теле запроса к API)
			// Чекай в инспекторе в браузере в Network - выбирай последний снизу файл и вкладку в файле Response
			// Ошибка конвертации лида в сделку
			echo json_encode(array(
				'success' => false,
				'message' => "Ошибка конвертации лида в сделку: $idLead",
				'response' => $response,
				'curl_error' => curl_error($curl),
				'curl_http_status' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
				'url' => $url,
				'idLead' => $idLead,
			), JSON_UNESCAPED_UNICODE);
		}

		curl_close($curl); // Закрываем запрос
	}
}