<?

class Colors {
	static function UpDown($in){
		if($in > 0):
			$return = 'zielony';
		elseif($in<0):
			$return = 'czerwony';
		else:
			$return = 'pomaranczowy';
		endif;

	return $return;
	}

	static function PercentHtml($in){
		if($in > 0):
			$return = '<span class="zielony">'.$in.' %</span>';
		elseif($in<0):
			$return = '<span class="czerwony">'.$in.' %</span>';
		else:
			$return = '<span class="pomaranczowy">'.$in.' %</span>';
		endif;

	return $return;
	}

	static function UpDownInverse($in){
		if($in < 0):
			$return = 'zielony';
		elseif($in>0):
			$return = 'czerwony';
		else:
			$return = 'pomaranczowy';
		endif;

	return $return;
	}

	static function CodeStatus($in){
		if(($in==10)||($in==12)):
			$return = 'zielony';
		elseif( ($in==13) || ($in==11) || ($in==0)|| ($in==20) )	:
			$return = 'pomaranczowy';
		else:	
			$return = 'czerwony';
		endif;

	return $return;
	}





// te nizej są zle ! wyszukac w stronie i zmienic na te wyzej
	static function ColorsUpDown($in){
		if($in > 0):
			$return = 'zielony';
		elseif($in<0):
			$return = 'czerwony';
		else:
			$return = 'pomaranczowy';
		endif;

	return $return;
	}



	static function ColorsUpDownInverse($in){
		if($in < 0):
			$return = 'zielony';
		elseif($in>0):
			$return = 'czerwony';
		else:
			$return = 'pomaranczowy';
		endif;

	return $return;
	}

	static function ColorsCodeStatus($in){
		if(($in==10)||($in==12)):
			$return = 'zielony';
		elseif( ($in==13) || ($in==11) || ($in==0)|| ($in==20) )	:
			$return = 'pomaranczowy';
		else:	
			$return = 'czerwony';
		endif;

	return $return;
	}

	static function colors_percent_html($in, $noZero = false){
		if($in > 0):
			$return = '<span class="zielony">'.$in.' %</span>';
		elseif($in<0):
			$return = '<span class="czerwony">'.$in.' %</span>';
		else:
			$return = '<span class="pomaranczowy">'.$in.' %</span>';
		endif;

		if($noZero && ($in == 0)):
			return null;
		else:
			return $return;
		endif;
	}

	static function colors_percent_inverse_html($in, $noZero = false){
		if($in > 0):
			$return = '<span class="czerwony">'.$in.' %</span>';
		elseif($in<0):
			$return = '<span class="zielony">'.$in.' %</span>';
		else:
			$return = '<span class="pomaranczowy">'.$in.' %</span>';
		endif;

		if($noZero && ($in == 0)):
			return null;
		else:
			return $return;
		endif;
	}	
}