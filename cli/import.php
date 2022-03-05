<?
require_once '../loader.php';
require_once '../inc/function_us/import_transactions.php';
require_once '../inc/function_us/import_fee.php';
require_once '../inc/function_us/import_add.php';

$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();


if($_GET['delredis']):
	$redis->del('import');
	$redis->del('import_fee');
endif;

echo '<pre>';
$redis_import = unserialize($redis->get('import'));
$redis_import_fee = unserialize($redis->get('import_fee'));

if($redis_import['userID']):
	echo 'TRANSACTIONS'.PHP_EOL;
	echo '<hr>'.PHP_EOL;
	echo '<meta http-equiv="refresh" content="0;">';

	import_year();
elseif($redis_import_fee['userID']):
	echo 'FEE'.PHP_EOL;
	echo '<hr>'.PHP_EOL;
	import_fee();
	echo '<meta http-equiv="refresh" content="0;">';


else:
	echo 'ADD'.PHP_EOL;
	echo '<hr>'.PHP_EOL;
	import_add();

endif;


