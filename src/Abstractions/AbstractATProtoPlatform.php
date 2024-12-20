<?php

namespace MediaWiki\Extension\ATBridge\Abstractions;

use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;

/**
 * Abstract Platform implementation that uses AT Protocol
 */
abstract class AbstractATProtoPlatform {
	public function __construct(
		public readonly string $name,
		private readonly ATProtoPlatformHelper $helper
	) {
	}

	/**
	 * If the given platform requires an email to register
	 * @return bool
	 */
	public function requiresValidEmail(): bool {
		return true;
	}

	/**
	 * Return a set of supported Domain validators
	 * @return string[]
	 */
	public function supportedDomainValidation(): array {
		return [ 'well-known', 'dns' ];
	}
}