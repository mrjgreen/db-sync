<?php namespace DbSync\Transfer;

use DbSync\Hash\HashInterface;

interface TransferInterface {

    /**
     * @return HashInterface
     */
    public function getHashStrategy();

    /**
     * @return int
     */
    public function getBlockSize();

    /**
     * @return int
     */
    public function getTransferSize();

    /**
     * @param HashInterface $hashInterface
     */
    public function setHashStrategy(HashInterface $hashInterface);

    /**
     * @param int $blockSize
     */
    public function setBlockSize($blockSize);

    /**
     * @param int $transferSize
     */
    public function setTransferSize($transferSize);
}
