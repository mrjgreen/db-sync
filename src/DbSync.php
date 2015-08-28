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

    private $delete;

    public function __construct(HashInterface $hashStrategy = null)
    {
        $this->hashStrategy = $hashStrategy ?: new ShaHash();

        $this->logger = new NullLogger();
    }

    /**
     * @param bool|true $dryRun
     */
    public function dryRun($dryRun = true)
    {
        $this->dryRun = (bool)$dryRun;
    }

    /**
     * @param bool|true $delete
     */
    public function delete($delete = true)
    {
        $this->delete = (bool)$delete;
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
     * @return Result
     */
    public function sync(Table $source, Table $destination, ColumnConfiguration $syncConfig = null)
    {
        $tableColumns = $source->getColumns();

        $primaryKey = $source->getPrimaryKey();

        if(!$primaryKey)
        {
            throw new \RuntimeException("The table $source does not have a primary key");
        }

        $syncColumns = $syncConfig ? $syncConfig->getIntersection($tableColumns) : $tableColumns;

        $syncColumns = array_unique(array_merge($primaryKey, $syncColumns));

        $hash = $this->hashStrategy->getHashSelect($source->columnize($syncColumns));

        return $this->doComparison($source, $destination, $syncColumns, $hash, $this->blockSize);

    }

    /**
     * @param Table $source
     * @param Table $destination
     * @param $syncColumns
     * @param $hash
     * @param $blockSize
     * @param array $index
     * @return Result
     */
    private function doComparison(Table $source, Table $destination, $syncColumns, $hash, $blockSize, array $index = array())
    {
        $result = new Result();

        $i = 0;

        while($i++ < 2 || $blockSize == $this->blockSize)
        {
            $nextIndex = $source->getKeyAtPosition($index, $blockSize);

            $endIndex = $nextIndex ?: array();

            if($source->getHashForKey($syncColumns, $hash, $index, $endIndex) !== $destination->getHashForKey($syncColumns, $hash, $index, $endIndex)) {

                $this->logger->debug("Found mismatch for tables '$source' => '$destination' at block '$i' at block size '$blockSize'");

                if($blockSize == $this->transferSize) {

                    $result->addRowsTransferred($blockSize);

                    $result->addRowsAffected($this->copy($source, $destination, $syncColumns, $index, $endIndex));
                }
                else{
                    $result->merge($this->doComparison($source, $destination, $syncColumns, $hash, $blockSize / 2, $index));
                }
            }

            if($blockSize == $this->blockSize)
            {
                $result->addRowsChecked($blockSize);

                $this->logger->info("Written '{$result->getRowsAffected()}' rows, checked '{$result->getRowsChecked()}' rows for tables '$source' => '$destination'");
            }

            $index = $nextIndex;

            if($index === null)
            {
                $this->logger->debug("Reached end of results set table '$source' at block '$i' at block size '$blockSize'");
                break;
            }
        }

        return $result;
    }


    /**
     * @param Table $source
     * @param Table $destination
     * @param $columns
     * @param $startIndex
     * @param $endIndex
     * @return int
     */
    private function copy(Table $source, Table $destination, $columns, $startIndex, $endIndex)
    {
        $rows = $source->getRowsForKey($columns, $startIndex, $endIndex);

        $count = count($rows);

        $this->logger->debug("Copying '$count' rows from '$source' => '$destination'");

        if($this->dryRun)
        {
            $this->logger->info("DRY RUN: would copy '$count' rows from '$source' => '$destination'");

            return 0;
        }

        if($rowCount = $destination->insert($rows, $columns))
        {
            $this->logger->info("Inserted/Updated '$rowCount' rows from '$source' => '$destination'");
        }

        if($this->delete)
        {
            if($deleteCount = $destination->delete($startIndex, $endIndex, $rows))
            {
                $this->logger->info("Deleted '$rowCount' rows from '$source' => '$destination'");

                $rowCount += $deleteCount;
            }
        }

        return $rowCount;
    }
}
