<?php namespace DbSync;

use DbSync\Hash\Md5Hash;
use DbSync\Transfer\Transfer;
use DbSync\Transfer\TransferInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DbSync {

    private $logger;

    private $transferInterface;

    private $dryRun;

    private $delete;

    public function __construct(TransferInterface $transfer = null)
    {
        $this->transferInterface = $transfer ?: new Transfer(new Md5Hash());

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
     * @param Table $source
     * @param Table $destination
     * @param ColumnConfiguration $syncConfig
     * @param ColumnConfiguration $compareConfig
     * @return Result
     */
    public function sync(Table $source, Table $destination, ColumnConfiguration $syncConfig = null, ColumnConfiguration $compareConfig = null)
    {
        $tableColumns = $source->getColumns();

        $primaryKey = $source->getPrimaryKey();

        if(!$primaryKey)
        {
            throw new \RuntimeException("The table $source does not have a primary key");
        }

        $syncConfig or $syncConfig = new ColumnConfiguration([], []);
        $compareConfig or $compareConfig = new ColumnConfiguration([], []);

        $syncColumns = $syncConfig->getIntersection($tableColumns, $primaryKey);

        $compareConfig = $compareConfig->getIntersection($syncColumns, $primaryKey);

        $hash = $this->transferInterface->getHashStrategy()->getHashSelect($source->columnize($compareConfig));

        $this->logger->info("Hash calculation: \n\n" . $hash);

        return $this->doComparison($source, $destination, $syncColumns, $hash, $this->transferInterface->getBlockSize());

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
    private function doComparison(Table $source, Table $destination, $syncColumns, $hash, $blockSize, array $index = [])
    {
        $result = new Result();

        $i = 0;

        $transferSize = $this->transferInterface->getTransferSize();
        $maxBlockSize = $this->transferInterface->getBlockSize();

        while($i++ < 2 || $blockSize == $maxBlockSize)
        {
            $nextIndex = $source->getKeyAtPosition($index, $blockSize);

            $endIndex = $nextIndex ?: [];

            if($source->getHashForKey($syncColumns, $hash, $index, $endIndex) !== $destination->getHashForKey($syncColumns, $hash, $index, $endIndex)) {

                $this->logger->debug("Found mismatch for tables '$source' => '$destination' at block '$i' at block size '$blockSize'");

                if($blockSize == $transferSize) {

                    $result->addRowsTransferred($blockSize);

                    $result->addRowsAffected($this->copy($source, $destination, $syncColumns, $index, $endIndex));
                }
                else{
                    $result->merge($this->doComparison($source, $destination, $syncColumns, $hash, $blockSize / 2, $index));
                }
            }

            if($blockSize == $maxBlockSize)
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
