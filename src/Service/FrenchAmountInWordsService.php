<?php

namespace App\Service;

class FrenchAmountInWordsService
{
    public function toDinarsAndMillimes(float $amount): string
    {
        $amount = round(max(0, $amount), 3);

        $dinarPart = (int) floor($amount);
        $millimePart = (int) round(($amount - $dinarPart) * 1000);

        if ($millimePart === 1000) {
            $dinarPart += 1;
            $millimePart = 0;
        }

        $dinarText = $this->numberToWords($dinarPart);
        $labelDinar = $dinarPart > 1 ? 'dinars' : 'dinar';

        if ($millimePart === 0) {
            return sprintf('%s %s', $dinarText, $labelDinar);
        }

        $millimeText = $this->numberToWords($millimePart);
        $labelMillime = $millimePart > 1 ? 'millimes' : 'millime';

        return sprintf('%s %s et %s %s', $dinarText, $labelDinar, $millimeText, $labelMillime);
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $parts = [];

        $billions = intdiv($number, 1000000000);
        if ($billions > 0) {
            $parts[] = $billions === 1 ? 'un milliard' : $this->belowOneThousand($billions) . ' milliards';
            $number %= 1000000000;
        }

        $millions = intdiv($number, 1000000);
        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'un million' : $this->belowOneThousand($millions) . ' millions';
            $number %= 1000000;
        }

        $thousands = intdiv($number, 1000);
        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'mille' : $this->belowOneThousand($thousands) . ' mille';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = $this->belowOneThousand($number);
        }

        return trim(implode(' ', $parts));
    }

    private function belowOneThousand(int $number): string
    {
        $units = [
            0 => 'zero',
            1 => 'un',
            2 => 'deux',
            3 => 'trois',
            4 => 'quatre',
            5 => 'cinq',
            6 => 'six',
            7 => 'sept',
            8 => 'huit',
            9 => 'neuf',
            10 => 'dix',
            11 => 'onze',
            12 => 'douze',
            13 => 'treize',
            14 => 'quatorze',
            15 => 'quinze',
            16 => 'seize',
        ];

        $text = '';
        $hundreds = intdiv($number, 100);
        $rest = $number % 100;

        if ($hundreds > 0) {
            if ($hundreds === 1) {
                $text = 'cent';
            } else {
                $text = $units[$hundreds] . ' cent';
            }

            if ($rest === 0 && $hundreds > 1) {
                $text .= 's';
            }
        }

        if ($rest > 0) {
            if ($text !== '') {
                $text .= ' ';
            }

            if ($rest <= 16) {
                $text .= $units[$rest];
            } elseif ($rest < 20) {
                $text .= 'dix-' . $units[$rest - 10];
            } elseif ($rest < 70) {
                $tens = intdiv($rest, 10);
                $unit = $rest % 10;
                $tensMap = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];
                $text .= $tensMap[$tens];
                if ($unit === 1) {
                    $text .= ' et un';
                } elseif ($unit > 1) {
                    $text .= '-' . $units[$unit];
                }
            } elseif ($rest < 80) {
                $text .= 'soixante';
                $unit = $rest - 60;
                if ($unit === 11) {
                    $text .= ' et onze';
                } elseif ($unit > 0) {
                    $text .= '-' . $this->belowOneThousand($unit);
                }
            } else {
                $text .= 'quatre-vingt';
                $unit = $rest - 80;
                if ($unit === 0) {
                    $text .= 's';
                } elseif ($unit > 0) {
                    $text .= '-' . $this->belowOneThousand($unit);
                }
            }
        }

        return $text;
    }
}
