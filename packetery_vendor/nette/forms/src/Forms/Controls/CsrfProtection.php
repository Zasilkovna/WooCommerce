<?php

/**
 * This file is part of the PacketeryNette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryNette\Forms\Controls;

use PacketeryNette;
use PacketeryNette\Application\UI\Presenter;


/**
 * CSRF protection field.
 */
class CsrfProtection extends HiddenField
{
	public const PROTECTION = 'PacketeryNette\Forms\Controls\CsrfProtection::validateCsrf';

	/** @var PacketeryNette\Http\Session|null */
	public $session;


	/**
	 * @param string|object  $errorMessage
	 */
	public function __construct($errorMessage)
	{
		parent::__construct();
		$this->setOmitted()
			->setRequired()
			->addRule(self::PROTECTION, $errorMessage);

		$this->monitor(Presenter::class, function (Presenter $presenter): void {
			if (!$this->session) {
				$this->session = $presenter->getSession();
				$this->session->start();
			}
		});

		$this->monitor(PacketeryNette\Forms\Form::class, function (PacketeryNette\Forms\Form $form): void {
			if (!$this->session && !$form instanceof PacketeryNette\Application\UI\Form) {
				$this->session = new PacketeryNette\Http\Session($form->httpRequest, new PacketeryNette\Http\Response);
				$this->session->start();
			}
		});
	}


	/**
	 * @return static
	 * @internal
	 */
	public function setValue($value)
	{
		return $this;
	}


	public function loadHttpData(): void
	{
		$this->value = $this->getHttpData(PacketeryNette\Forms\Form::DATA_TEXT);
	}


	public function getToken(): string
	{
		if (!$this->session) {
			throw new PacketeryNette\InvalidStateException('Session initialization error');
		}
		$session = $this->session->getSection(self::class);
		if (!isset($session->token)) {
			$session->token = PacketeryNette\Utils\Random::generate();
		}
		return $session->token ^ $this->session->getId();
	}


	private function generateToken(string $random = null): string
	{
		if ($random === null) {
			$random = PacketeryNette\Utils\Random::generate(10);
		}
		return $random . base64_encode(sha1($this->getToken() . $random, true));
	}


	public function getControl(): PacketeryNette\Utils\Html
	{
		return parent::getControl()->value($this->generateToken());
	}


	/** @internal */
	public static function validateCsrf(self $control): bool
	{
		$value = (string) $control->getValue();
		return $control->generateToken(substr($value, 0, 10)) === $value;
	}
}
