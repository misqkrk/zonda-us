<?php
//require '/var/www/bitbay/loader.php';


class Table{
	protected $arr;
	protected $td;
	protected $tr;
	protected $i;
	protected $char;


	public function __construct() {
		$this->sign = ' ';
		$this->i = 0;
		//$this->char = $this->char($char);
    }

	public function char($char = 10){
		$this->char = $char; 
	}

	public function L($val = '--------', $char = null){
		$char = $char ?? $this->char;
		$this->pad($val, $char, STR_PAD_LEFT); 
	}

	public function R($val = '--------', $char = null){
		$char = $char ?? $this->char;
		$this->pad($val, $char, STR_PAD_RIGHT); 
	}

	public function pad($val, $char, $align){
		$this->arr[$this->i][] = str_pad($val, $char, $this->sign, $align);
	}

	public function tr(){
		$this->i = $this->i + 1;
	}

	public function print($p = false){
		foreach ($this->arr as $k => $v):
			$this->td[] = implode('|', $v).'|';
		endforeach;

		foreach ($this->td as $k => $v):
			$this->out .= $v.PHP_EOL;
		endforeach;
			
		for ($i=0; $i < strlen($this->td[0]); $i++) { 
			$end_table .= '_';
		}
			
		$this->out .= $end_table.PHP_EOL;
		
		if($p):
			print_r($this->out);
		endif;

		return $this->out;
	}
	
}


/*

$test = new TABLE;
$test->char(9); 

$test->R('NUMER');
$test->L('AMOUNT');
$test->L('RATE');
$test->R('PRICE');
$test->L('SUM');

$test->tr();
$test->R('1');
$test->L('162');
$test->L('762.76');
$test->R('72625.09');
$test->L('72625.09');

$test->tr();
$test->R('2');
$test->L('362');
$test->L('462.76');
$test->R('52625.09');
$test->L('62625.09');


$test->print(1);
*/