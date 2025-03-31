<?php

namespace MediaWiki\Extension\ATBridge;

class SocialMediaUser {
    /** @var ?int $globalId Global (wiki-farm) Id for the account */
    public ?int $globalId;

	/** @var string $platform Platform hosting the account (bluesky) */
	public readonly string $platform;

    /** @var int $wikiId Id of the user for a single given wiki */
    public readonly int $wikiId;

	/** @var string $baseHandle Default handle for the account (*.bsky.social) */
	public readonly string $baseHandle;

	/** @var ?string $domainHandle Base handle for the account (*.wiki.gg) */
	public ?string $domainHandle = null;

	/** @var ?string $email Email address for registering */
	public ?string $email = null;

	/** @var ?string $password Password for API access */
	public ?string $password = null;

	public function __construct(
	    string $platform,
	    int $wikiId,
	    string $handle
	) {
		$this->platform = $platform;
		$this->wikiId = $wikiId;
		$this->baseHandle = $handle;
	}
}