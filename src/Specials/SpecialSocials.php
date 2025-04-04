<?php

namespace MediaWiki\Extension\ATBridge\Specials;

use ErrorPageError;
use MediaWiki\Extension\ATBridge\Abstractions\AbstractATProtoPlatform;
use MediaWiki\Extension\ATBridge\Consts\GrantNames;
use MediaWiki\Extension\ATBridge\Services\ATProtoHelper;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\Extension\ATBridge\SocialMediaUser;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use PermissionsError;
use RepoGroup;

class SpecialSocials extends SpecialPage {
    public function __construct() {
        parent::__construct( 'Socials', '', true );
    }

    private function getHelper(): ATProtoHelper {
        return MediaWikiServices::getInstance()
            ->getService(ATProtoHelper::ServiceName);
    }

    private function getPlatformHelper(): ATProtoPlatformHelper {
        return MediaWikiServices::getInstance()
            ->getService(ATProtoPlatformHelper::ServiceName);
    }

    private function getFileRepo(): RepoGroup {
        return MediaWikiServices::getInstance()
            ->getRepoGroup();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function userCanExecute( User $user ): bool {
        return MediaWikiServices::getInstance()
            ->getPermissionManager()
            ->userHasAnyRight( $user,
                // Check if user has any permission that would allow access to accounts (Either for visibility or to manage)
                GrantNames::Manage,
                GrantNames::ManageDangerous,
                GrantNames::Post,
                GrantNames::Repost,
                GrantNames::RepostGlobal
            );
    }

    /**
     * Whether the output of this page should be cacheable, only cache if we're not performing an action,
     *   or in some cases if we want the most up to date information
     * @return bool
     */
    public function isCached(): bool {
        return $this->getRequest()->getMethod() !== 'POST';
    }

    /**
     * @throws PermissionsError
     * @throws ErrorPageError
     */
    public function execute( $subPage ): void {
        $this->setHeaders();
        $this->checkPermissions();

        // Verify permission to view the page
        $securityLevel = $this->getLoginSecurityLevel();
        if ( $securityLevel !== false && !$this->checkLoginSecurityLevel( $securityLevel ) ) {
            return;
        }

        $this->outputHeader();

        if ( $subPage ) {
            // Read the platform and id from the URL
            [ $type, $accountNum ] = explode( '/', $subPage, 2 );

            // Get all the registered platforms
            $platforms = $this->getHelper()
                ->getPlatforms();

            $platform = $platforms[ $type ] ?? null;

            if ( !$platform ) {
                $this->getOutput()->showErrorPage(
                    'atbridge-platform-missing',
                    'atbridge-platform-missing-desc',
                    [ $type ],
                    'Special:Socials'
                );

            } else if ( $accountNum === 'new' ) {
                $this->executeAccountCreate( $platform );

            } else if ( is_numeric( $accountNum ) ) {
                // Run a db search for the account
                $account = $this->getPlatformHelper()
                    ->getUser( $type, $accountNum );

                if ( $account ) {
                    $this->executeAccountView( $account );
                    return;
                }

                // If accounts doesn't exist, redirect to the account selector
                $this->getOutput()
                    ->redirect( $this->getPageTitle()->getFullURL() );

            }
        } else {
            // No current account selected, display the selector / creator view
            $this->executeAccountSelector();

        }
    }

    /**
     * Display the account selector, or the option to create a new account
     * @return void
     */
    public function executeAccountSelector(): void {
        $platforms = $this->getHelper()
            ->getPlatforms();

        $accounts = $this->getPlatformHelper()
            ->getUsers();

        wfDebugLog('ATBridge', sizeof($platforms) . ' platforms');
        wfDebugLog('ATBridge', sizeof($accounts) . ' users');

        // Get all the keys for platforms
        $keys = [];
        $selectors = [];

        // Get an ordered set of accounts
        foreach ( $accounts as $account ) {
            $keys[] = $account->platform;
            $selectors[] = Html::rawElement( 'li', [], $this->createPlatformSelector( $platforms[$account->platform], $account ) );
        }

        foreach ( $platforms as $name => $platform ) {
            wfDebugLog('ATBridge', 'Platform ' . $name);

            // If we already have an account for the platform
            if ( in_array( $name, $keys ) ) {
                continue;
            }

            $selectors[] = Html::rawElement( 'li', [], $this->createPlatformSelector( $platform ) );
        }

        $output = $this->getOutput();
        $output->enableOOUI();

        $output->addHTML( Html::rawElement( 'ul', [
            'class' => 'ext-atb-selectorlist'
        ], implode('', $selectors) ) );
    }

    /**
     * Display an option to the user to create an account
     * @param AbstractATProtoPlatform $platform Platform to create an account for
     * @return void
     */
    public function executeAccountCreate( AbstractATProtoPlatform $platform ): void {
        $account = $this->getPlatformHelper()
            ->getUser( $platform->name );
        $out = $this->getOutput();

        if ( $account && $account->baseHandle ) {
            wfDebugLog('ATBridge', "Platform $platform->name account already exists");
            $out->showErrorPage(
                'atbridge-platform-exists',
                'atbridge-platform-exists-desc',
                [],
                'Special:Socials'
            );
        } else {
            $req = $this->getRequest();
            $posted = $req->getMethod() === 'POST';
            $helper = $this->getPlatformHelper();
            $created = false;

            if ( !$account && $posted ) {
                // Try submitting the job to create a new account
                $account = $helper->createUser( $platform->name );
                $created = $helper->saveUser( $account );
            }

            if ( $created ) {
                wfDebugLog('ATBridge', "Creating new $platform->name account");
            } else {
                wfDebugLog('ATBridge', "Not creating $platform->name account");
            }
        }
    }

    /**
     * Display the account view for an ATProto account
     * @param SocialMediaUser $user User to display options for
     * @return void
     */
    public function executeAccountView( SocialMediaUser $user ): void {
        
    }

    private function createPlatformSelector( AbstractATProtoPlatform $platform, ?SocialMediaUser $user = null ): string {
        $linkAttr = [];

        if ( $user ) {
            $special = $this->getPageTitle("$platform->name/$user->wikiId");
        } else {
            $special = $this->getPageTitle("$platform->name/new");
            $linkAttr['rel'] = 'nofollow';
        }

        $linkAttr['href'] = $special->getLocalURL();

        return Html::rawElement( 'a', $linkAttr, $platform->name );
    }
}