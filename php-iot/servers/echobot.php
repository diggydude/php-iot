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

  require_once(__DIR__ . '/../lib/php/vnd/Getopt.php');
  require_once(__DIR__ . '/../lib/php/classes/EchoServer.php');

  $options = new Zend\Console\Getopt(
               array(
                 'name|n=s' => 'server name',
                 'host|h=s' => 'server hostname or IP',
                 'port|p=i' => 'server listening port'
               )
             );

  $server = new EchoServer(
              (object) array(
                 'name'    => $options->n,
                 'address' => $options->h,
                 'port'    => $options->p
              )
            );

?>
