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

  require_once(__DIR__ . '/../functions/constants.php');
  require_once(__DIR__ . '/Parameter.php');
  require_once(__DIR__ . '/Droid.php');

  class SignalIoDroid extends Droid
  {

    public function __construct($params)
    {
      parent::__construct($params);
    } // ++construct

    public function getId()
    {
      return $this->execute("ID?");
    } // getId

    public function portSetPin($portNumber, $pinNumber, $level)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $pinNumber  = Parameter::constrain($pinNumber, 0, 7);
      $level      = Parameter::constrain($level, LOW, HIGH);
      return $this->execute("P," . $portNumber . ",S," . $pinNumber . "," . $level);
    } // portSetPin

    public function portGetPin($portNumber, $pinNumber)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $pinNumber  = Parameter::constrain($pinNumber, 0, 7);
      return $this->execute("P," . $portNumber . ",G," . $pinNumber);
    } // portGetPin

    public function portSetPinMode($portNumber, $pinNumber, $mode)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $pinNumber  = Parameter::constrain($pinNumber, 0, 7);
      $mode       = Parameter::constrain($mode, OUTPUT, INPUT);
      return $this->execute("P," . $portNumber . ",M," . $pinNumber . "," . $mode);
    } // portSetPinMode

    public function portGetPinMode($portNumber, $pinNumber)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $pinNumber  = Parameter::constrain($pinNumber, 0, 7);
      return $this->execute("P," . $portNumber . ",D," . $pinNumber);
    } // portGetPinMode

    public function portStrobePin($portNumber, $pinNumber, $level)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $pinNumber  = Parameter::constrain($pinNumber, 0, 7);
      $level      = Parameter::constrain($level, LOW, HIGH);
      return $this->execute("P," . $portNumber . ",T," . $pinNumber . "," . $level);
    } // portStrobePin

    public function portSetMode($portNumber, $mode)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $mode       = Parameter::constrain($mode, OUTPUT, INPUT);
      return $this->execute("P," . $portNumber . ",O," . $mode); 
    } // portSetMode

    public function portRead($portNumber)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      return $this->execute("P," . $portNumber . ",R"); 
    } // portRead

    public function portWrite($portNumber, $value)
    {
      $portNumber = Parameter::constrain($portNumber, 0, 7);
      $value      = Parameter::constrain($value, 0, 255);
      return $this->execute("P," . $portNumber . ",W," . $value); 
    } // portRWrite

    public function adcSetGain($channelNumber, $gain)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      if (!in_array($gain, array(0, 1, 2, 4, 8, 16))) {
        $gain = 0;
      }
      return $this->execute("A," . $channelNumber . ",G," . $gain);
    } // adcSetGain

    public function adcRead($channelNumber)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      return $this->execute("A," . $channelNumber . ",R");
    } // adcRead

    public function adcReadDiff($channelNumber)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      return $this->execute("A," . $channelNumber . ",D");
    } // adcReadDiff

    public function dacWrite($channelNumber, $voltage)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      $voltage       = Parameter::constrain($voltage, 0, 5);
      $value         = round(Parameter::scale($voltage, 0, 5, 0, 4095));
      return $this->execute("D," . $channelNumber . ",W," . $value);
    } // dacWrite

    public function pwmSetFrequency($frequency)
    {
      $frequency = Parameter::constrain($frequency, 40, 1000);
      return $this->execute("W,0,F," . $frequency);
    } // pwmSetFrequency

    public function pwmSetDutyCycle($channelNumber, $percent)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      $percent       = Parameter::constrain(0, 100);
      $offTime       = round(Parameter::scale($percent, 0, 100, 0, 4095));
      return $this->execute("W," . $channelNumber . ",C," . $offTime);
    } // pwmSetDutyCycle

    public function pwmSetAngle($channelNumber, $angle)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 15);
      $angle         = Parameter::constrain($angle, 0, 180);
      $offTime       = round(Parameter::scale($angle, 0, 180, SERVO_MIN, SERVO_MAX));
      return $this->execute("W," . $channelNumber . ",C," . $offTime);
    } // pwmSetAngle

    public function setClockFrequency($channelNumber, $frequency)
    {
      $channelNumber = Parameter::constrain($channelNumber, 0, 2);
      $frequency     = round(Parameter::constrain($frequency, 0, 125000000) * 100);
      return $this->execute("C," . $channelNumber . ",F," . $frequency);
    } // setClockFrequency

    public function setWaveFrequency($frequency)
    {
      $frequency = round(Parameter::constrain($frequency, 0, 70000000));
      return $this->execute("O," . $frequency);
    } // setWaveFrequency

  } // SignalIoDroid

?>
