<?php

namespace MediaWiki\Extension\ATBridge\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ATBridge\Platforms\BlueSky;
use MediaWiki\Extension\ATBridge\Platforms\BlueSkyAPI;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\Extension\ATBridge\WellKnownDomainValidator;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Utils\UrlUtils;

final class DefaultRegistrationHooks implements
	ATBridgeDomainValidatorRegistrationHook,
	ATBridgePlatformRegistrationHook
{
	public function __construct(
		private readonly UrlUtils $urls,
		private readonly Config $config,
		private readonly ATProtoPlatformHelper $helper,
		private readonly HttpRequestFactory $requestFactory
	) {
	}

	public function onATBridgeDomainValidatorRegistration( array $options, array &$validators ): void {
		$validators['well-known'] = new WellKnownDomainValidator(
			$this->urls,
			$this->config,
			$options['well-known'] ?? []
		);
	}

	public function onATBridgePlatformRegistration( array &$platforms ): void {
		$bluesky = new BlueSky(
			new BlueSkyAPI( $this->requestFactory ),
			$this->helper
		);
		$platforms[$bluesky->name] = $bluesky;
	}
}