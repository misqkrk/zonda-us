<?

class My {
	static function GetUUID($data){
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    } 

	static function is_order_active($in = null, $course = null){ //old: get_check_is_order_active
		if($in && in_array($course, $in)):
			return true;
		endif;
	}


}