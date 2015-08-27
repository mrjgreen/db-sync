<?php namespace DbSync;

use Psr\Log\LoggerInterface;

class DbSync {

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sync(Table $source, Table $destination, ColumnConfiguration $syncConfig = null, ColumnConfiguration $compareConfig = null)
    {
        $columns = $source->getColumns();

        $syncColumns = $syncConfig ? $syncConfig->getIntersection($columns) : $columns;

        $compareColumns = $compareConfig ? $compareConfig->getIntersection($syncColumns) : $syncColumns;

        if(count($compareColumns) === 0)
        {
            throw new \InvalidArgumentException("No tables columns to compare");
        }

        $chunkSize = 1024;
        //$copySize = 32;

        $currentChunksize = $chunkSize;

        $index = array();

        do
        {
            if(!$this->matches($source, $destination, $compareColumns, $index, $currentChunksize))
            {
                $this->copy($source, $destination, $syncColumns, $index, $currentChunksize);
            }

            $index = $source->getKeyAtPosition($index, $currentChunksize);

        }while($index !== null);
    }

    private function matches(Table $source, Table $destination, $compareColumns, $nextIndex, $blockSize)
    {
        return $source->getHashForKey($compareColumns, $nextIndex, $blockSize) === $destination->getHashForKey($compareColumns, $nextIndex, $blockSize);
    }

    private function copy(Table $source, Table $destination, $columns, $nextIndex, $blockSize)
    {
        $rows = $source->getRowsForKey($columns, $nextIndex, $blockSize);

        $destination->insert($rows);
    }
}
