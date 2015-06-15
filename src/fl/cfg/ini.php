<?php
namespace fl\cfg;

/**
 *
 * @author guliuzhong
 *        
 */
class ini extends cfg
{

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::parse()
     */
    public function parse()
    {
        $this->data = parse_ini_string($this->cfgdata, true);
    }

    /*
     * (non-PHPdoc)
     * @see \fl\cfg\cfg::exportdata()
     */
    public function exportdata()
    {
        $content = "";
        foreach ($this->data as $key => $val) {
            if (is_array($val)) {
                $content .= "[{$key}]\n";
                foreach ($val as $key2 => $val2) {
                    $content .= "{$key2}={$val2}\n";
                }
            } else {
                $content .= "{$key}={$val}\n";
            }
        }
        return $content;
    }
}
