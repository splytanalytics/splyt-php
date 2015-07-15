<?php

/**
 * A simple class to emulate memcache using cookies.
 */
class fakememcache
{
    // in memory cache needed since setCookie doesn't effect $_COOKIE until the next call
    private static $_cache = array();
    
    
    /**
     * Read a key from the cache and return it.  Returns false if the key is not found.
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        // if we already have it in memory, use it
        if(array_key_exists($key, self::$_cache))
        {
            return json_decode(self::$_cache[$key], true);
        }
        
        // otherwise, try to pull it from a cookie
        $value = false;
        if(array_key_exists($key, $_COOKIE))
        {
            $value = $_COOKIE[$key];
            $value = json_decode($value, true);
        }
        
        // save it back to memory, since cookie value may be unreliable until next call
        self::$_cache[$key] = $value;
        
        return self::$_cache[$key];
    }
    
    /**
     * Write a key to the cache with the given value
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $value = json_encode($value);
        
        // save it to memory for now, and a cookie for later
        self::$_cache[$key] = $value;
        setCookie($key, $value, 0, '/');
    }
    
    public function delete($key)
    {
        unset(self::$_cache[$key]);
        setCookie($key);
    }
}
?>