<?php
class HashAbstractTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_BLOCK_SIZE = 1024;
    const DEFAULT_TRANSFER_SIZE = 8;

    public function testItReturnsCorrectHashAlgorithmForString()
    {
        $types = [
           'crc32' => \DbSync\Hash\CrcHash::class,
           'md5' => \DbSync\Hash\Md5Hash::class,
           'sha1' => \DbSync\Hash\ShaHash::class,
        ];

        foreach ($types as $name => $class) {
            $hash = \DbSync\Hash\HashAbstract::buildHashByName($name);
            $this->assertInstanceOf($class, $hash);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Hash algorithm 'not_a_hash' does not exist.
     */
    public function testItThrowsExceptionForInvalidHash()
    {
        \DbSync\Hash\HashAbstract::buildHashByName('not_a_hash');
    }
}
