<?
$results = $DB->fetchAll("SELECT * FROM `username` WHERE `ID` = '".$user['userID']."' ")[0];

if($_POST['publickey'] && $_POST['privatekey']):
	$API = new API(['public' => $_POST['publickey'], 'private' => $_POST['privatekey'], 'host' => 'api.zonda.exchange']);
	$res = $API->getTransactionsHistory('start', 10, '2017-01-01', '2023-01-01');

	if($res['status'] == 'Ok'):
		$notice = '<div class="notice ok">Klucze dodano poprawnie!</div>';
		$q = [
			'ID' => $user['userID'],
			'publickey' => $_POST['publickey'],
			'privatekey' => $_POST['privatekey'],
		];
		$DB->insertOrUpdate('username', $q);

	else:
		$notice = '<div class="notice error">Błąd dodawania kluczy! ERR-101 | '.$res['errors'][0].'</div>';
	endif;

	//print_r($res);

endif;
?>

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>USTAWIENIA</h1>

					<? echo $notice;?>
					<form method="post">
						<input type="text" name="publickey" value="<?=$results['publickey'];?>" placeholder="Bitbay public key" required="" minlength="36" maxlength="38">
						<input type="text" name="privatekey" value="<?=$results['privatekey'];?>" placeholder="Bitbay private key" required="" minlength="36" maxlength="38">
						<input type="submit" name="send" value="Dodaj Klucze API">
					</form>
					<p>Zaloguj się na swoje konto Zonda (bitbay). Przejdź do "Ustawienia API" dodaj nowy klucz zaznaczając tylko i wyłącznie opcję "Historia". Zapisz i otrzymane klucze publiczny i prywatny dodaj na tej stronie w sekcji ustawienia.</p>
					<p>Po więcej informacji sięgnij do działu <a href="/dashboard/faq">pomoc</a>

				</div>
		</div>
	</div>
</div>