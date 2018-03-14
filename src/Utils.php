<?php namespace AmazeeLabs\alcli;

class Utils
{
    public static function expandTilde($path)
    {
        if (function_exists('posix_getuid') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return $path;
    }

    public static function removeMultiSlash($path) {
      while( strpos($path,"//") !== FALSE ) {
          $path = preg_replace("|//|","/", $path);
      }

      return $path;
    }

    public static function processPath($path)
    {
      $path = self::expandTilde($path);
      $path = self::removeMultiSlash($path);

      return $path;
    }
  
    public static function secToHM($sec) {
      $hours = floor($sec / 3600);
      $minutes = floor(($sec / 60) % 60);
      $seconds = $sec % 60;

      return $hours . "h ". $minutes ."m";
    }
}
