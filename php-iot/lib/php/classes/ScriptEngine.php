<?php

#             An Internet of Things (IoT) Framework in PHP
#
#                    Copyright 2017 James Elkins
#
# This software is released under the Pay It Forward License (PIFL) with
# neither express nor implied warranty as regards merchantablity or fitness
# for any particular use. The end user assumes responsibility for all
# consequences arising from the use of this software.
#
# Use of this software in whole or in in part for any commercial purpose,
# including use in undistributed in-house applications, obligates the user
# to "Pay It Forward" by contributing monetarily or in kind to such open
# source software and/or hardware project(s) as the user may choose.
#
# This software may be freely copied and distributed as long as said copies
# are accompanied by this copyright notice and licensing agreement. This
# document shall cosntitute the entirety of the agreement between the
# software's author and the end user.

  require_once(__DIR__ . '/../functions/datetime.php');
  require_once(__DIR__ . '/Port.php');

  abstract class ScriptEngine
  {

    protected

      $port;

    public function __construct($params)
    {
      $this->port = new Port($params);
    } // __construct

    abstract protected function onReceive($response);

    abstract protected function onError($errorMessage);

    public function run($filename, $params = null)
    {
      if (!file_exists($filename)) {
        echo "File not found.\n";
        return;
      }
      $basename = basename($filename);
      $script   = file_get_contents($filename);
      echo now() . " Running \"" . $basename . "\"...\n";
      if ($params) {
        echo "Binding parameters...\n";
        if (is_object($params)) {
          $params = get_object_vars($params);
        }
        $search  = array_keys($params);
        $replace = array_values($params);
        $script  = str_replace($search, $replace, $script);
      }
      $lines = explode("\n", $script);
      foreach ($lines as $line) {
        $line = trim($line);
        if (!strlen($line)) {
          echo "Skipped empty line.\n";
          continue;
        }
        echo "Command: " . $line . "\n";
        $response = trim($this->port->send($line));
        echo "Response: " . $response . "\n";
        if (stripos($response, "#ERROR") === 0) {
          $message = substr($response, stripos($response, " ") + 1);
          $this->onError($message);
        }
        else {
          $this->onReceive($response);
        }
        usleep(10000);
      }
      echo now() . " Script \"" . $basename . "\" completed.\n-----\n";
    } // run

  } // ScriptEngine

?>
