<?
include_once '../loader.php';
require_once '../inc/function_us/generating.php';

$request = $DB->fetchAll("SELECT * FROM `request` WHERE `t` = 3 AND `f` = 3 AND `g` != 3  AND `allow` = 1 ORDER BY `year` DESC, `ID` ASC LIMIT 1")[0];

if($request):
	echo '<meta http-equiv="refresh" content="1;">';
	report_generate($request['userID'], $request['year']);
else:
	echo 'Brak raportów do generowania';
endif;

