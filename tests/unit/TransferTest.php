<?php
class TransferTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_BLOCK_SIZE = 1024;
    const DEFAULT_TRANSFER_SIZE = 8;

    public function testGettersForDefaultValidParams()
    {
        $hashObject = new \DbSync\Hash\Md5Hash();

        $transfer = new DbSync\Transfer\Transfer($hashObject);

        $this->assertSame($hashObject, $transfer->getHashStrategy());

        $this->assertEquals(self::DEFAULT_BLOCK_SIZE, $transfer->getBlockSize());
        $this->assertEquals(self::DEFAULT_TRANSFER_SIZE, $transfer->getTransferSize());
    }

    public function testSettersForValidParams()
    {
        $transfer = new DbSync\Transfer\Transfer(new \DbSync\Hash\Md5Hash());

        $hashObject = new \DbSync\Hash\ShaHash();

        $transfer->setHashStrategy($hashObject);

        $this->assertSame($hashObject, $transfer->getHashStrategy());

        $newBlockSize = self::DEFAULT_BLOCK_SIZE * 2;
        $newTransferSize = self::DEFAULT_TRANSFER_SIZE * 2;

        $transfer->setBlockSize($newBlockSize);
        $transfer->setTransferSize($newTransferSize);

        $this->assertEquals($newBlockSize, $transfer->getBlockSize());
        $this->assertEquals($newTransferSize, $transfer->getTransferSize());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidParamsProvider
     */
    public function testItThrowsExceptionsForInvalidParams($invalidParams)
    {
        $transfer = new DbSync\Transfer\Transfer(new \DbSync\Hash\Md5Hash());

        $transfer->setTransferSize($invalidParams);
        $transfer->setBlockSize($invalidParams);
    }

    public function invalidParamsProvider()
    {
        return [
            [5],
            [2.1],
            [-4],
            [-4.4],
            [0],
            [0.23],
            [-0.23],
            ["foo"],
            ["1foo"],
        ];
    }
}