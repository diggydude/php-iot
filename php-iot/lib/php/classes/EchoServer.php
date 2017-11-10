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

  require_once(__DIR__ . '/SocketServer.php');

  class EchoServer extends SocketServer
  {

    protected

      $_clients;

    public function __construct($params)
    {
      parent::__construct($params);
      $this->_run();
    } // __construct

    protected function onConnect($socket, $address, $port)
    {
      $host = $address . ":" . $port;
      $this->_clients[$socket] = $host;
      $this->_write($socket, $this->name);
      echo now() . " Client connected from " . $host . "\n";
    } // onConnect

    protected function onDisconnect($socket)
    {
      $host = $this->_clients[$socket];
      echo now() . " Client at " . $host . " disconnected.\n";
      unset($this->_clients[$socket]);
    } // onDisconnect

    protected function onReceive($socket, $data)
    {
      $this->_write($socket, 'Received: ' . $data);
      echo now() . " Echoed data from client at " . $this->_clients[$socket] . "\n";
    } // onReceive

  } // EchoServer
