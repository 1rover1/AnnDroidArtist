<?php

namespace Rover2011\AnnDroidArtist;

use timesplinter\gphpio\RPi;
use timesplinter\gphpio\GPIO;

class Motor
{
    // Basic motor attributes
    private $gpioPins;       // Comma separated list of 4 GPIO pins
    private $direction;      // 1 or -1 to indicate CW or CCW
    private $speed;          // high or low
    private $length;         // Length of arm

    private $gpioController;

    private $stepSequence;
    private $stepIndex;

    public function __construct($config = null)
    {
        // Setting properties via the constructor

        if (isset($config['direction'])) {
            $this->setDirection($config['direction']);
        }

        if (isset($config['gpio'])) {
            $this->setGpio($config['gpio']);
        }

        if (isset($config['speed'])) {
            $this->setSpeed($config['speed']);
        }

        if (isset($config['length'])) {
            $this->setLength($config['length']);
        }

        // Set the pin step sequence
        $this->stepSequence = [
            [1,0,0,1],
            [1,0,0,0],
            [1,1,0,0],
            [0,1,0,0],
            [0,1,1,0],
            [0,0,1,0],
            [0,0,1,1],
            [0,0,0,1]
        ];
        $this->stepIndex = 0;

        // Set up and turn off all pins

        $this->gpioController = new GPIO(new RPi());
    }

    public function setDirection($value)
    {
        if ($value === 'cw' || $value === 'ccw') {
            $this->direction = $value;
        } else {
            throw new \InvalidArgumentException('Direction needs to be cw or ccw');
        }
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function setGpio($value)
    {
        $regex = '/\d{1,2},\d{1,2},\d{1,2},\d{1,2}/';

        if (preg_match($regex, $value)) {
            $this->gpioPins = explode(',', $value);
        } else {
            throw new \InvalidArgumentException(
                'GPIO pins need to be provided as a comma separated list'
            );
        }
    }

    public function getGpio()
    {
        return implode(',', $this->gpioPins);
    }

    public function setSpeed($value)
    {
        if ($value === 'high' || $value === 'low') {
            $this->speed = $value;
        } else {
            throw new \InvalidArgumentException('Speed value of high or low expected.');
        }
    }

    public function getSpeed()
    {
        return $this->speed;
    }

    public function setLength($value)
    {
        if (is_numeric($value)) {
            $this->length = $value;
        } else {
            throw new \InvalidArgumentException('Length needs to be numeric');
        }
    }

    public function getLength()
    {
        return $this->length;
    }

    public function reset()
    {
        for ($pinCounter = 0; $pinCounter < 4; $pinCounter++) {

            // Export the pin if it's not already
            if($this->gpioController->isExported($this->gpioPins[$pinCounter]) === false) {
                $this->gpioController->export(
                    $this->gpioPins[$pinCounter],
                    GPIO::MODE_OUTPUT
                );
            }

            // Turn off the GPIO pin
            $this->gpioController->write(
                $this->gpioPins[$pinCounter],
                0
            );

        }
    }

    public function shorten()
    {
        // pull the pen closer to the Motor
        $this->changeLength(-1);
    }

    public function lengthen()
    {
        // push the pen farther away
        $this->changeLength(1);
    }


    private function changeLength($direction)
    {
        // turn on/off each of the pins
        for ($i = 0; $i < 4; $i++) {
            $this->gpioController->write(
                $this->gpioPins[$i],
                $this->stepSequence[$this->stepIndex][$i]
            );
        }

        $this->nextPinSequence($direction);
        if ($this->speed === 'high') {
            // high speed means two pin sequences
            $this->nextPinSequence($direction);
        }
    }

    private function nextPinSequence($direction)
    {
        if ($this->direction === 'cw') {
            $this->stepIndex += $direction;
        } else {
            $this->stepIndex -= $direction;
        }

        if ($this->stepIndex === -1) {
            $this->stepIndex = count($this->stepSequence) - 1;
        }

        if ($this->stepIndex === count($this->stepSequence)) {
            $this->stepIndex = 0;
        }
    }
}
