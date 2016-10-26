<?php

/**
* Генерация разных уникальных значений.
*/
class rMyUniqID
{
	
	/**
	 * Генерация уникальной строки содержащей буквы и цифры
	 * @param  integer $len    Длинна результирующей строки
	 * @param  string  $prefix Префикс
	 * @return string          Уникальный ID
	 */
	public static function alphaNum($len = 10, $prefix = '')
	{
		$hex = md5($prefix . uniqid("", true));

	    $pack = pack('H*', $hex);

	    $uid = base64_encode($pack);        // max 22 chars

	    $uid = preg_replace("~[^A-Za-z0-9]~", "", $uid);    // mixed case
	    

	    if ($len<4)
	        $len=4;
	    if ($len>128)
	        $len=128;                       // prevent silliness, can remove

	    while (strlen($uid)<$len)
	        $uid = $uid . self::alphaNum(22);     // append until length achieved

	    return substr($uid, 0, $len);
	}
}