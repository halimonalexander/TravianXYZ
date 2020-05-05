<?php

namespace GameEngine;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Mailer.php                                                  ##
##  Developed by:  Dixie                                                       ##
##  License:       TravianX Project                                            ##
##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##
##                                                                             ##
#################################################################################

class Mailer
{
	public function sendActivationMail($email, $username, $password, $acticationCode)
    {
		$subject = 'Welcome to ' . SERVER_NAME;
		$url = SERVER;

		$message = <<<EOD
Hello {$username}!

Thank you for your registration.

----------------------------
Login: {$username}
Password: {$password}
Activation code: {$acticationCode}
----------------------------

Click the following link in order to activate your account:
{$url}activate.php?code={$acticationCode}

Greetings,
Travian administration
EOD;

		$headers = "From: " . ADMIN_EMAIL . "\n";

		mail($email, $subject, $message, $headers);
	}

	public function sendInvite($email, $uid, $text)
    {
        $serverName = SERVER_NAME;
        $url = SERVER;

		$subject = "{$serverName} registeration";

		$message = <<<EOD
Hello!

Try the new {$serverName}!

Link: {$url}anmelden.php?id=ref".$uid."

".$text."

Greetings,
Travian
EOD;

		$headers = "From: " . ADMIN_EMAIL . "\n";

		mail($email, $subject, $message, $headers);
	}

	public function sendPassword($email, $uid, $username, $npw, $cpw)
    {
        $url = SERVER;
		$subject = "Password forgotten";

		$message = <<< EOD
Hello {$username}

You have requested a new password for Travian.

----------------------------
Name: {$username}
Password: {$npw}
----------------------------

Please click this link to activate your new password. The old password then
becomes invalid:

{$url}/password.php?cpw={$cpw}&npw={$uid}

If you want to change your new password, you can enter a new one in your profile
on tab "account".

In case you did not request a new password you may ignore this email.

Travian
EOD;

		$headers = "From: " . ADMIN_EMAIL . "\n";

		mail($email, $subject, $message, $headers);
	}
}


