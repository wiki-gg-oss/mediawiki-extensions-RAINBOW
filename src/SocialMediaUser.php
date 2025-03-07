<?php

namespace MediaWiki\Extension\ATBridge;

class SocialMediaUser {
	/** @var string Platform hosting the account (bluesky) */
	public readonly string $platform;

	/** @var string Default handle for the account (*.bsky.social) */
	public readonly string $baseHandle;

	/** @var ?string Base handle for the account (*.wiki.gg) */
	public ?string $domainHandle = null;

	/** @var ?string Email address for registering */
	public ?string $email = null;

	/** @var ?string Password for API access */
	public ?string $password = null;

	public function __construct( string $platform, string $handle ) {
		$this->platform = $platform;
		$this->baseHandle = $handle;
	}
}