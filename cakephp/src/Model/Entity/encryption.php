<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class encryption extends Entity {

	protected $_key = [
		'mrhnKey' = true
	];

	public function encrypt($values){
		$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);  
		$iv = mcrypt_create_iv($size, MCRYPT_RAND);
		$cryptedString = mcrypt_encrypt(MCRYPT_RIJNDAEL_128,$this->$_key,$values,MCRYPT_MODE_CBC,$iv);
		return $cryptedString;
	}

}