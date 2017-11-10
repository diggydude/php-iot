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
  require_once(__DIR__ . '/Port.php');

  final class Gateway extends SocketServer
  {

    protected

      $_clients,
      $_servers,
      $_routes;

    public function __construct($params)
    {
      parent::__construct($params);
      $this->_clients = array();
      $this->_servers = array();
      $this->_routes  = array();
      foreach ($params->servers as $server) {
        $this->addServer($server);
      }
      $this->_run();
    } // __construct

    protected function addServer($params)
    {
      $this->_servers[$params->name] = new Port($params);
      echo now() . " Added server \"" . $params->name . "\" at " . $params->address . ":" . $params->port . "\n";
    } // addServer

    protected function serverExists($serverName)
    {
      return (isset($this->_servers[$serverName]));
    } // serverExists

    protected function getRoute($socket)
    {
      return $this->_routes[$socket];
    } // getRoute

    protected function setRoute($socket, $serverName)
    {
      $this->_routes[$socket] = $serverName;
      echo now() . " Routed client at " . $this->_clients[$socket] . " to server \"" . $serverName . "\".\n";
    } // setRoute

    protected function forward($socket, $data)
    {
      $server   = $this->getRoute($socket);
      $response = $this->_servers[$server]->send($data);
      $this->_write($socket, $response);
      echo now() . " Forwarded data from client at " . $this->_clients[$socket] . " to server \"" . $server . "\".\n";
    } // forward

    protected function onConnect($socket, $address, $port)
    {
      $host = $address . ":" . $port;
      $this->_clients[$socket] = $host;
      $this->setRoute($socket, $this->name);
      $this->_write($socket, "#PROMPT " . $this->name);
      echo now() . " Client connected from " . $host . "\n";
    } // onConnect

    protected function onDisconnect($socket)
    {
      $host = $this->_clients[$socket];
      $this->_write($socket, "#GOODBYE");
      unset($this->_clients[$socket]);
      unset($this->_routes[$socket]);
      echo now() . " Client at " . $host . " disconnected.\n";
    } // onDisconnect

    protected function onReceive($socket, $data)
    {
      $route = $this->getRoute($socket);
      if (strtoupper($data) == "QUIT") {
        if ($route == $this->name) {
          $this->_disconnect($socket);
        }
        else {
          $this->setRoute($socket, $this->name);
          $this->_write($socket, "#PROMPT " . $this->name);
          echo now() . " Client at " . $this->_clients[$socket] . " quit \"" . $route . "\".\n";
        }
      }
      else if ($route == $this->name) {
        if (stripos($data, "GO ") === 0) {
          list($command, $serverName) = explode(" ", trim($data));
          if ($this->serverExists($serverName)) {
            $this->setRoute($socket, $serverName);
            $this->_write($socket, "#PROMPT " .$serverName);
          }
          else {
            $message = "Server \"" . $serverName . "\" does not exist.";
            $this->_write($socket, "#ERROR " . $message);
            $this->onError($message);
          }
        }
        else if (strtolower($data) == "list") {
          $servers = implode(" ", array_keys($this->_servers));
          $this->_write($socket, $servers);
        }
        else {
          $message = "Unknown command \"" . $data . "\".";
          $this->_write($socket, "#ERROR " . $message);
          $this->onError($message);
        }
      }
      else {
        $this->forward($socket, $data);
      }
    } // onReceive

    protected function onError($message)
    {
      echo now() . " " . $message . "\n";
    } // onError

  } // Gateway
