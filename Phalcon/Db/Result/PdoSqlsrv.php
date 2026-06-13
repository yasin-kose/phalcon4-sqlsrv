<?php

namespace Phalcon\Db\Result;

/**
 * Phalcon\Db\Result\PdoSqlsrv
 * Resultset'in iç işleyişini kapsüller
 * <code>
 * $result = $connection->query("SELECT * FROM robots ORDER BY name");
 * $result->setFetchMode(Phalcon\Db::FETCH_NUM);
 * while ($robot = $result->fetchArray()) {
 * print_r($robot);
 * }
 * </code>.
 */
class PdoSqlsrv extends Pdo
{
    /**
     * Bir resultset tarafından döndürülen satır sayısını alır
     * <code>
     * $result = $connection->query("SELECT * FROM robots ORDER BY name");
     * echo 'There are ', $result->numRows(), ' rows in the resultset';
     * </code>.
     *
     * @return int
     */
    public function numRows(): int
    {
        $rowCount = $this->rowCount?:0;
        if ($rowCount === false) {
            $rowCount = $this->_pdoStatement->rowCount();

            if ($rowCount === false) {
                parent::numRows();
            }

            $this->rowCount = $rowCount;
        }

        return $rowCount;
    }
}
