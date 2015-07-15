<?php
// early out if the userid arg is missing
if(!isset($_REQUEST['userid']))
{
    return json_encode(array('status' => 'invalidargs'));
}

// include fakememcache here, as a convenience
require_once dirname(__FILE__) . '/fakememcache.php';

// If XML_Serializer is not already in the include_path, dynamically add the
// folder that is bundled with this sample that includes a copy of XML_Serializer
// (along with its own dependencies).
if (!in_include_path('XML/Serializer.php'))
{
    set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pear');
}

// initialize split here, since all services will include this code
require_once dirname(__FILE__) . '/Splyt/Splyt.php';

// We must always initialize Splyt before use with either a user id, a device id, or both.  In
// the case of BubblePop, we use only a user id
$splyt = Splyt::init(
    'rsb-bubblepopphp-test',
    $_REQUEST['userid']
);

/**
 * Determines whether a file is found in the PHP include path.
 *
 * @param $file The file to find.
 *
 * Newer PHP versions could use stream_resolve_include_path(); this function
 * is provided in case we're running on an older version.
 */
function in_include_path($file)
{
    $paths = explode(PATH_SEPARATOR, get_include_path());
    $found = false;
    foreach($paths as $p)
    {
        $fullname = $p.DIRECTORY_SEPARATOR.$file;
        if(is_file($fullname)) {
            $found = $fullname;
            break;
        }
    }
    return $found;
}

?>