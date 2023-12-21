EESchema Schematic File Version 2  date Wed 23 Jul 2014 05:11:10 PM MDT
LIBS:power
LIBS:device
LIBS:transistors
LIBS:conn
LIBS:linear
LIBS:regul
LIBS:74xx
LIBS:cmos4000
LIBS:adc-dac
LIBS:memory
LIBS:xilinx
LIBS:special
LIBS:microcontrollers
LIBS:dsp
LIBS:microchip
LIBS:analog_switches
LIBS:motorola
LIBS:texas
LIBS:intel
LIBS:audio
LIBS:interface
LIBS:digital-audio
LIBS:philips
LIBS:display
LIBS:cypress
LIBS:siliconi
LIBS:opto
LIBS:atmel
LIBS:contrib
LIBS:valves
LIBS:CONGAFLOCK-cache
EELAYER 27 0
EELAYER END
$Descr User 8500 5500
encoding utf-8
Sheet 1 1
Title "CONGAFLOCK"
Date "23 jul 2014"
Rev ""
Comp "Copyright 2014 Michael Ossmann"
Comment1 "License: GPL v2, https://github.com/mossmann/retroreflectors"
Comment2 ""
Comment3 ""
Comment4 ""
$EndDescr
$Comp
L MOSFET_N Q1
U 1 1 5370319E
P 4750 2250
F 0 "Q1" H 4760 2420 60  0000 R CNN
F 1 "MOSFET_N" V 4950 2450 60  0000 R CNN
F 4 "NXP" H 4750 2250 60  0001 C CNN "Manufacturer"
F 5 "NX3020NAKT,115" H 4750 2250 60  0001 C CNN "Part Number"
F 6 "MOSFET N-CH 30V 180MA SC-75" H 4750 2250 60  0001 C CNN "Description"
	1    4750 2250
	1    0    0    -1  
$EndComp
Wire Wire Line
	4850 1450 4850 2050
Wire Wire Line
	3750 2250 4550 2250
$Comp
L R R2
U 1 1 53703213
P 4500 2550
F 0 "R2" V 4580 2550 50  0000 C CNN
F 1 "10k" V 4500 2550 50  0000 C CNN
	1    4500 2550
	0    -1   -1   0   
$EndComp
Connection ~ 4150 2250
$Comp
L R R1
U 1 1 5370DAFA
P 5200 1950
F 0 "R1" V 5280 1950 50  0000 C CNN
F 1 "DNP" V 5200 1950 50  0000 C CNN
F 4 "DNP" V 5100 1950 60  0000 C CNN "Note"
	1    5200 1950
	0    -1   -1   0   
$EndComp
$Comp
L R R3
U 1 1 5370DB07
P 4850 3250
F 0 "R3" V 4930 3250 50  0000 C CNN
F 1 "0" V 4850 3250 50  0000 C CNN
F 4 "DNP" V 4750 3250 60  0000 C CNN "Note"
	1    4850 3250
	-1   0    0    1   
$EndComp
$Comp
L GND #PWR01
U 1 1 5370DB21
P 4850 3600
F 0 "#PWR01" H 4850 3600 30  0001 C CNN
F 1 "GND" H 4850 3530 30  0001 C CNN
	1    4850 3600
	1    0    0    -1  
$EndComp
$Comp
L GND #PWR02
U 1 1 5370DB27
P 5550 1950
F 0 "#PWR02" H 5550 1950 30  0001 C CNN
F 1 "GND" H 5550 1880 30  0001 C CNN
	1    5550 1950
	0    -1   -1   0   
$EndComp
Wire Wire Line
	5550 1950 5450 1950
Wire Wire Line
	4950 1950 4850 1950
Connection ~ 4850 1950
Wire Wire Line
	4850 2450 4850 3000
Wire Wire Line
	4850 3600 4850 3500
Wire Wire Line
	4150 2250 4150 2550
Wire Wire Line
	4150 2550 4250 2550
Wire Wire Line
	4750 2550 4850 2550
Connection ~ 4850 2550
$Comp
L CONN_2 P2
U 1 1 53B03152
P 4950 1100
F 0 "P2" V 4900 1100 40  0000 C CNN
F 1 "ANTENNA" V 5000 1100 40  0000 C CNN
	1    4950 1100
	0    -1   -1   0   
$EndComp
$Comp
L GND #PWR03
U 1 1 53B0316B
P 5050 1550
F 0 "#PWR03" H 5050 1550 30  0001 C CNN
F 1 "GND" H 5050 1480 30  0001 C CNN
	1    5050 1550
	1    0    0    -1  
$EndComp
Wire Wire Line
	5050 1450 5050 1550
$Comp
L CONN_2 P1
U 1 1 53B03194
P 3400 2350
F 0 "P1" V 3350 2350 40  0000 C CNN
F 1 "INPUT" V 3450 2350 40  0000 C CNN
	1    3400 2350
	-1   0    0    1   
$EndComp
$Comp
L GND #PWR04
U 1 1 53B031B0
P 3850 3150
F 0 "#PWR04" H 3850 3150 30  0001 C CNN
F 1 "GND" H 3850 3080 30  0001 C CNN
	1    3850 3150
	1    0    0    -1  
$EndComp
Wire Wire Line
	3750 2450 3850 2450
Wire Wire Line
	3850 2450 3850 2550
$Comp
L R R4
U 1 1 53B032AD
P 3850 2800
F 0 "R4" V 3930 2800 50  0000 C CNN
F 1 "0" V 3850 2800 50  0000 C CNN
F 4 "DNP" V 3750 2800 60  0000 C CNN "Note"
	1    3850 2800
	-1   0    0    1   
$EndComp
Wire Wire Line
	3850 3050 3850 3150
Text Label 4850 1800 1    40   ~ 0
DRAIN
Text Label 3800 2250 0    40   ~ 0
GATE
Text Label 4850 2850 1    40   ~ 0
SOURCE
Text Notes 4100 3000 0    40   ~ 0
Cut trace to install\nresistor on R3 or R4.
Text Notes 5150 2400 0    40   ~ 0
For inputs greater than\n6V, a different MOSFET\nmay be required.
Wire Notes Line
	3850 2550 4000 2550
Wire Notes Line
	4000 2550 4000 3050
Wire Notes Line
	4000 3050 3850 3050
Wire Notes Line
	4850 3000 5000 3000
Wire Notes Line
	5000 3000 5000 3500
Wire Notes Line
	5000 3500 4850 3500
Text Notes 5250 1100 0    40   ~ 0
Connect dipole to pins 1 and 2\nor connect monopole to pin 1.
Text Notes 2400 2050 0    40   ~ 0
Connect target signal to pin 2\nand target ground to pin 1.
$EndSCHEMATC
