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
     * @return int
     */
    private function doComparison(Table $source, Table $destination, $syncColumns, $hash, $blockSize, array $index = array())
    {
        $rowCount = 0;

        $i = 0;

        while($i++ < 2 || $blockSize == $this->blockSize)
        {
            $nextIndex = $source->getKeyAtPosition($index, $blockSize);

            $endIndex = $nextIndex ?: array();

            if($source->getHashForKey($syncColumns, $hash, $index, $endIndex) !== $destination->getHashForKey($syncColumns, $hash, $index, $endIndex)) {

                $this->logger->debug("Found mismatch for tables '$source' => '$destination' at block '$i' at block size '$blockSize'");

                if($blockSize == $this->transferSize) {
                    $rowCount += $this->copy($source, $destination, $syncColumns, $index, $endIndex);
                }
                else{
                    $rowCount += $this->doComparison($source, $destination, $syncColumns, $hash, $blockSize / 2, $index);
                }
            }

            if($blockSize == $this->blockSize)
            {
                $checked = $i * $blockSize;

                $this->logger->info("Written '$rowCount' rows, checked '$checked' rows for tables '$source' => '$destination'");
            }

            $index = $nextIndex;

            if($index === null)
            {
                $this->logger->debug("Reached end of results set table '$source' at block '$i' at block size '$blockSize'");
                break;
            }
        }

        return $rowCount;
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

        $count = $rows->rowCount();

        $this->logger->debug("Copying '$count' rows from '$source' => '$destination'");

        if($this->dryRun)
        {
            $this->logger->info("DRY RUN: would copy '$count' rows from '$source' => '$destination'");

            return 0;
        }

        $rowCount = $destination->insert($rows, $columns);

        $this->logger->info("Inserted/Updated '$rowCount' rows from '$source' => '$destination'");

        return $rowCount;
    }
}
