<?php

namespace MediaWiki\Extension\ATBridge\Hooks;

use MediaWiki\Extension\ATBridge\Consts\ConfigNames;
use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\WikiMap\WikiMap;

class DbSchemaHooks implements LoadExtensionSchemaUpdatesHook {
    /**
     * @inheritdoc
     */
    public function onLoadExtensionSchemaUpdates( $updater ): void {
        $config = MediaWikiServices::getInstance()->getMainConfig();
        $centralWiki = $config->get( ConfigNames::CentralWiki );
        $wikiId = WikiMap::getCurrentWikiId();

        if ( !$centralWiki || $wikiId === $centralWiki ) {
            $this->addExtensionTable( $updater, 'atproto_users', 'central_tables.sql' );
            $this->addExtensionTable( $updater, 'atproto_optin', 'central_tables.sql' );
        }

        $this->addExtensionTable( $updater, 'atproto_post', 'local_tables.sql' );
    }

    /**
     * @param DatabaseUpdater $updater
     * @param string $tableName
     * @param string $file
     * @return void
     */
    private function addExtensionTable( DatabaseUpdater $updater, string $tableName, string $file ): void {
        $type = $updater->getDB()->getType();
        $path = $type === 'postgres' || $type === 'sqlite' ? $type . '/' : '';

        $updater->addExtensionTable( $tableName, __DIR__ . '/../../sql/' . $path . $file );
    }
}