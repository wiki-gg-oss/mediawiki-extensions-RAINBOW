<?php

namespace MediaWiki\Extension\ATBridge\Services;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;

/**
 * Helper for general ATProto extension functionality
 */
final class ATProtoHelper {
	public const ServiceName = 'ATProtoHelper';

	private ?array $domainValidators = null;
	private ?array $atPlatforms = null;

	public function __construct(
		private readonly HookContainer $hooks
	) {
	}

	public function getPlatforms(): array {
		if ( !$this->atPlatforms ) {
			$this->hooks->run( 'ATProtoPlatformRegistration', [  ] );
		}

		return $this->atPlatforms;
	}

	public function getDomainValidators(): array {
		if ( !$this->domainValidators ) {
			$this->hooks->run( 'ATBridgeDomainValidatorRegistration', [  ] );
		}

		return $this->domainValidators;
	}
}