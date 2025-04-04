<?php

namespace MediaWiki\Extension\ATBridge\Services;

use Error;

class ATProtoPasscodes {
    const CIPHER = 'aes-256-cbc';

    public static function generate( $length = 32 ): string {
        $out = [];
        $validCharacters = 'abcdefghijklmnopqrstuvwxyz'
            . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            . '0123456789'
            . '`-=~!@#$%^&*()_+,./<>?;:[]{}';
        $max = strlen( $validCharacters ) - 1;

        for ( $i = 0; $i++ < $length; ) {
            $out[] = $validCharacters[ random_int( 0, $max ) ];
        }

        return implode( '', $out );
    }

    public static function encryptPasscode( string $key, string $passcode ): string {
        $size = openssl_cipher_iv_length( self::CIPHER );
        $nonce = $size > 0 ? openssl_random_pseudo_bytes( $size ) : '';

        return base64_encode( $nonce . openssl_encrypt(
            $passcode,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        ) );
    }

    public static function decryptPasscode( string $key, string $encrypted ): ?string {
        $raw = base64_decode( $encrypted );
        if ( !$raw ) {
            return null;
        }

        $size = openssl_cipher_iv_length( self::CIPHER );
        if ( $size > 0 ) {
            // Trim the nonce from the start
            $nonce = mb_substr( $raw, 0, $size, '8bit' );
            $passcode = mb_substr( $raw, $size, null, '8bit' );
        } else {
            $nonce = '';
            $passcode = $raw;
        }

        return openssl_decrypt(
            $passcode,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );
    }
}