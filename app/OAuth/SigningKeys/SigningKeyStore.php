<?php

namespace App\OAuth\SigningKeys;

use SensitiveParameter;

/**
 * The signing keys in the storage volume, and the rules for rotating them.
 *
 * Each key is a private key file named `<kid>.key`. The manifest `keys.json` records
 * each key's state and when it entered that state, and so marks the active key. One
 * write of the manifest moves a key between states, so readers never see two active
 * keys. See docs/adr/0003-signing-keys-and-rotation.md.
 */
class SigningKeyStore
{
    /** How long a key stays staged before it may sign. Matches the JWKS cache time. */
    public const STAGING_PERIOD_SECONDS = 3600;

    /** How long a key stays retired before it may be removed. */
    public const RETIREMENT_PERIOD_SECONDS = 7200;

    private const NEW_KEY_BITS = 2048;

    /**
     * The manifest and key files as last read. The store is scoped to one request,
     * so a request reads each file once. Writing the manifest clears them.
     *
     * @var array<string, ManifestEntry>|null
     */
    private ?array $manifest = null;

    /** @var array<string, SigningKey> */
    private array $loaded = [];

    public function __construct(private readonly string $directory)
    {
    }

    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Every key in the JWKS, active key first.
     *
     * @return list<SigningKey>
     */
    public function published(): array
    {
        $order = [KeyState::Active, KeyState::Staged, KeyState::Retired];
        $manifest = $this->manifest();
        $keys = array_map($this->load(...), array_keys($manifest), $manifest);
        usort($keys, fn (SigningKey $a, SigningKey $b) => array_search($a->state, $order) <=> array_search($b->state, $order));

        return $keys;
    }

    public function find(string $kid): ?SigningKey
    {
        $entry = $this->manifest()[$kid] ?? null;

        return $entry === null ? null : $this->load($kid, $entry);
    }

    /** The key that signs new tokens. */
    public function active(): SigningKey
    {
        return $this->first(fn (ManifestEntry $entry) => $entry->state === KeyState::Active)
            ?? throw new SigningKeyException('There is no active signing key. Run `php artisan signing-keys:import` to create one.');
    }

    /** Passport's original key, while it is still published. It verifies tokens that have no kid. */
    public function imported(): ?SigningKey
    {
        return $this->first(fn (ManifestEntry $entry) => $entry->imported);
    }

    public function isEmpty(): bool
    {
        return $this->manifest() === [];
    }

