<?php

namespace MediaWiki\Extension\ATBridge\Specials;

use ErrorPageError;
use MediaWiki\Extension\ATBridge\Consts\GrantNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PermissionsError;

class SpecialSocials extends SpecialPage {
    public function __construct() {
        parent::__construct( 'Socials', '', true );
    }

    public function userCanExecute( User $user ) {
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
            [ $type, $accountNum ] = str_split( $subPage, 2 );

            // TODO: Run a db search for the account
            $this->executeAccountView( $type, $accountNum );
            return;
        }

        // No current account selected, display the selector / creator view
        $this->executeAccountSelector();
    }

    public function executeAccountSelector() {
        
    }

    public function executeAccountView() {
        
    }
}