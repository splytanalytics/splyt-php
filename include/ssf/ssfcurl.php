<?php
/* Simple wrapper around curl for communicating with SSF servers.  Supports
 * synchronous and async calls, but async calls have some 'gotchas' when trying
 * to execute more than one per frame
 */
class SSFCurl
{
    // We keep our socket around between async calls because opening a
    // connection (especially SSL) can be really slow
    // Note, this is ok because we only support one destination
    // for the moment anyways.
    private $async;

    public function __construct($async = false)
    {
        $this->async = $async;
    }

    /*
     * Post to the supplied URL with the given post data.
     *
     * @param string $url the URL to contact, including any rest parms and query parms
     * @param array $postData indexed array of post parameters
     * @param number $retry (optional) Number of retries.  A minimum of 1 retry will always be used for async calls.
     */
    public function post($url, $postData, $retry=0)
    {
        if($this->async)
        {
            // async call requires at least 1 retry because the socket can get reset
            return $this->postAsync($url, $postData, max(1, $retry));
        }
        else
        {
            return $this->postSync($url, $postData, $retry);
        }
    }

    /*
     * Do the post asynchronously
     */
    private function postAsync($url, $postData, $retry)
    {
        //Get the parts for the url
        $parts=parse_url($url);

        // Determine the host name and the port (based on config and rules about https)
        $hostToUse = $parts['host'];
        $defaultPort = 80;

        if ( $parts['scheme'] == 'https' )
        {
            $defaultPort = 443;
            $hostToUse = "ssl://".$parts['host'];
        }

        $portToUse = isset($parts['port']) ? $parts['port'] : $defaultPort;

        $errstr = "";
        $errno = 0;

        // Check the SSL cert, and while we are checking it, make sure the cert matches the server
        // See http://phpsecurity.readthedocs.org/en/latest/Transport-Layer-Security-%28HTTPS-SSL-and-TLS%29.html
        $contextOptions = array(
            'ssl' => array(
                'verify_peer'   => TRUE,
                'cafile'        => __DIR__ . '/cacert.pem',
                'verify_depth'  => 5,
                'CN_match'      => $parts['host']
            )
        );

        $sslContext = stream_context_create($contextOptions);

        $socket = stream_socket_client($hostToUse.":".$portToUse, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT, $sslContext);

        $urlandquery = $parts['path'] ;
        if( isset( $parts['query'] ) )
        {
            $urlandquery .= '?' . $parts['query'];
        }

        if(!isset($postData)) $postData = '';

        $out = "POST ".$urlandquery." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($postData)."\r\n";
        $out.= "ssf-use-positional-post-params: true\r\n";
        $out.= "ssf-contents-not-url-encoded: true\r\n";
        $out.= "Connection: keep-alive\r\n\r\n";
        $out.= $postData;

        // Actually try and write to the socket, but don't issue notices about expected occasional
        // failures.  That is why we implement retry.
        if($retry > 0)
        {
            $oldSettings = error_reporting(E_ALL ^ E_NOTICE);
        }

        $ret = fwrite($socket, $out);

        if($retry > 0)
        {
            error_reporting($oldSettings);
        }

        // NOTE:  fwrite can return 0 under certain error conditions, in which case, we'll close the socket and perform a retry if one is requested
        if ((false === $ret) || (0 === $ret))
        {
            // It may happen that our cached connection was available as far
            // as we know, but the server (even though we told it to please
            // keep the connection alive) could have shut it down on the other
            // end.  So, if we fail to write the bytes, let's try to reconnect
            if (is_resource($socket))
            {
                fclose($socket);
            }

            if($retry > 0)
            {
                return $this->postAsync($url, $postData, $retry-1);
            }
            else
            {
                return Error::ConnectionFailed("Non-retriable attempt");
            }
        }

        if (is_resource($socket))
        {
            fclose($socket);
        }

        return Error::Success();
    }

    /*
     * Do the post synchronously
     */
    private function postSync($url, $postData, $retry)
    {
        $ret = $this->curl_post($url, $postData);

        if($ret->isSuccess())
        {
            $result = $ret['contents'];
            $retArray = json_decode($result, true);
            if(false === $retArray)
            {
                return Error::ConnectionFailed("Could not deserialize output for $url.  Output = $result");
            }

            $ret = new SSFType();
            $ret->fromArray($retArray);
            return $ret;
        }

        if($retry > 0)
        {
            return $this->postSync($url, $postData, $retry-1);
        }
        return $ret;
    }

    /*
     * The actual curl call for the synchronous post
     */
    private function curl_post($url, $data, $optional=false)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_HTTPHEADER, array('ssf-use-positional-post-params: true', 'ssf-contents-not-url-encoded: true'));

        $contents = curl_exec($c);

        if ($contents)
        {
            $info = curl_getinfo($c);
            curl_close($c);

            if (empty($info['http_code']))
            {
                return Error::ConnectionFailed("No http code for url $url body was $contents");
            }
            else if ($info['http_code'] != 200)
            {
                // errors are ok for optional posts, user may have denied permission
                // however, if it is an optional post and the error is not permission
                // denied, report an error
                if (!$optional || ($info['http_code'] != 403))
                {
                    return Error::ConnectionFailed("http_code != 200 for url $url. body was $contents");
                }
                else
                {
                    return Error::Success();
                }
            }

            return SSFType::create(array('contents' => $contents));
        }

        curl_setopt($c, CURLINFO_HEADER_OUT, TRUE);
        $contents = curl_getinfo($c);
        $data = print_r($contents, true);
        curl_close($c);

        return Error::ConnectionFailed("Could not open url $url info is $data");
    }
}
?>