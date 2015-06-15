<?php
namespace fl\cfg;

/**
 *
 * @author guliuzhong
 *        
 */
class php extends cfg
{

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::parse()
     */
    public function parse()
    {
        $this->data = unserialize($this->cfgdata);
        $this->cfgdata = null;
    }

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::exportdata()
     */
    public function exportdata()
    {
        return serialize($this->data);
    }
}