    /** Adopt an existing key as the first active key of an empty store. */
    public function import(#[SensitiveParameter] string $privateKeyPem): SigningKey
    {
        $this->ensureEmpty('import a key');

        $kid = Jwk::thumbprint(Jwk::fromPrivateKey($privateKeyPem));
        $this->writeKeyFile($kid, $privateKeyPem);
        $entry = ManifestEntry::now(KeyState::Active, imported: true);
        $this->writeManifest([$kid => $entry]);

        return $this->load($kid, $entry);
    }

    /** Create the first key of an empty store, active at once with no staging. */
    public function createFirst(): SigningKey
    {
        $this->ensureEmpty('create a first key');

        return $this->createActive([]);
    }

    /** Create a new RSA key in the staged state. */
    public function generate(): SigningKey
    {
        $kid = $this->createKey();
        $entry = ManifestEntry::now(KeyState::Staged);
        $this->writeManifest($this->manifest() + [$kid => $entry]);

        return $this->load($kid, $entry);
    }

    /**
     * Make a staged key the active key and retire the old one.
     *
     * @return SigningKey|null The key that was retired.
     */
    public function activate(string $kid, bool $force = false): ?SigningKey
    {
        $manifest = $this->manifest();
        $key = $this->findOrFail($kid);

        if ($key->state !== KeyState::Staged) {
            throw new SigningKeyException("Key {$kid} is {$key->state->value}. Only a staged key can be activated.");
        }

        $this->ensureWaitIsOver($key, $force, 'activate', 'it has been staged for an hour, so relying parties have fetched it');

        $retired = null;
        foreach ($manifest as $otherKid => $entry) {
            if ($entry->state === KeyState::Active) {
                $manifest[$otherKid] = ManifestEntry::now(KeyState::Retired, $entry->imported);
                $retired = $otherKid;
            }
        }
        $manifest[$kid] = ManifestEntry::now(KeyState::Active, $key->imported);
        $this->writeManifest($manifest);

        return $retired === null ? null : $this->load($retired, $manifest[$retired]);
    }

    /** Delete a retired key once tokens it signed have expired. */
    public function remove(string $kid, bool $force = false): void
    {
        $key = $this->findOrFail($kid);

        if ($key->state !== KeyState::Retired) {
            throw new SigningKeyException("Key {$kid} is {$key->state->value}. Only a retired key can be removed. To delete it at once, use signing-keys:revoke.");
        }

        $this->ensureWaitIsOver($key, $force, 'remove', 'it has been retired for two hours, so the tokens it signed have expired');

        $this->deleteKey($kid);
    }

    /**
     * Delete a key at once. If it was active, a new key takes over with no staging.
     *
     * @return SigningKey|null The new active key, when the revoked key was active.
     */
    public function revoke(string $kid): ?SigningKey
    {
        $key = $this->findOrFail($kid);

        if ($key->state !== KeyState::Active) {
            $this->deleteKey($kid);

            return null;
        }

        // The successor becomes active in the same manifest write that drops the
        // revoked key, so there is never a moment with no active key.
        $manifest = $this->manifest();
        unset($manifest[$kid]);
        $successor = $this->createActive($manifest);
        $this->deleteKeyFile($kid);

        return $successor;
    }

    /** @param  callable(ManifestEntry): bool  $match */
    private function first(callable $match): ?SigningKey
    {
        foreach ($this->manifest() as $kid => $entry) {
            if ($match($entry)) {
                return $this->load($kid, $entry);
            }
        }

        return null;
    }

    private function ensureEmpty(string $action): void
    {
        if (! $this->isEmpty()) {
            throw new SigningKeyException("The key store already holds keys. Only an empty store can {$action}.");
        }
    }

    private function ensureWaitIsOver(SigningKey $key, bool $force, string $action, string $reason): void
    {
        $ready = $key->readyAt();

        if (! $force && $ready->isFuture()) {
            throw new SigningKeyException(sprintf(
                'You can %s key %s at %s, once %s. Pass --force to skip the wait.',
                $action, $key->kid, $ready->toIso8601String(), $reason,
            ));
        }
    }

    private function findOrFail(string $kid): SigningKey
    {
        return $this->find($kid) ?? throw new SigningKeyException("There is no published key with kid {$kid}.");
    }

    /** Unpublish a key and delete its file. */
    private function deleteKey(string $kid): void
    {
        $manifest = $this->manifest();
        unset($manifest[$kid]);

        $this->writeManifest($manifest);
        $this->deleteKeyFile($kid);
    }

    /**
     * Create a new key and write it to the manifest as the active key.
     *
     * @param  array<string, ManifestEntry>  $manifest  The other keys, none of them active.
     */
    private function createActive(array $manifest): SigningKey
    {
        $kid = $this->createKey();
        $manifest[$kid] = ManifestEntry::now(KeyState::Active);
        $this->writeManifest($manifest);

        return $this->load($kid, $manifest[$kid]);
    }

    /** Write a new RSA key file and return its kid. The manifest is left to the caller. */
    private function createKey(): string
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => self::NEW_KEY_BITS]);

        if ($key === false || ! openssl_pkey_export($key, $pem)) {
            throw new SigningKeyException('OpenSSL could not generate an RSA key: '.openssl_error_string());
        }

        $kid = Jwk::thumbprint(Jwk::fromPrivateKey($pem));
        $this->writeKeyFile($kid, $pem);

        return $kid;
    }

    private function load(string $kid, ManifestEntry $entry): SigningKey
    {
        return $this->loaded[$kid] ??= $this->read($kid, $entry);
    }

    private function read(string $kid, ManifestEntry $entry): SigningKey
    {
        $pem = @file_get_contents($this->keyPath($kid));

        if ($pem === false) {
            throw new SigningKeyException("The key file for {$kid} is missing or unreadable in {$this->directory}.");
        }

        return new SigningKey($kid, $entry->state, $pem, $entry->since, $entry->imported);
    }

    /** @return array<string, ManifestEntry> */
    private function manifest(): array
    {
        return $this->manifest ??= $this->readManifest();
    }

    /** @return array<string, ManifestEntry> */
    private function readManifest(): array
    {
        $path = $this->manifestPath();

        if (! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        if (! is_array($manifest) || ! is_array($manifest['keys'] ?? null)) {
            throw new SigningKeyException("The key manifest {$path} is not valid.");
        }

        return array_map(ManifestEntry::fromArray(...), $manifest['keys']);
    }

    /** @param  array<string, ManifestEntry>  $keys */
    private function writeManifest(array $keys): void
    {
        $data = ['keys' => array_map(fn (ManifestEntry $entry): array => $entry->toArray(), $keys)];
        $this->writeAtomically($this->manifestPath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->manifest = null;
        $this->loaded = [];
    }

    private function writeKeyFile(string $kid, #[SensitiveParameter] string $pem): void
    {
        $this->writeAtomically($this->keyPath($kid), $pem);
    }

    private function deleteKeyFile(string $kid): void
    {
        @unlink($this->keyPath($kid));
    }

    private function writeAtomically(string $path, #[SensitiveParameter] string $contents): void
    {
        if (! is_dir($this->directory)) {
            if (! @mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) {
                throw new SigningKeyException("Could not create the key directory {$this->directory}.");
            }
            $this->giveToStorageOwner($this->directory);
        }

        $temporary = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        $previousUmask = umask(0077);

        try {
            if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
                @unlink($temporary);
                throw new SigningKeyException("Could not write {$path}.");
            }
            $this->giveToStorageOwner($path);
        } finally {
            umask($previousUmask);
        }
    }

    /**
     * A command run as root would leave files that only root can read, and the web
     * server could no longer sign. Hand them to the owner of the storage directory.
     */
    private function giveToStorageOwner(string $path): void
    {
        $storage = dirname($this->directory);

        if (function_exists('posix_geteuid') && posix_geteuid() === 0 && is_dir($storage)) {
            chown($path, fileowner($storage));
            chgrp($path, filegroup($storage));
        }
    }

    private function keyPath(string $kid): string
    {
        return $this->directory.'/'.$kid.'.key';
    }

    private function manifestPath(): string
    {
        return $this->directory.'/keys.json';
    }
}
