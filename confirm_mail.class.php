<?php
// PHP class for confirming operation by using confirmation e-mail 
//   by Atsushi Watanabe
//
// Copyright 2013 Atsushi Watanabe, All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are met:
//
// * Redistributions of source code must retain the above copyright notice, this
//   list of conditions and the following disclaimer.
// * Redistributions in binary form must reproduce the above copyright notice,
//   this list of conditions and the following disclaimer in the documentation
//   and/or other materials provided with the distribution.
//
// THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR
// IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
// MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
// EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
// INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
// LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
// ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
// SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

class ConfirmMail
{
	public $datafile = '';
	public $timeout = '';
	public $admin_mail = '';
	public $key = '';
	public $action = '';

	public function __construct( $datafile, $admin_mail, $timeout = 86400 )
	{
		$this->datafile		= $datafile;
		$this->admin_mail	= $admin_mail;
		$this->timeout		= $timeout;
	}

	public function confirm( $action, $to, $key, $subject, $header, $footer )
	{
		$now = time();
		$random = substr( base_convert( md5( uniqid() ), 16, 36 ), 0, 20 );

		if( !preg_match( '/^([\w.!#$%&\'*+\\/=?^`{|}~-])+@([\w-])+\.([\w\.-])+$/m', $to ) || 
				preg_match( '/\n/m', $to ) )
		{
			return -1;
		}

		$fp = fopen( $this->datafile, 'r' );
		if( flock( $fp, LOCK_EX ) )
		{
			$file = '';
			while( !feof( $fp ) )
			{
				$line = fgets( $fp );
				$data = array();
				$data = explode( ' ', $line );
				if( count( $data ) !== 5 ) continue;

				if( $key === $data[2] && 
						$action === $data[3] && 
						$now < $data[0] + $this->timeout )
				{
					$key = '';
					break;
				}
			}
		}
		fclose( $fp );
		if( $key === '' )
		{
			return -2;
		}

		$fp = fopen( $this->datafile, 'a' );
		if( flock( $fp, LOCK_EX ) )
		{
			fwrite( $fp, $now . ' ' . $random . ' ' . $key . ' ' . $action . ' ' . "\n" );
		}
		fclose( $fp );

		$subject .= "\n";
		$headers =
			'From: ' . $this->admin_mail . "\n" .
			'Reply-To: ' . $this->admin_mail . "\n" .
			'X-Mailer: PHP/' . phpversion();
		$message = $header .
			'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?confirm=' . $random . $footer;
		mb_language('japanese');
		mb_internal_encoding('UTF-8');
		$result = mb_send_mail( $to, $subject, $message, $headers, '-f ' . $this->admin_mail );
		return 0;
	}

	public function check( )
	{
		$now = time();

		$status = 0;
		if( isset( $_REQUEST['confirm'] ) )
		{
			$status = -1;
			$fp = fopen( $this->datafile, 'r+' );
			if( flock( $fp, LOCK_EX ) )
			{
				$file = '';
				while( !feof( $fp ) )
				{
					$line = fgets( $fp );
					$data = array();
					$data = explode( ' ', $line );
					if( count( $data ) !== 5 ) continue;

					if( $now > $data[0] + $this->timeout ) $timedout = true;
					else $timedout = false;

					if( $data[1] === $_REQUEST['confirm'] ) $confirmed = true;
					else $confirmed = false;

					if( $confirmed && $timedout ) $status = -2;
					if( $confirmed && !$timedout )
					{
						$this->key = $data[2];
						$this->action = $data[3];
						$status = 1;
					}
					if( !$confirmed && !$timedout ) $file .= $line;
				}
				ftruncate( $fp, 0 );
				fseek( $fp, 0 );
				fwrite( $fp, $file );
			}
			fclose( $fp );
		}
		return $status;
	}
	public function getKey( )
	{
		return $this->key;
	}
	public function getAction( )
	{
		return $this->action;
	}
}


?>
