<?php

/**
 * This file is part of the PacketeryTracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryTracy\Bridges\PacketeryNette;

use PacketeryNette;
use PacketeryTracy;


/**
 * PacketeryTracy logger bridge for PacketeryNette Mail.
 */
class MailSender
{
	use PacketeryNette\SmartObject;

	/** @var PacketeryNette\Mail\IMailer */
	private $mailer;

	/** @var string|null sender of email notifications */
	private $fromEmail;


	public function __construct(PacketeryNette\Mail\IMailer $mailer, string $fromEmail = null)
	{
		$this->mailer = $mailer;
		$this->fromEmail = $fromEmail;
	}


	/**
	 * @param  mixed  $message
	 */
	public function send($message, string $email): void
	{
		$host = preg_replace('#[^\w.-]+#', '', $_SERVER['SERVER_NAME'] ?? php_uname('n'));

		$mail = new PacketeryNette\Mail\Message;
		$mail->setHeader('X-Mailer', 'PacketeryTracy');
		if ($this->fromEmail || PacketeryNette\Utils\Validators::isEmail("noreply@$host")) {
			$mail->setFrom($this->fromEmail ?: "noreply@$host");
		}
		foreach (explode(',', $email) as $item) {
			$mail->addTo(trim($item));
		}
		$mail->setSubject('PHP: An error occurred on the server ' . $host);
		$mail->setBody(PacketeryTracy\Logger::formatMessage($message) . "\n\nsource: " . PacketeryTracy\Helpers::getSource());

		$this->mailer->send($mail);
	}
}
