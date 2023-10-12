<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayException extends Exception {
	public function errorMessage()
	{
	    logtext('异常发生:') ;
	     
	    logtext($this->getMessage()) ;

		return $this->getMessage();
	

	}
}
