<?php

namespace Freelancehunt\Validators;

/**
 * Class CreditCard.
 * Validates popular debit and credit cards' numbers against regular expressions and Luhn algorithm.
 * Also validates the CVC and the expiration date.
 *
 * @package   Freelancehunt\CreditCardValidator
 *
 * @author    Ignacio de Tomás <nacho@inacho.es>
 * @copyright 2014 Ignacio de Tomás (http://inacho.es)
 */
class CreditCard
{
    const TYPE_AMEX               = 'amex';
    const TYPE_DANKORT            = 'dankort';
    const TYPE_DINERS_CLUB        = 'diners_club';
    const TYPE_DISCOVER           = 'discover';
    const TYPE_FORBRUGSFORENINGEN = 'forbrugsforeningen';
    const TYPE_JCB                = 'jcb';
    const TYPE_MAESTRO            = 'maestro';
    const TYPE_MASTERCARD         = 'mastercard';
    const TYPE_UNIONPAY           = 'unionpay';
    const TYPE_VISA               = 'visa';
    const TYPE_VISA_ELECTRON      = 'visa_electron';
    const TYPE_ELO                = 'elo';

    protected static $cards = [
        // Debit cards must come first, since they have more specific patterns than their credit-card equivalents.

        self::TYPE_VISA_ELECTRON      => [
            'type'      => self::TYPE_VISA_ELECTRON,
            'pattern'   => '/^4(026|17500|405|508|844|91[37])/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_MAESTRO            => [
            'type'      => self::TYPE_MAESTRO,
            'pattern'   => '/^(5(018|0[23]|[68])|6(39|7))/',
            'length'    => [12, 13, 14, 15, 16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_FORBRUGSFORENINGEN => [
            'type'      => self::TYPE_FORBRUGSFORENINGEN,
            'pattern'   => '/^600/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_DANKORT            => [
            'type'      => self::TYPE_DANKORT,
            'pattern'   => '/^5019/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        // Credit cards
        self::TYPE_VISA               => [
            'type'      => self::TYPE_VISA,
            'pattern'   => '/^4/',
            'length'    => [13, 16, 19],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_MASTERCARD         => [
            'type'      => self::TYPE_MASTERCARD,
            'pattern'   => '/^(5[0-5]|(222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720))/', // 2221-2720, 51-55
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_AMEX               => [
            'type'      => self::TYPE_AMEX,
            'pattern'   => '/^3[47]/',
            'format'    => '/(\d{1,4})(\d{1,6})?(\d{1,5})?/',
            'length'    => [15],
            'cvcLength' => [3, 4],
            'luhn'      => true,
        ],
        self::TYPE_DINERS_CLUB        => [
            'type'      => self::TYPE_DINERS_CLUB,
            'pattern'   => '/^3[0689]/',
            'length'    => [14],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_DISCOVER           => [
            'type'      => self::TYPE_DISCOVER,
            'pattern'   => '/^6([045]|22)/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_UNIONPAY           => [
            'type'      => self::TYPE_UNIONPAY,
            'pattern'   => '/^(62|88)/',
            'length'    => [16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn'      => false,
        ],
        self::TYPE_JCB                => [
            'type'      => self::TYPE_JCB,
            'pattern'   => '/^35/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
        self::TYPE_ELO                => [
            'type'      => self::TYPE_ELO,
            'pattern'   => '/^4011|438935|45(1416|76)|50(4175|6699|67|90[4-7])|63(6297|6368)/',
            'length'    => [16],
            'cvcLength' => [3],
            'luhn'      => true,
        ],
    ];

    public static function validCreditCard($number, $types = [])
    {
        $ret = [
            'valid'  => false,
            'number' => '',
            'type'   => '',
        ];

        if (!is_array($types)) {
            $types = [$types];
        }

        // Strip non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        if (empty($types)) {
            $types[] = self::creditCardType($number);
        }

        foreach ($types as $type) {
            if (isset(self::$cards[$type]) && self::validCard($number, $type)) {
                return [
                    'valid'  => true,
                    'number' => $number,
                    'type'   => $type,
                ];
            }
        }

        return $ret;
    }

    public static function validCvc($cvc, $type)
    {
        return (ctype_digit($cvc) && array_key_exists($type, self::$cards) && self::validCvcLength($cvc, $type));
    }

    public static function validDate($year, $month)
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        if (!preg_match('/^20\d\d$/', $year)) {
            return false;
        }

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
            return false;
        }

        // past date
        if ($year < date('Y') || $year == date('Y') && $month < date('m')) {
            return false;
        }

        return true;
    }

    protected static function creditCardType($number)
    {
        foreach (self::$cards as $type => $card) {
            if (preg_match($card['pattern'], $number)) {
                return $type;
            }
        }

        return '';
    }

    protected static function validCard($number, $type)
    {
        return (self::validPattern($number, $type) && self::validLength($number, $type) && self::validLuhn($number, $type));
    }

    protected static function validPattern($number, $type)
    {
        return preg_match(self::$cards[$type]['pattern'], $number);
    }

    protected static function validLength($number, $type)
    {
        foreach (self::$cards[$type]['length'] as $length) {
            if (strlen($number) == $length) {
                return true;
            }
        }

        return false;
    }

    protected static function validCvcLength($cvc, $type)
    {
        foreach (self::$cards[$type]['cvcLength'] as $length) {
            if (strlen($cvc) == $length) {
                return true;
            }
        }

        return false;
    }

    protected static function validLuhn($number, $type)
    {
        if (!self::$cards[$type]['luhn']) {
            return true;
        } else {
            return self::luhnCheck($number);
        }
    }

    protected static function luhnCheck($number)
    {
        $checksum = 0;
        for ($i = (2 - (strlen($number) % 2)); $i <= strlen($number); $i += 2) {
            $checksum += (int) substr($number, $i - 1, 1);
        }

        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i = (strlen($number) % 2) + 1; $i < strlen($number); $i += 2) {
            $digit = (int) substr($number, $i - 1, 1);
            $digit *= 2;
            if ($digit < 10) {
                $checksum += $digit;
            } else {
                $checksum += ($digit - 9);
            }
        }

        if (($checksum % 10) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
