

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>RAPORTY PODATKOWE</h1>


					<? $r1 = $DB->fetchAll("SELECT * FROM `results` WHERE `userID` = '".$user['userID']."' ORDER BY `results`.`year` ASC"); ?>

					<? if($r1): ?>
						<h3><a href="https://www.facebook.com/groups/bitbaypolska/posts/1256142594896082/" target="_blank">Twoje raporty są już wykonane. Bardzo proszę o komentarz na grupie giełdy zonda o wygenerowaniu raportu. Dziękujemy.</a></h3>
					<? endif;?>
					<? $results = $DB->fetchAll("SELECT * FROM `results` WHERE `userID` = '".$user['userID']."' ORDER BY `results`.`year` ASC");
					//echo '<pre>';
					//print_r($results);
					//echo '</pre>'
					 ?>
					 <? if($results):?>
					 	<? foreach ($results as $k => $v): ?>
					 		<h2><?= $v['year']; ?></h2>
					 		<table class="table-1">
								<tr><td class="ta-l"></td><td>KOSZTY</td><td>PRZYCHÓD</td><td>DOCHÓD</td></tr>
								<tr><td class="ta-l">KRYPTOWALUTY</td><td><?= my_number($v['koszty']); ?> <span class="szary">PLN</span></td> <td><?= my_number($v['przychody']); ?> <span class="szary">PLN</span></td><td></td></tr>
								<tr><td class="ta-l">PROWIZJE</td><td><?= my_number($v['koszty_fee']); ?> <span class="szary">PLN</span></td><td><span class="szary">N/A</span></td><td></td></tr>
								<tr><td class="ta-l">SUMA</td><td><?= my_number($v['koszty_fee'] + $v['koszty']); ?> <span class="szary">PLN</span></td><td><?= my_number($v['przychody']); ?> <span class="szary">PLN</span></td><td><?= my_number($v['sum']); ?> <span class="szary">PLN</span></td></tr>
							</table>
							<br>
							<a class="button-1" href="/results/<?= crc32($v['userID']); ?>_<?= $v['year']; ?>.html" target="_blank">Pobierz szczegółowy raport</a>
							<a class="button-1" href="/dashboard/user/report-list/detail/-/<?= $v['year']; ?>">Dodatkowe informacje</a>

							<hr>
					 	<? endforeach ?>

					 <? else: ?>
					 	<div class="notice">Brak dostępnych raportów. Aby poprosić o pobranie i wygenerowanie raportów przejdź do sekcji "PROŚBA O RAPORT"</div>
					 <? endif;?>




				</div>
		</div>
	</div>
</div>