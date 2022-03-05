<?
//print_r($_SERVER);
// if($_POST['username'] && $_POST['pass']): 

// 	$res = $DB->fetchAll("SELECT * FROM `username` WHERE `username` = '".$_POST['username']."' ")[0];
// 	if( password_verify($_POST['pass'], $res['userpass']) ):
// 		$status = 'Zalogowano'.PHP_EOL;
// 		$_SESSION[PROJECT]['AUTH'] = [
// 			'username' => $res['username'],
// 			'userID' => $res['ID'],
// 			'acces' => $res['acces'],
// 		];
// 		//print_r($_SESSION);
// 		//header("HTTP/1.1 404 Not Found");
// 		header('Location: https://'.$_SERVER['HTTP_HOST'].'/dashboard/user/home');

		
// 	else:

// 		$status = 'Błedne dane logowania.'.PHP_EOL;
// 	endif;


// endif;



?>
<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">



					<? if($status): ?>
					     <div class="notice error"><?= $status; ?> </div>
					<? else: ?>

					  <div>
					    <form class="login-form" method="post">
					      <input name="username" type="text" placeholder="login" required minlength="4" maxlength="31"/>
					      <input name="pass" type="password" placeholder="hasło" required minlength="6" maxlength="32"/>
					      <input type="submit" value="ZALOGUJ SIĘ" />
					    </form>
					  </div>
<? endif; ?>


				</div>
		</div>
	</div>
</div>