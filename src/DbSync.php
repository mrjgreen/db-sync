<?php namespace DbSync;

use DbSync\Hash\HashInterface;
use DbSync\Hash\ShaHash;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DbSync {

    private $logger;

    private $hashStrategy;

    private $blockSize = 1024;

    private $transferSize = 4;

    private $dryRun;

    public function __construct($dryRun = false, HashInterface $hashStrategy = null)
    {
        $this->dryRun = $dryRun;

        $this->hashStrategy = $hashStrategy ?: new ShaHash();

        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $blockSize
     */
    public function setBlockSize($blockSize)
    {
        $this->validatePower2Int($blockSize);

        $this->blockSize = $blockSize;
    }

    /**
     * @param $transferSize
     */
    public function setTransferSize($transferSize)
    {
        $this->validatePower2Int($transferSize);

        $this->transferSize = $transferSize;
    }

    /**
     * @param $int
     */
    private function validatePower2Int($int)
    {
        if($int != (int)$int || $int < 1 || (($int & ($int - 1)) !== 0))
        {
            throw new \InvalidArgumentException("Argument must be a positive, power of 2 integer. '$int' given.");
        }
    }

    /**
     * @param Table $source
     * @param Table $destination
     * @param ColumnConfiguration|null $syncConfig
     * @return int|void
     */
    public function sync(Table $source, Table $destination, ColumnConfiguration $syncConfig = null)
    {
        $columns = $source->getColumns();

        $primaryKey = $source->getPrimaryKey();

        $syncColumns = $syncConfig ? $syncConfig->getIntersection($columns) : $columns;

        $syncColumns = array_merge($primaryKey, $syncColumns);

        return $this->doComparison($source, $destination, $syncColumns, $this->blockSize);

    }

    /**
     * @param Table $source
     * @param Table $destination
     * @param $syncColumns
     * @param $currentBlockSize
     * @param array $index
     * @return int|void
     */
    private function doComparison(Table $source, Table $destination, $syncColumns, $currentBlockSize, array $index = array())
    {
        $rowCount = 0;

        for($i = 0; $i < 2; $i++)
        {
            if(!$this->matches($source, $destination, $syncColumns, $index, $currentBlockSize)) {

                $this->logger->debug("Found mismatch for tables '$source' => '$destination' at block '$i' at block size '$currentBlockSize'");

                if($currentBlockSize == $this->transferSize) {
                    $rowCount += $this->copy($source, $destination, $syncColumns, $index, $currentBlockSize);
                }
                else{
                    $rowCount += $this->doComparison($source, $destination, $syncColumns, $currentBlockSize / 2, $index);
                }
            }

            $index = $source->getKeyAtPosition($index, $currentBlockSize);

            if($index === null) break;
        }

        return $rowCount;
    }

    /**
     * @param Table $source
     * @param Table $destination
     * @param $compareColumns
     * @param $nextIndex
     * @param $blockSize
     * @return bool
     */
    private function matches(Table $source, Table $destination, array $compareColumns, array $nextIndex, $blockSize)
    {
        $hash = $this->hashStrategy->getHashSelect($source->columnize($compareColumns));

        return $source->getHashForKey($hash, $nextIndex, $blockSize) === $destination->getHashForKey($hash, $nextIndex, $blockSize);
    }

    /**
     * @param Table $source
     * @param Table $destination
     * @param $columns
     * @param $nextIndex
     * @param $blockSize
     * @return int
     */
    private function copy(Table $source, Table $destination, $columns, $nextIndex, $blockSize)
    {
        $rows = $source->getRowsForKey($columns, $nextIndex, $blockSize);

        $count = $rows->rowCount();

        $this->logger->debug("Copying '$count' rows from '$source' => '$destination' for block size '$blockSize'");

        if($this->dryRun)
        {
            $this->logger->info("DRY RUN: would copy '$count' rows from '$source' => '$destination' for block size '$blockSize'");

            return 0;
        }

        $rowCount = $destination->insert($rows, $columns);

        $this->logger->info("Inserted/Updated '$rowCount' rows from '$source' => '$destination' for block size '$blockSize'");

        return $rowCount;
    }
}
