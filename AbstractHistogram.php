<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
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

include_once "Math/Stats.php";

// constants for the selection of bins
define("HISTOGRAM_ALL_BINS", 1);
define("HISTOGRAM_MID_BINS", 2);
define("HISTOGRAM_LO_BINS", 3);
define("HISTOGRAM_HI_BINS", 4);

// histogram types
define("HISTOGRAM_SIMPLE", 1);
define("HISTOGRAM_CUMMULATIVE", 2);

/**
 * Abstract class defining common properties and methods for
 * the other histogram classes
 *
 * Originally this class was part of NumPHP (Numeric PHP package)
 *
 * @author  Jesus M. Castagnetto <jmcastagnetto@php.net>
 * @version 1.0
 * @access  public
 * @package Math_Histogram
 */

class Math_AbstractHistogram {/*{{{*/
    // properties /*{{{*/

    /**
     * The Math_Stats object
     * 
     * @access  private
     * @var object  Math_Stats
     * @see Math_Stats
     */
    var $_stats = null;
    /**
     * Mode for the calculation of statistics
     * 
     * @access  private
     * @var int one of STATS_BASIC or STATS_FULL
     * @see Math_Stats
     */
    var $_statsMode;
    /**
     * Array of bins
     * 
     * @access  private
     * @var array
     */
    var $_bins = array();
    /**
     * Number of bins to use in calculation
     *
     * @access  private
     * @var int
     */
    var $_nbins;
    /**
     * The lowest value to be used when generating the bins
     *
     * @access  private
     * @var float
     */
    var $_rangeLow;
    /**
     * The highest value to be used when generating the bins
     *
     * @access  private
     * @var float
     */
    var $_rangeHigh;
    /**
     * The data set after filtering using $this->_rangeHigh and
     * $this->_rangeLow
     *
     * @access  private
     * @var array
     * @see $_rangeLow
     * @see $_rangeHigh
     */
    var $_data = null;
    /**
     * The original data, count, etc.
     *
     * @access  private
     * @var array
     * @see $_data
     */
    var $_orig = array();

    /*}}}*/


    /**
     * Constructor
     * @param   optional    float   $rangeHigh   highest value to use for bin frequency calculation
     * @return  object  Math_Histogram
     *
     * @see setType()
     * @see setBinOptions()
     */
    function Math_AbstractHistogram($type=HISTOGRAM_SIMPLE) {
        $this->setType($type);
    }/*}}}*/

    /**
     * Sets the type of histogram to compute
     *
     * @access  public
     * @param   int $type one of HISTOGRAM_SIMPLE or HISTOGRAM_CUMMULATIVE
     * @return  mixed   boolean true on success, a PEAR_Error object otherwise
     */
    function setType($type) {/*{{{*/
        if ($type == HISTOGRAM_SIMPLE || $type == HISTOGRAM_CUMMULATIVE) {
            $this->_type = $type;
            return true;
        } else {
            return PEAR::raiseError("wrong histogram type requested");
        }
    }/*}}}*/

}/*}}}*/
// vim: ts=4:sw=4:et:
// vim6: fdl=0:

?>
