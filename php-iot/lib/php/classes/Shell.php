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

  require_once(__DIR__ . '/SocketClient.php');

  final class Shell extends SocketClient
  {

    protected

      $prompt;

    public function __construct($params)
    {
      try {
        parent::__construct($params);
      }
      catch (Exception $e) {
        $this->onError(__METHOD__ . ' > ' . $e->getMessage());
      }
    } // __construct

    public function run()
    {
      while (true) {
        $response = "";
        $data     = "";
        while (!feof(STDIN)) {
          $data .= fgets(STDIN, 1024);
          if (stripos($data, self::EOT) !== false) {
            break;
          }
        }
        $data = trim($data);
        if (strlen($data) > 0) {
          $response = $this->send($data, 30);
        }
        if (strlen($response) > 0) {
          $command = strtolower($response);
          if ($command == "#goodbye") {
            break;
          }
          else if (stripos($command, "#prompt ") === 0) {
            list($discard, $prompt) = explode(" ", $command);
            $this->prompt = $prompt;
          }
          else if (stripos($command, "#error ") === 0) {
            $message = substr($response, stripos($response, " ") + 1);
            fwrite(STDOUT, "Server error: " . $message . self::EOT);
          }
          else {
            fwrite(STDOUT, $response . self::EOT);
          }
        }
        fwrite(STDIN, $this->prompt . "> ");
        usleep(10000);
      }
      fwrite(STDOUT, "Disconnecting...");
      @socket_shutdown($this->socket);
      @socket_close($this->socket);
      fwrite(STDOUT, " done.\n");
      exit(0);
    } // run

    protected function onConnect()
    {
      $greeting = $this->_read();
      if (stripos($greeting, "#PROMPT ") === 0) {
        list($discard, $prompt) = explode(" ", $greeting);
        $this->prompt = $prompt;
      }
      else {
        $this->prompt = "phiot";
      }
      fwrite(STDIN, $this->prompt . "> ");
    } // onConnect

    protected function onDisconnect()
    {
      fwrite(STDOUT, "Disconnected!" . self::EOT);
    } // onDisconnect

    protected function onError($message)
    {
      fwrite(STDOUT, "Socket error: " . $message . self::EOT);
    } // onError

  } // Shell

?>
