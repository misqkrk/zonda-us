<?
    //header('Location: http://zonda.michalw.pl/przerwa.html');
require_once 'loader.php';





$site = $_GET['site'];
                          
//print_r($_GET);

switch($_GET['switch']){

	default; 
		include_once 'html/header.php';

	  include_once 'html/public/home.php';
	break;

	case 'public';


		if($_POST['username'] && $_POST['pass']): 
			$res = $DB->fetchAll("SELECT * FROM `username` WHERE `username` = '".$_POST['username']."' ")[0];
			if( password_verify($_POST['pass'], $res['userpass']) ):
				$status = 'Zalogowano'.PHP_EOL;
				$_SESSION[PROJECT]['AUTH'] = [
					'username' => $res['username'],
					'userID' => $res['ID'],
					'acces' => $res['acces'],
				];
				header('Location: /dashboard/user/home');
			else:
				$status = 'Błedne dane logowania.'.PHP_EOL;
			endif;
		elseif($_GET['site'] == 'logout'):
			login_logout();
		endif;

		include_once 'html/header.php';

		if ( is_file('html/public/'.$site.'.php') ):
	  		include_once 'html/public/'.$site.'.php';
	  	else:
	  		echo html_error_html('Brak pliku: html/public/'.$site.'.php' );
		endif;
	break;

	case 'user';
		login_acces(1);

		include_once 'html/header_login.php';

		if ( is_file('html/user/'.$site.'.php') ):
	  		include_once 'html/user/'.$site.'.php';
	  	else:
	  		echo html_error_html('Brak pliku: html/user/'.$site.'.php' );
		endif;
	break;

	case 'private';
		login_acces(5);
		include_once 'html/header_private.php';

		if ( is_file('html/private/'.$site.'.php') ):
	  		include_once 'html/private/'.$site.'.php';
	  	else:
	  		echo html_error_html('Brak pliku: html/private/'.$site.'.php');
		endif;

	break;
}

include_once 'html/footer.php';
