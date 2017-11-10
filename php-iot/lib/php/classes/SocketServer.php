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

  abstract class SocketServer
  {

    const EOT = "\n";

    protected

      $name,
      $address,
      $port,
      $clients,
      $shutdown,
      $socket;

    public function __construct($params)
    {
      $this->name     = $params->name;
      $this->address  = $params->address;
      $this->port     = $params->port;
      $this->clients  = array();
      $this->shutdown = false;
      if (($this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP))=== false) {
        throw new Exception(__METHOD__ . ' > ' . socket_strerror(socket_last_error()));
      }
      if (@socket_bind($this->socket, $this->address, $this->port) === false) {
        throw new Exception(__METHOD__ . ' > ' . socket_strerror(socket_last_error($this->socket)));
      }
      if (@socket_listen($this->socket) === false) {
        throw new Exception(__METHOD__ . ' > ' . socket_strerror(socket_last_error($this->socket)));
      }
      if (@socket_set_nonblock($this->socket) === false) {
        throw new Exception(__METHOD__ . ' > ' . socket_strerror(socket_last_error($this->socket)));
      }
    } // __construct

    abstract protected function onConnect($socket, $address, $port);

    abstract protected function onDisconnect($socket);

    abstract protected function onReceive($socket, $data);

    protected function _run()
    {
      while (true) {
        if ($this->shutdown) {
          break;
        }
        $sockets   = $this->clients;
        $sockets[] = $this->socket;
        if (@socket_select($sockets, $write = null, $except = null, null) < 1) {
          usleep(150000);
          continue;
        }
        if (in_array($this->socket, $sockets)) {
          $this->clients[] = $socket = @socket_accept($this->socket);
          @socket_getpeername($socket, $address, $port);
          $this->onConnect($socket, $address, $port);
          $key = array_search($this->socket, $sockets);
          unset($sockets[$key]);
        }
        foreach ($sockets as $socket) {
          if (($data = $this->_read($socket)) === false) {
            $this->_disconnect($socket);
            continue;
          }
          $data = trim($data);
          $this->onReceive($socket, $data);
        }
        usleep(150000);
      }
      echo now() . " Disconnecting clients...\n";
      foreach ($this->clients as $socket) {
        $this->_disconnect($socket);
      }
      echo now() . " Shutting down...\n";
      @socket_shutdown($this->socket);
      @socket_close($this->socket);
      exit(0);
    } // _run

    protected function _read($socket, $length = 1024)
    {
      $data = @socket_read($socket, $length, PHP_NORMAL_READ);
      if (($data === false) || ($data == "")) {
        return false;
      }
      return trim($data) . self::EOT;
    } // _read

    protected function _write($socket, $data)
    {
      $data   = trim($data) . self::EOT;
      $length = strlen($data);
      while (true) {
        $numBytes = @socket_write($socket, $data, $length);
        if (($numBytes === false) || ($numBytes <= 0)) {
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
      return false;
    } // _write

    protected function _disconnect($socket)
    {
      $this->onDisconnect($socket);
      $key = array_search($socket, $this->clients);
      @socket_shutdown($socket);
      @socket_close($socket);
      unset($this->clients[$key]);
    } // _disconnect

    public function __destruct()
    {
      $this->address = "";
      $this->port    = 0;
      $this->clients = array();
      $this->socket  = null;
    } // __destruct

  } // SocketServer

?>
