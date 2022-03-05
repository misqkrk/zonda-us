
<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<h1>DARMOWY PROGRAM DO GENEROWANIA RAPORTU PODATKOWEGO </h1>
					<p><img alt="Zonda"  height="50" src="https://zondaglobal.com/o/bb-theme/images/zonda_black.svg"></p><br>
					<p>Darmowy program do generowanie raportu podatkowego z serwisu Zonda (bitbay) za lata: 2021, 2020, 2019, 2018, 2017 (od września).</p><br>
					<br>
					<p>Raport podatkowy z karty binance. (Wersja beta)</p><br>
					<hr>

					<? 
						$u = $DB->fetchAll("SELECT COUNT(ID) as c FROM `username`")[0]['c'];
						$r = $DB->fetchAll("SELECT COUNT(ID) as c FROM `results`")[0]['c'];
					?>
					<p>Ilość użytkowników: <b><?= $u; ?></b></p>
					<p>Wygenerowanych raportów: <b><?= $r; ?></b></p>
				</div>
		</div>
	</div>
</div>