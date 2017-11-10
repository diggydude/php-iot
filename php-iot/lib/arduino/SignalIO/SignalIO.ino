/*
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
*/

#define portAddr_0   0x20
#define portAddr_1   0x21
#define portAddr_2   0x22
#define portAddr_3   0x23
#define portData_A   0x12
#define portData_B   0x13
#define portDir_A    0x00
#define portDir_B    0x01
#define portMode_OUT 0x00
#define portMode_IN  0x01
#define adcAddr_0    0x48
#define adcAddr_1    0x49
#define adcAddr_2    0x50
#define adcAddr_3    0x51
#define muxAddr_0    0x71
#define muxAddr_1    0x72
#define dacAddr_0    0x62
#define dacAddr_1    0x63
#define pwmAddr      0x40
#define clockAddr    0x60
#define dds_RESET    5
#define dds_DATA     6
#define dds_LOAD     7
#define dds_CLOCK    8
#define MAXSIZE      30
#define UINT_MAX     4294967295U

#include <Wire.h>
#include <Adafruit_ADS1015.h>
#include <Adafruit_MCP4725.h>
#include <Adafruit_PWMServoDriver.h>
#include <si5351.h>
#include <Messenger.h>

Adafruit_ADS1115 adc0(adcAddr_0);
Adafruit_ADS1115 adc1(adcAddr_1);
Adafruit_ADS1115 adc2(adcAddr_2);
Adafruit_ADS1115 adc3(adcAddr_3);
Adafruit_MCP4725 dac0;
Adafruit_MCP4725 dac1;
Adafruit_PWMServoDriver pwm = Adafruit_PWMServoDriver(pwmAddr);
Si5351 clock(clockAddr);
Messenger message = Messenger(',');

char               id[6] = "sigio";
char               inBuffer[MAXSIZE];
byte               portModes[8];
char               command;
int                channel, pinNum, mode, level, value, rate, gain;
unsigned int       voltage, pwmFreq, offTime;
unsigned long      waveFreq;
char               frequency[MAXSIZE];
unsigned long long clockFreq;

void messageCompleted()
{
  if (message.checkString("ID?")) {
    Serial.println(id);
  }
  // port
  else if (message.checkString("P")) {
    channel = message.readInt();
    command = message.readChar();
    switch (command) {
      case 'S': // set pin
	pinNum = message.readInt();
        level  = message.readInt();
	portSetPin();
	break;
      case 'G': // get pin
	pinNum = message.readInt();
	portGetPin();
	break;
      case 'M': // set pin mode
	pinNum = message.readInt();
        mode   = message.readInt();
	portSetPinMode();
	break;
      case 'D': // get pin mode
	pinNum = message.readInt();
	portGetPinMode();
	break;
      case 'T': // strobe pin
	pinNum = message.readInt();
        level  = message.readInt();
	portStrobePin();
	break;
      case 'O': // set port mode
	mode = message.readInt();
	portSetMode();
	break;
      case 'R': // read port
	portRead();
	break;
      case 'W': // write port
	value = message.readInt();
	portWrite();
	break;
      default:
        Serial.print("#ERROR Unknown command: ");
        Serial.println(command);
        break;
    }
  }
  // adc
  else if (message.checkString("A")) {
    channel = message.readInt();
    command = message.readChar();
    switch (command) {
      case 'G': // set gain
	gain = message.readInt();
        switch (gain) {
          case 0:
          default:
            gain = GAIN_TWOTHIRDS;
            break;
          case 1:
            gain = GAIN_ONE;
            break;
          case 2:
            gain = GAIN_TWO;
            break;
          case 4:
            gain = GAIN_FOUR;
            break;
          case 8:
            gain = GAIN_EIGHT;
            break;
          case 16:
            gain = GAIN_SIXTEEN;
            break;
        }
	adcSetGain();
	break;
      case 'R': // read single-ended
	adcRead();
	break;
      case 'D': // read differential
	adcReadDiff();
	break;
      default:
        Serial.print("#ERROR Unknown command: ");
        Serial.println(command);
	break;
    }
  }
  // dac
  else if (message.checkString("D")) {
    channel = message.readInt();
    command = message.readChar();
    switch (command) {
      case 'W': // write
	voltage = message.readInt();
	dacWrite();
	break;
      default:
        Serial.print("#ERROR Unknown command: ");
        Serial.println(command);
	break;
    }
  }
  // pwm/servo
  else if (message.checkString("W")) {
    channel = message.readInt();
    command = message.readChar();
    switch (command) {
      case 'F': // set freq
        pwmFreq = message.readInt();
	pwmSetFrequency();
	break;
      case 'C': // set duty cycle
	offTime = message.readInt();
	pwmSetDutyCycle();
	break;
      default:
        Serial.print("#ERROR Unknown command: ");
        Serial.println(command);
	break;
    }
  }
  // clock
  else if (message.checkString("C")) {
    channel   = message.readInt();
    command   = message.readChar();
    message.copyString(frequency, MAXSIZE);
    clockFreq = _strtoull(frequency);
    switch (command) {
      case 'F':
	setClockFrequency();
	break;
      default:
        Serial.print("#ERROR Unknown command: ");
        Serial.println(command);
	break;
    }
  }
  // dds synthesizer
  else if (message.checkString("O")) {
    waveFreq = message.readUL();
    setWaveFrequency();
  }
  // unknown
  else {
    message.copyString(inBuffer, MAXSIZE);
    Serial.print("#ERROR Unknown module: ");
    Serial.println(inBuffer);
  }
} // MessageCompleted

