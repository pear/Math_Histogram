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
// Last change: Tuesday 2002-05-21 18:52:58 PDT.
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
     * Number(s) of bins to use in calculation
     *
     * @access  private
     * @var mixed
     */
    var $_nbins;
    /**
     * The lowest value(s) to be used when generating the bins
     *
     * @access  private
     * @var mixed
     */
    var $_rangeLow;
    /**
     * The highest value(s) to be used when generating the bins
     *
     * @access  private
     * @var mixed
     */
    var $_rangeHigh;
    /**
     * The data set 
     *
     * @access  private
     * @var array
     * @see $_rangeLow
     * @see $_rangeHigh
     */
    var $_data = null;

    /*}}}*/


    /**
     * Constructor
     * @param   optional    float   $rangeHigh   highest value to use for bin frequency calculation
     * @return  object  Math_Histogram
     *
     * @see setType()
     * @see setBinOptions()
     */
    function Math_AbstractHistogram($type=HISTOGRAM_SIMPLE) {/*{{{*/
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

    /**
     * Sets the binning options
     * 
     * @access  public
     * @param   array   $binOptions associative array of bin options
     * @return  mixed   true on succcess, a PEAR_Error object otherwise 
     */
	function setBinOptions($binOptions) {/*{{{*/
		if (!is_array($binOptions))
			return PEAR::raiseError("incorrect options array");
		$this->_rangeLow = $binOptions["low"];
		$this->_rangeHigh = $binOptions["high"];
		$this->_nbins = $binOptions["nbins"];
        return true;
	}/*}}}*/

    /**
     * Abstract method to set data. Needs to be implemented in each subclass
     *
     * @access  public
     * @param   array   $data
     */
    function setData($data) {/*{{{*/
    }/*}}}*/

    /**
     * Returns the array of data set using setData()
     *
     * @access  public
     * @return  mixed   a numerical array on success, a PEAR_Error object otherwise
     *
     * @see setData()
     */
    function getData() {/*{{{*/
        if (is_null($this->_data))
            return PEAR::raiseError("data has not been set");
        else
            return $this->_data;
    }/*}}}*/

    /**
     * Returns the array of data used to calculate the histogram,
     * i.e. the data that was inside the range specified for the
     * histogram bins
     *
     * @access  public
     * @return  mixed   a numerical array on success, a PEAR_Error object otherwise
     *
     * @see setData()
     */
    function getHistogramData() {/*{{{*/
        if (is_null($this->_data))
            return PEAR::raiseError("data has not been set");
        else
            return $this->_histogramData();
    }/*}}}*/

    /**
     * Returns bins and frequencies for the histogram data set
     *
     * @access  public
     * @param   optional    int $mode   one of HISTOGRAM_ALL_BINS, HISTOGRAM_LO_BINS, HISTOGRAM_MID_BINS, or HISTOGRAM_HI_BINS 
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getBins($mode = HISTOGRAM_ALL_BINS) {/*{{{*/
        if (empty($this->_bins))
            return PEAR::raiseError("histogram has not been calculated");
        switch ($mode) {
            case HISTOGRAM_ALL_BINS :
                return $this->_bins;
                break;
            case HISTOGRAM_MID_BINS :
            case HISTOGRAM_LO_BINS :
            case HISTOGRAM_HI_BINS :
                return $this->_filterBins($mode);
                break;
            default :
                return PEAR::raiseError("incorrect mode for bins");
        }
    }/*}}}*/

    /**
     * Returns the statistics for the data set and the histogram bins and
     * frequencies
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */ 
    function getHistogramInfo() {/*{{{*/
        if (!empty($this->_nbins)) {
            $info = array (
                        "type" => ($this->_type == HISTOGRAM_CUMMULATIVE) ?  
                                            "cummulative frequency" : "histogram",
                        "data_stats" => $this->getDataStats(),
                        "hist_data_stats" => $this->getHistogramDataStats(),
                        "bins" => $this->_bins,
                        "nbins" => $this->_nbins,
                        "range" => array(
                                       "low" => $this->_rangeLow,
                                       "high" => $this->_rangeHigh
                                   )
                    );
            return $info;
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/



    /**
     * Resets the values of several private properties
     *
     * @access  private
     * @return  void
     */
    function _clear() {/*{{{*/
        $this->_stats = null;
        $this->_statsMode = null;
        $this->_data = null;
        $this->_orig = array();
        $this->_bins = array();
    }/*}}}*/

    /**
     * Abstract method that returns an array of data contained within the
     * range for the histogram calculation
     * Each subclass must implement this method
     *
     * @access  private
     * @return  array
     */
    function _histogramData() {/*{{{*/
        return array();
    }/*}}}*/
    


}/*}}}*/
// vim: ts=4:sw=4:et:
// vim6: fdl=1:

?>
