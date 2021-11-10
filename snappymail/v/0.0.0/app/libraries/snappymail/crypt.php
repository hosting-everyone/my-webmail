<?php

namespace SnappyMail;

abstract class Crypt
{
	/**
	 * Or use 'aes-256-xts' ?
	 */
	protected static $cipher = 'aes-256-cbc-hmac-sha1';

	public static function listCiphers() : array
	{
		$list = array();
		if (\function_exists('openssl_get_cipher_methods')) {
			$list = \openssl_get_cipher_methods();
			$list = \array_diff($list, \array_map('strtoupper',$list));
			$list = \array_filter($list, function($v){
				// DES/ECB/bf/rc insecure, GCM/CCM not supported
				return !\preg_match('/(^(des|bf|rc))|-(ecb|gcm|ccm)/i', $v);
			});
			\natcasesort($list);
		}
		return $list;
	}

	public static function setCipher(string $cipher) : bool
	{
		if ($cipher) {
			$aCiphers = static::listCiphers();
			if (\in_array($cipher, $aCiphers)) {
				static::$cipher = $cipher;
				return true;
			}
		}
		return false;
	}

	private static function Passphrase() : string
	{
		return \sha1(\preg_replace('/[^a-z]+/i', '', \explode(')', $_SERVER['HTTP_USER_AGENT'])[0]) . APP_SALT, true);
	}

	public static function DecryptCookie($sData)
	{
		return \json_decode(
			\zlib_decode(
				\MailSo\Base\Crypt::Decrypt(
					\MailSo\Base\Utils::UrlSafeBase64Decode($sData),
					static::Passphrase()
				)
			),
			true
		);
	}

	public static function EncryptCookie($mData) : string
	{
		return \MailSo\Base\Utils::UrlSafeBase64Encode(
			\MailSo\Base\Crypt::Encrypt(
				\zlib_encode(
					\json_encode($mData),
					ZLIB_ENCODING_RAW,
					9
				),
				static::Passphrase()
			)
		);
	}

	public static function EncryptRaw($data) : array
	{
		if (static::$cipher && \is_callable('openssl_encrypt')) {
			$iv = \random_bytes(\openssl_cipher_iv_length(static::$cipher));
			$data = \openssl_encrypt(
				\json_encode($data),
				static::$cipher,
				static::Passphrase(),
				OPENSSL_RAW_DATA,
				$iv
			);
			return [static::$cipher, $iv, $data];
		}

		$salt = \random_bytes(16);
		return ['xxtea', $salt, static::XxteaEncrypt($data, $salt)];
	}

	public static function OpenSSLDecrypt(string $data, string $iv)
	{
		if (!$data || !$iv) {
			return null;
		}
		if (!static::$cipher || !\is_callable('openssl_decrypt')) {
			return static::XxteaDecrypt($data, $iv);
		}
		return \json_decode(\openssl_decrypt(
			$data,
			static::$cipher,
			static::Passphrase(),
			OPENSSL_RAW_DATA,
			$iv
		), true);
	}

	public static function XxteaDecrypt(string $data, string $salt)
	{
		if (!$data || !$salt) {
			return null;
		}
		$key = $salt . static::Passphrase();
		return \json_decode(\is_callable('xxtea_decrypt')
			? \xxtea_decrypt($data, $key)
			: \MailSo\Base\Xxtea::decrypt($data, $key)
		, true);
	}

	public static function XxteaEncrypt($data, string $salt) : string
	{
		if (!$data || !$salt) {
			return null;
		}
		$data = \json_encode($data);
		$key = $salt . static::Passphrase();
		return \is_callable('xxtea_encrypt')
			? \xxtea_encrypt($data, $key)
			: \MailSo\Base\Xxtea::encrypt($data, $key);
	}

}
