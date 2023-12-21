<?php
/**
 * File: Formatters.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\Utils;


use App\GoodsPackage;

class Formatters
{
    /**
     * @param $measure
     * @param float $amount
     * @return string
     */
    public static function getHumanMeasure($measure, $amount = 1.00)
    {
        switch ($measure) {
            case GoodsPackage::MEASURE_ML:
                return 'мл.';

            case GoodsPackage::MEASURE_GRAM:
                return 'г.';

            case GoodsPackage::MEASURE_PIECE:
                return 'шт.';

            default:
                throw new \InvalidArgumentException('Unknown measure type.');
        }
    }

    /**
     * @param $amount
     * @param null $measure
     * @return string
     */
    public static function getHumanAmount($amount, $measure = null)
    {
        return trim_zeros(number_format($amount, 4, '.', ' '));
    }

    /**
     * @param $amount
     * @param $measure
     * @return string
     */
    public static function getHumanWeight($amount, $measure)
    {
        return self::getHumanAmount($amount, $measure) . ' ' . self::getHumanMeasure($measure, $amount);
    }

    /**
     * @param $price
     * @param $currency
     * @return string
     */
    public static function getHumanPrice($price, $currency)
    {
        return trim_zeros(number_format(round_price($price, $currency), 6, '.', ' ')) . ' ' . self::getHumanCurrency($currency);
    }

    /**
     * Return currency in human readable format
     * @param $currency
     * @return string
     */
    public static function getHumanCurrency($currency)
    {
        switch ($currency) {
            case BitcoinUtils::CURRENCY_RUB:
                return 'руб.';

            case BitcoinUtils::CURRENCY_USD:
                return '$';

            case BitcoinUtils::CURRENCY_BTC:
                return 'BTC';

            default:
                throw new \InvalidArgumentException('Unknown currency');
        }
    }

    public static function formatMessage($text, $escape = true)
    {
        if($escape) {
            $text = e(trim($text));
        }

        $text = nl2br($text);

        preg_match_all('/(-----BEGIN PGP MESSAGE-----.*?-----END PGP MESSAGE-----)/isU', $text, $pgpMessages);
        foreach ($pgpMessages[1] as $pgpMessage) {
            $formattedPgpMessage = str_replace('<br />', '', $pgpMessage);
            $formattedPgpMessage = '<pre style="font-size: 9px; margin-bottom: 0">' . $formattedPgpMessage . '</pre>';
            $text = str_replace($pgpMessage, $formattedPgpMessage, $text);
        }

        preg_match_all('/(-----BEGIN PGP SIGNED MESSAGE-----.*?-----END PGP SIGNATURE-----)/isU', $text, $pgpSigMessages);
        foreach ($pgpSigMessages[1] as $pgpSigMessage) {
            $formattedPgpMessage = str_replace('<br />', '', $pgpSigMessage);
            $formattedPgpMessage = '<pre style="font-size: 9px; margin-bottom: 0">' . $formattedPgpMessage . '</pre>';
            $text = str_replace($pgpSigMessage, $formattedPgpMessage, $text);
        }

        preg_match_all('/(-----BEGIN PGP PUBLIC KEY BLOCK-----.*?-----END PGP PUBLIC KEY BLOCK-----)/isU', $text, $pgpPubKeys);
        foreach ($pgpPubKeys[1] as $pgpPubKey) {
            $formattedPgpMessage = str_replace('<br />', '', $pgpPubKey);
            $formattedPgpMessage = '<pre style="font-size: 9px; margin-bottom: 0">' . $formattedPgpMessage . '</pre>';
            $text = str_replace($pgpPubKey, $formattedPgpMessage, $text);
        }

        $text = str_replace('{url}', url('/'), $text);

        return $text;
    }

    public static function formatReview($text)
    {
        $text = nl2br(e(trim($text)));
        return $text;
    }
}