<?php

namespace WebexEvents;

use WebexEvents\Exceptions\AccessTokenIsRequiredError;
use  WebexEvents\Constants\Platform;
use  WebexEvents\Constants\Version;

class Helpers
{
    public static string $ACCESS_TOKEN_IS_MISSING = "Access token is missing.";
    private static ?string $userAgent = null;
    private static array $uris = [];

    /**
     * @return string
     */
    static function getSdkVersion(): string
    {
        return Version::VERSION;
    }

    /**
     * @throws AccessTokenIsRequiredError
     */
    static function validateAccessTokenExistence(): void
    {
        if (Configuration::getAccessToken() === "" || Configuration::getAccessToken() === null) {
            throw new AccessTokenIsRequiredError(self::$ACCESS_TOKEN_IS_MISSING);
        }
    }

    static function getUserAgent(): string
    {
        if (self::$userAgent != null) {
            return self::$userAgent;
        }
        self::$userAgent = 'WebexEventsPhpSDK';
        return self::$userAgent;
    }

    static function getUri(string $accessToken): string
    {
        if (array_key_exists($accessToken, self::$uris)) {
            return self::$uris[$accessToken];
        }

        $url = "";
        if (strpos($accessToken, "sk_live") === 0) {
            $url = Platform::productionUrl . Platform::graphqlPath;
        } else if (strpos($accessToken, "sk_test") === 0) {
            $url = Platform::testUrl . Platform::graphqlPath;
        } else {
            $url = Platform::localUrl . Platform::graphqlPath;
        }

        self::$uris[$accessToken] = $url;
        return $url;
    }

    static function getIntrospectionQuery(): string
    {
        return 'query IntrospectionQuery {
        __schema {
            queryType { name }
              mutationType { name }
              subscriptionType { name }
              types {
                ...FullType
              }
              directives {
                name
                description
                locations
                args {
                    ...InputValue
                }
              }
            }
          }
          fragment FullType on __Type {
        kind
            name
            description
            fields(includeDeprecated: true) {
            name
              description
              args {
            ...InputValue
              }
              type {
            ...TypeRef
              }
              isDeprecated
              deprecationReason
            }
            inputFields {
            ...InputValue
            }
            interfaces {
            ...TypeRef
            }
            enumValues(includeDeprecated: true) {
            name
              description
              isDeprecated
              deprecationReason
            }
            possibleTypes {
            ...TypeRef
            }
          }
          fragment InputValue on __InputValue {
        name
            description
            type { ...TypeRef }
            defaultValue
          }
          fragment TypeRef on __Type {
        kind
            name
            ofType {
            kind
              name
              ofType {
                kind
                name
                ofType {
                    kind
                  name
                  ofType {
                        kind
                    name
                    ofType {
                            kind
                      name
                      ofType {
                                kind
                        name
                        ofType {
                                    kind
                          name
                        }
                      }
                    }
                  }
                }
              }
            }
          }';
    }

    static function generateUUID(): string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}