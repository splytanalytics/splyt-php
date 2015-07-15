<?php
require_once 'fakememcache.php';

class Splyt_ext
{
    private $memcache;
    
    public function __construct()
    {
        $this->memcache = new fakememcache();
    }
    
    public function store($key, $value)
    {
        $this->memcache->set('Splyt_'.$key, $value);
    }
    
    public function retrieve($key)
    {
        return $this->memcache->get('Splyt_'.$key);
    }
    
    public function clear($key)
    {
        $this->memcache->delete('Splyt_'.$key);
    }
}
?>