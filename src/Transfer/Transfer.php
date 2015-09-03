<?php namespace DbSync\Transfer;

use DbSync\Hash\HashInterface;

class Transfer implements TransferInterface {

    /**
     * @var HashInterface
     */
    private $hashStrategy;

    /**
     * @var int
     */
    private $blockSize;

    /**
     * @var int
     */
    private $transferSize;

    public function __construct(HashInterface $hashStrategy, $blockSize = 1024, $transferSize = 8)
    {
        $this->setHashStrategy($hashStrategy);

        $this->setBlockSize($blockSize);

        $this->setTransferSize($transferSize);
    }

    /**
     * @return HashInterface
     */
    public function getHashStrategy()
    {
        return $this->hashStrategy;
    }

    /**
     * @return int
     */
    public function getBlockSize()
    {
        return $this->blockSize;
    }

    /**
     * @return int
     */
    public function getTransferSize()
    {
        return $this->transferSize;
    }

    /**
     * @param HashInterface $hashStrategy
     */
    public function setHashStrategy(HashInterface $hashStrategy)
    {
        $this->hashStrategy = $hashStrategy;
    }

    /**
     * @param int $blockSize
     */
    public function setBlockSize($blockSize)
    {
        $this->validatePower2Int($blockSize);

        $this->blockSize = $blockSize;
    }

    /**
     * @param int $transferSize
     */
    public function setTransferSize($transferSize)
    {
        $this->validatePower2Int($transferSize);

        $this->transferSize = $transferSize;
    }

    /**
     * @param int $value
     */
    private function validatePower2Int($value)
    {
        if(!is_numeric($value) || $value != (int)$value || $value < 1 || (($value & ($value - 1)) !== 0))
        {
            throw new \InvalidArgumentException("Argument must be a positive, power of 2 integer. '$value' given.");
        }
    }
}