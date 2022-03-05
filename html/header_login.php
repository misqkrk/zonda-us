<html>
<head>
<title>Raport Podatkowy - Zonda (Bitbay)</title>
<link rel='stylesheet'  href='/src/style.css?<?= time();?>' type='text/css'/>
<link rel="icon" type="image/png" href="/src/img/fav.png" />

</head>
<body>

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<a class="button-1" href="/dashboard/user/home">DASHBOARD</a>
					<a class="button-1" href="/dashboard/user/report-request">PROŚBA O RAPORT</a>
					<a class="button-1" href="/dashboard/user/report-list">RAPORTY PODATKOWE</a>
					<a class="button-1" href="/dashboard/user/settings">USTAWIENIA</a>
					<a class="button-1" href="/dashboard/user/binance">BINANCE</a>



					<div class="menu_button_r">
						<a class="button-1" href="/dashboard/logout">WYLOGUJ SIĘ</a>
						<? if(acces(5)):?>
							<a class="button-1" href="/dashboard/private/home">ADM</a>
						<? endif;?>

					</div>


				</div>
		</div>
	</div>
</div>
