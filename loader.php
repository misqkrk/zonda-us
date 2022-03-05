<?php


//// LOAD CLASS ////
spl_autoload_register(function ($class_name) {
    $dir_arr = [
        '/class',
        '/class/ws',
        '/class/static',
    ];
    
    foreach ($dir_arr as $dir):
        $filename = __DIR__.'/inc'.$dir.'/'.$class_name.'.php';
        if( file_exists($filename) ):
            include_once $filename;
        endif;
    endforeach;

});


//// END LOAD CLASS ////
require_once __DIR__.'/conf.php';

//// LOAD FUNCTION ////
$function_load_arr = [
	'function',

	'CourseConvert',


];
foreach ($function_load_arr as $key => $f_name):
	require_once __DIR__.'/inc/function/'.$f_name.'.php'; 
endforeach;
//// END LOAD FUNCTION ////

// dodać if jak session dev


    
$_ENV[PROJECT]['DB'] = $DB = Connect::DB();
$_ENV[PROJECT]['redis'] = $redis = Connect::redis();


$_ENV[PROJECT]['API'] = $API = Connect::API();

// define('GET_TRADING_CONFIG', get_sql_trading_config());
// $_ENV[PROJECT]['get_course'] = Exchange::GetCourse();

session_start();
$user = $_SESSION[PROJECT]['AUTH'];