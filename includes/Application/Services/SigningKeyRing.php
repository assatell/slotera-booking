<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
if (!defined('ABSPATH')) { exit; }

final class SigningKeyRing
{
    private const OPTION_NAME = 'sltr_signing_keyring_v1';
    private const PURPOSES = ['license', 'update'];

    public function resolve(string $purpose, string $keyId, string $builtInId, string $builtInPem): ?string
    {
        if (!in_array($purpose, self::PURPOSES, true) || !$this->validKeyId($keyId)) { return null; }
        $state = $this->state();
        if ($this->isRetired($state, $purpose, $keyId)) { return null; }
        if (hash_equals($builtInId, $keyId)) { return $this->pemMatchesId($builtInPem, $keyId) ? $builtInPem : null; }
        $entry = $state['keys'][$purpose][$keyId] ?? null;
        if (!is_array($entry) || !is_string($entry['public_key'] ?? null) || !is_string($entry['not_before'] ?? null)) { return null; }
        $notBefore = strtotime($entry['not_before']);
        if ($notBefore === false || $notBefore > time() + 300 || !$this->pemMatchesId($entry['public_key'], $keyId)) { return null; }
        return $entry['public_key'];
    }

    public function acceptTransition(string $purpose, array $payload, string $signingKeyId): void
    {
        $transition = $payload['next_key'] ?? null;
        if (!is_array($transition) || !in_array($purpose, self::PURPOSES, true)) { return; }
        if (($transition['schema'] ?? '') !== 'slotera-next-signing-key/v1'
            || ($transition['purpose'] ?? '') !== $purpose
            || ($transition['signed_by'] ?? '') !== $signingKeyId
            || !is_string($transition['key_id'] ?? null)
            || !is_string($transition['public_key'] ?? null)
            || !is_string($transition['not_before'] ?? null)
            || !is_string($transition['retire_current_after'] ?? null)) { return; }

        $nextId = $transition['key_id'];
        $nextPem = $transition['public_key'];
        if (!$this->validKeyId($nextId) || hash_equals($signingKeyId, $nextId) || !$this->pemMatchesId($nextPem, $nextId)) { return; }
        $notBefore = strtotime($transition['not_before']);
        $retireAfter = strtotime($transition['retire_current_after']);
        if ($notBefore === false || $retireAfter === false || $retireAfter <= $notBefore || $retireAfter <= time()) { return; }

        $state = $this->state();
        $state['keys'][$purpose][$nextId] = [
            'public_key' => $nextPem,
            'not_before' => gmdate('c', $notBefore),
            'added_by' => $signingKeyId,
        ];
        $state['retire_at'][$purpose][$signingKeyId] = gmdate('c', $retireAfter);
        update_option(self::OPTION_NAME, $state, false);
    }

    private function state(): array
    {
        $state = get_option(self::OPTION_NAME, []);
        if (!is_array($state)) { $state = []; }
        if (!isset($state['keys']) || !is_array($state['keys'])) { $state['keys'] = []; }
        if (!isset($state['retire_at']) || !is_array($state['retire_at'])) { $state['retire_at'] = []; }
        foreach (self::PURPOSES as $purpose) {
            if (!isset($state['keys'][$purpose]) || !is_array($state['keys'][$purpose])) { $state['keys'][$purpose] = []; }
            if (!isset($state['retire_at'][$purpose]) || !is_array($state['retire_at'][$purpose])) { $state['retire_at'][$purpose] = []; }
        }
        return $state;
    }

    private function isRetired(array $state, string $purpose, string $keyId): bool
    {
        $retireAt = $state['retire_at'][$purpose][$keyId] ?? '';
        if (!is_string($retireAt) || $retireAt === '') { return false; }
        $timestamp = strtotime($retireAt);
        return $timestamp !== false && $timestamp <= time();
    }

    private function validKeyId(string $keyId): bool
    {
        return preg_match('/^sha256:[a-f0-9]{64}$/', $keyId) === 1;
    }

    private function pemMatchesId(string $pem, string $keyId): bool
    {
        $body = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $pem);
        if (!is_string($body) || $body === '') { return false; }
        $der = base64_decode($body, true);
        if (!is_string($der) || $der === '') { return false; }
        return hash_equals($keyId, 'sha256:' . hash('sha256', $der));
    }
}
