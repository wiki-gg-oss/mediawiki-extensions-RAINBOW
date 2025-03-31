<?php

namespace MediaWiki\Extension\ATBridge\Specials;

use ErrorPageError;
use MediaWiki\Extension\ATBridge\Consts\GrantNames;
use MediaWiki\Extension\ATBridge\Services\ATProtoPlatformHelper;
use MediaWiki\Extension\ATBridge\SocialMediaUser;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use PermissionsError;

class SpecialSocials extends SpecialPage {
    public function __construct() {
        parent::__construct( 'Socials', '', true );
    }

    private function getHelper(): ATProtoPlatformHelper {
        return MediaWikiServices::getInstance()
            ->getService(ATProtoPlatformHelper::ServiceName);
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
     * @throws PermissionsError
     * @throws ErrorPageError
     */
    public function execute( $subPage ) {
        $this->setHeaders();
        $this->checkPermissions();

        // Verify permission to view the page
        $securityLevel = $this->getLoginSecurityLevel();
        if ( $securityLevel !== false && !$this->checkLoginSecurityLevel( $securityLevel ) ) {
            return;
        }

        $this->outputHeader();

        if ( $subPage ) {
            [ $type, $accountNum ] = explode( '/', $subPage, 2 );

            if ( is_numeric( $accountNum ) ) {
                // Run a db search for the account
                $account = $this->getHelper()
                    ->getUser($type, $accountNum);

                if ( $account ) {
                    $this->executeAccountView( $account );
                    return;
                }
            }

            // If accounts doesn't exist, redirect to the account selector
            $this->getOutput()
                ->redirect(SpecialPage::getTitleFor('Socials')->getFullURL());

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
        $accounts = $this->getHelper()
            ->getUsers();
    }

    /**
     * Display the account view for an ATProto account
     * @param SocialMediaUser $user User to display options for
     * @return void
     */
    public function executeAccountView( SocialMediaUser $user ): void {
        
    }
}