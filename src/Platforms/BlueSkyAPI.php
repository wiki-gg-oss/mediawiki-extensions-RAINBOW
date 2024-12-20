<?php

namespace MediaWiki\Extension\ATBridge\Platforms;

use MediaWiki\Extension\ATBridge\Abstractions\AbstractATAPI;
use MediaWiki\Http\HttpRequestFactory;

class BlueSkyAPI extends AbstractATAPI {
	public function __construct(
		HttpRequestFactory $requestFactory
	) {
		parent::__construct( 'bsky.social', $requestFactory );
	}
}