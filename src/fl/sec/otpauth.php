<?php
namespace fl\sec;

use fl\base\object;

/**
 * google otpauth
 *
 * @author guliuzhong
 *        
 */
class otpauth extends object
{

    private static $base32map = array(
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H', // 7
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P', // 15
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X', // 23
        'Y',
        'Z',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7', // 31
        '='
    ) // padding char
;

    private static $base32flippedMap = array(
        'A' => '0',
        'B' => '1',
        'C' => '2',
        'D' => '3',
        'E' => '4',
        'F' => '5',
        'G' => '6',
        'H' => '7',
        'I' => '8',
        'J' => '9',
        'K' => '10',
        'L' => '11',
        'M' => '12',
        'N' => '13',
        'O' => '14',
        'P' => '15',
        'Q' => '16',
        'R' => '17',
        'S' => '18',
        'T' => '19',
        'U' => '20',
        'V' => '21',
        'W' => '22',
        'X' => '23',
        'Y' => '24',
        'Z' => '25',
        '2' => '26',
        '3' => '27',
        '4' => '28',
        '5' => '29',
        '6' => '30',
        '7' => '31'
    );

    /**
     * 密匙请使用createSecret进行生成
     *
     * @var string
     */
    private $secretkey = null;

    /**
     * 谷歌两次认证功能
     *
     * @param string $secretkey
     *            密匙请使用createSecret生成的格式
     */
    public function __construct($secretkey = null)
    {
        if ($secretkey) {
            $this->setSecret($secretkey);
        }
    }

    /**
     * 设置密匙
     *
     * @param unknown $secretkey            
     */
    public function setSecret($secretkey)
    {
        $this->secretkey = $secretkey;
    }

    /**
     * 创建密匙
     * 本算法必须使用此方法创建的密匙
     *
     * 16 characters, randomly chosen from the allowed Base32 characters
     *
     * @return string
     */
    function createSecret()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // allowed characters in Base32
        $secret = '';
        for ($i = 0; $i < 16; $i ++) {
            $secret .= substr($chars, rand(0, strlen($chars) - 1), 1);
        }
        $this->setSecret($secret);
        return $secret;
    }

    /**
     * 返回当前有效的KEY
     *
     * @return number
     */
    public function getverifye()
    {
        $tm = floor(time() / 30);
        $secretkey = self::decode($this->secretkey);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $tm + 1);
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hm, - 1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack("N", $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        $value = $value % 1000000;
        return $value;
    }

    /**
     * 验证用户密匙
     *
     * @param string $thistry            
     * @return boolean number
     */
    function verify($thistry)
    {
        if ($thistry <= 0) {
            return false;
        }
        $thistry = intval($thistry);
        $firstcount = - 8;
        $lastcount = 8;
        $tm = floor(time() / 30);
        $secretkey = self::decode($this->secretkey);
        for ($i = $firstcount; $i <= $lastcount; $i ++) {
            // Pack time into binary string
            $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $tm + $i);
            // Hash it with users secret key
            $hm = hash_hmac('SHA1', $time, $secretkey, true);
            // Use last nipple of result as index/offset
            $offset = ord(substr($hm, - 1)) & 0x0F;
            // grab 4 bytes of the result
            $hashpart = substr($hm, $offset, 4);
            // Unpak binary value
            $value = unpack("N", $hashpart);
            $value = $value[1];
            // Only 32 bits
            $value = $value & 0x7FFFFFFF;
            $value = $value % 1000000;
            if ($value === $thistry) {
                return $tm + $i;
            }
        }
        return false;
    }

    /**
     * Use padding false when encoding for urls
     *
     * @return base32 encoded string
     * @author Bryan Ruiz
     *        
     */
    public static function encode($input, $padding = true)
    {
        if (empty($input))
            return "";
        $input = str_split($input);
        $binaryString = "";
        for ($i = 0; $i < count($input); $i ++) {
            $binaryString .= str_pad(base_convert(ord($input[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = "";
        $i = 0;
        while ($i < count($fiveBitBinaryArray)) {
            $base32 .= self::$base32map[base_convert(str_pad($fiveBitBinaryArray[$i], 5, '0'), 2, 10)];
            $i ++;
        }
        if ($padding && ($x = strlen($binaryString) % 40) != 0) {
            if ($x == 8)
                $base32 .= str_repeat(self::$base32map[32], 6);
            else 
                if ($x == 16)
                    $base32 .= str_repeat(self::$base32map[32], 4);
                else 
                    if ($x == 24)
                        $base32 .= str_repeat(self::$base32map[32], 3);
                    else 
                        if ($x == 32)
                            $base32 .= self::$base32map[32];
        }
        return $base32;
    }
    public static function decode($input)
    {
        if (empty($input))
            return;
        $paddingCharCount = substr_count($input, self::$base32map[32]);
        $allowedValues = array(
            6,
            4,
            3,
            1,
            0
        );
        if (! in_array($paddingCharCount, $allowedValues))
            return false;
        for ($i = 0; $i < 4; $i ++) {
            if ($paddingCharCount == $allowedValues[$i] && substr($input, - ($allowedValues[$i])) != str_repeat(self::$base32map[32], $allowedValues[$i]))
                return false;
        }
        $input = str_replace('=', '', $input);
        $input = str_split($input);
        $binaryString = "";
        for ($i = 0; $i < count($input); $i = $i + 8) {
            $x = "";
            if (! in_array($input[$i], self::$base32map))
                return false;
            for ($j = 0; $j < 8; $j ++) {
                $x .= str_pad(base_convert(@self::$base32flippedMap[@$input[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z ++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : "";
            }
        }
        return $binaryString;
    }
}