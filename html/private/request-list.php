<div class="container">
	<div class="row">
			<div class="col col-12">

			<? if($_GET['accept']):	?>
				<? 
				$q = [
					'ID' => $_GET['ID'],
					't' => 1,
					'f' => 1,
					'g' => 1,
					'error' => '++NULL',
					'allow' => 1,
				];
				$DB->insertOrUpdate('request', $q);

				$notice = '<div class="notice ok">Accepted</div>';
				echo $notice;
			?>	
			<? endif;?>

			<? if($_GET['generate']):	?>
				<? 
				$q = [
					'ID' => $_GET['ID'],
					'g' => 1,
					'allow' => 1,
				];
				$DB->insertOrUpdate('request', $q);

				$notice = '<div class="notice ok">Accepted</div>';
				echo $notice;
			?>	
			<? endif;?>

				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>

					<div class="col col-12">
						<a class="button-1" href="/dashboard/private/request-list">REQUEST</a>

						<a class="button-1" href="/dashboard/private/request-list/-/waiting">WAITING</a>
						<a class="button-1" href="/dashboard/private/request-list/-/finished">FINISHED</a>
						<a class="button-1" href="/dashboard/private/request-list/-/other">OTHER</a>
						<a class="button-1" href="/dashboard/private/request-list/-/all">ALL</a>

					</div>


					<h1>REQUEST LIST</h1>
					<?
					if($_GET['get'] == 'waiting'):
						$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID` WHERE `r`.`g` = '1' AND `r`.`allow` = '1' ORDER BY `r`.`year` DESC");

					elseif($_GET['get'] == 'finished'):
						$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID` WHERE `r`.`g` = '3' ORDER BY `r`.`id` DESC");

					elseif($_GET['get'] == 'other'):
						$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID` WHERE `r`.`T` > '3' OR  `r`.`F` > '3' OR `r`.`G` > '3'  ORDER BY `r`.`id` DESC");
					elseif($_GET['get'] == 'all'):
						$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID`  ORDER BY `r`.`id` DESC");
					else:
						$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID` WHERE `r`.`allow` IS NULL ORDER BY `r`.`year` DESC");
					endif;
					?>

				
				
					<? $$user_list = $DB->fetchAll("SELECT `r`.*, `u`.`username` FROM request as `r` LEFT JOIN username as `u` ON `u`.`id`=r.`userID` ORDER BY `r`.`year` DESC"); ?>

					<table class="table-2">
						<tr>
							<td>ID</td><td>USERID</td><td>USERNAME</td><td>YEAR</td><td>T</td><td>F</td><td>G</td><td>ALLOW</td><td>ERROR</td><td>TIME</td><td>INFO</td><td>ACCEPTANCE</td><td>RENEGATE</td>
						</tr>
						<? if($user_list): ?>
							<? foreach ($user_list as $k => $v):?>

								<tr>
									<td><?=$v['ID'];?></td>
									<td><?=$v['userID'];?></td>
									<td><?=$v['username'];?></td>

									<td><?=$v['year'];?></td>
									<td><?=$v['t'];?></td>
									<td><?=$v['f'];?></td>
									<td><?=$v['g'];?></td>
									<td><?=$v['allow'];?></td>
									<td><?=$v['error'];?></td>
									<td><?=$v['time'];?></td>
									<td><?=$v['info'];?></td>
									<td>
										<form class="form-1">
											<input type="submit" name="accept" value="ACCEPT" />
											<input type="hidden" name="ID" value='<?=$v['ID'];?>' />
										</form>										
									</td>

									<td>
										<? if($v['t'] == 3 && $v['g'] == 3): ?>
										<form class="form-1">
											<input type="submit" name="generate" value="GENERATE" />
											<input type="hidden" name="ID" value='<?=$v['ID'];?>' />
										</form>	
										<? endif;?>									
									</td>

								</tr>
							<? endforeach;?>
						<? endif;?>
					</table>

				</div>
		</div>
	</div>
</div>