void setup()
{
  Wire.begin();
  Serial.begin(9600);
  message.attach(messageCompleted);
  dac0.begin(dacAddr_0);
  dac1.begin(dacAddr_1);
  pwm.begin();
  clock.init(SI5351_CRYSTAL_LOAD_8PF, 0, 0);
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
} // loop

unsigned long long _strtoull(const char *s)
{
  unsigned sumu = 0;
  while (*s) {
    sumu = sumu * 10 + (*s++ - '0');
    if (sumu >= (UINT_MAX - 10) / 10) {
      break;
    }
  }
  unsigned long long sum = sumu;
  while (*s) {
    sum = sum * 10 + (*s++ - '0');
  }
  return sum;
} // _strtoull

void _setupRegister(byte busAddr, byte registerAddr, bool close = false)
{
  Wire.beginTransmission(busAddr);
  Wire.write(registerAddr);
  if (close) {
    Wire.endTransmission();
  }
} // _setupRegister

byte _getRegisterValue(byte busAddr, byte registerAddr)
{
  _setupRegister(busAddr, registerAddr, true);
  Wire.requestFrom(busAddr, (uint8_t) 1);
  return Wire.read();
} // _getRegisterValue

byte _setBit()
{
  if (level == 0) {
    bitClear(value, pinNum);
  }
  else {
    bitSet(value, pinNum);
  }
} // _setBit

void _strobe(byte busAddr, byte registerAddr)
{
  value = _getRegisterValue(busAddr, registerAddr);
  _setBit();
  _setupRegister(busAddr, registerAddr);
  Wire.write(value);
  Wire.endTransmission();
  level = (level == 0) ? 1 : 0;
  _setBit();
  _setupRegister(busAddr, registerAddr);
  Wire.write(value);
  Wire.endTransmission();
} // _strobe

void _setPin(byte busAddr, byte registerAddr)
{
  value = _getRegisterValue(busAddr, registerAddr);
  _setBit();
  _setupRegister(busAddr, registerAddr);
  Wire.write(value);
  Wire.endTransmission();
} // _setPin

void portSetPin()
{
  switch (channel) {
    case 0:
      _setPin(portAddr_0, portData_A);
      break;
    case 1:
      _setPin(portAddr_0, portData_B);
      break;
    case 2:
      _setPin(portAddr_1, portData_A);
      break;
    case 3:
      _setPin(portAddr_1, portData_B);
      break;
    case 4:
      _setPin(portAddr_2, portData_A);
      break;
    case 5:
      _setPin(portAddr_2, portData_B);
      break;
    case 6:
      _setPin(portAddr_3, portData_A);
      break;
    case 7:
      _setPin(portAddr_3, portData_B);
      break;
  }
  Serial.println("OK");
} // portSetPin

