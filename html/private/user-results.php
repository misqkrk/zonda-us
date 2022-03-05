<div class="container">
	<div class="row">
			<div class="col col-12">


			 
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>USER LIST</h1>
					<? $user_list = $DB->fetchAll("SELECT * FROM `username` WHERE `publickey` IS NOT NULL ORDER BY `username`.`ID` DESC"); ?>
				

						<? if($user_list): ?>
							<? foreach ($user_list as $k => $v):?>
								<? $res = $DB->fetchAll("SELECT * FROM `results` WHERE `userID` = ".$v['ID'], null, 'year'); ?>

<table class="table-2">
	<tr>
		<td style="width:30px;" rowspan="6"><?=$v['ID'];?></td><td style="width:190px;"  rowspan="6"><?=$v['username'];?></td><td style="width:100px;" >2017</td><td><?=my_number($res['2017']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
	<tr>
		<td>2018</td><td><?=my_number($res['2018']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
	<tr>
		<td>2019</td><td><?=my_number($res['2019']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
	<tr>
		<td>2020</td><td><?=my_number($res['2020']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
	<tr>
		<td>2021</td><td><?=my_number($res['2021']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
	<tr>
		<td>2022</td><td><?=my_number($res['2022']['sum']) ;?> <span class="szary">PLN</span></td>
	</tr>
</table>
<br>


							<? endforeach;?>
						<? endif;?>
				

				</div>
		</div>
	</div>
</div>