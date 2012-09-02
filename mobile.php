<?php

/* mobile detection

  thanks to the great code found in the mobble plugin:

  http://www.toggle.uk.com/journal/mobble
  Copyright (c) 2011 toggle labs ltd <http://www.toggle.uk.com>

  This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT
	HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,
	INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR
	FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE
	OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,
	COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.COPYRIGHT HOLDERS WILL NOT
	BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL
	DAMAGES ARISING OUT OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://gnu.org/licenses/>.

*/

if (!class_exists('WPPBMobile')) {

  class WPPBMobile {
  
    protected $useragent = false;
    
    //PHP 4 constructor
    function WPPBMobile() {
        $this->__construct();
    }

    /*
     * Constructor used to set some initial vars.
     * If the subclass makes use of a constructor, make sure the subclass calls parent::__construct()
     */
    function __construct() {
      $this->useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
    }

    /***************************************************************
    * Function is_iphone
    * Detect the iPhone
    ***************************************************************/
    function is_iphone() {
      return(preg_match('/iphone/i',$this->useragent));
    }

    /***************************************************************
    * Function is_ipad
    * Detect the iPad
    ***************************************************************/
    function is_ipad() {
      return(preg_match('/ipad/i',$this->useragent));
    }

    /***************************************************************
    * Function is_ipod
    * Detect the iPod, most likely the iPod touch
    ***************************************************************/
    function is_ipod() {
      return(preg_match('/ipod/i',$this->useragent));
    }

    /***************************************************************
    * Function is_android
    * Detect an android device. They *SHOULD* all behave the same
    ***************************************************************/
    function is_android() {
      return(preg_match('/android/i',$this->useragent));
    }

    /***************************************************************
    * Function is_blackberry
    * Detect a blackberry device 
    ***************************************************************/
    function is_blackberry() {
      return(preg_match('/blackberry/i',$this->useragent));
    }

    /***************************************************************
    * Function is_opera_mobile
    * Detect both Opera Mini and hopfully Opera Mobile as well
    ***************************************************************/
    function is_opera_mobile() {
      return(preg_match('/opera mini/i',$this->useragent));
    }

    /***************************************************************
    * Function is_palm
    * Detect a webOS device such as Pre and Pixi
    ***************************************************************/
    function is_palm() {
      return(preg_match('/webOS/i', $this->useragent));
    }

    /***************************************************************
    * Function is_symbian
    * Detect a symbian device, most likely a nokia smartphone
    ***************************************************************/
    function is_symbian() {
      return(preg_match('/Series60/i', $this->useragent) || preg_match('/Symbian/i', $this->useragent));
    }

    /***************************************************************
    * Function is_windows_mobile
    * Detect a windows smartphone
    ***************************************************************/
    function is_windows_mobile() {
      return(preg_match('/WM5/i', $this->useragent) || preg_match('/WindowsMobile/i', $this->useragent));
    }

    /***************************************************************
    * Function is_lg
    * Detect an LG phone
    ***************************************************************/
    function is_lg() {
      return(preg_match('/LG/i', $this->useragent));
    }

    /***************************************************************
    * Function is_motorola
    * Detect a Motorola phone
    ***************************************************************/
    function is_motorola() {
      return(preg_match('/\ Droid/i', $this->useragent) || preg_match('/XT720/i', $this->useragent) || preg_match('/MOT-/i', $this->useragent) || preg_match('/MIB/i', $this->useragent));
    }

    /***************************************************************
    * Function is_nokia
    * Detect a Nokia phone
    ***************************************************************/
    function is_nokia() {
      return(preg_match('/Series60/i', $this->useragent) || preg_match('/Symbian/i', $this->useragent) || preg_match('/Nokia/i', $this->useragent));
    }

    /***************************************************************
    * Function is_samsung
    * Detect a Samsung phone
    ***************************************************************/
    function is_samsung() {
      return(preg_match('/Samsung/i', $this->useragent));
    }

    /***************************************************************
    * Function is_samsung_galaxy_tab
    * Detect the Galaxy tab
    ***************************************************************/
    function is_samsung_galaxy_tab() {
      return(preg_match('/SPH-P100/i', $this->useragent));
    }

    /***************************************************************
    * Function is_sony_ericsson
    * Detect a Sony Ericsson
    ***************************************************************/
    function is_sony_ericsson() {
      return(preg_match('/SonyEricsson/i', $this->useragent));
    }

    /***************************************************************
    * Function is_nintendo
    * Detect a Nintendo DS or DSi
    ***************************************************************/
    function is_nintendo() {
      return(preg_match('/Nintendo DSi/i', $this->useragent) || preg_match('/Nintendo DS/i', $this->useragent));
    }

    /***************************************************************
    * Function is_handheld
    * Wrapper function for detecting ANY handheld device
    ***************************************************************/
    function is_handheld() {
      return($this->is_iphone() || $this->is_ipad() || $this->is_ipod() || $this->is_android() || $this->is_blackberry() || $this->is_opera_mobile() || $this->is_palm() || $this->is_symbian() || $this->is_windows_mobile() || $this->is_lg() || $this->is_motorola() || $this->is_nokia() || $this->is_samsung() || $this->is_samsung_galaxy_tab() || $this->is_sony_ericsson() || $this->is_nintendo());
    }

    /***************************************************************
    * Function is_mobile
    * Wrapper function for detecting ANY mobile phone device
    ***************************************************************/
    function is_mobile() {
      if ($this->is_tablet()) { return false; }  // this catches the problem where an Android device may also be a tablet device
      return($this->is_iphone() || $this->is_ipod() || $this->is_android() || $this->is_blackberry() || $this->is_opera_mobile() || $this->is_palm() || $this->is_symbian() || $this->is_windows_mobile() || $this->is_lg() || $this->is_motorola() || $this->is_nokia() || $this->is_samsung() || $this->is_sony_ericsson() || $this->is_nintendo());
    }

    /***************************************************************
    * Function is_ios
    * Wrapper function for detecting ANY iOS/Apple device
    ***************************************************************/
    function is_ios() {
      return($this->is_iphone() || $this->is_ipad() || $this->is_ipod());
    }

    /***************************************************************
    * Function is_tablet
    * Wrapper function for detecting tablet devices (needs work)
    ***************************************************************/
    function is_tablet() {
      return($this->is_ipad() || $this->is_samsung_galaxy_tab());
    }
    
  }

}

if (!function_exists('is_mobile')) {
  
  function is_mobile() {
    $mobile = new WPPBMobile();
    return $mobile->is_mobile();
  }
  
}