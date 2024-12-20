<?php

namespace MediaWiki\Extension\ATBridge;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ATBridge\Abstractions\AbstractDomainValidator;
use MediaWiki\MainConfigNames;
use MediaWiki\Utils\UrlUtils;

class WellKnownDomainValidator extends AbstractDomainValidator {
	public function __construct(
		private readonly UrlUtils $urls,
		private readonly Config $config,
		private readonly array $options
	) {}

	/**
	 * Only be enabled if our path is set and the domain equals the current domain
	 * @param array $domain
	 * @return bool
	 */
	public function supportsDomain( array $domain ): bool {
		if ( !( $this->options['path'] ?? false ) )
			return false;

		return empty( array_diff(
			$domain,
			$this->urls->parse( $this->config->get( MainConfigNames::Server ) )
		) );
	}
}