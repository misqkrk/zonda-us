<div class="container">
	<div class="row">
			<div class="col col-12">

			<? if($_GET['check']):	
				$get_user = $DB->fetchAll("SELECT * FROM `username` WHERE `ID` = '".$_GET['userID']."' ")[0]; 
				$API = new API(['public' => $get_user['publickey'], 'private' => $get_user['privatekey'], 'host' => 'api.bitbay.net']);
				$res = $API->getTransactionsHistory('start', 10, '2017-01-01', '2023-01-01');

					if($res['status'] == 'Ok'):
						$notice = '<div class="notice ok">'.$get_user['username'].': '.$res['status'].'</div>';
					else:
						$notice = '<div class="notice error">'.$get_user['username'].': '.$res['status'].'</div>';
					endif;
					echo $notice;
			 endif; ?>
			 
			<? if($_GET['user_login']):	
					$_SESSION[PROJECT]['AUTH'] = [
						'username' => $_GET['username'],
						'userID' => $_GET['ID'],
						'acces' => 8,
					];
					$notice = '<div class="notice ok">Zalogowano jako: '.$_GET['username'].'</div>';
					echo $notice;
		header('Location: https://'.$_SERVER['SERVER_NAME'].'/dashboard/user/home');

				endif;
			?>

				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>USER LIST</h1>
					<? $user_list = $DB->fetchAll("SELECT * FROM `username` ORDER BY `username`.`ID` DESC"); ?>
				

					<table class="table-2">
						<tr>
							<td>ID</td><td>USERNAME</td><td>ADD TIME</td><td>IP</td><td>ACCES</td><td>CHECK KEY</td><td>LOG IN</td>
						</tr>
						<? if($user_list): ?>
							<? foreach ($user_list as $k => $v):?>

								<tr>
									<td><?=$v['ID'];?></td>
									<td><?=$v['username'];?></td>
									<td><?=$v['addtime'];?></td><td><?=$v['add_ip'];?></td>
									<td><?=$v['acces'];?></td>
									
									<td>
										<? if($v['publickey'] && $v['privatekey']): ?>
											<form class="form-1">
												<input type="submit" name="check" value="CHECK" />
												<input type="hidden" name="userID" value='<?=$v['ID'];?>' />
											</form>
										<? else: ?>
											
										<? endif;?>
									</td>

									<td>
										<form class="form-1">
											<input type="submit" name="user_login" value="LOGIN" />
											<input type="hidden" name="username" value='<?=$v['username'];?>' />
											<input type="hidden" name="ID" value='<?=$v['ID'];?>' />
										</form>											
									</td>

								</tr>
							<? endforeach;?>
						<? endif;?>
					</table>

				</div>
		</div>
	</div>
</div>