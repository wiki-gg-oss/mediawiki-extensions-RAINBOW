<?php

namespace MediaWiki\Extension\ATBridge\Abstractions;

use FormatJson;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Utils\UrlUtils;

abstract class AbstractATAPI {
	private readonly UrlUtils $urls;

	public function __construct(
		protected readonly string $domain,
		protected readonly HttpRequestFactory $requestFactory
	) {
		$this->urls = new UrlUtils( [
			'server' => $this->domain,
			'fallbackProtocol' => 'https',
		] );
	}

	/**
	 * POST a payload to the account creation endpoint
	 * @param array $payload Payload details for creation
	 * @return ?string
	 */
	public function createAccount( array $payload ): ?string {
		return $this->post( '/xrpc/com.atproto.server.createAccount', $payload );
	}

	protected function post( $path, array $payload ): ?string {
		$req = $this->requestFactory->create( $this->urls->expand( $path ), [
			// Encode our payload into JSON
			'postData' => FormatJson::encode( $payload, false, FormatJson::ALL_OK )
		] );

		// Set the content type to JSON
		$req->setHeader( 'Content-Type', 'application/json' );

		$status = $req->execute();

		if ( $status->isOK() ) {
			return $req->getContent();
		} else {
			wfDebugLog( 'ATBridge', "Bad Request {$req->getContent()}" );
			return null;
		}
	}
}