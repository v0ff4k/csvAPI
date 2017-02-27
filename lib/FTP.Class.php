<?php
/**
 * Simple FTP Class
 *
 * <code>
 * functions used in this class
 * cd() Change currect directory on FTP server
 * close() Close FTP connection
 * connect() Connect to FTP server
 * get() Download file from server
 * modifiedTime() Returns the last modified time of the given file.
 * ls() Get list of files/directories in directory
 * pwd() Get current directory
 * </code>
 *
 *
 * @name FTPC
 * @version 0.1
 */
final class FTPC {
	/**
	 * FTP host
	 * @var string $_host
	 */
	private $_host;

	/**
	 * FTP port
	 * @var int $_port
	 */
	private $_port = 21;

	/**
	 * FTP password
	 * @var string $_pwd
	 */
	private $_pwd;
	
	/**
	 * FTP stream
	 * @var resource $_id
	 */
	private $_stream;

	/**
	 * FTP timeout
	 * @var int $_timeout
	 */
	private $_timeout = 90;

	/**
	 * FTP user
	 * @var string $_user
	 */
	private $_user;

	/**
	 * Last error
	 * @var string $error
	 */
	public $error;

	/**
	 * FTP passive mode flag
	 * @var bool $passive
	 */
	public $passive = false;

	/**
	 * SSL-FTP connection flag
	 * @var bool $ssl
	 */
	public $ssl = false;

	/**
	 * System type of FTP server
	 * @var string $system_type
	 */
	public $system_type;

	/**
	 * Initialize connection params
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param int $port
	 * @param int $timeout (seconds)
	 */
	public function  __construct($host = null, $user = null, $password = null, $port = 21, $timeout = 90) {
		$this->_host = $host;
		$this->_user = $user;
		$this->_pwd = $password;
		$this->_port = (int)$port;
		$this->_timeout = (int)$timeout;
	}

	/**
	 * Auto close connection
	 */
	public function  __destruct() {
		$this->close();
	}

	/**
	 * Change currect directory on FTP server
	 *
	 * @param string $directory
	 * @return bool
	 */
	public function cd($directory = null) {
		// attempt to change directory
		if(ftp_chdir($this->_stream, $directory)) {
			// success
			return true;
		// fail
		} else {
			$this->error = "Failed to change directory to \"{$directory}\"";
			return false;
		}
	}

	/**
	 * Close FTP connection
	 */
	public function close() {
		// check for valid FTP stream
		if($this->_stream) {
			// close FTP connection
			ftp_close($this->_stream);

			// reset stream
			$this->_stream = false;
		}
	}

	/**
	 * Connect to FTP server
	 *
	 * @return bool
	 */
	public function connect() {
		// check if non-SSL connection
		if(!$this->ssl) {
			// attempt connection
			if(!$this->_stream = ftp_connect($this->_host, $this->_port, $this->_timeout)) {
				// set last error
				$this->error = "Failed to connect to {$this->_host}";
				return false;
			}
		// SSL connection
		} elseif(function_exists("ftp_ssl_connect")) {
			// attempt SSL connection
			if(!$this->_stream = ftp_ssl_connect($this->_host, $this->_port, $this->_timeout)) {
				// set last error
				$this->error = "Failed to connect to {$this->_host} (SSL connection)";
				return false;
			}
		// invalid connection type
		} else {
			$this->error = "Failed to connect to {$this->_host} (invalid connection type)";
			return false;
		}

		// attempt login
		if(ftp_login($this->_stream, $this->_user, $this->_pwd)) {
			// set passive mode
			ftp_pasv($this->_stream, (bool)$this->passive);

			// set system type
			$this->system_type = ftp_systype($this->_stream);

			// connection successful
			return true;
		// login failed
		} else {
			$this->error = "Failed to connect to {$this->_host} (login failed)";
			return false;
		}
	}


	/**
	 * Download file from server
	 *
	 * @param string $remote_file
	 * @param string $local_file
	 * @param int $mode
	 * @return bool
	 */
	public function get($remote_file = null, $local_file = null, $mode = FTP_ASCII) {
		// attempt download
		if(ftp_get($this->_stream, $local_file, $remote_file, $mode)) {
			// success
			return true;
		// download failed
		} else {
			$this->error = "Failed to download file \"{$remote_file}\"";
			return false;
		}
	}


	/**
	 * Returns the last modified time of the given file.
	 * Return -1 on error
	 *
	 * @param string $remote_file
	 * @param string|null $format
	 * @return int
	 */
	public function modifiedTime($remote_file, $format = null)
	{
		$time = ftp_mdtm($this->_stream, $remote_file);

    if (-1 !== $time){

		if ( null !== $format ) {
			return date($format, $time);
		}else{
            return $time;
        }

    } else {

        $this->error = "Failed to request creation time for file \"{$remote_file}\"";
        return false;

    }

	}

	/**
	 * Get list of files/directories in directory
	 *
	 * @param string $directory
	 * @return array
	 */
	public function ls($directory = null) {
		$list = array();

		// attempt to get list
		if($list = ftp_nlist($this->_stream, $directory)) {
			// success
			return $list;
		// fail
		} else {
			$this->error = "Failed to get directory list";
			return array();
		}
	}

	/**
	 * Get current directory
	 *
	 * @return string
	 */
	public function pwd() {
		return ftp_pwd($this->_stream);
	}

}
?>