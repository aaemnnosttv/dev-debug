<?php

/* Finally, A light, permissions-checking logging class.
 *
 * Author	: Kenneth Katzgrau < katzgrau@gmail.com >
 * Date	: July 26, 2008
 * Comments	: Originally written for use with wpSearch
 * Website	: http://codefury.net
 * Version	: 1.0
 *
 * Usage:
 *		$log = new KLogger ( "log.txt" , KLogger::INFO );
 *		$log->LogInfo("Returned a million search results");	//Prints to the log file
 *		$log->LogFATAL("Oh dear.");				//Prints to the log file
 *		$log->LogDebug("x = 5");					//Prints nothing due to priority setting
*/

class DevDebug_Logger
{

	const DEBUG 	= 1;	// Most Verbose
	const INFO 		= 2;	// ...
	const WARN 		= 3;	// ...
	const ERROR 	= 4;	// ...
	const FATAL 	= 5;	// Least Verbose
	const OFF 		= 6;	// Nothing at all.

	const LOG_OPEN 		= 1;
	const OPEN_FAILED 	= 2;
	const LOG_CLOSED 	= 3;

	/* Public members: Not so much of an example of encapsulation, but that's okay. */
	public $Log_Status 	= DevDebug_Logger::LOG_CLOSED;
	public $DateFormat	= "Y-m-d G:i:s";
	public $MessageQueue;

	private $log_file;
	private $priority = DevDebug_Logger::INFO;

	private $file_handle;

	public function __construct( $filepath , $priority )
	{
		if ( $priority == DevDebug_Logger::OFF ) return;

		$this->log_file = $filepath;
		$this->MessageQueue = array();
		$this->priority = $priority;

		if ( file_exists( $this->log_file ) )
		{
			if ( !is_writable($this->log_file) )
			{
				$this->Log_Status = DevDebug_Logger::OPEN_FAILED;
				$this->MessageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
				return;
			}
		}

		if ( $this->file_handle = fopen( $this->log_file , "a" ) )
		{
			$this->Log_Status = DevDebug_Logger::LOG_OPEN;
			$this->MessageQueue[] = "The log file was opened successfully.";
		}
		else
		{
			$this->Log_Status = DevDebug_Logger::OPEN_FAILED;
			$this->MessageQueue[] = "The file could not be opened. Check permissions.";
		}

		return;
	}

	public function __destruct()
	{
		if ( $this->file_handle )
			fclose( $this->file_handle );
	}

	public function LogInfo($line)
	{
		$this->Log( $line , DevDebug_Logger::INFO );
	}

	public function LogDebug($line)
	{
		$this->Log( $line , DevDebug_Logger::DEBUG );
	}

	public function LogWarn($line)
	{
		$this->Log( $line , DevDebug_Logger::WARN );
	}

	public function LogError($line)
	{
		$this->Log( $line , DevDebug_Logger::ERROR );
	}

	public function LogFatal($line)
	{
		$this->Log( $line , DevDebug_Logger::FATAL );
	}

	public function Log($line, $priority)
	{
		if ( $this->priority <= $priority )
		{
			$status = $this->getTimeLine( $priority );
			$this->WriteFreeFormLine ( "$status $line\n" );
		}
	}

	public function WriteFreeFormLine( $line )
	{
		if ( $this->Log_Status == DevDebug_Logger::LOG_OPEN && $this->priority != DevDebug_Logger::OFF )
		{
		    if (fwrite( $this->file_handle , $line ) === false) {
		        $this->MessageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
		    }
		}
	}

	private function getTimeLine( $level )
	{
		$time = date( $this->DateFormat );

		switch( $level )
		{
			case DevDebug_Logger::INFO:
				return "$time - INFO  -->";
			case DevDebug_Logger::WARN:
				return "$time - WARN  -->";
			case DevDebug_Logger::DEBUG:
				return "$time - DEBUG -->";
			case DevDebug_Logger::ERROR:
				return "$time - ERROR -->";
			case DevDebug_Logger::FATAL:
				return "$time - FATAL -->";
			default:
				return "$time - LOG   -->";
		}
	}

}
