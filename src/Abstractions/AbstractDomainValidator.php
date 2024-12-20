<?php

namespace MediaWiki\Extension\ATBridge\Abstractions;

/**
 * For handling validation of Domains
 */
abstract class AbstractDomainValidator {
	/**
	 * If the domain validator works for the given domain
	 * @param array $domain
	 * @return bool
	 */
	public abstract function supportsDomain( array $domain ): bool;
}