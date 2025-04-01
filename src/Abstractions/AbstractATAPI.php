<?php

namespace MediaWiki\Extension\ATBridge\Abstractions;

use MediaWiki\Json\FormatJson;
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

	public function get( $path ): ?string {
		return $this->requestFactory->get( $this->urls->expand( $path ) );
	}

	public function post( $path, array $payload ): mixed {
		$req = $this->requestFactory->create( $this->urls->expand( $path ), [
			// Encode our payload into JSON
			'postData' => FormatJson::encode( $payload, false, FormatJson::ALL_OK )
		] );

		// Set the content type to JSON
		$req->setHeader( 'Content-Type', 'application/json' );

        // Execute the HTTP request
		$status = $req->execute();

		if ( $status->isOK() ) {
		    // Convert to JSON
			return FormatJson::decode( $req->getContent() );
		} else {
			wfDebugLog( 'ATBridge', "Bad Request {$req->getContent()}" );
			return null;
		}
	}
}