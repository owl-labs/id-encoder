<?php
declare(strict_types=1);

namespace OwlLabs\IdEncoder;

use Hashids\Hashids;
use Hashids\HashidsInterface;

/**
 * @package OwlLabs\IdEncoder
 */
final class IdEncoder
{
    private ?HashidsInterface $hasherWithoutSalt = null;

    /** @var HashidsInterface[] */
    private array $saltedHashers = [];

    public function __construct(
        private $defaultMinLength = 6,
        private $minLengthsBySalt = [],
        private $defaultAlphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        private $alphabetsBySalt = [],
    ) {
    }

    public function encode(int $id, $object = null): string
    {
        return $this->getHasherForObject($object)->encode($id);
    }

    public function decode(string $encodedId, $object = null): ?int
    {
        $numbers = $this->getHasherForObject($object)->decode($encodedId);
        if (array_key_exists(0, $numbers)) {
            return $numbers[0];
        }
        throw new IdEncoderException();
    }

    private function getHasherForObject(mixed $object): HashidsInterface
    {
        return $this->getHasher($this->getSaltFromObject($object));
    }

    private function getSaltFromObject($object): ?string
    {
        if ($object === null) {
            return null;
        }

        if (is_object($object)) {
            return get_class($object);
        }

        return (string)$object;
    }

    private function getHasher(?string $salt): HashidsInterface
    {
        if ($salt === null) {
            return $this->getHasherWithoutSalt($this->defaultMinLength, $this->defaultAlphabet);
        }
        return $this->getHasherWithSalt($salt);
    }

    private function getMinHashLengthForSalt(string $salt): int
    {
        return array_key_exists($salt, $this->minLengthsBySalt)
            ? $this->minLengthsBySalt[$salt]
            : $this->defaultMinLength;
    }

    private function getAlphabetForSalt(string $salt): string
    {
        return array_key_exists($salt, $this->alphabetsBySalt)
            ? $this->alphabetsBySalt[$salt]
            : $this->defaultAlphabet;
    }

    private function getHasherWithoutSalt(int $minHashLength, string $alphabet): HashidsInterface
    {
        if ($this->hasherWithoutSalt === null) {
            $this->hasherWithoutSalt = $this->createHashids('', $minHashLength, $alphabet);
        }
        return $this->hasherWithoutSalt;
    }

    private function getHasherWithSalt(string $salt): HashidsInterface
    {
        if (!array_key_exists($salt, $this->saltedHashers)) {
            $minHashLength = $this->getMinHashLengthForSalt($salt);
            $alphabet = $this->getAlphabetForSalt($salt);
            $this->saltedHashers[$salt] = $this->createHashids($salt, $minHashLength, $alphabet);
        }
        return $this->saltedHashers[$salt];
    }

    private function createHashids(string $salt, int $minHashLength, string $alphabet): Hashids
    {
        return new Hashids($salt, $minHashLength, $alphabet);
    }
}
