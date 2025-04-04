<?php

namespace MediaWiki\Extension\ATBridge\Services;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ATBridge\Consts\ConfigNames;
use MediaWiki\Extension\ATBridge\SocialMediaUser;
use MediaWiki\WikiMap\WikiMap;
use ValueError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;
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
	    $wikiId = WikiMap::getCurrentWikiId();
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
		$query = $this->newUsersDatabaseQuery()
		    ->where( [ 'at_wiki' => WikiMap::getCurrentWikiId() ] );

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
		return $this->getReadDatabase()
		    ->newSelectQueryBuilder()
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
			->orderBy([ 'at_wiki', 'at_platform', 'at_platform_uniq' ])
			->caller( $caller );
	}

    public function createUser( string $platform ): SocialMediaUser {
        $email = $this->config->get( ConfigNames::Email );
        if ( !$email ) {
            throw new ValueError( 'Email address for ATProto accounts not configured' );
        }

        $password = ATProtoPasscodes::generate();
        $id = 1;

        wfDebugLog('ATBridge', 'Password ' . $password);

        $query = $this->getReadDatabase()
            ->newSelectQueryBuilder()
            ->table( 'atproto_users' )
            ->field( 'MAX(at_platform_uniq)', 'max' )
            ->where( [
                'at_wiki' => WikiMap::getCurrentWikiId(),
                'at_platform' => $platform
            ] )
            ->caller( __METHOD__ )
            ->fetchRow();

        // If read from the database, take the current max id and add 1
        if ( $query ) {
            $id = intval( $query->max ) + 1;
        }

        $user = new SocialMediaUser( $platform, $id );

        $user->email = $email;
        $user->password = $password;

        return $user;
    }

	public function saveUser( SocialMediaUser $user ): bool {
	    // If we already have a global Id, we need to update and not insert new
	    if ( $user->globalId ) {
	        return false;
	    }

        if ( !$user->email ) {
            throw new ValueError( 'Unable to save atproto user to database, missing a configured registration email' );
        }

        if ( !$user->password ) {
            throw new ValueError( 'Unable to save atproto user to database, missing a configured generated password' );
        }

        $passcode = $this->encryptPasscode( $user->password );
        wfDebugLog('ATBridge', 'Encrypted password ' . $passcode);

		$db = $this->getWriteDatabase();
		$db->newInsertQueryBuilder()
			->table( 'atproto_users' )
			->row( [
				'at_wiki' => WikiMap::getCurrentWikiId(),
				'at_platform' => $user->platform,
				'at_platform_uniq' => $user->wikiId,
				'at_handle' => $user->baseHandle,
				'at_domain' => $user->domainHandle,
				'at_email' => $user->email,
				'at_passcode' => $passcode,
			] )
			->caller( __METHOD__ )
			->execute();

        if ( !$db->lastErrno() ) {
            $user->globalId = $db->insertId();
            return true;
        }

		return false;
	}

    public function updateUser( SocialMediaUser $user ): bool {
        // Must have a global Id in order to update
        if ( !$user->globalId ) {
            return false;
        }

        $db = $this->getWriteDatabase();
        $db->newUpdateQueryBuilder()
            ->table( 'atproto_users' )
            ->set([
                'at_handle' => $user->baseHandle,
                'at_domain' => $user->domainHandle,
                'at_email' => $user->email,
                'at_passcode' => $this->encryptPasscode( $user->password )
            ])
            ->where([
                'at_uniq' => $user->globalId
            ])
            ->caller( __METHOD__ )
            ->execute();

        return !$db->lastErrno();
    }

	private function encryptPasscode( string $passcode ): string {
		$key = $this->config->get( ConfigNames::EncryptionKey );
		if ( !$key ) {
			throw new IllegalOperationException( 'Should not be reachable here when an encryption key has not been defined' );
		}

        return ATProtoPasscodes::encryptPasscode( $key, $passcode );
	}

	private function decryptPasscode( string $encrypted ): ?string {
		$key = $this->config->get( ConfigNames::EncryptionKey );
		if ( !$key ) {
			throw new IllegalOperationException( 'Should not be reachable here when an encryption key has not been defined' );
		}

		return ATProtoPasscodes::decryptPasscode( $key, $encrypted );
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

	private function getReadDatabase(): IReadableDatabase {
        return $this->factory->getReplicaDatabase( $this->getDatabaseKey() );
    }

    private function getWriteDatabase(): IDatabase {
        return $this->factory->getPrimaryDatabase( $this->getDatabaseKey() );
    }

    private function getDatabaseKey(): string {
        $wikiId = $this->config->get( ConfigNames::CentralWiki );
        return $wikiId ?? WikiMap::getCurrentWikiId();
    }
}