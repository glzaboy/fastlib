<?php
namespace fl\im;

use fl\base\object;

/**
 * 微信
 */
class wechat extends object
{

    /**
     * 缓存KEY
     *
     * @var string
     */
    const CACHE_KEY = "boss.wechat.access";

    /**
     * 微信token
     *
     * @var string
     */
    private $token = null;

    /**
     * 消息加解密密钥
     *
     * @var string
     */
    private $EncodingAESKey = null;

    /**
     * 微信access_token
     *
     * @var string
     */
    public $access_token = '';

    /**
     * 微信access_token有效时间 access_expires
     *
     * @var int
     */
    public $access_expires = 0;

    /**
     * 第三方用户唯一凭证
     *
     * @var string
     */
    private $appid = '';

    /**
     * 第三方用户唯一凭证密钥
     *
     * @var string
     */
    private $appsecret = '';

    /**
     * 微信代码网站
     *
     * @var string
     */
    public $sender = null;

    /**
     * 消息接收人自身
     *
     * @var string
     */
    public $recver = null;

    /**
     * 消息类型 text,image,link,event,location
     *
     * @var string
     */
    public $msgType = null;

    /**
     * 用户发来的信息文本
     *
     * @var string
     */
    public $text = null;

    /**
     * 收到用户发来的图片URL
     *
     * @var string
     */
    public $image = null;

    /**
     * 用户发来的链接地址
     * title 标题
     * description 描述
     * url　地址
     *
     * @var array
     */
    public $link = null;

    /**
     * 　用户发来的事件
     * event 事件类型，subscribe(订阅)、unsubscribe(取消订阅)、CLICK(自定义菜单点击事件)
     * eventkey 事件KEY值，与自定义菜单接口中KEY值对应
     *
     * @var array
     */
    public $event = null;

    /**
     * 用户发来的地理信息
     * x 地理位置纬度
     * y 地理位置经度
     * scale 地图缩放大小
     * label 地理位置信息
     *
     * @var array
     */
    public $location = null;

    /**
     * 用户语音信息
     * mediaid 地理位置纬度
     *
     * @var array
     */
    public $voice = null;

    /**
     * 企业应用ID
     * 
     * @var int
     */
    public $agentID = 0;

    /**
     * 用户主动发来的信息不用第三方凭证取access_token有次数限制需要缓存
     *
     * @param string $token
     *            Token
     * @param string $EncodingAESKey
     *            消息加解密密钥
     * @param string $appid
     *            第三方用户唯一凭证
     * @param string $appsecret
     *            第三方用户唯一凭证密钥
     */
    public function __construct($token, $EncodingAESKey = '', $appid = null, $appsecret = null)
    {
        $this->token = $token;
        if (strlen($EncodingAESKey) != 43) {
            die('AESKEY ERROR.');
        }
        $this->EncodingAESKey = $EncodingAESKey;
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        // list($token,$time)=explode(',', file_get_contents(self::CACHE_KEY));
        
        // $this->access_token = $token;
        // $this->access_expires = $time;
        
        // $s_cache = new zbj_lib_cache ( 'memcache' );
        // $cache = $s_cache->get ( self::CACHE_KEY );
        // $this->access_token = $cache ['access_token'];
        // $this->access_expires = $cache ['access_expires'];
    }

