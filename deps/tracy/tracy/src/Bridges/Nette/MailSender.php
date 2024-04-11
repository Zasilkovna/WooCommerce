<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Tracy\Bridges\Nette;

use Packetery\Nette;
use Packetery\Tracy;
/**
 * Tracy logger bridge for Nette Mail.
 * @internal
 */
class MailSender
{
    use Nette\SmartObject;
    /** @var Nette\Mail\IMailer */
    private $mailer;
    /** @var string|null sender of email notifications */
    private $fromEmail;
    public function __construct(Nette\Mail\IMailer $mailer, ?string $fromEmail = null)
    {
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
    }
    /**
     * @param  mixed  $message
     */
    public function send($message, string $email) : void
    {
        $host = \preg_replace('#[^\\w.-]+#', '', $_SERVER['SERVER_NAME'] ?? \php_uname('n'));
        $mail = new Nette\Mail\Message();
        $mail->setHeader('X-Mailer', 'Tracy');
        if ($this->fromEmail || Nette\Utils\Validators::isEmail("noreply@{$host}")) {
            $mail->setFrom($this->fromEmail ?: "noreply@{$host}");
        }
        foreach (\explode(',', $email) as $item) {
            $mail->addTo(\trim($item));
        }
        $mail->setSubject('PHP: An error occurred on the server ' . $host);
        $mail->setBody(\Packetery\Tracy\Logger::formatMessage($message) . "\n\nsource: " . \Packetery\Tracy\Helpers::getSource());
        $this->mailer->send($mail);
    }
}
