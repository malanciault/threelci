<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Readable print_r
 *
 * Prints human-readable information about a variable
 *
 * @access    public
 * @param    mixed
 */
if (!function_exists('printr')) {
    function printr($var)
    {
        $CI =& get_instance();
        echo '<pre>' . print_r($var, TRUE) . '</pre>';
    }
}

// ------------------------------------------------------------------------

/**
 * Readable var_dump
 *
 * Readable dump information about a variable
 *
 * @access    public
 * @param    mixed *
 */
if (!function_exists('vardump')) {
    function vardump($var)
    {
        $CI =& get_instance();
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}


/* End of file debug_helper.php */
/* Location: ./application/helpers/debug_helper.php */

function x($var, $key = false, $exit = false)
{
    echo '<pre>';
    if ($key) {
        echo "<b>$key</b>: ";
        if ($key == 'query')
            echo "<br />";
    }
    if (is_array($var) || is_object($var)) {
        echo "<br />";
        var_dump($var);
    } else {
        echo("$var");
    }
    echo '</pre>';
    if ($exit) die;
}

function xd($var, $key = false)
{
    x($var, $key, true);
}

function get_table_name($object)
{
    $class_name = strtolower(get_class($object));
    return str_replace('_model', '', $class_name);
}

function generate_seo_slug($string, $wordLimit = 0)
{
    $separator = '-';

    if ($wordLimit != 0) {
        $wordArr = explode(' ', $string);
        $string = implode(' ', array_slice($wordArr, 0, $wordLimit));
    }

    $quoteSeparator = preg_quote($separator, '#');

    $trans = array(
        '&.+?;' => '',
        '[^\w\d _-]' => '',
        '\s+' => $separator,
        '(' . $quoteSeparator . ')+' => $separator,
        'é' => 'e',
        'à' => 'a',
        'ù' => 'u',
        'â' => 'a',
        'ê' => 'e',
        'û' => 'u',
        'ô' => 'o',
    );

    $string = strip_tags($string);
    foreach ($trans as $key => $val) {
        $string = preg_replace('#' . $key . '#i' . (UTF8_ENABLED ? 'u' : ''), $val, $string);
    }

    $string = strtolower($string);

    return trim(trim($string, $separator));
}

function xselect()
{
    $CI =& get_instance();
    return $CI->db->get_compiled_select();
}

function array_depth(array $array)
{
    $max_depth = 1;

    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = array_depth($value) + 1;

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }
    }
    return $max_depth;
}

function now_str()
{
    return date("Y-m-d H:i:s");
}

function trim_array_elements(&$array)
{
    foreach ($array as &$v) {
        $v = trim($v);
    }
}

function format_dollar($number, $decimal = 2)
{
    $ci =& get_instance();
    $number = str_replace(',', '.', $number);
    if (CURRENT_LANG == 'french') {
        return number_format($number, $decimal, $ci->i18n->decimal(), ' ') . ' ' . $ci->i18n->dollar();
    } else {
        return $ci->i18n->dollar() . ' ' . number_format($number, $decimal, $ci->i18n->decimal(), ' ');
    }

}

function get_currency()
{
    $ci =& get_instance();
    return $ci->i18n->dollar();
}

function format_decimal($value)
{
    $value = str_replace(',', '.', $value);
    $ci =& get_instance();
    return number_format($value, 2, $ci->i18n->decimal(), ' ');
}

function format_thousand($value)
{
    $value = str_replace(',', '.', $value);
    $ci =& get_instance();
    return number_format($value, 0, '', $ci->i18n->thousand());
}

function __($str)
{
    $ci =& get_instance();
    return $ci->lang->line($str) ? $ci->lang->line($str) : $str;
}

function redirect_login()
{
    redirect(base_url('auth/login?url=' . urlencode(current_url())));
}

function limit_text($text, $limit = 20)
{
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}

function generateRandomString($length = 10)
{
    $characters = '23456789abcdefghkmnpqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function makeNumeric($str)
{
    return str_replace(',', '.', $str);
}

function main_url($path = '')
{
    $ci =& get_instance();
    if (CURRENT_LANG == 'english')
        $path = 'en/' . $path;
    return MAIN_URL . $path;

}

function planetair_transform($str)
{
    switch ($str) {
        case 'business':
            return __('Affaires');
            break;
        case 'first':
            return __('Première');
            break;
        case 'economic':
            return __('Économique');
            break;
        case 'voiture-sous-compacte':
            return __('Voiture sous-compacte (Ex: Smart for two)');
            break;
        case 'voiture-compacte':
            return __('Voiture compacte (Ex: Volkswagen Golf)');
            break;
        case 'voiture-intermediaire':
            return __('Voiture intermédiaire (Ex: Ford Fusion)');
            break;
        case 'voiture-berline':
            return __('Voiture berline (Ex: Toyota Avalon)');
            break;
        case 'voiture-berline-de-luxe':
            return __('Voiture berline de luxe');
            break;
        case 'voiture-coupee-de-luxe':
            return __('Voiture coupée de luxe (Ex: Bentley Continental GT)');
            break;
        case 'voiture-sport':
            return __('Voiture sport (Ex: Mustang)');
            break;
        case 'voiture-tout-terrain':
            return __('Véhicule tout terrain 4x4 (Ex: Grand Cherokee)');
            break;
        case 'voiture-mini-fourgonnette':
            return __('Mini-fourgonnette (Ex: Mazda 5)');
            break;
        case 'gasoline':
            return __('Essance');
            break;
        case 'diesel':
            return __('Diesel');
            break;
    }
}

function show_not_found()
{
    redirect('404');
}

function get_array_value($array, $value)
{
    if (isset($array[$value]))
        return $array[$value];
    else
        return false;
}