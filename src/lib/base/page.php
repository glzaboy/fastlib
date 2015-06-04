<?php
namespace fl\base;

/**
 *
 * @author guliuzhong
 *        
 */
class page extends object
{

    /**
     * 是否处于debug
     *
     * @var bool
     */
    private $debug = false;

    /**
     * executes & displays the template results
     *
     * 是否压缩传输默认是开启的，最终还是取决于浏览器和服务器
     *
     * @param $template string
     *            模板文件名
     * @param $return bool
     *            是否返回到php变量
     */
    public function render($tplvar = array(), $template = null)
    {
        header('content-type:text/html;charset=utf-8');
        if ($template == null) {
            $template = FL_PAGE_MOD . '/' . FL_PAGE_ACT;
        }
        $template .= '.html';
        $smarty = new \Smarty();
        $smarty->allow_php_templates = true;
        $smarty->left_delimiter = '{';
        $smarty->right_delimiter = '}';
        // $this->addPluginsDir ( __FASTLIB__ . FastLoad::getplatform ( true ) .
        // '/smarty_plug' ); // 加入PLUG目录
        $smarty->setTemplateDir(FL_PAGE_DIR . '/templates');
        $smarty->setCompileDir(FL_TMP . '/templates_c/' . FL_PAGE_DIR . '/compile');
        $smarty->assign($tplvar);
        // $this->registerFilter ( 'pre', create_function ( '$tplString',
        // 'return strtr($tplString,array(\'<!--{\'=>\'{\',\'}-->\'=>\'}\'));' )
        // );
        // $this->registerFilter ( 'pre', create_function ( '$tplString',
        // 'return
        // preg_replace_callback("/\{{$delimiter}[^\{$delimiter}]+{$delimiter}\}/",
        // array(fasttpl,gettextCompiler), $tplString);' ) );
        if (FL_DEBUG) {
            $smarty->compile_check = true;
            $smarty->caching = false;
        } else {
            $smarty->compile_check = false;
            $smarty->caching = false;
            $smarty->merge_compiled_includes = true;
        }
        $smarty->display($template);
    }

    /**
     * 自动跳转
     *
     * @param string $url
     *            跳转URL地址
     * @param number $http_response_code
     *            HTTP状态码
     * @param number $time
     *            自动跳转时间
     */
    static public function redirect($url, $http_response_code = 302, $time = 0)
    {
        if ($time) {
            header('refresh: ' . $time . '; url=' . $url);
        } else {
            header('Location: ' . $url);
        }
    }

    /**
     * 初始化thrift 对象
     *
     * @param unknown $class            
     */
    static function getthrift(&$class, $apihost = "")
    {
        $class = get_class($class);
        $class = new \fl\base\thriftproxy($class, $apihost);
    }
}