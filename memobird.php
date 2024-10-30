<?php namespace comment2memobird;
class memobird {
	var $ak = ''; //access key
	private $memobird_webapi_url = array(
		'getUserId'      => 'http://open.memobird.cn/home/setuserbind/',
		'printPaper'     => 'http://open.memobird.cn/home/printpaper/',
		'getPrintStatus' => 'http://open.memobird.cn/home/getprintstatus/'
	);

	function __construct() {
		date_default_timezone_set('PRC'); 
	}

	public function getUserId($memobirdID, $useridentifying) {
		// access key should be exactly composed by 32 chars in [a-z0-9]
		if ( preg_grep( '/^[a-z0-9]{32}$/', array( $this->ak ) ) ) {
			// UDID should be exactly composed by 16 chars in [a-z0-9]
			if ( preg_grep( '/^[a-z0-9]{16}$/', array( $memobirdID ) ) ) {
				$params = array(
					'ak'             => $this->ak,
					'timestamp'      => date( 'Y-m-d h:m:s', time() ),
					'memobirdID'     => $memobirdID,
					'useridentifying'=> $useridentifying
				);
				return $this->memobird_open_api( $this->memobird_webapi_url['getUserId'], $params);
			}
		}
		
		// otherwise return a hard encoded error response to simplify handling
		return '{"showapi_res_code":0}';
}

	public function printPaper($printcontent, $memobirdID, $userID) {
		// access key should be exactly composed by 32 chars in [a-z0-9]
		if ( preg_grep( '/^[a-z0-9]{32}$/', array( $this->ak ) ) ) {
			// UDID should be exactly composed by 16 chars in [a-z0-9]
			if ( preg_grep( '/^[a-z0-9]{16}$/', array( $memobirdID ) ) ) {
				$params = array(
					'ak'          => $this->ak,
					'timestamp'   => date( 'Y-m-d h:m:s', time() ),
					'printcontent'=> $printcontent,
					'memobirdID'  => $memobirdID,
					'userID'      => $userID
				);
				return $this->memobird_open_api( $this->memobird_webapi_url['printPaper'], $params );
			}
		}

		// otherwise return a hard encoded error response to simplify handling
		return '{"showapi_res_code":0}';
	}
	
	public function getPaperStatus($printcontentID) {
		// access key should be exactly composed by 32 chars in [a-z0-9]
		if ( preg_grep( '/^[a-z0-9]{32}$/', array( $this->ak ) ) ) {
			$params = array(
				'ak'             => $this->ak,
				'timestamp'      => date( 'Y-m-d h:m:s', time() ),
				'printcontentID' => $printcontentID
			);
			return $this->memobird_open_api( $this->memobird_webapi_url['getPrintStatus'], $params);
		}

		// otherwise return a hard encoded error response to simplify handling
		return '{"showapi_res_code":0}';
	}
	
    public function memobird_open_api($webapi_url, $params) {
		// initialized to a hard encoded error response
		$result = '{"showapi_res_code":0}';
		
		if ( !defined( 'WPINC' ) ) return $result;
		if ( !class_exists( 'WP_Http' ) ) {
			include_once( ABSPATH . WPINC . '/class-http.php' );
		}
		
		if ( in_array($webapi_url, $this->memobird_webapi_url) ) {
			$returned = wp_remote_post( $webapi_url, array( 'body' => $params ) );
			if ( is_array($returned) ) {
				// if returned an array, the post request is done
				$result = $returned['body'];
			} else {
				// return a hard encoded error response to simplify handling
				$result = '{"showapi_res_code":0}';
			}
		}

        return $result;
    }

	// 构造printPaper方法中$printcontent格式，多个可以循环并用|拼接
	public function contentSet($type, $content) {
		switch ($type) {
			case 'T':
				$ret = $type.':'.base64_encode($this->charsetToGBK($content)); break;
			case 'P':
				$ret = 'P:'.base64_encode($content);
			default:
		}
		return $ret;
	}
	
	public function charsetToGBK($mixed) {
		if ( function_exists( 'mb_convert_encoding' ) === false ) return 'This plugin requires php-mbstring extension.';
		
		if ( is_array( $mixed ) ) {
			// recursive converting
			foreach ( $mixed as $k => $v ) {
				if ( is_array( $v )) {
					$mixed[$k] = charsetToGBK( $v );
				} else {
					$mixed[$k] = mb_convert_encoding( $v, 'GBK', 'ASCII, UTF-8, GB2312, GBK, BIG5');
				}
			}
		} else {
			$mixed = mb_convert_encoding( $mixed, 'GBK', 'ASCII, UTF-8, GB2312, GBK, BIG5' );
		}
		
		return $mixed;
	}
};
