<?php

namespace MediaWiki\Extension\ATBridge\Platforms;

use MediaWiki\Extension\ATBridge\Abstractions\AbstractATProtoPlatform;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\Http\HttpRequestFactory;

class BlueSky extends AbstractATProtoPlatform {
	public function __construct(
		private readonly BlueSkyAPI $api,
		ATProtoPlatformHelper $helper,
	) {
		parent::__construct( 'bluesky', $helper );
	}
}