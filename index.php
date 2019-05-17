<?
// Get code from authorization web page on https://accounts.zoho.com/oauth/v2/auth
authorization();

$client_id = '1000.6HC8GXVGEPOP14698XB3PMLH13R0BR';
$client_secret = '04d6a39a93b2c8bdeedf5c3adfa50b0269dd2f389d';
$redirect_uri = 'http://roistat-test.ru/';

// Second step of authorization if $code exists by first step authorization <a href = "https://accounts.zoho.com/oauth/v2/auth...
function authorization()
{
	global $client_id, $client_secret, $redirect_uri;
	if (isset($_COOKIE['access_token'])) {
		// Already authorized
		return true;
	} else {
		// First stage of authorization
		$code = $_GET['code'];
		if ($code) {
			// Second stage of authorization
			$accountsServer = $_GET['accounts-server'];

			if ($curl = curl_init()) {
				$grant_type = 'authorization_code';

				curl_setopt($curl, CURLOPT_URL, "$accountsServer/oauth/v2/token");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS,
					"grant_type=$grant_type&client_id=$client_id&client_secret=$client_secret&redirect_uri=$redirect_uri&code=$code");
				$request = json_decode(curl_exec($curl));
				curl_close($curl);

				$access_token = $request->access_token;

				if ($access_token) {
					setcookie('access_token', $access_token, time() + 3600);
				}

				// Success authorization
				return true;
			}
		} else {
			// Need authorization
			return false;
		}
	}
}

// Get fields from form
$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$budget = $_POST['budget'];
$source = $_POST['source'];

// Main function
if (checkForm($name, $phone, $email) && authorization()) {
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
			echo "<script>console.log('Контакт уже существует: $idContact');</script>";
		}
	}
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
		$request = curl_exec($curl);
		$id = json_encode(json_decode($request)->data[0]->id);
		curl_close($curl);

		if ($request) {
			echo "<script>console.log('Лид найден: $id');</script>";
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
		$request = curl_exec($curl);
		$id = json_encode(json_decode($request)->data[0]->id);
		curl_close($curl);


		if ($request) {
			echo "<script>console.log('Контакт найден: $id');</script>";
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
		$request = curl_exec($curl);
		$id = json_encode(json_decode($request)->data[0]->id);
		curl_close($curl);


		if ($request) {
			echo "<script>console.log('Аккаунт найден: $id');</script>";
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
		$request = json_encode(json_decode(curl_exec($curl)));
		curl_close($curl);

		$id = json_encode(json_decode($request)->data[0]->details->id);
		echo "<script>console.log('Лид создан: $id');</script>";
		return $id;
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
		$request = json_encode(json_decode(curl_exec($curl)));
		curl_close($curl);

		$id = json_encode(json_decode($request)->data[0]->details->id);
		echo "<script>console.log('Контакт создан: $id');</script>";
		return $id;
	}
}

function convertLeadToDeal($idAccount, $idLead, $idContact)
{
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, "https://www.zohoapis.eu/crm/v2/Leads/$idLead/actions/convert");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Zoho-oauthtoken ' . $_COOKIE['access_token']
		));

		$postData = json_encode(array(
				'data' => array(
					array(
						'overwrite' => true,
						'Accounts' => $idAccount,
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
		);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		$request = curl_exec($curl);
		curl_close($curl);
		echo "<script>console.log($request);</script>";
		if($request) {
			echo '<script>console.log("Лид уже существует, существующий лид конвертирован в сделку");</script>';
		}
	}
}

if (authorization()) {
?>
<html>
<body>
<form action="/" method="POST">
	<input value="" placeholder="Имя" name="name" required><br><br>
	<input value="" placeholder="Телефон" name="phone" type="tel" required><br><br>
	<input value="" placeholder="email" name="email" type="email" required><br><br>
	<input value="" placeholder="Сумма сделки" name="budget"><br><br>
	<input value="" placeholder="Источник" name="source"><br><br>
	<input type="submit" value="Создать лид">
</form>

<? } else { ?>
<html>
<body>
<a href="https://accounts.zoho.com/oauth/v2/auth?scope=ZohoCRM.modules.all&client_id=<? echo $client_id; ?>
&response_type=code&access_type=offline&redirect_uri=<? echo $redirect_uri; ?>">Авторизация</a>
<? } ?>

</body>
</html>