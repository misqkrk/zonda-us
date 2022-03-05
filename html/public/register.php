<?



if($_POST['pass'] != $_POST['pass2']): 
	$status = 'Błąd - Pola hasła są różne!!';

elseif($_POST['username'] && $_POST['pass']): 

	$duplicateuser = $DB->fetchAll("SELECT * FROM `username` WHERE `username` = '".$_POST['username']."' ");
	if($duplicateuser):
		$status = 'Błąd - taki uzytkownik już istnieje.'.PHP_EOL;

	else:
		$q = [
			'username' => $_POST['username'],
			'userpass' => password_hash($_POST['pass'], PASSWORD_DEFAULT),
			'add_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'],
		];
		$DB->insertOne('username', $q);
		$test_add = $DB->fetchAll("SELECT * FROM `username` WHERE `username` = '".$_POST['username']."' ")[0];

		if($test_add['username']):

			$status = 'Zarejetrowano poprawnie. Twój Login: <b>'.$_POST['username'].'</b>. Zaloguj się.'.PHP_EOL;
		else:
			$status = 'Nieznany błąd - spróbuj ponownie'.PHP_EOL;
		endif;
	endif;




endif;

?>

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">

					<? if($status): ?>
					     <div class="notice"><?= $status; ?> </div>
					<? else: ?>
							<div class="notice">Pamiętaj, dla Twojego bezpieczeństwa nie używaj loginu identycznego jak na giełdzie zondaglobal.com.</div>
					    <form class="login-form" method="post">
					      <input name="username" type="text" placeholder="Wpisz login" required minlength="4" maxlength="31"/>
					      <input name="pass" type="password" placeholder="Wpisz hasło" required minlength="6" maxlength="32"/>
					      <input name="pass2" type="password" placeholder="Powtórz hasło" required minlength="6" maxlength="32"/>

					      <input type="submit" value="ZAREJESTRUJ SIĘ" />
					    </form>
<? endif; ?>

				</div>
		</div>
	</div>
</div>

