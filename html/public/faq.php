

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>FAQ</h1>
					<div class="faq">
						<h3>Czy program jest bezpłatny?</h3>
						<span>Tak. Program obecnie jest w 100% darmowy</span>

						<h3>Czy pracuję w zonda i narzędzie jest bezpieczne?</h3>
						<span>Nie, nie pracuję w zonda, jestem jak inni jedynie użytkownikiem giełdy. Program jest w pełni bezpieczny, zakładając konto u nas nie ujawiasz swojego adresu email, profil zakładany w żaden sposób nie jest powiązany z Twoim kontem na zondaglobal.com. Jeżeli masz jakieś obawy co do programu, upewnij się na oficjalnej grupie telegram giełdy zonda, zapytaj support.</span>

						<h3>Czy potrzeba dodać klucze api?</h3>
						<span>Tak. To jedyny sposób aby program pobrał historyczne transakcję z Twojego konta.</span>

						<h3>Czy raporty generowane są automatycznie?</h3>
						<span>Po wysłaniu zapytania o raport - generowany jest on automatycznie dopiero po zaakceptowaniu przez administrację - zwykle jest to do 48h. O wygenerowaniu nie informujemy w żaden sposób (nie zbieramy żadnych informacji o użytkownikach w tym również adresu email).</span>
						
						<h3>Czy da się zapisać w PDF?</h3>
						<span>Owszem. W przeglądarce CHROME daj drukuj - następnie wybierz "drukuj do PDF"</span>

						<h3>Czy to bezpieczne podawać klucze api?</h3>
						<span>Tak o ile podczas generowania klucza api na zondaglobal.com (bitbay.net) zaznaczysz opcję tylko "historia". Taki klucz nie będzie miał uprawnień do niczego więcej jak wglądu do Twojej historii transakcji.</span>

						<h3>Jakie giełdy obsługuje program?</h3>
						<span>Obecnie tylko zondaglobal.com (bitbay.net), może w przyszłości będzie obsługiwał inne.</span>

						<h3>Czy zajmujemy się doradztwem skarbowym lub prawnym?</h3>
						<span>Nie, nie zajmujemy się. Jeżeli potrzebujesz pomoc zgłoś się do swojego księgowego.</span>

						<h3>Czemu nie wszystkie transakcje są pobrane?</h3>
						<span>Wedle Polskiego prawa rozliczamy transakcję "crypto-fiat", dlatego transakcje crypto-crypto nie są uwzględniane.</span>
						
						<h3>Czy prowizja jest uznawana przez program jako koszt?</h3>
						<span>Tak, prowizja tylko z zbycia kryptowalut jest dodawana jako koszt. Nabycie kryptowalut nie można zaliczyc do kosztu.</span>

						<h3>Czy program uwzględnia transakcje w walucie innej jak PLN, np w USD czy EUR?</h3>
						<span>Tak, program zlicza wszystkie transakcje w walucie "fiat" tj. USD, EUR, GBP oraz PLN. Wszystkie transakcje inne jak PLN przeliczane są na PLN po średnim kursie NBP z dnia poprzedniego.</span>

						<h3>Jak dodać klucz API z zondaglobal.com (bitbay.net)?</h3>
						<span>Zaloguj się na swoje konto (https://zondaglobal.com). Przejdź do "Ustawienia API" Następnie "+ dodaj nowy klucz" zaznacz tylko i wyłącznie "Historia", kliknij "Utwórz". Otrzymane klucze publiczny i prywatny dodaj na tej stronie w sekcji "ustawienia".</span>
						<p>Instrukcja obrazkowa:</p>
						<p><img src="/src/img/instrukcja_zonda/1.png"/></p>
						<p><img src="/src/img/instrukcja_zonda/2.png"/></p>
						<p><img src="/src/img/instrukcja_zonda/3.png"/></p>


					</div>

				</div>
		</div>
	</div>
</div>