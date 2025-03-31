<?php

namespace MediaWiki\Extension\ATBridge\Services;

use http\Exception\UnexpectedValueException;
use MediaWiki\Config\Config;
use MediaWiki\Extension\ATBridge\Consts\ConfigNames;
use MediaWiki\Extension\ATBridge\SocialMediaUser;
use MediaWiki\MainConfigNames;
use MediaWiki\WikiMap\WikiMap;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Stats\Exceptions\IllegalOperationException;

/**
 * Helper for ATProto Platforms
 */
final class ATProtoPlatformHelper {
	public const ServiceName = 'ATProtoPlatformHelper';

	public function __construct(
		private readonly Config $config,
		private readonly LBFactory $factory
	) {
	}

	/**
	 * Get the SocialMediaUser for this wiki for the given platform
	 * @param string $platform
     * @param ?int $wikiUniq
	 * @return ?SocialMediaUser
	 */
	public function getUser( string $platform, ?int $wikiUniq = null ): ?SocialMediaUser {
	    $wikiId = null;
	    $where = [
            'at_platform' => $platform,
            'at_wiki' => $wikiId
        ];

        if ( $wikiUniq ) {
            $where['at_platform_uniq'] = $wikiUniq;
        }

		$query = $this->newUsersDatabaseQuery()
			->where( $where )
			->limit( 1 );

		// Complete the query
		$users = $this->queryUsers( $query );

		// Get the first entry or null
		return array_shift( $users );
	}

	/**
	 * Get all the SocialMediaUsers for this wiki
	 * @return SocialMediaUser[]
	 */
	public function getUsers(): array {
        $wikiId = null;
		$query = $this->newUsersDatabaseQuery()
		    ->where( [ 'at_wiki' => $wikiId ] );

		return $this->queryUsers( $query );
	}

    /**
     * Get all the SocialMediaUsers that are currently registered
     * @return SocialMediaUser[]
     */
    public function getAllUsers(): array {
        $query = $this->newUsersDatabaseQuery();

        return $this->queryUsers( $query );
    }

	/**
	 * Read a new user from the given row
	 * @param SelectQueryBuilder $query
	 * @return SocialMediaUser[]
	 */
	private function queryUsers( SelectQueryBuilder $query ): array {
		$users = [];
		$results = $query->fetchResultSet();

		foreach ( $results as $row ) {
			// Create the user account
			$user = new SocialMediaUser(
			    $row->at_platform,
			    $row->at_platform_uniq,
			    $row->at_handle
			);

			$user->domainHandle = $row->at_domain;
			$user->email = $row->at_email;
			$user->password = $this->decryptPasscode( $row->at_passcode );

            $user->globalId = $row->at_uniq;

			$users[] = $user;
		}

		return $users;
	}

	private function newUsersDatabaseQuery( string $caller = __METHOD__ ): SelectQueryBuilder {
		$wikiId = $this->config->get( ConfigNames::CentralWiki );
		$wikiIds = $this->config->get( MainConfigNames::LocalDatabases );

		if ( !in_array( $wikiId, $wikiIds ) ) {
			throw new UnexpectedValueException( "\"{$wikiId}\" is not a valid database for querying AT Protocol users" );
		}

		$db = $this->factory->getReplicaDatabase( $wikiId );
		return $db->newSelectQueryBuilder()
			->table( 'atproto_users' )
			->fields( [
			    'at_uniq', // Unique ID (For wiki farms)
				'at_wiki', // The wiki db key that made this account
				'at_platform', // The platform that the account was made on
				'at_platform_uniq', // The nth account made by this wiki
				'at_handle', // Randomly generated string handle used for the account
				'at_domain', // Domain string used as the handle for the account
				'at_email', // The email used to register the account
				'at_passcode', // The password that was used to make the account
			] )
			->where( [ 'at_wiki' => WikiMap::getCurrentWikiId() ] )
			->caller( $caller );
	}

	public function saveUser( SocialMediaUser $user ): bool {
		$wikiId = $this->config->get( ConfigNames::CentralWiki );
		$wikiIds = $this->config->get( MainConfigNames::LocalDatabases );

		if ( !in_array( $wikiId, $wikiIds ) ) {
			throw new UnexpectedValueException( "\"{$wikiId}\" is not a valid database for querying AT Protocol users" );
		}

		$db = $this->factory->getPrimaryDatabase( $wikiId );
		$db->newInsertQueryBuilder()
			->table( 'atproto_users' )
			->row( [
				'at_wiki' => WikiMap::getCurrentWikiId(),
				'at_platform' => $user->platform,
				'at_handle' => $user->baseHandle,
				'at_domain' => $user->domainHandle,
				'at_email' => $user->email,
				'at_passcode' => $this->encryptPasscode( $user->password ),
			] )
			->caller( __METHOD__ )
			->execute();

		return false;
	}

	private function encryptPasscode( string $passcode ): string {
		$key = $this->config->get( ConfigNames::EncryptionKey );
		if ( !$key ) {
			throw new IllegalOperationException( 'Should not be reachable here when an encryption key has not been defined' );
		}

		$size = openssl_cipher_iv_length( 'sha256' );
		$nonce = openssl_random_pseudo_bytes( $size );

		return base64_encode( openssl_encrypt(
			$passcode,
			'sha256',
			$key,
			OPENSSL_RAW_DATA,
			$nonce
		) );
	}

	private function decryptPasscode( string $encrypted ): ?string {
		$key = $this->config->get( ConfigNames::EncryptionKey );
		if ( !$key ) {
			throw new IllegalOperationException( 'Should not be reachable here when an encryption key has not been defined' );
		}

		$raw = base64_decode( $encrypted );
		if ( !$raw ) {
			return null;
		}

		$size = openssl_cipher_iv_length( 'sha256' );
		$nonce = mb_substr( $raw, 0, $size, '8bit' );
		$passcode = mb_substr( $raw, $size, null, '8bit' );

		return openssl_decrypt(
			$passcode,
			'sha256',
			$key,
			OPENSSL_RAW_DATA,
			$nonce
		);
	}

	/**
	 * Form a complete description using the configured prefix and suffix,
	 *   trims any whitespace and joins together by spaces
	 * @param string $input User provided description
	 * @return string
	 */
	public function formDescription( string $input ): string {
		$prefix = $this->config->get( ConfigNames::DescPrefix ) ?? '';
		$suffix = $this->config->get( ConfigNames::DescSuffix ) ?? '';

		$values = [];
		$raws = [
			$prefix,
			$input,
			$suffix
		];

		foreach ( $raws as $raw ) {
			$raw = trim( $raw );
			if ( $raw ) {
				$values[] = $raw;
			}
		}

		return implode( ' ', $values );
	}
}