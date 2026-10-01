<?php

namespace SokinPay\PaymentGateway\Service;

/**
 * Redacts sensitive values before they are written to the module log file.
 *
 * The gateway request/response cycle carries the decrypted `x-api-key` secret,
 * cardholder references and customer personal data. None of that may be
 * persisted in clear text to var/log (CWE-532), so every value whose key is
 * recognised as sensitive is replaced by a fixed placeholder while the
 * surrounding structure is kept intact for debugging.
 */
class LogSanitizer
{
    /**
     * Placeholder written in place of a sensitive value
     */
    public const REDACTED = '***REDACTED***';

    /**
     * Normalised keys whose value must never be logged
     *
     * @var string[]
     */
    private const SENSITIVE_KEYS = [
        // Gateway credentials
        'xapikey',
        'apikey',
        'authorization',
        'proxyauthorization',
        'secretkey',
        'secret',
        'password',
        'token',
        'signature',
        // Card / bank details
        'cardnumber',
        'cardno',
        'pan',
        'cvv',
        'cvc',
        'cvv2',
        'securitycode',
        'expirydate',
        'iban',
        'accountnumber',
        'sortcode',
        // Customer personal data
        'firstname',
        'lastname',
        'fullname',
        'email',
        'phone',
        'phonenumber',
        'mobile',
        'addressline1',
        'addressline2',
        'addressline3',
        'street',
        'streetline1',
        'streetline2',
        'postcode',
        'posttown',
        'zip',
        'city',
    ];

    /**
     * Normalised fragments that mark a key as sensitive wherever they appear
     *
     * @var string[]
     */
    private const SENSITIVE_FRAGMENTS = [
        'apikey',
        'authorization',
        'secret',
        'password',
        'token',
        'cardnumber',
        'cvv',
    ];

    /**
     * Recursively redact sensitive values in arbitrary log payloads.
     *
     * Arrays are walked key by key. Strings holding a JSON document are
     * decoded, redacted and re-encoded so request bodies are covered too.
     * Any other scalar is returned untouched.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = $this->isSensitiveKey((string) $key)
                    ? self::REDACTED
                    : $this->sanitize($value);
            }
            return $sanitized;
        }

        if (is_string($data)) {
            return $this->sanitizeJsonString($data);
        }

        return $data;
    }

    /**
     * Redact a JSON encoded payload, preserving the original string when it is not JSON.
     *
     * @param string $value
     *
     * @return string
     */
    private function sanitizeJsonString($value)
    {
        if ($value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $value;
        }

        $encoded = json_encode($this->sanitize($decoded));

        return $encoded === false ? self::REDACTED : $encoded;
    }

    /**
     * Decide whether the value stored under the given key must be redacted.
     *
     * @param string $key
     *
     * @return bool
     */
    private function isSensitiveKey($key)
    {
        $normalized = $this->normalizeKey($key);
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (strpos($normalized, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduce a key to a comparable form, ignoring case and separators.
     *
     * @param string $key
     *
     * @return string
     */
    private function normalizeKey($key)
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($key));
    }
}
