<?php
// Sample code for confirm_mail.class.php
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

?><html>
<head>
<title>Example of confirm_mail class</title>
</head>
<body>
<h1>Registration and Deregistration form</h1>

<?php
error_reporting( E_ALL );

include '../confirm_mail.class.php';

$from_addr		= 'hoge@hoge.foo';
$confirm_file	= './confirm.dat'; // MUST NOT BE ACCESSIBLE from browser
$member_file	= './members'; // MUST NOT BE ACCESSIBLE from browser
$timeout		= 60 * 5; // 5 minutes

date_default_timezone_set( 'Asia/Tokyo' );

$cm = new ConfirmMail( $confirm_file, $from_addr, $timeout );
$status = $cm->check();
switch( $status )
{
case -2:
	// Timed out
	?><p style="color:red">ERROR: Operation timed out.</p><?php
	break;
case -1:
	// Failed
	?><p style="color:red">ERROR: Operation failed. Might be timed out.</p><?php
	break;
case 0:
	// Not in confirm mode
	// Display a registration form
	$action = '';
	$addr = '';
	$members = '';
	if( isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action'];
	if( isset( $_REQUEST['addr'] ) ) $addr = $_REQUEST['addr'];
	
	if( $action === 'register' )
	{
		?><h2>Registration</h2><?php
		$status = $cm->confirm( 'register', $addr, $addr, 'Confirm your registration', 
			'Open following URL until ' . date( 'r', time() + $timeout ) . ' to finish the operation. ' . "\n" . "\n", 
			"\n" . "\n" . 'Request of registration was from ' . $_SERVER["REMOTE_ADDR"] . '.' . "\n" . "\n" . "\n" .
			'--' .  "\n" .
			' Administrator name here' . "\n" .
			' ' . $from_addr . "\n" );
		if( $status === 0 )
		{
			?><p>Please open confirmation URL in the e-mail to finish the operation. </p><?php
		}
		else if( $status === -1 )
		{
			?><p style="color:red">ERROR: Invalid e-mail address.</p><?php
		}
		else if( $status === -2 )
		{
			?><p style="color:red">ERROR: Already requested.</p><?php
		}
	}
	else if( $action === 'delete' )
	{
		?><h2>Deregistration</h2><?php
		$status = $cm->confirm( 'delete', $addr, $addr, 'Confirm your deregistration', 
			'Open following URL until ' . date( 'r', time() + $timeout ) . ' to finish the operation. ' . "\n" . "\n", 
			"\n" . "\n" . 'Request of deregistration was from ' . $_SERVER["REMOTE_ADDR"] . '.' . "\n" . "\n" . "\n" .
			'--' .  "\n" .
			' Administrator name here' . "\n" .
			' ' . $from_addr . "\n" );
		if( $status === 0 )
		{
			?><p>Please open confirmation URL in the e-mail to finish the operation. </p><?php
		}
		else if( $status === -1 )
		{
			?><p style="color:red">ERROR: Invalid e-mail address.</p><?php
		}
		else if( $status === -2 )
		{
			?><p style="color:red">ERROR: Already requested.</p><?php
		}
	}
	
	if( $action === '' )
	{
	?>
		<h2>Registration</h2>
		<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'];?>">
		 <div id="join_ml">
		  Registering e-mail address: <input type="text" name="addr" value="" />
		  <input type="hidden" name="action" value="register" />
		  <input type="submit" value="Register" />
		 </div>
		</form>
		<h2>Deregistration</h2>
		<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'];?>">
		 <div id="leave_ml">
		  Deregistering e-mail address: <input type="text" name="addr" value="" />
		  <input type="hidden" name="action" value="delete" />
		  <input type="submit" value="Deregister" />
		 </div>
		</form>
	<?php
	}
	break;
case 1:
	// Confirmed
	if( $cm->getAction() === 'register' )
	{
		?><h2>Registration</h2><?php
		?><p><?php echo $cm->getKey();?> has been registered.</p><?php
    	$fp = fopen( $member_file, 'a' );
		if( flock( $fp, LOCK_EX ) )
		{
			fwrite( $fp, $cm->getKey() . "\n" );
		}
		fclose( $fp );
	}
	else if( $cm->getAction() === 'delete' )
	{
		?><h2>Deregistration</h2><?php
		?><p><?php echo $cm->getKey();?> has been deregistered.</p><?php
    	$fp = fopen( $member_file, 'r+' );
		if( flock( $fp, LOCK_EX ) )
		{
			$file = '';
			while( !feof( $fp ) )
			{
				$line = fgets( $fp );
				if( !preg_match( '/^' . preg_quote( $cm->getKey() ) . '$/m', $line ) )
				{
					$file .= $line;
				}
			}
			ftruncate( $fp, 0 );
			fseek( $fp, 0 );
			fwrite( $fp, $file );
		}
		fclose( $fp );
	}
	break;
}

?>

</body>
</html>

