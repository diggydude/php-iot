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
  require_once(__DIR__ . '/../lib/php/classes/Droid.php');
  require_once(__DIR__ . '/../lib/php/classes/Cache.php');

  $options = new Zend\Console\Getopt(
               array(
                 'pattern|p=s'  => 'Filename pattern to glob',
                 'servers|s=s'  => 'Cache config filename',    
                 'baudRate|b=i' => 'Serial device baud rate',
                 'rescan|r'     => 'Clear cache and rescan'
               )
             );

  $servers = array();

  foreach (file($options->s) as $line) {
    $servers[] = explode("\t", trim($line));
  }

  $cache = Cache::connect(
             (object) array(
               'id'      => 'iot',
               'servers' => $servers
             )
           );

  if (isset($options->r)) {
    $cache->delete('droids');
  }

  if (!$cache->exists('droids')) { 
    $droids = Droid::scan(
                (object) array(
                  'pattern'  => $options->p,
                  'baudRate' => $options->b,
                  'lineEnd'  => "\r\n",
                  'timeout'  => 5
                )
              );
    $cache->set('droids', $droids);
  }
  else {
    echo "Cached droids:\n\n";
    $droids = $cache->get('droids');
    foreach ($droids as $id => $device) {
      echo $id . "\t" . $device . "\n";
    }
    echo "\n";
  }

?>
