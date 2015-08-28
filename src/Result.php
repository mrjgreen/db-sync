<?php namespace DbSync;


class Result {

    private $rowCount;

    private $checkedCount;

    private $transferredCount;

    public function __construct($transferredCount, $affectedCount, $checkedCount)
    {
        $this->transferredCount = $transferredCount;

        $this->affectedCount = $affectedCount;

        $this->checkedCount = $checkedCount;
    }

    public function getRowsAffected()
    {
        return $this->rowCount;
    }

    public function getRowsTransferred()
    {
        return $this->transferredCount;
    }

    public function getCheckedCount()
    {
        return $this->checkedCount;
    }

    public function toArray()
    {
        return array(
            'checked'       => $this->checkedCount,
            'transferred'   => $this->transferredCount,
            'affected'      => $this->rowCount,
        );
    }
}
