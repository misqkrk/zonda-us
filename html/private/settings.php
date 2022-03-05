<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>SETTINGS</h1>
					<p> - <a href="/cli/import.php" target="_blank">IMPORT</a> </p>	
					<p> - <a href="/cli/generate.php" target="_blank">GENERATE</a> </p>	
					<p> - <a href="/cli/nbp.php?od=<?=date('Y-m-d', strtotime('-8 month'));?>&do=<?=date('Y-m-d');?>" target="_blank">NBP</a> </p>	


				</div>
		</div>
	</div>
</div>

