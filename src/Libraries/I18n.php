<?php

namespace Malanciault\Threelci\Libraries;

class i18n
{
    private $available_languages = array('french', 'english');
    private $inverse_language = array(
        'french' => 'english',
        'english' => 'french'
    );
    private $short = array(
        'french' => 'FR',
        'english' => 'EN'
    );
    private $current_lang;

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();

        $lang = isset($_GET['lang']) ? $_GET['lang'] : $this->ci->session->site_lang;

        if (isset($this->short[$lang])) {
            $this->current_lang = $lang;
        } else {
            $this->current_lang = 'french';
        }
        $this->ci->config->set_item('language', $lang);
        $this->ci->session->set_userdata('site_lang', $this->current_lang);
    }

    public function current()
    {
        return $this->current_lang;
    }

    public function switch($new_language)
    {
        if (!in_array($new_language, $this->available_languages))
            $new_language = 'french';

        $this->ci->session->set_userdata('site_lang', $new_language);
        redirect(site_url());
    }

    public function get_inversed_language($current_lang = false)
    {
        if (!$current_lang)
            $current_lang = $this->ci->session->site_lang;
        return $this->inverse_language[$current_lang];
    }

    public function get_inversed_short($current_lang = false)
    {
        $ret = $this->get_inversed_language($current_lang);
        return $this->short[$ret];
    }

    public function get_current_short($lower = false)
    {
        return $lower ? strtolower($this->short[$this->current()]) : $this->short[$this->current()];
    }

    public function decimal()
    {
        if ($this->current_lang == 'french')
            return ',';
        else
            return '.';
    }

    public function thousand()
    {
        if ($this->current_lang == 'french')
            return ' ';
        else
            return ',';
    }

    public function dollar()
    {
        if ($this->current_lang == 'french')
            return '$ CA';
        else
            return 'Can$';
    }
}