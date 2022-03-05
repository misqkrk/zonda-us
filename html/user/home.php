

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>DASHBOARD</h1>
					
					<? $notice_1 = $DB->fetchAll("SELECT * FROM `username` WHERE `ID` = '".$user['userID']."' AND `publickey` IS NOT NULL AND `privatekey` IS NOT NULL ")[0]; ?>
					<? $notice_2 = $DB->fetchAll("SELECT * FROM `request` WHERE `userID` = '".$user['userID']."' ")[0]; ?>


					<? $results = $DB->fetchAll("SELECT * FROM `results` WHERE `userID` = '".$user['userID']."' ORDER BY `results`.`year` ASC"); ?>



					
					<? if(!$notice_1): ?>
						<div class="notice error">Aby prosić o pobranie i wygenerowanie raportu doodaj klucze API w sekcji "ustawienia"!</div>
					<? endif; ?>



					<p>1. <?= ($notice_1 ? '🟢' : '🔴' ); ?> Uzupełnione klucze API <?= ($notice_1 ? '' : ' - <a href="/dashboard/user/settings">dodaj</a>' ); ?></p>
					<p>2. <?= ($notice_2 ? '🟢' : '🔴' ); ?> Wysłana prośba o raport <?= ($notice_1 ? '' : ' - <a href="/dashboard/user/report-request">wyślij</a>' ); ?></p>
					<p>3. <?= ($results ? '🟢' : '🔴' ); ?> Oczekiwanie na wygenerowanie raportów (zwykle do 48h)</p>
					<p>4. <?= ($results ? '🟢' : '🔴' ); ?> Raporty gotowe</p>
					<br>
					
					<? if($results): ?>
						<h3><a href="https://www.facebook.com/groups/bitbaypolska/posts/1256142594896082/" target="_blank">Twoje raporty są już wykonane. Bardzo proszę o komentarz na grupie giełdy zonda o wygenerowaniu raportu. Dziękujemy.</a></h3>
						<p>Posiadasz wygenerowane raporty - przejdź do sekcji "RAPORTY PODATKOWE".</p>
					<? else : ?>
						<span>Obecnie nie posiadasz wygenerowanych raportów.</span>
					<? endif;?>
					<p><b>Dla wszystkich którzy wyslali prośbę o raport za rok 2021 (niepełny) - WSZYSTKIE RAPORTY ZA 2021 zostały wygenerowane za pełny okres 2021 w dniu 1.1.2022.</b></p>






				</div>
		</div>
	</div>
</div>