<?php

namespace MediaWiki\Extension\ATBridge\Hooks;

/**
 * Register a new ATProtocol Platform
 */
interface ATBridgePlatformRegistrationHook {
	/**
	 * @param array $platforms Assoc array of 'AbstractATProtocolPlatform's
	 * @return void
	 */
	public function onATBridgePlatformRegistration( array &$platforms ): void;
}