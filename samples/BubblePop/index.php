<!DOCTYPE HTML>
<html>
<head>
</head>
<body>
  <div id="container"></div>
  <script src="js/jquery-1.7.2.min.js"></script>
  <script src="js/kinetic-v4.4.0.min.js"></script>
  <script src="js/BubblePop.js"></script>
  
<?php

// send them to a login page if they don't already have a user name
if(!array_key_exists('username', $_REQUEST))
{
    echo "<script type='text/javascript'>top.location.href = 'login.php';</script>";
    die();
}

// This blurb is just to create a relative path to the backend processing script.
// It is not specific to Splyt in any way
function getURLBase($current)
{
    $urlparts = parse_url($current);
    $scheme = isset($urlparts['scheme']) ? $urlparts['scheme'] : 'http';
    $host = isset($urlparts['host']) ? $urlparts['host'] : 'localhost';
    $path = str_replace('index.php', '', $urlparts['path']);
    if(strrpos($path, '/')!==strlen($path)-1) $path .= '/';
    return "$scheme://$host/$path";
};

$serviceRoot = getURLBase($_SERVER['REQUEST_URI']);
?>

  <script>
      serviceRoot = "<?php echo $serviceRoot; ?>";
      
      /**********************************/
      //initialize the game and run...
      BubblePop.init("container", serviceRoot, 640, 960);
  </script>
</body>
</html>