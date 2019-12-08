<?php

namespace Malanciault\Threelci\Core;

class Threel_lang extends \CI_lang {
  /**
   * Language line
   *
   * Fetches a single line of text from the language array
   *
   * @param string  $line   Language line key
   * @param bool  $log_errors Whether to log an error message if the line is not found
   * @return  string  Translation
   */
  public function line($line, $log_errors = TRUE)
  {
    $value = isset($this->language[$line]) ? $this->language[$line] : FALSE;

    return $value;
  }
}