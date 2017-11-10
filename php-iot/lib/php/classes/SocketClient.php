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

  abstract class SocketClient
  {

    const EOT = "\n";

    protected static $clients = array();

    protected

      $name,
      $address,
      $port,
      $socket;

    public static function connect($params)
    {
      if (is_string($params)) {
        $name = $params;
        if (isset(self::$clients[$name])) {
          return self::$clients[$name];
        }
        return null;
      }
      $client = new self($params);
      self::$clients[$params->name] = $client;
      return $client;
    } // connect

    abstract public function run();

    abstract protected function onConnect();

    abstract protected function onDisconnect();

    abstract protected function onError($errorMessage);

    public function send($data)
    {
      if ($this->_write($data) === false) {
        $this->onError(socket_strerror(socket_last_error($this->socket)));
        return false;
      }
      return $this->_read();
    } // send

    protected function _read($length = 1024)
    {
      $data = @socket_read($this->socket, $length, PHP_NORMAL_READ);
      if (($data === false) || ($data == "")) {
        return false;
      }
      return trim($data);
    } // _read

    protected function _write($data)
    {
      $data   = trim($data) . self::EOT;
      $length = strlen($data);
      while (true) {
        $numBytes = @socket_write($this->socket, $data, $length);
        if (($numBytes === false) || ($numBytes <= 0)) {
          $this->onError(__METHOD__ . ' > ' . socket_strerror(socket_last_error($this->socket)));
          return false;
        }
        if ($numBytes < $length) {
          $data    = substr($data, $numBytes);
          $length -= $numBytes;
        }
        else {
          return true;
        }
      }
    } // _write

    protected function __construct($params)
    {
      $this->name    = $params->name;
      $this->address = $params->address;
      $this->port    = $params->port;
      if (($this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
        $this->onError(__METHOD__ . ' > ' . socket_strerror(socket_last_error()));
        return;
      }
      if (@socket_connect($this->socket, $this->address, $this->port) === false) {
        $this->onError(__METHOD__ . ' > ' . socket_strerror(socket_last_error($this->socket)));
        return;
      }
      $this->onConnect();
    } // __construct

  } // SocketClient

?>
