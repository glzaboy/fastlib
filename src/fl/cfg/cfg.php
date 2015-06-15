<?php
namespace fl\cfg;

use fl\base\object;

/**
 *
 * @author guliuzhong
 *        
 */
abstract class cfg extends object implements Icfg
{

    /**
     * 文件原始内容
     *
     * @var unknown
     */
    protected $cfgfile = null;

    /**
     * 解析后的数据
     *
     * @var array
     */
    protected $data = null;

    /**
     * 配置文件
     *
     * @var string
     */
    protected $cfgdata = null;

    protected $supportsave = false;

    /**
     *
     * @var string
     */
    protected $diestr = '<?php die("HTTP 403");?>';

    function __construct($uri)
    {
        $urlinfo = parse_url($uri);
        if (! isset($urlinfo['scheme'])) {
            $this->cfgfile = FL_RUNDIR . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . $uri . '.php';
            $this->cfgdata = substr(@file_get_contents($this->cfgfile), strlen($this->diestr));
            $this->supportsave = true;
        } elseif (in_array($urlinfo['scheme'], array(
            'http',
            'https'
        ))) {
            $httpclient = new \fl\helpers\httpclient();
            $this->cfgdata = $httpclient->getData($uri);
        }
        $this->parse();
    }

    public function change($nameSpace, $key, $value)
    {
        if (empty($this->data[$nameSpace])) {
            $this->data[$nameSpace] = array();
        }
        $this->data[$nameSpace][$key] = $value;
    }

    public function get($nameSpace = null, $key = null)
    {
        if (! $nameSpace) {
            return $this->data;
        }
        if (! $key) {
            if (isset($this->data[$nameSpace])) {
                return $this->data[$nameSpace];
            } else {
                return null;
            }
        } else {
            if (isset($this->data[$nameSpace][$key])) {
                return $this->data[$nameSpace][$key];
            } else {
                return null;
            }
        }
    }

    public function save()
    {
        if (! $this->supportsave) {
            $this->setError('not support save cfg file');
            return false;
        }
        $cfgdata = $this->exportdata();
        if ($cfgdata === null) {
            $this->setError('not support this format cfg save');
            return false;
        } else {
            return file_put_contents($this->cfgfile, $this->diestr . $cfgdata);
        }
    }

    /**
     * 配置读取适配器
     *
     * @param string $uri            
     * @param string $format
     *            数据格式
     * @return \fl\cfg\icfg|boolean
     */
    public static function instance($uri, $format = 'json')
    {
        switch (strtolower($format)) {
            case 'php':
                return new \fl\cfg\php($uri);
                break;
            case 'ini':
                return new \fl\cfg\ini($uri);
                break;
            case 'json':
                return new \fl\cfg\json($uri);
                break;
            default:
                return false;
        }
    }
}