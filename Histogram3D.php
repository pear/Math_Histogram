<?php

class Math_Histogram3D extends Math_AbstractHistogram {/*{{{*/

	function Math_Histogram3D($type=HISTOGRAM_SIMPLE,$binOptions="") {/*{{{*/
		$this->setType($type);
		$this->setBinOptions($binOptions);
	}/*}}}*/

    function setBinOptions($binOptions) {/*{{{*/
        if ( $this->_validBinOptions($binOptions) )
            return parent::setBinOptions($binOptions);
        else
            return PEAR::raiseError("incorrect options array");
    }/*}}}*/

	function setData($data) {/*{{{*/
        $this->_clear();
        if (!$this->_validData($data))
            return PEAR::raiseError("array of numeric coordinates expected");
        $this->_data = $data;
        list($xMin, $xMax) = $this->_getMinMax('x');
        list($yMin, $yMax) = $this->_getMinMax('y');
        if (is_null($this->_rangeLow))
            $this->_rangeLow = array('x'=>$xMin, 'y'=>$yMin);
        if (is_null($this->_rangeHigh))
            $this->_rangeHigh = array('x'=>$xMax, 'y'=>$yMax);
        if (is_null($this->_nbins))
            $this->_nbins = array('x'=>10, 'y'=>10);
        return true;
	}/*}}}*/

    function calculate() {
    }


    function _getMinMax($elem) {
        return array(min($this->_data[$elem]), max($this->_data[$elem]));
    }

    function _validBinOptions($binOptions) {
        $barray = ( is_array($binOptions)
                     && is_array($binOptions['low'])
                     && is_array($binOptions['high'])
                     && is_array($binOptions['nbins']) );
        $low = $binOptions['low'];
        $high = $binOptions['high'];
        $nbins = $binOptions['nbins'];
        $blow = ( isset($low['x']) && isset($low['y'])
                && is_numeric($low['x']) && is_numeric($low['y']) );
        $bhigh = ( isset($high['x']) && isset($high['y'])
                && is_numeric($high['x']) && is_numeric($high['y']) );
        $bnbins = ( isset($nbins['x']) && isset($nbins['y'])
                && is_numeric($nbins['x']) && is_numeric($nbins['y']) );
        return ($barray && $blow && $bhigh && $bnbins);
    }

    function _validData($data) {
        if (is_array($data) && is_array($data['x']) && is_array($data['y'])) {
            $n = count($data['x'])
            if (count($data) == 2 && $n == count($data['y']))
                for ($i=0; $i < $n; $i++)
                    if (!is_numeric($data['x'][$i]) || !is_numeric($data['y'][$i]))
                        return false;
                // if everything checks out
                return true; 
            else
                return false;
        } else {
            return false;
        }
    }

}/*}}}*/

// vim: ts=4:sw=4:et:
// vim6: fdl=1:

?>
