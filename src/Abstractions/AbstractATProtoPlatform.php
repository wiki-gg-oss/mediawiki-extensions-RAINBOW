<?php

namespace MediaWiki\Extension\ATBridge\Abstractions;

use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\Extension\ATBridge\SocialMediaUser;

/**
 * Abstract Platform implementation that uses AT Protocol
 */
abstract class AbstractATProtoPlatform {
	public function __construct(
		public readonly string $name,
		private readonly AbstractATAPI $api,
		private readonly ATProtoPlatformHelper $helper
	) {
		
	}

    public function getAPI(): AbstractATAPI {
        return $this->api;
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

	/**
	 * Get the maximum allowed description length
	 * @return int
	 */
	public function maxDescriptionLength(): int {
		return 255;
	}

	/**
	 * Get the user for this Platform
	 * @return ?SocialMediaUser
	 */
	public function getUser(): ?SocialMediaUser {
		return $this->helper->getUser( $this->name );
	}
}