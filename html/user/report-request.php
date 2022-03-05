<?
define('REPORT_YEAR_ALLOW',['2017', '2018', '2019', '2020', '2021', '2022']);
?>

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>PROŚBA O RAPORT PODATKOWY</h1>

					<?
					if($_POST['req']):
						echo '<div class="notice ok">Wysłano prośbę o raport podatkowy za rok '.$_POST['req'].'. Oczekuj na akceptacje akcji przez administratora.</div>';
					$q = [
						'userID' => $user['userID'],
						'year' => $_POST['req'],
						'info' => 'wysłano prośbę o raport',
						't' => 1,
						'f' => 1,
						'g' => 1,
						'allow' => '',
						'error' => '',
					];
					$DB->insertOrUpdate('request', $q);
					endif;

					?>
					<? $notice_1 = $DB->fetchAll("SELECT * FROM `username` WHERE `ID` = '".$user['userID']."' AND `publickey` IS NOT NULL AND `privatekey` IS NOT NULL ")[0]; ?>
					<? $results = $DB->fetchAll("SELECT * FROM `request` WHERE `userID` = '".$user['userID']."' ORDER BY `request`.`year` ASC ", null, 'year'); ?>



					<? if(!$notice_1): ?>
						<div class="notice error">Aby prosić o pobranie i wygenerowanie raportu doodaj klucze API w sekcji "ustawienia"!</div>
					<? endif; ?>

				<? foreach (REPORT_YEAR_ALLOW as $year): ?>
					<div class="reguest-report-1">
						<span class="y"><?=$year;?></span>
						<span class="text">
						<? if(!$results[$year]):
								echo 'Poproś o wygenerowanie raportu';
							else:
								if($results[$year]['allow'] != 1):
									echo 'Oczekiwanie na akceptacje administratora.';

								elseif($results[$year]['error'] == 1):
									echo 'Błąd importowania!';

								else:
									if($results[$year]['t'] == 1):
										echo 'Oczekiwanie na rozpoczęcie importowania transakcji';

									elseif($results[$year]['t'] == 2):
										echo 'Importowanie transakcji oraz prowizji...';

									elseif($results[$year]['f'] == 3 && $results[$year]['f'] == 3 && $results[$year]['g'] == 3):
										echo 'Wykonane. Przejdź do sekcji "RAPORTY PODATKOWE"';

									elseif($results[$year]['f'] == 3 && $results[$year]['f'] == 3):
										echo 'Importowanie transakcji i prowizji ukończone. Trwa generowanie raportu';

									elseif($results[$year]['t'] == 3):
										echo 'Importowanie transakcji ukończone. Trwa importowanie prowizji...';
									else:
										echo 'Oczekiwanie..."';

									endif;
								endif;
							endif;
						?>
						</span>
						<span class="f">
							<form method="post">
								<input type="hidden" name="req" value="<?=$year?>">
								<? if(!$notice_1): ?>		
									<input disabled="disabled" type="submit" value="Dodaj klucze API"> 	

								<? elseif(!$results[$year]): ?>		
									<input type="submit" value="Poproś o raport"> 	
								<? elseif($results[$year]['error'] == 1): ?>		
									<input type="submit" value="Poproś ponownie o raport"> 
								<? elseif($year == 2022 && $results[$year]['f'] == 3 && $results[$year]['f'] == 3 && $results[$year]['g'] == 3): ?>		
									<input type="submit" value="Poproś ponownie o raport"> 
								
								<? else: ?>
									<input disabled="disabled" type="submit" value="Poprośba wysłana"> 	
								<? endif;?>	
							</form>							
						</span> 
					</div>
				<? endforeach; ?>




				</div>
		</div>
	</div>
</div>

