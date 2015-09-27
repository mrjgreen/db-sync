<?php namespace DbSync;


class Result {

    private $rowCount = 0;

    private $checkedCount = 0;

    private $transferredCount = 0;

    public function addRowsChecked($rows)
    {
        $this->checkedCount += $rows;
    }

    public function addRowsAffected($rows)
    {
        $this->rowCount += $rows;
    }

    public function addRowsTransferred($rows)
    {
        $this->transferredCount += $rows;
    }

    public function getRowsAffected()
    {
        return $this->rowCount;
    }

    public function getRowsTransferred()
    {
        return $this->transferredCount;
    }

    public function getRowsChecked()
    {
        return $this->checkedCount;
    }

    public function merge(Result $result)
    {
        $this->addRowsAffected($result->getRowsAffected());
        $this->addRowsChecked($result->getRowsChecked());
        $this->addRowsTransferred($result->getRowsTransferred());
    }

    public function toArray()
    {
        return [
            'checked'       => $this->checkedCount,
            'transferred'   => $this->transferredCount,
            'affected'      => $this->rowCount,
        ];
    }
}