void portGetPin()
{
  switch (channel) {
    case 0:
      value = _getRegisterValue(portAddr_0, portData_A);
      break;
    case 1:
      value = _getRegisterValue(portAddr_0, portData_B);
      break;
    case 2:
      value = _getRegisterValue(portAddr_1, portData_A);
      break;
    case 3:
      value = _getRegisterValue(portAddr_1, portData_B);
      break;
    case 4:
      value = _getRegisterValue(portAddr_2, portData_A);
      break;
    case 5:
      value = _getRegisterValue(portAddr_2, portData_B);
      break;
    case 6:
      value = _getRegisterValue(portAddr_3, portData_A);
      break;
    case 7:
      value = _getRegisterValue(portAddr_3, portData_B);
      break;
  }
  Serial.println(bitRead(value, pinNum));
} // portgetPin

void portSetPinMode()
{
  if (mode == 0) {
    bitClear(portModes[channel], pinNum);
  }
  else {
    bitSet(portModes[channel], pinNum);
  }
  switch (channel) {
    case 0:
      _setupRegister(portAddr_0, portDir_A);
      break;
    case 1:
      _setupRegister(portAddr_0, portDir_B);
      break;
    case 2:
      _setupRegister(portAddr_1, portDir_A);
      break;
    case 3:
      _setupRegister(portAddr_1, portDir_B);
      break;
    case 4:
      _setupRegister(portAddr_2, portDir_A);
      break;
    case 5:
      _setupRegister(portAddr_2, portDir_B);
      break;
    case 6:
      _setupRegister(portAddr_3, portDir_A);
      break;
    case 7:
      _setupRegister(portAddr_3, portDir_B);
      break;
  }
  Wire.write(portModes[channel]);
  Wire.endTransmission();
  Serial.println("OK");
} // portSetPinMode

void portGetPinMode()
{
  Serial.println(bitRead(portModes[channel], pinNum));
} // portGetPinMode

void portStrobePin()
{
  switch (channel) {
    case 0:
      _strobe(portAddr_0, portData_A);
      break;
    case 1:
      _strobe(portAddr_0, portData_B);
      break;
    case 2:
      _strobe(portAddr_1, portData_A);
      break;
    case 3:
      _strobe(portAddr_1, portData_B);
      break;
    case 4:
      _strobe(portAddr_2, portData_A);
      break;
    case 5:
      _strobe(portAddr_2, portData_B);
      break;
    case 6:
      _strobe(portAddr_3, portData_A);
      break;
    case 7:
      _strobe(portAddr_3, portData_B);
      break;
  }
  Serial.println("OK");
} // portStrobePin

void portSetMode()
{
  portModes[channel] = (mode == 0) ? B00000000 : B11111111;
  switch (channel) {
    case 0:
      _setupRegister(portAddr_0, portDir_A);
      break;
    case 1:
      _setupRegister(portAddr_0, portDir_B);
      break;
    case 2:
      _setupRegister(portAddr_1, portDir_A);
      break;
    case 3:
      _setupRegister(portAddr_1, portDir_B);
      break;
    case 4:
      _setupRegister(portAddr_2, portDir_A);
      break;
    case 5:
      _setupRegister(portAddr_2, portDir_B);
      break;
    case 6:
      _setupRegister(portAddr_3, portDir_A);
      break;
    case 7:
      _setupRegister(portAddr_3, portDir_B);
      break;
  }
  Wire.write(portModes[channel]);
  Wire.endTransmission();
  Serial.println("OK");
} // portSetMode

void portGetMode()
{
  Serial.println(portModes[channel]);
} // portGetMode

void portRead()
{
  switch (channel) {
    case 0:
      value = _getRegisterValue(portAddr_0, portData_A);
      break;
    case 1:
      value = _getRegisterValue(portAddr_0, portData_B);
      break;
    case 2:
      value = _getRegisterValue(portAddr_1, portData_A);
      break;
    case 3:
      value = _getRegisterValue(portAddr_1, portData_B);
      break;
    case 4:
      value = _getRegisterValue(portAddr_2, portData_A);
      break;
    case 5:
      value = _getRegisterValue(portAddr_2, portData_B);
      break;
    case 6:
      value = _getRegisterValue(portAddr_3, portData_A);
      break;
    case 7:
      value = _getRegisterValue(portAddr_3, portData_B);
      break;
  }
  Serial.println(value);
} // portRead

