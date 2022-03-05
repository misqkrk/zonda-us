<?
define('PROJECT', 'BB');
define('SQL_1', ['name' => 'bitbay', 'user' => 'root', 'pass' => 'qXfCkY3GZxjJ', 'host' => 'localhost']);
define('REDIS_AUTH', '1323');

class Auth{
	public $user;
	public $access;
	public $info;

	public function __construct() {
		$this->run = $this->run();
	}	

	public function logout(){
		unset($_SESSION[PROJECT]['AUTH']);
		unset($_SESSION); // test
		setcookie('user', '', time() - 3600);
		setcookie('pass', '', time() - 3600);


		$this->login_html();
		header("Location: https://".$_SERVER['SERVER_NAME'], true, 307);
		exit();
	}

	public function conn(){ // potem do zmiany to
		include_once '/var/www/bitbay/inc/class/DBPDO.php';
		
		$this->DB = new DBPDO;
		$this->redis = new Redis();
		$this->redis->connect('/var/run/redis/redis.sock');
		$this->redis->auth(REDIS_AUTH);
	}

	public function users($u = null){
		$this->conn();
		$res = $this->DB->fetchAll("SELECT * FROM `USER` WHERE `name` = '".$u."'", null, null, 30)[0];
		if($res):
			return $res;
		endif;

	}

	public function update_time($u = null){
		$this->conn();
		$res = $this->DB->execute("UPDATE `USER` SET `LastLogin` = NOW() WHERE `name` = '".$u."'");
	}

	public function is_logged(){

	}

	public function set_session($user){
		$_SESSION[PROJECT]['AUTH'] = [
			'user' => $user,
			'access' => $this->users($user)['access'],
		];
	}

	public function set_cookies($user){
		if($_POST['set_cookies']):
			setcookie("user", $user, time()+15552000); 
			setcookie("pass", $this->myHas($this->users($user)['pass'], 'encode'), time()+15552000);
		endif;

	}

	public function login_cookies(){
		if($_COOKIE['user'] && $_COOKIE['pass']):
			$user = $_COOKIE['user'];
			$pass = $this->myHas($_COOKIE['pass']);

			if( $pass == $this->users($user)['pass'] ):
				$this->set_session($user);
				$this->is_logged = true;
				return true;
			endif;

		endif;	
	}
	public function error_login(){
		error_log(date("Y-m-d H:i:s").' || IP: '.$_SERVER['REMOTE_ADDR'].' || User: '.$_POST['login'].' || Pass: '.$_POST['pass'].' || '.$_SERVER['HTTP_USER_AGENT'].PHP_EOL,3, '/var/www/log/login.log');
	}

	public function login_post(){
		if($_POST['auth']):
			$user = $_POST['login'];
			$pass = $_POST['pass'];

			if($user && $pass):
				if($this->users($user)):

					if( password_verify($pass, $this->users($user)['pass']) ):
						$this->set_session($user);
						$this->set_cookies($user);

						$this->info = 'OK';
						$this->is_logged = true;

					else:
						$this->info = 'ERROR - Invalid password.';
						$this->login_html();
						$this->error_login();

					endif;

				else:
					$this->info = 'ERROR: user not found!';
					$this->login_html();
					$this->error_login();

				endif;

			else:
				$this->info = 'Brak Loginu lub hasła';
				$this->login_html();
				$this->error_login();

			endif;

		else:
			$this->info = 'Zaloguje się';
			$this->login_html();
		endif;	
	}

	public function login_html(){
		echo '
			<html>
				<head>
					<title>BB Trade Bot - Login</title>
					<link rel=\'stylesheet\'  href=\'/src/css/login.css?v7\' type=\'text/css\'/>
					<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

				</head>
				<body>
					<div class="login-page">
					  <div class="form">
					    <form class="login-form" method="post">
					      <input name="login" type="text" placeholder="username"/>
					      <input name="pass" type="password" placeholder="password"/>
							<p class="c"><input type="checkbox" name="set_cookies" checked="" value="1">save me:)</p> 
					      <input type="hidden" name="auth" value="1" />
					      <button type="submit">login</button>
					      
					      <p class="message">'.$this->info.'</p>

					      <p class="message">BB Trade Bot</p>
					    </form>
					  </div>
					</div>
				</body>
			</html>

		';
		
	}

	public function run(){

		if($_SESSION[PROJECT]['AUTH']):
			$this->info = 'Zalogowany';
			$this->is_logged = true;
			$this->user = $_SESSION[PROJECT]['AUTH']['user'];
			$this->access = $_SESSION[PROJECT]['AUTH']['access'];

		elseif( $this->login_cookies() ):
			$this->is_logged = true;
			$this->user = $_SESSION[PROJECT]['AUTH']['user'];
			$this->access = $_SESSION[PROJECT]['AUTH']['access'];
			$this->update_time($this->user);
		else:
			$this->login_post();
			if($this->is_logged):
				$this->info = 'Zalogowany';
				$this->is_logged = true;
				$this->user = $_SESSION[PROJECT]['AUTH']['user'];
				$this->access = $_SESSION[PROJECT]['AUTH']['access'];
				$this->update_time($this->user);
			else:
				die();
			endif;
			
		endif;
	}

	public function myHas($stringData, $flag = null){
		$key = 'gdildhstabiwynba';
		$cipher = "AES-128-CBC";
	 	$iv = '8971936292748490';

		if($flag == 'encode'):
			return  urlencode(base64_encode(openssl_encrypt($stringData, $cipher, $key, $options=0, $iv)));
		else:
	  		return openssl_decrypt(base64_decode(urldecode($stringData)), $cipher, $key, $options=0, $iv);
	  endif;
	}



}
?>