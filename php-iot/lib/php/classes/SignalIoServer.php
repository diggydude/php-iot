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
  require_once(__DIR__ . '/SignalIoDroid.php');

  class SignalIoServer extends SocketServer
  {

    protected static

      $_commands = array(
                     'getId'             => '/^ID\?$/',
                     'portSetPin'        => '/^PORT (\d+) SET PIN (\d+) (\w+)$/',
                     'portGetPin'        => '/^PORT (\d+) GET PIN (\d+)$/',
                     'portSetPinMode'    => '/^PORT (\d+) SET PIN MODE (\d+) (\w+)$/',
                     'portGetPinMode'    => '/^PORT (\d+) GET PIN MODE (\d+)$/',
                     'portStrobePin'     => '/^PORT (\d+) STROBE PIN (\d+) (\w+)$/',
                     'portSetMode'       => '/^PORT (\d+) SET MODE (\w+)$/',
                     'portGetMode'       => '/^PORT (\d+) GET MODE$/',
                     'portRead'          => '/^PORT (\d+) READ$/',
                     'portWrite'         => '/^PORT (\d+) WRITE (\d+)$/',
                     'adcSetGain'        => '/^ADC (\d+) SET GAIN (\d+)$/',
                     'adcReadDiff'       => '/^ADC (\d+) READ DIFF$/',
                     'adcRead'           => '/^ADC (\d+) READ$/',
                     'dacWrite'          => '/^DAC (\d+) WRITE (\d+)$/',
                     'pwmSetFrequency'   => '/^PWM SET FREQ (\d+)$/',
                     'pwmSetDutyCycle'   => '/^PWM (\d+) SET DUTY CYCLE (\d+)$/',
                     'pwmSetAngle'       => '/^PWM (\d+) SET ANGLE (\d+)$/',
                     'setClockFrequency' => '/^CLOCK (\d+) SET FREQ (\d+)$/',
                     'setWaveFrequency'  => '/^WAVE SET FREQ (\d+)$/'
                   );

    protected

      $_droid,
      $_clients,
      $_error;

    public function __construct($params)
    {
      parent::__construct($params);
      $this->_droid   = new SignalIoDroid($params->droid);
      $this->_clients = array();
      $this->_error   = "";
      $this->_run();
    } // __construct

    protected function call($command)
    {
      $command = strtoupper(trim($command));
      foreach (self::$_commands as $method => $pattern) {
        if (!method_exists($this->_droid, $method)) {
          $this->_error = "Unknown method \"" . $method . "\".";
          return false;
        }
        if (preg_match($pattern, $command, $matches)) {
          $args = array_slice($matches, 1);
          return call_user_func_array(array($this->_droid, $method), $args);
        }
      }
      $this->_error = "Unrecognized command: \"" . $command . "\".";
      return false;
    } // call

    protected function onConnect($socket, $address, $port)
    {
      $host = $address . ":" . $port;
      $this->_clients[$socket] = $host;
      $this->_write($socket, $this->name);
      echo now() . "Client connected from  " . $host . "\n";
    } // onConnect

    protected function onDisconnect($socket)
    {
      $host = $this->_clients[$socket];
      echo now() . " Client at " . $host . " disconnected.\n";
      unset($this->_clients[$socket]);
    } // onDisconnect

    protected function onReceive($socket, $command)
    {
      $result = $this->call($command);
      if ($result !== false) {
        $this->_write($socket, $result);
        echo now() . " Processed command from client at " . $this->_clients[$socket] . "\n";
        return;
      }
      $this->_write($socket, "#ERROR " . $this->_error);
      echo now() . " Error processing command \"" . trim($command) . "\": " . $this->_error . ".\n";
    } // onReceive

  } // SignalIoServer

?>
