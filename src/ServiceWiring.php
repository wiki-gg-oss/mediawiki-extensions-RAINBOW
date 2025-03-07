<?php

use MediaWiki\Extension\ATBridge\Services\ATProtoHelper;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\MediaWikiServices;

return [
	ATProtoHelper::ServiceName => static function (
		MediaWikiServices $services
	) {
		return new ATProtoHelper(
			$services->getHookContainer()
		);
	},
	ATProtoPlatformHelper::ServiceName => static function(
		MediaWikiServices $services
	) {
		return new ATProtoPlatformHelper(
			$services->getMainConfig(),
			$services->getDBLoadBalancerFactory()
		);
	}
];