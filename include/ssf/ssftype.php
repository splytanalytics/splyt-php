<?php

@include_once ('XML/Serializer.php');

if (false === class_exists('SSFType'))
{
    class SSFType implements ArrayAccess, Iterator, Countable
    {
        private $error;
        private $description;
        private $data;
        private $bLogIfError;

        static public function SUCCEEDED( $type )
        {
            return $type->isSuccess() ;
        }
        static public function SUCCEEDED_WITH_DATA( $type )
        {
            if( $type->isSuccess() )
            {
                $data = $type->getData() ;
                if( !is_null( $data ) )
                {
                    return true;
                }
            }
            return false;
        }
        static public function FAILED( $type )
        {
            return !$type->isSuccess();
        }

        public function __construct ()
        {
        	$this->bLogIfError = TRUE;
        }

        public function getErrorString ()
        {
            return $this->description;
        }

        public function getError ()
        {
            return $this->error;
        }

        public function setError ($error = -1, $desc = "", $bLogIfError = TRUE)
        {
            $this->error = $error;
            $this->description = $desc;
            $this->bLogIfError = $bLogIfError;
        }

        public function setDescription ($desc)
        {
            $this->description = $desc;
        }

        public function setData ($data)
        {
            $this->error = 0;
            $this->description = "";
            $this->data = $data;
        }

        public function isError ()
        {
            if ($this->error < 0)
            {
                return true;
            }
            return false;
        }

        public function isSuccess ()
        {
            if ($this->error >= 0)
            {
                return true;
            }
            return false;
        }

        public function getData ($call = null)
        {
            // everything EXCEPT interface return values should go here
            if(null == $call)
            {
                return $this->data;
            }

            // if a nested call's data is requested, validate the structure before blindly serving up the data
            $callOutput = $this[$call];
            if( !array_key_exists('error', $callOutput) ||
                !array_key_exists('description', $callOutput) ||
                !array_key_exists('data', $callOutput))
            {
                //SSFLog::warning("Cannot get SSFType for subcall");
                return null;
            }

            return $callOutput['data'];
        }
        public function logIfError()
        {
        	return $this->bLogIfError;
        }

        public static function createWithError ($error = -1, $desc = "", $bLogIfError = TRUE)
        {
            $ret = new SSFType();
            $ret->setError($error, $desc, $bLogIfError);
            return $ret;
        }

        public static function create ($data)
        {
            $ret = new SSFType();
            $ret->setData($data);
            return $ret;
        }

        public static function createEmpty ()
        {
            $ret = new SSFType();
            $ret->setError(0, 'success');
            return $ret;
        }

        public function toArray ()
        {
            return array('error' => $this->error, 'description' => $this->description, 'data' => $this->data);
        }

        public function fromArray (array $in)
        {
        	$this->error = isset($in['error']) ? $in['error'] : $in['errorCode'];
        	$this->description = $in['description'];
        	$this->data = $in['data'];
        }

        public function serialize ( $output = 'xml')
        {
            if( $output == 'xml' )
            {
                return $this->serializeXml();
            }
            else if( $output == 'json' )
            {
                return $this->serializeJson();
            }
            else if( $output == 'php' )
            {
                return $this->serializePhp();
            }


            return '';
        }

        public function serializeXml()
        {
            $data = $this->toArray();
            $options = array("typeHints" => true, 'defaultTagName' => 'ssftype',
            'mode' => 'simplexml');
            $serializer = new XML_Serializer($options);
            $ret = $serializer->serialize($data);
            if ($ret == true)
            {
                return $serializer->getSerializedData();
            }
            return "";
        }
        public function serializeJson()
        {
            $data = $this->toArray();
            return json_encode( $data ) ;
        }
        public function serializePhp()
        {
            $data = $this->toArray();
            return serialize( $data );
        }

        /* BEGIN arrayaccess interface implementation */

        /* (non-PHPdoc)
         * @see ArrayAccess::offsetExists()
         */
        public function offsetExists($offset)
        {
            if(isset($this->data) && is_array($this->data) && array_key_exists($offset, $this->data))
            {
                return true;
            }
            return false;
        }

        /* (non-PHPdoc)
         * @see ArrayAccess::offsetSet()
         */
        public function offsetSet($offset, $value)
        {
            if(!isset($this->data))
            {
                $this->data = array($offset => $value);
                return;
            }

            if(!is_array($this->data))
            {
                //SSFLog::error("Trying to set $offset => $value into an SSFType which doesn't use an array for it's data.  Unsupported!");
                return;
            }

            $this->data[$offset] = $value;
        }

        /* (non-PHPdoc)
         * @see ArrayAccess::offsetGet()
         */
        public function offsetGet($offset)
        {
            if(isset($this->data) && is_array($this->data) && array_key_exists($offset, $this->data))
            {
                return $this->data[$offset];
            }
            return null;
        }

        /* (non-PHPdoc)
         * @see ArrayAccess::offsetUnset()
         */
        public function offsetUnset($offset)
        {
            if(isset($this->data) && is_array($this->data) && array_key_exists($offset, $this->data))
            {
                unset($this->data[$offset]);
            }
        }

        /* BEGIN iterator interface implementation */

        /* (non-PHPdoc)
         * @see Iterator::current()
         */
        public function current ( )
        {
            if(is_array($this->data))
            {
                return current($this->data);
            }
            return null;
        }

        /* (non-PHPdoc)
         * @see Iterator::key()
         */
        public function key ( )
        {
            if(is_array($this->data))
            {
                return key($this->data);
            }
            return null;
        }

        /* (non-PHPdoc)
         * @see Iterator::next()
         */
        public function next ( )
        {
            if(is_array($this->data))
            {
                return next($this->data);
            }
            return null;
        }

        /* (non-PHPdoc)
         * @see Iterator::rewind()
         */
        public function rewind ( )
        {
            if(is_array($this->data))
            {
                return reset($this->data);
            }
            return null;
        }

        /* (non-PHPdoc)
         * @see Iterator::valid()
         */
        public function valid ( )
        {
            if(is_array($this->data))
            {
                return key($this->data) !== null;;
            }
            return false;
        }

        /* BEGIN countable interface implementation */

        /* (non-PHPdoc)
         * @see Countable::count()
         */
            public function count ( )
        {
            if(!isset($this->data))
            {
                return 0;
            }
            if(is_array($this->data))
            {
                return count($this->data);
            }
            return 1;
        }
    }
}
?>
