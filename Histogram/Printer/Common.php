<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jesus M. Castagnetto <jmcastagnetto@php.net>                |
// +----------------------------------------------------------------------+
//
// $Id$
//
// Last change: Tuesday 2002-05-21 19:58:48 PDT.
//

require_once 'Math/Histogram.php';

/**
 * Base class for histogram printer objects
 *
 * @author  Jesus M. Castagnetto <jmcastagnetto@php.net>
 * @version 0.9.1beta
 * @access  public
 * @package Math_Histogram
 */
class Math_Histogram_Printer_Common {/*{{{*/

    // properties /*{{{*/

    /**
     * An associative array of options for the printer object
     *
     * @access private
     * @var array
     */
    var $_options;

    /**
     * The Math_Histogram object
     *
     * @access private
     * @var object Math_Histogram
     * @see Math_Histogram
     */
    var $_hist;

    // /*}}}*/

    /**
     * Constructor
     *
     * @access  public
     * @param   optional array $options     An associative array of printer options
     * @param   optional object Math_Histogram $hist    A Math_Histogram object
     * @return  object  Math_Histogram_Printer_Common
     */
    function Math_Histogram_Printer_Common($hist = null, $options = null) {/*{{{*/
        $this->setHistogram($hist);
        $this->setOptions($options);
    }/*}}}*/

    /**
     * Sets the printer options
     * 
     * @access  public
     * @param   array $options     An associative array of printer options
     *              Common options:
     *              'useHTTPHeaders' (default = false), whether to output HTTP headers when using printOutput()
     *              'outputStatistics' (default = false), whether to include histogram statistics when generating the output
     * @return  boolean TRUE on success, FALSE otherwise
     */
    function setOptions($options) {/*{{{*/
        if (!is_array($options)) {
            $this->_options = null;
            return false;
        } else {
            $this->_options = $options;
            return true;
        }
        if (!array_key_exists('useHTTPHeaders', $this->_options)) {
            $this->_options['useHTTPHeaders'] = false;
        }
        if (!array_key_exists('outputStatistics', $this->_options)) {
            $this->_options['outputStatistics'] = false;
        }
    }/*}}}*/

    /**
     * Sets the Math_Histogram object to plot
     * 
     * @access  public
     * @param   object Math_Histogram $hist A Math_Histogram instance
     * @return  boolean TRUE on success, FALSE otherwise
     */
    function setHistogram(&$hist) {/*{{{*/
        if (Math_Histogram::isValidHistogram($hist)) {
            $this->_hist = &$hist;
            return true;
        } else {
            $this->_hist = null;
            return false;
        }
    }/*}}}*/

    // override this method in child classes
    /**
     * Returns a (binary safe) string representation of a Histogram plot
     *
     * @access public
     * @return string|PEAR_Error A string on succcess, a PEAR_Error otherwise
     */
    function generateOutput() { /*{{{*/
        return PEAR::raiseError('Unimplemented method');
    }/*}}}*/

    // override this method in child classes
    /**
     * Prints out a graphic representation of a Histogram
     *
     * @access public
     * @return boolean|PEAR_Error TRUE on success, a PEAR_Error otherwise
     */
    function printOutput() {/*{{{*/
        return PEAR::raiseError('Unimplemented method');
    }/*}}}*/

    // override this method in child classes
    /**
     * Static method to print out a graphic representation of a Histogram
     *
     * @static
     * @access public
     * @param object Math_Histogram $hist A Math_Histogram instance
     * @param array $options An array of options for the printer object
     * @return boolean|PEAR_Error TRUE on success, a PEAR_Error otherwise
     */
    function printHistogram(&$hist, $options = array()) {/*{{{*/
        return PEAR::raiseError('Unimplemented method');
    }/*}}}*/

    /**
     * Utility method to do static printing
     *
     * @static
     * @access private
     * @param object $printer An instance of a Math_Histogram_Printer_* class
     * @param object Math_Histogram $hist A Math_Histogram instance
     * @param array $options An array of options for the printer object
     * @return boolean|PEAR_Error TRUE on success, a PEAR_Error otherwise
     */
    function _doStaticPrint(&$printer, &$hist, $options) {/*{{{*/
        if (!$printer->setHistogram($hist)) {
            return PEAR::raiseError('Not a valid Math_Histogram object');
        }
        if (!$printer->setOptions($options)) {
            return PEAR::raiseError('Expecting an associative array of options');
        }
        // try to plot, clean up object, and return
        $err = $printer->printOutput();
        unset($printer);
        return $err;
    }/*}}}*/
    
}/*}}}*/

// vim: ts=4:sw=4:et:
// vim6: fdl=1:

?>
