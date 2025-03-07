<?php

namespace MediaWiki\Extension\ATBridge\Platforms;

use MediaWiki\Extension\ATBridge\Abstractions\AbstractATProtoPlatform;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;

class BlueSky extends AbstractATProtoPlatform {
	public function __construct(
		private readonly BlueSkyAPI $api,
		ATProtoPlatformHelper $helper,
	) {
		parent::__construct( 'bluesky', $helper );
	}

	/**
	 * POST a payload to the account creation endpoint
	 * @param array $payload Payload details for creation
	 * @return ?string
	 */
	public function createAccount( array $payload ): ?string {
		return $this->api->post( '/xrpc/com.atproto.server.createAccount', $payload );
	}
}