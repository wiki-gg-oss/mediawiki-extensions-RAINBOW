-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: /home/greg/Projects/Phpstorm/mediawiki-extensions-ATBridge/sql/central_tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/atproto_users (
  at_uniq INT UNSIGNED AUTO_INCREMENT NOT NULL,
  at_wiki VARBINARY(255) NOT NULL,
  at_platform VARBINARY(255) NOT NULL,
  at_platform_uniq INT UNSIGNED NOT NULL,
  at_handle VARBINARY(255) NOT NULL,
  at_domain VARBINARY(255) DEFAULT NULL,
  at_email VARBINARY(255) NOT NULL,
  at_passcode VARBINARY(255) NOT NULL,
  UNIQUE INDEX per_wiki_user (
    at_wiki, at_platform, at_platform_uniq
  ),
  INDEX at_wiki (at_wiki),
  PRIMARY KEY(at_uniq)
) /*$wgDBTableOptions*/;


CREATE TABLE /*_*/atproto_optin (
  opt_account INT UNSIGNED NOT NULL,
  opt_key VARBINARY(255) NOT NULL,
  opt_value TINYINT NOT NULL,
  INDEX opt_account (opt_account),
  PRIMARY KEY(opt_account, opt_key)
) /*$wgDBTableOptions*/;
