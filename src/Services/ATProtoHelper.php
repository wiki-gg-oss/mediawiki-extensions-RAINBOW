<?php

namespace MediaWiki\Extension\ATBridge\Services;

use MediaWiki\Extension\ATBridge\Abstractions\AbstractATProtoPlatform;
use MediaWiki\Extension\ATBridge\Abstractions\AbstractDomainValidator;
use MediaWiki\HookContainer\HookContainer;

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

    /**
     * Get all registered ATProto platforms
     * @return AbstractATProtoPlatform[]
     */
	public function getPlatforms(): array {
		if ( !$this->atPlatforms ) {
		    $values = [];

			$this->hooks->run( 'ATBridgePlatformRegistration', [ &$values ], [
			    'abortable' => false
            ] );

            if ( is_array( $values ) ) {
                $platforms = [];

                foreach ( $values as $value ) {
                    if ( $value instanceof AbstractATProtoPlatform ) {
                        $platforms[ $value->name ] = $value;
                    }
                }

                $this->atPlatforms = $platforms;
            }
		}

		return $this->atPlatforms;
	}

    /**
     * Get all forms of being able to validate a domain
     * @return AbstractDomainValidator[]
     */
	public function getDomainValidators(): array {
		if ( !$this->domainValidators ) {
		    $validators = [];

			$this->hooks->run( 'ATBridgeDomainValidatorRegistration', [ &$validators ], [
			    'abortable' => false
            ] );

			$this->domainValidators = $validators;
		}

		return $this->domainValidators;
	}
}