void portWrite()
{
  switch (channel) {
    case 0:
      _setupRegister(portAddr_0, portData_A);
      break;
    case 1:
      _setupRegister(portAddr_0, portData_B);
      break;
    case 2:
      _setupRegister(portAddr_1, portData_A);
      break;
    case 3:
      _setupRegister(portAddr_1, portData_B);
      break;
    case 4:
      _setupRegister(portAddr_2, portData_A);
      break;
    case 5:
      _setupRegister(portAddr_2, portData_B);
      break;
    case 6:
      _setupRegister(portAddr_3, portData_A);
      break;
    case 7:
      _setupRegister(portAddr_3, portData_B);
      break;
  }
  Wire.write(value);
  Wire.endTransmission();
  Serial.println("OK");
} // portWrite

void adcSetGain()
{
  if ((channel >= 0) && (channel <= 3)) {
    adc0.setGain(gain);
  }
  else if ((channel >= 4) && (channel <= 7)) {
    adc1.setGain(gain);
  }
  else if ((channel >= 8) && (channel <= 11)) {
    adc2.setGain(gain);
  }
  else {
    adc3.setGain(gain);
  }
  Serial.println("OK");
} // adcSetGain

void adcRead()
{
  switch (channel) {
    case 0:
      value = adc0.readADC_SingleEnded(0);
      break;
    case 1:
      value = adc0.readADC_SingleEnded(1);
      break;
    case 2:
      value = adc0.readADC_SingleEnded(2);
      break;
    case 3:
      value = adc0.readADC_SingleEnded(3);
      break;
    case 4:
      value = adc1.readADC_SingleEnded(0);
      break;
    case 5:
      value = adc1.readADC_SingleEnded(1);
      break;
    case 6:
      value = adc1.readADC_SingleEnded(2);
      break;
    case 7:
      value = adc1.readADC_SingleEnded(3);
      break;
    case 8:
      value = adc2.readADC_SingleEnded(0);
      break;
    case 9:
      value = adc2.readADC_SingleEnded(1);
      break;
    case 10:
      value = adc2.readADC_SingleEnded(2);
      break;
    case 11:
      value = adc2.readADC_SingleEnded(3);
      break;
    case 12:
      value = adc3.readADC_SingleEnded(0);
      break;
    case 13:
      value = adc3.readADC_SingleEnded(1);
      break;
    case 14:
      value = adc3.readADC_SingleEnded(2);
      break;
    case 15:
      value = adc3.readADC_SingleEnded(3);
      break;
  }
  Serial.println(value);
} // adcRead

void adcReadDiff()
{
  switch (channel) {
    case 0:
    case 1:
      value = adc0.readADC_Differential_0_1();
      break;
    case 2:
    case 3:
      value = adc0.readADC_Differential_2_3();
      break;
    case 4:
    case 5:
      value = adc1.readADC_Differential_0_1();
      break;
    case 6:
    case 7:
      value = adc1.readADC_Differential_2_3();
      break;
    case 8:
    case 9:
      value = adc2.readADC_Differential_0_1();
      break;
    case 10:
    case 11:
      value = adc2.readADC_Differential_2_3();
      break;
    case 12:
    case 13:
      value = adc3.readADC_Differential_0_1();
      break;
    case 14:
    case 15:
      value = adc3.readADC_Differential_2_3();
      break;
  }
  Serial.println(value);
} // adcReadDiff

void dacWrite() {
  if (channel < 8) {
    Wire.beginTransmission(muxAddr_0);
    Wire.write(1 << channel);
    Wire.endTransmission();
    dac0.setVoltage(voltage, false);
  }
  else {
    Wire.beginTransmission(muxAddr_1);
    Wire.write(1 << (channel - 8));
    Wire.endTransmission();
    dac1.setVoltage(voltage, false);
  }
  Serial.println("OK");
} // dacWrite

void pwmSetFrequency()
{
  pwm.setPWMFreq(pwmFreq);
} // pwmSetFrequency

void pwmSetDutyCycle()
{
  if (offTime == 0) {
    pwm.setPWM(channel, 4096, 0);
  }
  else {
    pwm.setPWM(channel, 0, offTime);
  }
} // pwmSetDutyCycle

void setClockFrequency()
{
  clock.set_freq(clockFreq, channel);
  Serial.println("OK");
} // setClockFrequency

void setWaveFrequency()
{
  dds(waveFreq);
} // setWaveFrequency




