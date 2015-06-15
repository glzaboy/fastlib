<?php
namespace fl\base;

use fl\base\object;

class thriftproxy extends object
{

    protected $class = null;

    protected $_host;

    function __construct(&$class, $apihost = "http://dev.com/api/")
    {
        $this->class = $class;
        $this->_host = $apihost;
    }

    function __call($name, $params)
    {
        return $this->request(1, array(), $this->class, $name, $params);
    }

    /**
     * request remote api service
     *
     * @param string $timeout            
     * @param string $headers            
     * @param string $uri            
     * @param string $clientClass            
     * @param string $clientMethod            
     * @param array $arguments            
     * @throws Exception
     * @return mixed
     */
    private function request($timeout = 0, array $headers = array(), $clientClass, $clientMethod, array $arguments)
    {
        if (! $this->_host) {
            $Constant = preg_replace("/[a-zA-Z0-9_]{1,}Client$/", "Constant", $clientClass);
            $this->_host = call_user_func_array(array(
                "$Constant",
                "get"
            ), array(
                "apihost"
            ));
        }
        preg_match_all("/([A-Za-z0-9]{1,})Client$/", $clientClass, $match);
        $urlinfo = parse_url($this->_host);
        $socket = new \Thrift\Transport\THttpClient($urlinfo['host'], isset($urlinfo['port']) ? $urlinfo['port'] : 80, $urlinfo['path'] . $match[1][0]);
        $transport = new \Thrift\Transport\TBufferedTransport($socket, 1024, 1024);
        // $protocol = new \Thrift\Protocol\TJSONProtocol($transport);
        $protocol = new \Thrift\Protocol\TBinaryProtocol($transport);
        $client = new $clientClass($protocol);
        // add request timeout
        if ($timeout > 0) {
            $socket->setTimeoutSecs($timeout);
        }
        // add request header
        if ($headers) {
            $socket->addHeaders($headers);
        }
        $transport->open();
        try {
            $result = call_user_func_array(array(
                $client,
                $clientMethod
            ), $arguments);
        } catch (\Exception $e) {
            print_r($e);
        }
        
        $transport->close();
        return $result;
    }
}

?>