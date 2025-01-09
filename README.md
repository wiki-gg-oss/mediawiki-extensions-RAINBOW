# RAINBOW

This repository is for an extension for MediaWiki to integrate with Bluesky's ATProtocol.

RAINBOW (Reach An Integrated Network (Bluesky), On-Wiki)

## Considerations

Some wiki admins will create Official (affiliated to the Wiki, not the subject of the wiki) social media accounts, so that contributors or users of that wiki can follow updates, events, or other information about the wiki.

The main idea of this extension is to allow wiki administrators, that do not also control the domain that the wiki is on, to control a [Bluesky](https://bsky.social/about) account that shares a username to the domain. Bluesky uses a domain-based verification system to allow it's users to identify authentic sources, for example `wikipedia.com` might be `@wikipedia.com` or even a subdomain `@bob.wikipedia.com`.

AT Protocol is a federated and decentralized protocol that allows users to host and follow their own web feeds that similarly implement the AT Protocol.

Although the AT Protocol allows decentralization, this extension simply makes to interact with the Bluesky API, and not act as a host itself. This is mainly to save on bandwidth. An extension could host and implement all of the protocols itself, but interacting with a third party API is a lot simpler in many ways.

This extension utilizes the simpler method of verifying DNS to grant access to the username, which is to write to the public `.well-known` folder, rather than trying to implement communications with different DNS providers. Though implementing a service is supported.

This extension does not operate to offer authentication to a pre-existing account on Bluesky made by the admins, but instead creates its own Bluesky account and retains that control on the wikifarm or host offering this extension. This is done in some effort to prevent admins from holding accounts hostage from other admins, and equally sharing accessibility to accounts to all who should have access. Admins may come and go, and the access to the account does not come into question.

In the case of wikifarms this extension also offers to its staff the ability to use wiki accounts to help spread its own posts. Wiki users and contributors may follow a wiki account to receive information about that specific wiki, but by allowing wikifarm staff to repost content, those users may receive information about new offerings, outages, updates, and other information.

## Installation



## Configurations

Below are the set of configuration options and their default values.

```php
$wgATProtoCentralWiki = null;
```

Sets the central Database identifier to be used for storing accounts in. This table is later referenced for doing cross-wiki reposts.

```php
$wgATProtoEmail = null;
```

The email that is used for registering accounts. An Email is required for registering to BlueSky.

```php
$wgATProtoPostCategories = [
    'announcements' => [
        'description' => '',
        'required' => false,
    ],
    'updates' => [
        'description' => '',
        'required' => false,
    ],
    'maintenance' => [
        'description' => '',
        'required' => true,
    ],
];
```

Associative array of categories that (re)posts can be set as. Non-primary accounts can control which of these categories may be reposted to their account, or always reposted by setting `required` to `true`.

```php
$wgATProtoDescPrefix = '';
$wgATProtoDescSuffix = '';
```

Forces a Prefix or Suffix into the description of the account. Otherwise allows users to customize the accounts description.

Default value is a string, but can be set to an array with language-code keys to have wiki-language specific values.

```php
$wgATProtoValidatorSettings = [
    'well-known' => [
        'path' => null,
    ],
];
```

Configure options for Domain validators. The only built-in option is utilizing the `.well-known` folder, which can be defined to a specific path.

```php
$wgATProtoEncryptionKey = null;
```

Encryption key used for storing and reading account passwords from the database.

## Permissions

Permissions are not granted to any groups automatically, but some suggestions are made below.

The implementation for deleting an account is offered, but should generally not be granted to wiki admins in a wikifarm as to not have an abuse loop of deleting-and-recreating an account over and over again.

If desired by the admins of a wiki, special user groups may be given access to post messages without having direct control over the rest of the account. Posts are tracked to the wiki account that made them, so they can be deleted by the poster, or by an admin.

- `atproto-manage` (Wiki admins)
  - Create and Manage the Social media account
- `atproto-manage-dangerous` (Staff)
  - Delete the Social media account
- `atproto-post` (Permitted wiki contributors)
  - Create new posts to the Social account
- `atproto-post-images` (Permitted wiki contributors)
  - Create new posts that include images
- `atproto-post-embeds` (Permitted wiki contributors)
  - Create new posts that include page embeds
- `atproto-post-delete` (Permitted wiki contributors)
  - Delete posts that were created by the user that created them
- `atproto-post-deleteany` (Wiki admins)
  - Delete posts made by any user
- `atproto-repost` (Permitted wiki contributors)
  - Create reposts
- `atproto-repost-global` (Staff)
  - Create global reposts

## Hooks

- `ATProtoPlatformRegister`
  - By default this Extension only supports BlueSky, this hook can be utilized to register additional platforms
- `ATProtoDomainValidationRegister`
    - By default this Extension only supports `well-known` domain validation. This hook can be used for example to introduce `dns` or other options.

## Usage
