<?php

require_once dirname(__FILE__).'/ssftype.php';

if (false === class_exists('Error'))
{
    class Error
    {
      const Success = 0;
      const ErrorGeneric = -1;
      const ErrorAccessDenied = -2;
      const ErrorNotFound = -3;
      const ErrorInvalidArgs = -4;
      const ErrorDatabase = -5;
      const ErrorOutOfRange = -6;
      const ErrorVersion = -7;
      const ErrorSessionMismatch = -8;
      const ErrorSessionExpiration = -9;
      const ErrorNotImplemented = -10;
      const ErrorAuthorizationFailure = -11;
      const ErrorConnectionFailed = -12;


      public static function OutOfRange( $str = "", $bLogIfError = TRUE )
      {
          return SSFType::createWithError( Error::ErrorOutOfRange, "(OutOfRange) $str", $bLogIfError );
      }

      public static function AccessDenied( $str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorAccessDenied, "(Access denied) $str", $bLogIfError );
      }
      public static function InvalidArgs( $str = "", $bLogIfError = TRUE)
      {
          return SSFType::createWithError( Error::ErrorInvalidArgs, "(Invalid Args) $str", $bLogIfError );
      }
      public static function Db( $str = "", $bLogIfError = TRUE)
      {
          return SSFType::createWithError( Error::ErrorDatabase, "(Database Error) $str", $bLogIfError );
      }
      public static function NotFound( $str = "", $bLogIfError = TRUE)
      {
          return SSFType::createWithError( Error::ErrorNotFound, "(Not found) $str", $bLogIfError);
      }
      public static function Generic( $str = "")
      {
        return SSFType::createWithError( Error::ErrorGeneric, "(Generic error) $str" );
      }
      public static function WrongVersion($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorVersion, "(Version error) $str", $bLogIfError );
      }
      public static function SessionMismatch($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorSessionMismatch, "(Session mismatch) $str", $bLogIfError );
      }
      public static function SessionExpiration($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorSessionExpiration, "(Session expiration) $str", $bLogIfError );
      }
      public static function NotImplemented($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorNotImplemented, "(Method is not implemented) $str", $bLogIfError );
      }
      public static function AuthorizationFailure($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorAuthorizationFailure, "(Authorization failed) $str", $bLogIfError );
      }

      public static function ConnectionFailed($str = "", $bLogIfError = TRUE)
      {
        return SSFType::createWithError( Error::ErrorConnectionFailed, "(Connection failed) $str", $bLogIfError );
      }


      public static function Success( $str = "")
      {
        return SSFType::createWithError( Error::Success, "(Success) $str" );
      }

    }
}
?>