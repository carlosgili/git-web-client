<?php

namespace VersionControl\GitControlBundle\Encryptors;

use VersionControl\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

/**
 * Class for AES256 encryption.
 *
 * @author Paul Schweppe
 * @author Victor Melnik <melnikvictorl@gmail.com>
 */
class AES256Encryptor implements EncryptorInterface
{
    /**
     * Secret key for aes algorythm.
     *
     * @var string
     */
    private $secretKey;

    /**
     * Initialization of encryptor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->secretKey = $key;
    }

    /**
     * Implementation of EncryptorInterface encrypt method.
     *
     * @param string $data
     *
     * @return string
     */
    public function encrypt($data)
    {
        if (is_null($data) || trim($data) == '') {
            return $data;
        }

        if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7) {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-256-ECB"));
        
            return trim(base64_encode(openssl_encrypt ( 
                $data , 
                "AES-256-ECB", 
                $this->secretKey,
                0,
                $iv)));
        }else{
            return trim(base64_encode(mcrypt_encrypt(
                                        MCRYPT_RIJNDAEL_256, $this->secretKey, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND
                                        ))));
        }
    }

    /**
     * Implementation of EncryptorInterface decrypt method.
     *
     * @param string $data
     *
     * @return string
     */
    public function decrypt($data)
    {
        if (is_null($data) || trim($data) == '') {
            return $data;
        }

        if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7) {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-256-ECB"));
            
            return trim(openssl_decrypt ( 
                base64_decode($data) , 
                "AES-256-ECB", 
                $this->secretKey,
                0,
                $iv));
        }else{
            return trim(mcrypt_decrypt(
                                MCRYPT_RIJNDAEL_256, $this->secretKey, base64_decode($data), MCRYPT_MODE_ECB, mcrypt_create_iv(
                                        mcrypt_get_iv_size(
                                                MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB
                                        ), MCRYPT_RAND
                                )));
        }
    }
}
