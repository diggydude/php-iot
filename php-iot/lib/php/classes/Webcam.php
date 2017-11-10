<?php

#              Internet of Things (IoT) Framework in PHP
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

  class Webcam
  {

    protected

      $device;

    public function __construct($params)
    {
      $this->device = $params->device;
    } // __construct

    public function saveImage($params)
    {
      if (!in_array($params->colorDepth, array(15, 16, 24, 32))) {
        throw new Exception(__METHOD__ . ' > Color depth must be one of 15, 16, 24, or 32.');
      }
      if (strripos($params->filename, '.jpeg') !== (strlen($params->filename) - 5)) {
        $params->filename .= ".jpeg";
      }
      $command = "/usr/bin/streamer -c " . $this->device . " -b " . $params->colorDepth
               . " -o " . $params->filename;
      exec($command);
    } // saveImage

    public function saveVideo($params)
    {
      if (strripos($params->filename, '.avi') !== (strlen($params->filename) - 4)) {
        $params->filename .= ".avi";
      }
      $command = "/usr/bin/streamer -q -c " . $this->device . " -f rgb24 -r " . $params->frameRate
               . " -t " . $params->duration . " -o " . $params->filename;
      exec($command);
    } // saveVideo

  } // Webcam

?>
