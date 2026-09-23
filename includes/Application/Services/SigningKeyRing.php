<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class SigningKeyRing
{
    private const OPTION_NAME = 'sltr_signing_keyring_v1';

    private const PURPOSES = [
        'license',
        'update',
    ];

    public function resolve(
        string $purpose,
        string $keyId,
        string $builtInId,
        string $builtInPem
    ): ?string {
        if (
            !in_array($purpose, self::PURPOSES, true)
            || !$this->validKeyId($keyId)
        ) {
            return null;
        }

        $state = $this->state();

        if ($this->isCompromised($state, $purpose, $keyId)) {
            return null;
        }

        if ($this->isRetired($state, $purpose, $keyId)) {
            return null;
        }

        if (hash_equals($builtInId, $keyId)) {
            return $this->pemMatchesId($builtInPem, $keyId)
                ? $builtInPem
                : null;
        }

        $entry = $state['keys'][$purpose][$keyId] ?? null;

        if (
            !is_array($entry)
            || !is_string($entry['public_key'] ?? null)
            || !is_string($entry['not_before'] ?? null)
        ) {
            return null;
        }

        $notBefore = strtotime($entry['not_before']);

        if (
            $notBefore === false
            || $notBefore > time() + 300
            || !$this->pemMatchesId(
                $entry['public_key'],
                $keyId
            )
        ) {
            return null;
        }

        return $entry['public_key'];
    }

    public function acceptTransition(
        string $purpose,
        array $payload,
        string $signingKeyId
    ): void {
        $transition = $payload['next_key'] ?? null;

        if (
            !is_array($transition)
            || !in_array($purpose, self::PURPOSES, true)
        ) {
            return;
        }

        $state = $this->state();

        if (
            $this->isCompromised(
                $state,
                $purpose,
                $signingKeyId
            )
        ) {
            return;
        }

        if (
            ($transition['schema'] ?? '')
                !== 'slotera-next-signing-key/v1'
            || ($transition['purpose'] ?? '')
                !== $purpose
            || ($transition['signed_by'] ?? '')
                !== $signingKeyId
            || !is_string($transition['key_id'] ?? null)
            || !is_string($transition['public_key'] ?? null)
            || !is_string($transition['not_before'] ?? null)
            || !is_string(
                $transition['retire_current_after'] ?? null
            )
        ) {
            return;
        }

        $nextId = $transition['key_id'];
        $nextPem = $transition['public_key'];

        if (
            !$this->validKeyId($nextId)
            || hash_equals($signingKeyId, $nextId)
            || $this->isCompromised(
                $state,
                $purpose,
                $nextId
            )
            || !$this->pemMatchesId($nextPem, $nextId)
        ) {
            return;
        }

        $notBefore = strtotime($transition['not_before']);
        $retireAfter = strtotime(
            $transition['retire_current_after']
        );

        if (
            $notBefore === false
            || $retireAfter === false
            || $retireAfter <= $notBefore
            || $retireAfter <= time()
        ) {
            return;
        }

        $state['keys'][$purpose][$nextId] = [
            'public_key' => $nextPem,
            'not_before' => gmdate('c', $notBefore),
            'added_by' => $signingKeyId,
        ];

        $state['retire_at'][$purpose][$signingKeyId] =
            gmdate('c', $retireAfter);

        update_option(
            self::OPTION_NAME,
            $state,
            false
        );
    }

    public function acceptRecovery(
        string $purpose,
        array $payload,
        string $recoveryKeyId,
        string $builtInId
    ): bool {
        if (
            !in_array($purpose, self::PURPOSES, true)
            || !$this->validKeyId($recoveryKeyId)
            || !$this->validKeyId($builtInId)
        ) {
            return false;
        }

        if (
            ($payload['schema'] ?? '')
                !== 'slotera-license-recovery-v1'
            || ($payload['purpose'] ?? '') !== $purpose
            || !is_int($payload['recovery_sequence'] ?? null)
            || !is_string(
                $payload['compromised_key_id'] ?? null
            )
            || !is_string(
                $payload['replacement_key_id'] ?? null
            )
            || !is_string(
                $payload['replacement_public_key'] ?? null
            )
            || !is_string(
                $payload['not_before'] ?? null
            )
        ) {
            return false;
        }

        $sequence = $payload['recovery_sequence'];
        $compromisedId = $payload['compromised_key_id'];
        $replacementId = $payload['replacement_key_id'];
        $replacementPem =
            $payload['replacement_public_key'];

        if (
            $sequence < 1
            || !$this->validKeyId($compromisedId)
            || !$this->validKeyId($replacementId)
            || hash_equals(
                $compromisedId,
                $replacementId
            )
            || hash_equals(
                $recoveryKeyId,
                $replacementId
            )
            || !$this->pemMatchesId(
                $replacementPem,
                $replacementId
            )
        ) {
            return false;
        }

        $notBefore = strtotime($payload['not_before']);

        if ($notBefore === false) {
            return false;
        }

        $state = $this->state();

        $previousSequence = (int) (
            $state['recovery'][$purpose]['sequence']
                ?? 0
        );

        if ($sequence <= $previousSequence) {
            return false;
        }

        $knownCompromisedKey =
            hash_equals(
                $builtInId,
                $compromisedId
            )
            || isset(
                $state['keys'][$purpose][$compromisedId]
            );

        if (!$knownCompromisedKey) {
            return false;
        }

        if (
            $this->isCompromised(
                $state,
                $purpose,
                $replacementId
            )
        ) {
            return false;
        }

        $acceptedAt = gmdate('c');

        $state[
            'compromised'
        ][$purpose][$compromisedId] = [
            'recovery_sequence' => $sequence,
            'recovery_key_id' => $recoveryKeyId,
            'replaced_by' => $replacementId,
            'accepted_at' => $acceptedAt,
        ];

        $state[
            'keys'
        ][$purpose][$replacementId] = [
            'public_key' => $replacementPem,
            'not_before' => gmdate('c', $notBefore),
            'added_by' => 'recovery:' . $recoveryKeyId,
        ];

        $state['recovery'][$purpose] = [
            'sequence' => $sequence,
            'recovery_key_id' => $recoveryKeyId,
            'recovered_from' => $compromisedId,
            'replacement_key_id' => $replacementId,
            'accepted_at' => $acceptedAt,
        ];

        update_option(
            self::OPTION_NAME,
            $state,
            false
        );

        return true;
    }

    private function state(): array
    {
        $state = get_option(
            self::OPTION_NAME,
            []
        );

        if (!is_array($state)) {
            $state = [];
        }

        foreach (
            [
                'keys',
                'retire_at',
                'compromised',
                'recovery',
            ] as $section
        ) {
            if (
                !isset($state[$section])
                || !is_array($state[$section])
            ) {
                $state[$section] = [];
            }
        }

        foreach (self::PURPOSES as $purpose) {
            foreach (
                [
                    'keys',
                    'retire_at',
                    'compromised',
                ] as $section
            ) {
                if (
                    !isset(
                        $state[$section][$purpose]
                    )
                    || !is_array(
                        $state[$section][$purpose]
                    )
                ) {
                    $state[$section][$purpose] = [];
                }
            }

            if (
                !isset(
                    $state['recovery'][$purpose]
                )
                || !is_array(
                    $state['recovery'][$purpose]
                )
            ) {
                $state['recovery'][$purpose] = [
                    'sequence' => 0,
                ];
            }
        }

        return $state;
    }

    private function isRetired(
        array $state,
        string $purpose,
        string $keyId
    ): bool {
        $retireAt =
            $state['retire_at'][$purpose][$keyId]
                ?? '';

        if (
            !is_string($retireAt)
            || $retireAt === ''
        ) {
            return false;
        }

        $timestamp = strtotime($retireAt);

        return $timestamp !== false
            && $timestamp <= time();
    }

    private function isCompromised(
        array $state,
        string $purpose,
        string $keyId
    ): bool {
        return isset(
            $state[
                'compromised'
            ][$purpose][$keyId]
        );
    }

    private function validKeyId(
        string $keyId
    ): bool {
        return preg_match(
            '/^sha256:[a-f0-9]{64}$/',
            $keyId
        ) === 1;
    }

    private function pemMatchesId(
        string $pem,
        string $keyId
    ): bool {
        $body = preg_replace(
            '/-----BEGIN PUBLIC KEY-----|'
            . '-----END PUBLIC KEY-----|'
            . '\s+/',
            '',
            $pem
        );

        if (
            !is_string($body)
            || $body === ''
        ) {
            return false;
        }

        $der = base64_decode(
            $body,
            true
        );

        if (
            !is_string($der)
            || $der === ''
        ) {
            return false;
        }

        return hash_equals(
            $keyId,
            'sha256:' . hash('sha256', $der)
        );
    }
}
