<html>
<head>
<title>Raport Podatkowy - Zonda (Bitbay)</title>
<link rel='stylesheet'  href='/src/style.css?<?= time();?>' type='text/css'/>
<link rel="icon" type="image/png" href="/src/img/fav.png" />
<meta property="og:image" content="https://zondaglobal.com/o/bb-theme/images/zonda_black.svg">
</head>
<body>

<div class="container">
	<div class="row">
			<div class="col col-12">
				<? if($_SESSION[PROJECT]['AUTH']['username'] && $_SESSION[PROJECT]['AUTH']['userID']): ?>
					<div class="box-1">
						<a class="button-1" href="/dashboard/user/home">POWRÓT DO PANELU</a>
					</div>
				<? else: ?>

					<div class="box-1">
						<a class="button-1" href="/dashboard/login">LOGOWANIE</a>
						<a class="button-1" href="/dashboard/register">REJESTRACJA</a>
					</div>
				<? endif; ?>

		</div>
	</div>
</div>