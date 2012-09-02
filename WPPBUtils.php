<?php
/*
 * Util / Helper functions
 * Found from around the web:
 *  stackoverflow
 *  http://abir.kumarkhali.com/content/php-class-convert-plural-singular-or-vice-versa-english
 */


if (!class_exists('WPPBUtils')) {

  class WPPBUtils {
  
    static function to_key($input) {
        return str_replace(" ", "_", strtolower($input));
    }

    static function to_title($input) {
        return ucwords(str_replace( array("-","_"), " ", $input));
    }

    /*
     * returns true if a needle can be found in a haystack
     */
    static function str_contains($haystack, $needle) {
        if (empty($haystack) || empty($needle))
            return false;

        $pos = strpos(strtolower($haystack), strtolower($needle));

        if ($pos === false)
            return false;
        else
            return true;
    }

    /**
     * starts_with
     * Tests if a text starts with an given string.
     *
     * @param     string
     * @param     string
     * @return    bool
     */
    static function starts_with($haystack, $needle){
        return strpos($haystack, $needle) === 0;
    }

    static function ends_with($haystack, $needle, $case=true)
    {
      $expectedPosition = strlen($haystack) - strlen($needle);

      if($case)
          return strrpos($haystack, $needle, 0) === $expectedPosition;

      return strripos($haystack, $needle, 0) === $expectedPosition;
    }
    
    /**
    * Pluralizes English nouns. Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
    *
    * @access public
    * @static
    * @param    string    $word    English noun to pluralize
    * @return string Plural noun
    */
    static function pluralize($word)
    {
        $plural = array(
        '/(quiz)$/i' => '1zes',
        '/^(ox)$/i' => '1en',
        '/([m|l])ouse$/i' => '1ice',
        '/(matr|vert|ind)ix|ex$/i' => '1ices',
        '/(x|ch|ss|sh)$/i' => '1es',
        '/([^aeiouy]|qu)ies$/i' => '1y',
        '/([^aeiouy]|qu)y$/i' => '1ies',
        '/(hive)$/i' => '1s',
        '/(?:([^f])fe|([lr])f)$/i' => '12ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '1a',
        '/(buffal|tomat)o$/i' => '1oes',
        '/(bu)s$/i' => '1ses',
        '/(alias|status)/i'=> '1es',
        '/(octop|vir)us$/i'=> '1i',
        '/(ax|test)is$/i'=> '1es',
        '/s$/i'=> 's',
        '/$/'=> 's');

        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

        $irregular = array(
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves');

        $lowercased_word = strtolower($word);

        foreach ($uncountable as $_uncountable){
            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
            }
        }

        foreach ($plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }
        return false;
    }
    
    /**
    * Singularizes English nouns. Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
    *
    * @access public
    * @static
    * @param    string    $word    English noun to singularize
    * @return string Singular noun.
    */
    static function singularize($word)
    {
        $singular = array (
        '/(quiz)zes$/i' => '\1',
        '/(matr)ices$/i' => '\1ix',
        '/(vert|ind)ices$/i' => '\1ex',
        '/^(ox)en/i' => '\1',
        '/(alias|status)es$/i' => '\1',
        '/([octop|vir])i$/i' => '\1us',
        '/(cris|ax|test)es$/i' => '\1is',
        '/(shoe)s$/i' => '\1',
        '/(o)es$/i' => '\1',
        '/(bus)es$/i' => '\1',
        '/([m|l])ice$/i' => '\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/(m)ovies$/i' => '\1ovie',
        '/(s)eries$/i' => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i' => '\1f',
        '/(tive)s$/i' => '\1',
        '/(hive)s$/i' => '\1',
        '/([^f])ves$/i' => '\1fe',
        '/(^analy)ses$/i' => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i' => '\1um',
        '/(n)ews$/i' => '\1ews',
        '/s$/i' => '',
        );

        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

        $irregular = array(
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves');

        $lowercased_word = strtolower($word);
        foreach ($uncountable as $_uncountable){
            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
                return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
            }
        }

        foreach ($singular as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }

        return $word;
    }
    
   /**
    * Converts number to its ordinal English form. Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
    *
    * This method converts 13 to 13th, 2 to 2nd ...
    *
    * @access public
    * @static
    * @param    integer    $number    Number to get its ordinal value
    * @return string Ordinal representation of given string.
    */
    static function ordinalize($number)
    {
        if (in_array(($number % 100),range(11,13))){
            return $number.'th';
        }else{
            switch (($number % 10)) {
                case 1:
                return $number.'st';
                break;
                case 2:
                return $number.'nd';
                break;
                case 3:
                return $number.'rd';
                default:
                return $number.'th';
                break;
            }
        }
    }

    /**
     * Replace all linebreaks with one whitespace.
     *
     * @access public
     * @param string $string
     *   The text to be processed.
     * @return string
     *   The given text without any linebreaks.
     */
    static function replace_newline($string) {
      return (string)str_replace(array("\r", "\r\n", "\n"), '', $string);
    }
  
    static function get_files_by_ext($extension, $path){
      $list = array(); //initialise a variable
      $dir_handle = @opendir($path) or die("Unable to open $path"); //attempt to open path
      while($file = readdir($dir_handle)){ //loop through all the files in the path
          if($file == "." || $file == ".."){continue;} //ignore these
          $filename = explode(".",$file); //seperate filename from extenstion
          $cnt = count($filename); $cnt--; $ext = $filename[$cnt]; //as above
          if(strtolower($ext) == strtolower($extension)){ //if the extension of the file matches the extension we are looking for...
              array_push($list, $file); //...then stick it onto the end of the list array
          }
      }
      if($list[0]){ //...if matches were found...
        return $list; //...return the array
      } else {//otherwise...
        return false;
      }
    }
  }
  
}