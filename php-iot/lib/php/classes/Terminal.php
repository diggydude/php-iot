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

  require_once(__DIR__ . '/Serial.php');
  require_once(__DIR__ . '/SocketClient.php');

  final class Terminal extends SocketClient
  {

    protected

      $tty,
      $prompt;

    public function __construct($params)
    {
      try {
        parent::__construct(
          (object) array(
            'name'    => $params->name,
            'address' => $params->address,
            'port'    => $params->port
          )
        );
        $this->tty    = new Serial(
                          (object) array(
                            'device'   => $params->device,
                            'baudRate' => $params->baudRate,
                            'lineEnd'  => $params->lineEnd,
                            'type'     => 'terminal'
                          )
                        );
        $this->writePrompt();
      }
      catch (Exception $e) {
        $this->onError(__METHOD__ . ' > ' . $e->getMessage());
      }
    } // __construct

    public function run()
    {
      while (true) {
        $response = "";
        $data     = $this->tty->read();
        if (strlen($data) > 0) {
          $this->tty->write("");
          $response = $this->send($data);
          if (strlen($response) > 0) {
            if (stripos($response, "#PROMPT") === 0) {
              $this->prompt = substr($response, stripos($response, " ") + 1);
            }
            else {
              $this->tty->write($response);
            }
          }
        }
        else {
          $this->tty->write("");
        }
        $this->writePrompt();
        usleep(10000);
      }
    } // run

    protected function writePrompt()
    {
      $lineEnd = $this->tty->lineEnd;
      $this->tty->setLineEnd(" ");
      $this->tty->write($this->prompt . ">");
      $this->tty->setLineEnd($lineEnd);
    } // writePrompt

    protected function onConnect()
    {
      $greeting = $this->_read();
      if (stripos($greeting, "#PROMPT ") === 0) {
        $this->prompt = substr($greeting, stripos($greeting, " ") + 1);
      }
    } // onConnect

    protected function onDisconnect()
    {
      $this->tty->write("Disconnected!");
    } // onDisconnect

    protected function onError($message)
    {
      $this->tty->write("Socket error: " . $message);
    } // onError

  } // Terminal

?>
