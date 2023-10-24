<?php

namespace App;

use \Firebase\JWT\JWT;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use App\Models\PublicKey;

class CognitoJWT
{
    private $region;
    private $client_id;
    private $userpool_id;
    private $client;

    /**
     * Get the applications user pool public key from cognito server
     * @param string $kid
     * @param string $region
     * @param string $userPoolId
     * @return string|null
     */
    public static function getPublicKey(string $kid, string $region, string $userPoolId): ?string
    {
        $jwksUrl = sprintf('https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json', $region, $userPoolId);
        $ch = curl_init($jwksUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
         $jwks = curl_exec($ch);
        if ($jwks) {
            $json = json_decode($jwks, false);
            if ($json && isset($json->keys) &&
                is_array($json->keys)) {
                foreach ($json->keys as $jwk) {
                    if ($jwk->kid === $kid) {
                        return static::jwkToPem($jwk);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Convert JWT token to a Pem Key
     * @param object $jwk
     * @return string|null
     */
    public static function jwkToPem(object $jwk): ?string
    {
        if (isset($jwk->e) && isset($jwk->n)) {
            $rsa = new RSA();
            $rsa->loadKey([
                'e' => new BigInteger(
                    JWT::urlsafeB64Decode($jwk->e), 256),
                'n' => new BigInteger(
                    JWT::urlsafeB64Decode($jwk->n), 256)
            ]);
            return $rsa->getPublicKey();
        }
        return null;
    }

    /** Get Key ID from JWT token
     * @param string $jwt
     * @return string|null
     */
    public static function getKid(string $jwt): ?string
    {
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $header =
                JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
            if (isset($header->kid)) {
                return $header->kid;
            }
        }
        return null;
    }
    public static function verifyToken(string $jwt, string $region, string $userPoolId): ?object
    {
        $publicKey = null;
        // Get the token key id
        $kid = static::getKid($jwt);
        if ($kid) {
            // Check if the token is available in storage
            $row = PublicKey::find($kid);
            if ($row) {
                $publicKey = $row->public_key;
            }
            else {
                // Fetch from cognito server
                $publicKey = static::getPublicKey($kid, $region, $userPoolId);
                // Save in storage
                $row = PublicKey::create(['kid' => $kid, 'public_key' => $publicKey]);
            }
        }
        if ($publicKey) {
            try {
                return JWT::decode($jwt, $publicKey, array('RS256'));
            } catch (\ErrorException $exception) {
                return false;
                //@Todo catch the exception and return the proper error message
            }
        }
        return null;
    }
}