    /**
     * 发送文本信息
     *
     * @param string $text
     *            内容
     * @param string $recver
     *            接收人
     */
    public function senttext($text, $recver = null)
    {
        if ($recver) {
            if ($this->checkauth() == false) {
                return false;
            } else {
                $this->recver = $recver;
                $time = time();
                $msg = array();
                $msg['touser'] = $this->recver;
                $msg['msgtype'] = "text";
                $msg['agentid'] = $this->agentID;
                $msg['text']['content'] = $text;
                // var_dump(self::json_encode ( $msg ));
                $result = $this->http_post("https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=" . $this->access_token, self::json_encode($msg));
                // var_dump($result);
                if ($result) {
                    $json = json_decode($result, true);
                    if ($json) {
                        if ($json['errcode'] == 0) {
                            return true;
                        } else {
                            $this->setError(0, $json['errcode'] . $json['errmsg']);
                        }
                    } else {
                        $this->setError(0, 'http data error');
                        return false;
                    }
                } else {
                    $this->setError(0, 'http net error');
                    return false;
                }
            }
        } else {
            $time = time();
            $textTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[%s]]></MsgType>
	<Content><![CDATA[%s]]></Content>
	<FuncFlag>0</FuncFlag>
</xml>";
            $resultStr = sprintf($textTpl, $this->recver, $this->sender, $time, 'text', $text);
            return $this->encryptmessage($resultStr);
        }
    }

    /**
     * 发送音乐
     *
     * @param string $url
     *            音乐地址
     * @param string $name
     *            音乐内容
     * @param string $desc
     *            音乐描述
     * @param string $recver
     *            接收人
     */
    public function sentmusic($url, $name, $desc, $recver = null)
    {
        if ($recver) {
            if ($this->checkauth() == false) {
                return false;
            } else {
                $this->recver = $recver;
                $time = time();
                $msg = array();
                $msg['touser'] = $this->recver;
                $msg['msgtype'] = "music";
                $msg['music']['title'] = $name;
                $msg['music']['description'] = $desc;
                $msg['music']['musicurl'] = $url;
                $msg['music']['hqmusicurl'] = $url;
                $result = $this->http_post("https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=" . $this->access_token, self::json_encode($msg));
                if ($result) {
                    $json = json_decode($result, true);
                    if ($json) {
                        if ($json['errcode'] == 0) {
                            return true;
                        } else {
                            $this->setError(0, $json['errcode'] . $json['errmsg']);
                        }
                    } else {
                        $this->setError(0, 'http data error');
                        return false;
                    }
                } else {
                    $this->setError(0, 'http net error');
                    return false;
                }
            }
        } else {
            $time = time();
            $textTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[%s]]></MsgType>
	<Music>
		<Title><![CDATA[%s]]></Title>
		<Description><![CDATA[%s]]></Description>
		<MusicUrl><![CDATA[%s]]></MusicUrl>
		<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
	</Music>
	<FuncFlag>0</FuncFlag>
</xml>";
            $resultStr = sprintf($textTpl, $this->recver, $this->sender, $time, 'music', $name, $desc, $url, $url);
            return $this->encryptMessage($resultStr);
        }
    }

    /**
     * 发送文章
     * 每个文件都用数组的形式
     * 每篇包含title标题,description描述,picurl图片,url地址
     *
     * @param array $news
     *            文章
     * @param string $recver
     *            接收人
     */
    public function sentnews($news, $recver = null)
    {
        if ($recver) {
            if ($this->checkauth() == false) {
                return false;
            } else {
                $this->recver = $recver;
                $time = time();
                $msg = array();
                $msg['touser'] = $this->recver;
                $msg['msgtype'] = "news";
                $msg['agentid'] = $this->agentID;
                $msg['news'] = array();
                foreach ($news as $new) {
                    $temp = array();
                    $temp['title'] = $new['title'];
                    $temp['description'] = $new['description'];
                    if (isset($new['url']))
                        $temp['url'] = $new['url'];
                    if (isset($new['picurl']))
                        $temp['picurl'] = $new['picurl'];
                    
                    $msg['news']['articles'][] = $temp;
                }
                
                $result = $this->http_post("https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=" . $this->access_token, self::json_encode($msg));
                
                if ($result) {
                    $json = json_decode($result, true);
                    if ($json) {
                        if ($json['errcode'] == 0) {
                            return true;
                        } else {
                            $this->setError(0, $json['errcode'] . $json['errmsg']);
                            return false;
                        }
                    } else {
                        $this->setError(0, 'http data error');
                        return false;
                    }
                } else {
                    $this->setError(0, 'http net error');
                    return false;
                }
            }
        } else {
            $time = time();
            $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<ArticleCount>%d</ArticleCount>
<Articles>
	%s
</Articles>
</xml>";
            $itemTpl = "<item>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl>%s</PicUrl>
			<Url><![CDATA[%s]]></Url>
			</item>";
            $articles = "";
            if (is_array($news)) {
                foreach ($news as $new) {
                    $articles .= sprintf($itemTpl, $new['title'], $new['description'], $new['picurl'], $new['url']);
                }
            }
            $resultStr = sprintf($textTpl, $this->recver, $this->sender, $time, 'news', count($news), $articles);
            return $this->encryptMessage($resultStr);
        }
    }

    public function parser($postData = null)
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
            $postStr = $postData;
        } else {
            $postStr = file_get_contents("php://input");
        }
        if (empty($postStr)) {
            $this->setError(0, '没有微信服务器读取到数据无法进行解析');
            return false;
        }
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->sender = trim($postObj->ToUserName);
        
        $encrypt = trim($postObj->Encrypt);
        
        $pc = new Prpcrypt1($this->EncodingAESKey);
        
        // 提取密文
        if (! isset($_REQUEST['timestamp'])) {
            $sTimeStamp = time();
        } else {
            $sTimeStamp = $_REQUEST['timestamp'];
        }
        
        // $encrypt = $array[1];
        // $touser_name = $array[2];
        
        // 验证安全签名
        // $sha1 = new SHA1;
        $signature = $this->getSHA1($this->token, $sTimeStamp, $_REQUEST['nonce'], $encrypt);
        if ($signature != $_REQUEST['msg_signature']) {
            $this->setError(0, '签名不正确');
            return false;
        }
        $result = $pc->decrypt($encrypt, $this->appid);
        if (! $result) {
            $this->setError(0, '解码出错');
            return false;
        }
        $decObj = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->recver = trim($decObj->FromUserName);
        $this->sender = trim($decObj->ToUserName);
        $this->msgType = trim($decObj->MsgType);
        switch (strtolower($this->msgType)) {
            case 'text': // text
                $this->text = trim($decObj->Content);
                break;
            case 'image': // image
                $this->image = trim($decObj->PicUrl);
                break;
            case 'location': // location
                $this->location = array(
                    'x' => trim($decObj->Location_X),
                    'y' => trim($decObj->Location_y),
                    'scale' => trim($decObj->Scale),
                    'label' => trim($decObj->Label)
                );
                break;
            case 'link': // link
                $this->link = array(
                    'title' => trim($decObj->Title),
                    'description' => trim($decObj->Description),
                    'url' => trim($decObj->Url)
                );
                break;
            case 'event': // event
                $this->event = array(
                    'event' => trim($decObj->Event),
                    'eventkey' => trim($decObj->EventKey),
                    'ScanResult' => trim($decObj->ScanCodeInfo->ScanResult),
                    'AgentID' => trim($decObj->AgentID)
                );
                if ($decObj->AgentID) {
                    $this->agentID = intval($decObj->AgentID);
                }
                break;
            case 'voice': // event
                $this->voice = array(
                    'mediaid' => trim($decObj->MediaId)
                );
                break;
            default:
                $this->setError(0, '不支持的消息类型' . var_export($decObj, true));
                return false;
        }
        return true;
    }

    /**
     * 是否新用户订阅
     *
     * @return boolean
     */
    public function issubscribe()
    {
        return ($this->msgType == 'event' && $this->event['event'] == "subscribe");
    }

    /**
     * 是否新用户订阅
     *
     * @return boolean
     */
    public function isunsubscribe()
    {
        return ($this->msgType == 'event' && $this->event['event'] == "unsubscribe");
    }

    /**
     * 返回扫描推送
     * 
     * @return boolean
     */
    public function isScanPush()
    {
        return ($this->msgType == 'event' && $this->event['event'] == "scancode_push");
    }

    /**
     * 是否是扫描并能进行回复
     * 
     * @return boolean
     */
    public function isScanWait()
    {
        return ($this->msgType == 'event' && $this->event['event'] == "scancode_waitmsg");
    }

    /**
     * 取扫描返回值
     * 
     * @return boolean
     */
    public function getScanResult()
    {
        if ($this->isScanPush() or $this->isScanWait()) {
            return $this->event['ScanResult'];
        } else {
            // $this->setError(0,'您没有进行扫描');
            return '您没有进行扫描';
        }
    }

    /**
     * 和官方的公众平台进行对接
     */
    public function valid()
    {
        return self::checksignature();
    }

    /**
     * 进行签名认证
     *
     * @return boolean
     */
    private function checksignature()
    {
        $pc = new Prpcrypt1($this->EncodingAESKey);
        $signature = $this->getSHA1($this->token, $_REQUEST['timestamp'], $_REQUEST['nonce'], $_REQUEST['echostr']);
        if ($signature != $_REQUEST['msg_signature']) {
            return "msg_signature error! GET {$_REQUEST['msg_signature']} calc {$signature}";
        }
        $result = $pc->decrypt($_REQUEST['echostr'], $this->appid);
        return $result;
    }

    private function encryptMessage($message)
    {
        if ($this->EncodingAESKey) {
            $pc = new Prpcrypt1($this->EncodingAESKey);
            
            // 加密
            $encrypt = $pc->encrypt($message, $this->appid);
            
            $sTimeStamp = time();
            $sNonce = $sTimeStamp . rand(0, 100);
            // 生成安全签名
            $signature = $this->getSHA1($this->token, $sTimeStamp, $sNonce, $encrypt);
            $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
            return sprintf($format, $encrypt, $signature, $sTimeStamp, $sNonce);
        }
        return $message;
    }

    /**
     * 用SHA1算法生成安全签名
     *
     * @param string $token
     *            票据
     * @param string $timestamp
     *            时间戳
     * @param string $nonce
     *            随机字符串
     * @param string $encrypt
     *            密文消息
     */
    private function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
    {
        $array = array(
            $encrypt_msg,
            $token,
            $timestamp,
            $nonce
        );
        sort($array, SORT_STRING);
        $str = implode($array);
        return sha1($str);
    }

    /**
     * GET 请求
     *
     * @param string $url            
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 2);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * POST 请求
     *
     * @param string $url            
     * @param array $param            
     * @return string content
     */
    private function http_post($url, $param)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array(
            'Accept: application/json;charset=utf-8',
            'Content-Type: application/json;charset=utf-8'
        ));
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 5);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * 通用auth验证方法，更新access_token,access_expires
     */
    private function checkauth()
    {
        if ($this->access_token && $this->access_expires > time()) {
            return true;
        }
        $result = $this->http_get('https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . $this->appid . '&corpsecret=' . $this->appsecret);
        // var_dump($result);
        // exit;
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['access_token']) {
                    // $s_cache = new zbj_lib_cache ( 'memcache' );
                    // $s_cache->set ( self::CACHE_KEY, array (
                    // 'access_token' => $json ['access_token'],
                    // 'access_expires' => time () + intval ( $json ['expires_in'] )
                    // ) );
                    
                    $this->access_token = $json['access_token']; // token
                    $this->access_expires = time() + intval($json['expires_in']); // token有效期
                                                                                       // file_put_contents ( self::CACHE_KEY, $this->access_token . ',' . $this->access_expires );
                    return true;
                } else {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 微信api不支持中文转义的json结构
     *
     * @param array $arr            
     */
    static function json_encode($arr)
    {
        $parts = array();
        $is_list = false;
        // Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;
        if (($keys[0] === 0) && ($keys[$max_length] === $max_length)) { // See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i ++) { // See if each key correspondes to its position
                if ($i != $keys[$i]) { // A key fails at position check.
                    $is_list = false; // It is an associative array.
                    break;
                }
            }
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) { // Custom handling for arrays
                if ($is_list)
                    $parts[] = self::json_encode($value); /* :RECURSION: */
                else
                    $parts[] = '"' . $key . '":' . self::json_encode($value); /* :RECURSION: */
            } else {
                $str = '';
                if (! $is_list)
                    $str = '"' . $key . '":';
                    // Custom handling for multiple data types
                if (is_numeric($value) && $value < 2000000000)
                    $str .= $value; // Numbers
                elseif ($value === false)
                    $str .= 'false'; // The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes($value) . '"'; // All other things
                                                                   // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts[] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list)
            return '[' . $json . ']'; // Return numerical JSON
        return '{' . $json . '}'; // Return associative JSON
    }

    /**
     * 创建菜单
     *
     * @param array $menu            
     */
    public function createmenu($data)
    {
        if ($this->checkauth() === false) {
            return false;
        }
        $result = $this->http_post('https://qyapi.weixin.qq.com/cgi-bin/menu/create?agentid=' . $this->agentID . '&access_token=' . $this->access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] == 0) {
                    return true;
                } else {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
            } else {
                $this->setError(0, 'http error');
                return false;
            }
            return true;
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 取得菜单
     *
     * @param array $menu            
     */
    public function getmenu()
    {
        if ($this->checkauth() === false) {
            return false;
        }
        $result = $this->http_get('https://qyapi.weixin.qq.com/cgi-bin/menu/get?agentid=' . $this->agentID . '&access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] != 0) {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
                return $json;
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 删除菜单
     *
     * @return boolean
     */
    public function delmenu()
    {
        if ($this->checkauth() === false) {
            return false;
        }
        $result = $this->http_get('https://qyapi.weixin.qq.com/cgi-bin/menu/delete?agentid=' . $this->agentID . '&access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] != 0) {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
                return true;
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 取得用户信息
     *
     * @param string $openId
     *            OpenID
     * @return boolean mixed 用户信息
     */
    public function getuserinfo($openId = null)
    {
        if (! $openId) {
            $useropenip = $this->recver;
        } else {
            $useropenip = $openId;
        }
        if (empty($useropenip)) {
            $this->setError(0, "OpenId can't empty.");
            return false;
        }
        if ($this->checkauth() === false) {
            return false;
        }
        $result = $this->http_get('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->access_token . '&openid=' . $useropenip);
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] != 0) {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
                return $json;
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 获取用户列表
     *
     * @param string $next_openid
     *            上一次调用后取得的next_openid
     * @return boolean mixed
     */
    public function getusers($next_openid = null)
    {
        if ($this->checkauth() === false) {
            return false;
        }
        if ($next_openid) {
            $result = $this->http_get('https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->access_token . '&next_openid=' . $next_openid);
        } else {
            $result = $this->http_get('https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->access_token);
        }
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] != 0) {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
                return $json;
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * -----------二维码支持----------------------*
     */
    /**
     * 创建二维码
     *
     * @param int $scene_id
     *            场景ID，临时二维码时为32位非0整型，永久二维码时最大值为100000
     * @param number $expire
     *            二维码有效期0为永久有有效，其它为有效期不超过1800秒。
     * @return boolean string 失败或二维码ticket凭借ticket可以有效时间获取二维码
     */
    public function createqrcode($scene_id, $expire = 1800)
    {
        if (! $scene_id) {
            $this->setError(0, "sene is not empty.", 1);
            return false;
        }
        if ($this->checkauth() === false) {
            return false;
        }
        $urldata = array();
        $urldata['action_info']['scene']['scene_id'] = $expire;
        if ($expire) {
            $urldata['action_name'] = "QR_SCENE";
            $urldata['expire_seconds'] = $expire;
        } else {
            $urldata['action_name'] = "QR_LIMIT_SCENE";
        }
        $result = $this->http_post('https://qyapi.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->access_token, $urldata);
        if ($result) {
            $json = json_decode($result, true);
            if ($json) {
                if ($json['errcode'] != 0) {
                    $this->setError(0, $json['errcode'] . $json['errmsg']);
                    return false;
                }
                return $json['ticket'];
            } else {
                $this->setError(0, 'http error');
                return false;
            }
        }
        $this->setError(0, "http content error.");
        return false;
    }

    /**
     * 输出三维码的图片地址
     *
     * @param string $ticket
     *            获取的二维码ticket，凭借此ticket可以在有效时间内换取二维码
     * @return string
     */
    public function getqrcodeimg($ticket)
    {
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode(ticket);
    }

    /**
     * 取得用户扫描二维码的值
     *
     * @return boolean string 失败或二维码的值
     */
    public function getqrscan()
    {
        if ($this->msgType == "event") {
            switch ($this->event['event']) {
                case 'subscribe':
                    return strtr($this->event['eventkey'], array(
                        'qrscene_',
                        ''
                    ));
                    break;
                case 'SCAN':
                    return $this->event['eventkey'];
                    break;
                default:
                    $this->setError(0, 'Not support qrcode');
                    return false;
            }
        } else {
            $this->setError(0, 'Invalid scan');
            return false;
        }
    }
}

/**
 * PKCS7Encoder1 class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder1
{

    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     *
     * @param $text 需要进行填充补位操作的明文            
     * @return 补齐明文字符串
     */
    function encode($text)
    {
        $block_size = PKCS7Encoder1::$block_size;
        $text_length = strlen($text);
        // 计算需要填充的位数
        $amount_to_pad = PKCS7Encoder1::$block_size - ($text_length % PKCS7Encoder1::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder1::block_size;
        }
        // 获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index ++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     *
     * @param
     *            decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text)
    {
        $pad = ord(substr($text, - 1));
        if ($pad < 1 || $pad > PKCS7Encoder1::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
}

/**
 * Prpcrypt1 class
 *
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt1
{

    public $key;

    function Prpcrypt($k)
    {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     *
     * @param string $text
     *            需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text, $appid)
    {
        try {
            // 获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            // 网络字节序
            $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            // 使用自定义的填充方式对明文进行补位填充
            $pkc_encoder = new PKCS7Encoder1();
            $text = $pkc_encoder->encode($text);
            mcrypt_generic_init($module, $this->key, $iv);
            // 加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
            
            // print(base64_encode($encrypted));
            // 使用BASE64对加密后的字符串进行编码
            return base64_encode($encrypted);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 对密文进行解密
     *
     * @param string $encrypted
     *            需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted, $appid)
    {
        try {
            // 使用BASE64对需要解密的字符串进行解码
            $ciphertext_dec = base64_decode($encrypted);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            mcrypt_generic_init($module, $this->key, $iv);
            
            // 解密
            $decrypted = mdecrypt_generic($module, $ciphertext_dec);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            return null;
        }
        
        try {
            // 去除补位字符
            $pkc_encoder = new PKCS7Encoder1();
            $result = $pkc_encoder->decode($decrypted);
            // 去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16)
                return "";
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
        } catch (Exception $e) {
            return null;
        }
        if ($from_appid != $appid) {
            return null;
        } else {
            return $xml_content;
        }
    }
    /**
     * 随机生成16位字符串
     *
     * @return string 生成的字符串
     */
    function getRandomStr()
    {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i ++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}