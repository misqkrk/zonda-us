<?
require_once '../loader.php';
require_once '../inc/function_us/import_transactions.php';
require_once '../inc/function_us/import_fee.php';
require_once '../inc/function_us/import_add.php';

$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

	$year =  '2021';
	$year_plus = $p['year'] + 1;
	$API = new API(['public' => '2357b6b4-55ff-4cd6-9322-3b06dc0dc048', 'private' => 'fac24e41-7eaa-405f-bd35-de882c2c41a0', 'host' => 'api.zonda.exchange']);
	$res = $API->getTransactionsHistory($cursor, $limit, $year.'-01-01', $year_plus.'-01-01');



echo '<pre>';
print_r($res);