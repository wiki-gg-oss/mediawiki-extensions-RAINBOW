<?php

namespace MediaWiki\Extension\ATBridge\Hooks;

/**
 * Register a domain validator
 */
interface ATBridgeDomainValidatorRegistrationHook {
	/**
	 * Register domain validators
	 *   The $validators value can either be a singular 'AbstractDomainValidator' or an array of them
	 *   Since it's possible to register DNS for many different DNS providers
	 * 
	 *   For example;
	 *   $validators['dns'][] = new CloudFlareDNSValidator();
	 * 
	 * @param array $options Config 'wgATProtoValidatorSettings' value
	 * @param array $validators Assoc array of 'AbstractDomainValidator's
	 * @return void
	 */
	public function onATBridgeDomainValidatorRegistration( array $options, array &$validators ): void;
}