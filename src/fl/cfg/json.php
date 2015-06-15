<?php
namespace fl\cfg;

/**
 *
 * @author guliuzhong
 *        
 */
class json extends cfg
{

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::parse()
     */
    public function parse()
    {
        $this->data = json_decode($this->cfgdata, true);
        $this->cfgdata = null;
    }

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::exportdata()
     */
    public function exportdata()
    {
        return json_encode($this->data);
    }
}
