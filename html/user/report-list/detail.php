
<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>DODATKOWE INFORMACJE O DANYM ROKU: <?=$_GET['get'];?></h1>



					<? $results = $DB->fetchAll("SELECT * FROM `results` WHERE `year` = '".$_GET['get']."' AND `userID` = '".$user['userID']."' ORDER BY `results`.`year` ASC");

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
							<hr>
							<table class="table-1">
								<tr>
									<td></td><td>ILOŚĆ TRANSAKCJI</td>
								</tr>
								<tr>
									<td class="ta-l">KUPNO</td><td><?= my_number($v['items_buy'],0); ?></td>
								</tr>
								<tr>
									<td class="ta-l">SPRZEDAŻ</td><td><?= my_number($v['items_sell'],0); ?></td>
								</tr>
								<tr>
									<td class="ta-l">SPRZEDAŻ PROWIZJA</td><td><?= my_number($v['items_fee'],0); ?></td>
								</tr>
							</table>
<br><hr><br>
							<? if($v['json']): ?>
							<? $json = json_decode($v['json'], true); ?>

				
							<table class="table-1">
								<tr>
									<td class="ta-l">COIN</td><td colspan="2" class="ta-c">KUPNO</td><td colspan="2" class="ta-c">SPRZEDAŻ</td><td></td>
								</tr>
								<tr>
									<td></td><td>ILOŚĆ</td><td>KOSZTY PLN</td><td>ILOŚĆ</td><td>PROTIT PLN</td><td>SUMA PLN</td>
								</tr>
							<? if($json['coin']): ?>
							<? foreach ($json['coin'] as $cw=> $val): ?>
								<tr>
									<td class="ta-l"><?=$cw;?></td><td><?=my_number($val['items_buy'],0);?></td><td><?=my_number($val['zakupy']);?> <span class="szary">PLN</span></td><td><?=my_number($val['items_sell'],0);?></td><td><?=my_number($val['sprzedaze']);?> <span class="szary">PLN</span></td>
									<td><?= my_number($val['sprzedaze']+$val['zakupy']) ;?> <span class="szary">PLN</span> </td>
								</tr>								
							<? endforeach;?>
							<? endif;?>

							</table>
							<? endif;?>



					 	<? endforeach ?>

					 <? else: ?>
					 	<div class="notice">Brak raportu. </div>
					 <? endif;?>




				</div>
		</div>
	</div>
</div>