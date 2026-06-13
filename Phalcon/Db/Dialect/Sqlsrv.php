<?php

namespace Phalcon\Db\Dialect;

use Phalcon\Db\Column;
use Phalcon\Db\Exception;

/**
 * Phalcon\Db\Dialect\Sqlsrv
 * MsSQL RDBMS için veritabanına özgü SQL üretir.
 */
class Sqlsrv extends \Phalcon\Db\Dialect
{
    /**
     * Kaçış (escape) karakteri.
     *
     * @var string
     */
    protected $_escapeChar = '"';

    /**
     * LIMIT yan tümcesi için SQL üretir
     * <code>
     * $sql = $dialect->limit('SELECT * FROM robots', 10);
     * echo $sql; // SELECT * FROM robots ORDER BY 1 OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
     * $sql = $dialect->limit('SELECT * FROM robots', [10, 50]);
     * echo $sql; // SELECT * FROM robots ORDER BY 1 OFFSET 50 ROWS FETCH NEXT 10 ROWS ONLY
     * </code>.
     *
     * @param string $sqlQuery
     * @param mixed  $number
     *
     * @return string
     */
    public function limit(string $sqlQuery, $number): string
    {
        $offset = 0;
        if (is_array($number)) {
            if (isset($number[1]) && strlen($number[1])) {
                $offset = $number[1];
            }

            $number = $number[0];
        }

        if (strpos($sqlQuery, 'ORDER BY') === false) {
            $sqlQuery .= ' ORDER BY 1';
        }

        return $sqlQuery." OFFSET {$offset} ROWS FETCH NEXT {$number} ROWS ONLY";
    }

    /**
     * FOR UPDATE yan tümcesi eklenerek değiştirilmiş bir SQL döndürür.
     *
     * <code>
     * $sql = $dialect->forUpdate('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots WITH (UPDLOCK)
     * </code>
     */
    public function forUpdate(string $sqlQuery): string
    {
        return $sqlQuery.' WITH (UPDLOCK) ';
    }

    /**
     * LOCK IN SHARE MODE yan tümcesi eklenerek değiştirilmiş bir SQL döndürür.
     *
     * <code>
     * $sql = $dialect->sharedLock('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots WITH (NOLOCK)
     * </code>
     */
    public function sharedLock(string $sqlQuery): string
    {
        return $sqlQuery.' WITH (NOLOCK) ';
    }

    /**
     * MsSQL'deki sütun adını alır.
     *
     * @param mixed $column
     *
     * @return string
     */
    public function getColumnDefinition(\Phalcon\Db\ColumnInterface $column): string
    {
        $columnSql = '';
        $type = $column->getType();
        if (is_string($type)) {
            $columnSql .= $type;
            $type = $column->getTypeReference();
        }

        switch ($type) {
            case Column::TYPE_INTEGER:
                if (empty($columnSql)) {
                    $columnSql .= 'INT';
                }
                break;

            case Column::TYPE_DATE:
                if (empty($columnSql)) {
                    $columnSql .= 'DATE';
                }
                break;

            case Column::TYPE_VARCHAR:
                if (empty($columnSql)) {
                    $columnSql .= 'NVARCHAR';
                }
                $columnSql .= '('.$column->getSize().')';
                break;

            case Column::TYPE_DECIMAL:
                if (empty($columnSql)) {
                    $columnSql .= 'DECIMAL';
                }
                $columnSql .= '('.$column->getSize().','.$column->getScale().')';
                break;

            case Column::TYPE_DATETIME:
                if (empty($columnSql)) {
                    $columnSql .= 'DATETIME';
                }
                break;

            case Column::TYPE_TIMESTAMP:
                if (empty($columnSql)) {
                    $columnSql .= 'TIMESTAMP';
                }
                break;

            case Column::TYPE_CHAR:
                if (empty($columnSql)) {
                    $columnSql .= 'CHAR';
                }
                $columnSql .= '('.$column->getSize().')';
                break;

            case Column::TYPE_TEXT:
                if (empty($columnSql)) {
                    $columnSql .= 'NTEXT';
                }
                break;

            case Column::TYPE_BOOLEAN:
                if (empty($columnSql)) {
                    $columnSql .= 'BIT';
                }
                break;

            case Column::TYPE_FLOAT:
                if (empty($columnSql)) {
                    $columnSql .= 'FLOAT';
                }
                $size = $column->getSize();
                if ($size) {
                    $columnSql .= '('.$size.')';
                }
                break;

            case Column::TYPE_DOUBLE:
                if (empty($columnSql)) {
                    $columnSql .= 'NUMERIC';
                }
                $size = $column->getSize();
                if ($size) {
                    $scale = $column->getScale();
                    $columnSql .= '('.$size;
                    if ($scale) {
                        $columnSql .= ','.$scale.')';
                    } else {
                        $columnSql .= ')';
                    }
                }
                break;

            case Column::TYPE_BIGINTEGER:
                if (empty($columnSql)) {
                    $columnSql .= 'BIGINT';
                }
                $size = $column->getSize();
                if ($size) {
                    $columnSql .= '('.$size.')';
                }
                break;

            case Column::TYPE_TINYBLOB:
                if (empty($columnSql)) {
                    $columnSql .= 'VARBINARY(255)';
                }
                break;

            case Column::TYPE_BLOB:
            case Column::TYPE_MEDIUMBLOB:
            case Column::TYPE_LONGBLOB:
                if (empty($columnSql)) {
                    $columnSql .= 'VARBINARY(MAX)';
                }
                break;

            default:
                if (empty($columnSql)) {
                    throw new Exception('Unrecognized MsSql data type at column '.$column->getName());
                }

                $typeValues = $column->getTypeValues();
                if (!empty($typeValues)) {
                    if (is_array($typeValues)) {
                        $valueSql = '';
                        foreach ($typeValues as $value) {
                            $valueSql .= '"'.addcslashes($value, '"').'", ';
                        }
                        $columnSql .= '('.substr($valueSql, 0, -2).')';
                    } else {
                        $columnSql .= '("'.addcslashes($typeValues, '"').'")';
                    }
                }
                break;
        }

        return $columnSql;
    }

    /**
     * Bir tabloya sütun eklemek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $column
     *
     * @return string
     */
    public function addColumn(string $tableName, string $schemaName, \Phalcon\Db\ColumnInterface $column): string
    {
        $sql = 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' ADD ['.$column->getName().'] '.$this->getColumnDefinition($column);

        if ($column->hasDefault()) {
            $defaultValue = $column->getDefault();
            if (strpos(strtoupper($defaultValue), 'CURRENT_TIMESTAMP') !== false) {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $sql .= ' DEFAULT "'.addcslashes($defaultValue, '"').'"';
            }
        }

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }

        if ($column->isAutoIncrement()) {
            $sql .= ' IDENTITY(1,1)';
        }

        if ($column->isFirst()) {
            $sql .= ' FIRST';
        } else {
            $afterPosition = $column->getAfterPosition();
            if ($afterPosition) {
                $sql .= ' AFTER '.$afterPosition;
            }
        }

        return $sql;
    }

    /**
     * Bir tablodaki sütunu değiştirmek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $column
     * @param mixed  $currentColumn
     *
     * @return string
     */
    public function modifyColumn(string $tableName, string $schemaName, \Phalcon\Db\ColumnInterface $column, ?\Phalcon\Db\ColumnInterface $currentColumn = NULL): string
    {
        $sql = 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' ALTER COLUMN ['.$column->getName().'] '.$this->getColumnDefinition($column);

        if ($column->hasDefault()) {
            $defaultValue = $column->getDefault();
            if (strpos(strtoupper($defaultValue), 'CURRENT_TIMESTAMP') !== false) {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } else {
                $sql .= ' DEFAULT "'.addcslashes($defaultValue, '"').'"';
            }
        }

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }

        if ($column->isAutoIncrement()) {
            $sql .= ' IDENTITY(1,1)';
        }

        return $sql;
    }

    /**
     * Bir tablodan sütun silmek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $columnName
     *
     * @return string
     */
    public function dropColumn(string $tableName, string $schemaName, string $columnName): string
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' DROP COLUMN ['.$columnName.']';
    }

    /**
     * Bir tabloya index eklemek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $index
     *
     * @return string
     */
    public function addIndex(string $tableName, string $schemaName, \Phalcon\Db\IndexInterface $index): string
    {
        $indexType = $index->getType();
        if (!empty($indexType)) {
            $sql = ' CREATE '.$indexType.' INDEX ';
        } else {
            $sql = ' CREATE INDEX ';
        }

        $sql = '['.$index->getName().'] ON '.$this->prepareTable($tableName, $schemaName).' ('.$this->getColumnList($index->getColumns()).')';

        return $sql;
    }

    /**
     * Bir tablodan index silmek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $indexName
     *
     * @return string
     */
    public function dropIndex(string $tableName, string $schemaName, string $indexName): string
    {
        return 'DROP INDEX ['.$indexName.'] ON '.$this->prepareTable($tableName, $schemaName);
    }

    /**
     * Bir tabloya primary key eklemek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $index
     *
     * @return string
     */
    public function addPrimaryKey(string $tableName, string $schemaName, \Phalcon\Db\IndexInterface $index): string
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' ADD PRIMARY KEY ('.$this->getColumnList($index->getColumns()).')';
    }

    /**
     * Bir tablodan primary key silmek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function dropPrimaryKey(string $tableName, string $schemaName): string
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' DROP PRIMARY KEY';
    }

    /**
     * Bir tabloya index eklemek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $reference
     *
     * @return string
     */
    public function addForeignKey(string $tableName, string $schemaName, \Phalcon\Db\ReferenceInterface $reference): string
    {
        $sql = 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' ADD CONSTRAINT ['.$reference->getName().'] FOREIGN KEY ('.$this->getColumnList($reference->getColumns()).') REFERENCES '.$this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()).'('.$this->getColumnList($reference->getReferencedColumns()).')';

        $onDelete = $reference->getOnDelete();
        if (!empty($onDelete)) {
            $sql .= ' ON DELETE '.$onDelete;
        }

        $onUpdate = $reference->getOnUpdate();
        if (!empty($onUpdate)) {
            $sql .= ' ON UPDATE '.$onUpdate;
        }

        return $sql;
    }

    /**
     * Bir tablodan foreign key silmek için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $referenceName
     *
     * @return string
     */
    public function dropForeignKey(string $tableName, string $schemaName, string $referenceName): string
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' DROP FOREIGN KEY ['.$referenceName.']';
    }

    /**
     * Bir tablo oluşturmak için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array  $definition
     *
     * @return string
     */
    public function createTable(string $tableName, string $schemaName, array $definition): string
    {
        if (isset($definition['columns']) === false) {
            throw new Exception("The index 'columns' is required in the definition array");
        }

        $table = $this->prepareTable($tableName, $schemaName);

        $temporary = false;
        if (isset($definition['options']) === true) {
            $temporary = (bool) $definition['options']['temporary'];
        }

        /*
         * Geçici veya normal bir tablo oluştur
         */
        if ($temporary) {
            $sql = 'CREATE TEMPORARY TABLE '.$table." (\n\t";
        } else {
            $sql = 'CREATE TABLE '.$table." (\n\t";
        }

        $createLines = [];
        foreach ($definition['columns'] as $column) {
            $columnLine = '['.$column->getName().'] '.$this->getColumnDefinition($column);

            /*
             * Bir Default yan tümcesi ekle
             */
            if ($column->hasDefault()) {
                $defaultValue = $column->getDefault();
                if (strpos(strtoupper($defaultValue), 'CURRENT_TIMESTAMP') !== false) {
                    $columnLine .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $columnLine .= ' DEFAULT "'.addcslashes($defaultValue, '"').'"';
                }
            }

            /*
             * Bir NOT NULL yan tümcesi ekle
             */
            if ($column->isNotNull()) {
                $columnLine .= ' NOT NULL';
            }

            /*
             * Bir AUTO_INCREMENT yan tümcesi ekle
             */
            if ($column->isAutoIncrement()) {
                $columnLine .= ' IDENTITY(1,1)';
            }

            /*
             * Sütunu primary key olarak işaretle
             */
            if ($column->isPrimary()) {
                $columnLine .= ' PRIMARY KEY';
            }

            $createLines[] = $columnLine;
        }

        /*
         * İlişkili index'leri oluştur
         */
        if (isset($definition['indexes']) === true) {
            foreach ($definition['indexes'] as $index) {
                $indexName = $index->getName();
                $indexType = $index->getType();

                /*
                 * Index adı primary ise bir primary key ekleriz
                 */
                if ($indexName == 'PRIMARY') {
                    $indexSql = 'PRIMARY KEY ('.$this->getColumnList($index->getColumns()).')';
                } else {
                    if (!empty($indexType)) {
                        $indexSql = $indexType.' KEY ['.$indexName.'] ('.$this->getColumnList($index->getColumns()).')';
                    } else {
                        $indexSql = 'KEY ['.$indexName.'] ('.$this->getColumnList($index->getColumns()).')';
                    }
                }

                $createLines[] = $indexSql;
            }
        }

        /*
         * İlişkili referansları oluştur
         */
        if (isset($definition['references']) === true) {
            foreach ($definition['references'] as $reference) {
                $referenceSql = 'CONSTRAINT ['.$reference->getName().'] FOREIGN KEY ('.$this->getColumnList($reference->getColumns()).')'
                    .' REFERENCES ['.$reference->getReferencedTable().'] ('.$this->getColumnList($reference->getReferencedColumns()).')';

                $onDelete = $reference->getOnDelete();
                if (!empty($onDelete)) {
                    $referenceSql .= ' ON DELETE '.$onDelete;
                }

                $onUpdate = $reference->getOnUpdate();
                if (!empty($onUpdate)) {
                    $referenceSql .= ' ON UPDATE '.$onUpdate;
                }

                $createLines[] = $referenceSql;
            }
        }

        $sql .= implode(",\n\t", $createLines)."\n)";
        if (isset($definition['options'])) {
            $sql .= ' '.$this->_getTableOptions($definition);
        }

        return $sql;
    }

    /**
     * Bir tabloyu silmek (drop) için SQL üretir.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropTable(string $tableName, string $schemaName): string
    {
        return 'DROP TABLE '.$this->prepareTable($tableName, $schemaName);
    }

    /**
     * Bir view oluşturmak için SQL üretir.
     *
     * @param string $viewName
     * @param array  $definition
     * @param string $schemaName
     *
     * @return string
     */
    public function createView(string $viewName, array $definition, ?string $schemaName = NULL): string
    {
        if (!isset($definition['sql'])) {
            throw new Exception("The index 'sql' is required in the definition array");
        }

        return 'CREATE VIEW '.$this->prepareTable($viewName, $schemaName).' AS '.$definition['sql'];
    }

    /**
     * Bir view'i silmek (drop) için SQL üretir.
     *
     * @param string $viewName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropView(string $viewName, ?string $schemaName = NULL, bool $ifExists = NULL): string
    {
        $view = $this->prepareTable($viewName, $schemaName);

        if ($ifExists) {
            $sql = 'DROP VIEW IF EXISTS '.$view;
        } else {
            $sql = 'DROP VIEW '.$view;
        }

        return $sql;
    }

    /**
     * Bir schema.table'ın varlığını kontrol eden SQL üretir
     * <code>
     * echo $dialect->tableExists("posts", "blog");
     * echo $dialect->tableExists("posts");
     * </code>.
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function tableExists(string $tableName, ?string $schemaName = NULL): string
    {
        $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '{$tableName}'";

        if ($schemaName) {
            $sql .= " AND TABLE_SCHEMA = '{$schemaName}'";
        }

        return $sql;
    }

    /**
     * Bir schema.view'in varlığını kontrol eden SQL üretir.
     *
     * @param string $viewName
     * @param string $schemaName
     *
     * @return string
     */
    public function viewExists(string $viewName, ?string $schemaName = NULL): string
    {
        $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_NAME = '{$viewName}'";

        if ($schemaName) {
            $sql .= " AND TABLE_SCHEMA = '{$schemaName}'";
        }

        return $sql;
    }

    /**
     * Bir tabloyu betimleyen SQL üretir
     * <code>
     * print_r($dialect->describeColumns("posts"));
     * </code>.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeColumns(string $table, ?string $schema = NULL): string
    {
        $sql = "exec sp_columns @table_name = '{$table}'";
        if ($schema) {
            $sql .= ", @table_owner = '{$schema}'";
        }

        return $sql;
    }

    /**
     * Veritabanındaki tüm tabloları listeler
     * <code>
     * print_r($dialect->listTables("blog"))
     * </code>.
     *
     * @param string $schemaName
     *
     * @return string
     */
    public function listTables(?string $schemaName = NULL): string
    {
        $sql = 'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES';
        if ($schemaName) {
            $sql .= " WHERE TABLE_SCHEMA = '{$schemaName}'";
        }

        return $sql;
    }

    /**
     * Bir schema veya kullanıcının tüm view'lerini listelemek için SQL üretir.
     *
     * @param string $schemaName
     *
     * @return string
     */
    public function listViews($schemaName = null)
    {
        $sql = 'SELECT TABLE_NAME AS view_name FROM INFORMATION_SCHEMA.VIEWS';
        if ($schemaName) {
            $sql .= " WHERE TABLE_SCHEMA = '{$schemaName}'";
        }

        return $sql.' ORDER BY view_name';
    }

    /**
     * Bir tablodaki index'leri sorgulamak için SQL üretir.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeIndexes(string $table, ?string $schema = NULL): string
    {
        $sql = "SELECT * FROM sys.indexes ind INNER JOIN sys.tables t ON ind.object_id = t.object_id WHERE t.name = '{$table}'";

        return $sql;
    }

    /**
     * Bir tablodaki foreign key'leri sorgulamak için SQL üretir.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeReferences(string $table, ?string $schema = NULL): string
    {
        $sql = 'SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND ';
        if ($schema) {
            $sql .= "CONSTRAINT_SCHEMA = '".$schema."' AND TABLE_NAME = '".$table."'";
        } else {
            $sql .= "TABLE_NAME = '".$table."'";
        }

        return $sql;
    }

    /**
     * Tablo oluşturma seçeneklerini betimlemek için SQL üretir.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function tableOptions(string $table, ?string $schema = NULL): string
    {
        $sql = 'SELECT TABLES.TABLE_TYPE AS table_type,TABLES.AUTO_INCREMENT AS auto_increment,TABLES.ENGINE AS engine,TABLES.TABLE_COLLATION AS table_collation FROM INFORMATION_SCHEMA.TABLES WHERE ';
        if ($schema) {
            $sql .= "TABLES.TABLE_SCHEMA = '".$schema."' AND TABLES.TABLE_NAME = '".$table."'";
        } else {
            $sql .= "TABLES.TABLE_NAME = '".$table."'";
        }

        return $sql;
    }

    /**
     * Tablo oluşturma seçeneklerini eklemek için SQL üretir.
     *
     * @param array $definition
     *
     * @return string
     */
    protected function _getTableOptions($definition)
    {
        if (isset($definition['options']) === true) {
            $tableOptions = array();
            $options = $definition['options'];

            /*
             * Bir ENGINE seçeneği olup olmadığını kontrol et
             */
            if (isset($options['ENGINE']) === true &&
                $options['ENGINE'] == true) {
                $tableOptions[] = 'ENGINE='.$options['ENGINE'];
            }

            /*
             * Bir AUTO_INCREMENT seçeneği olup olmadığını kontrol et
             */
            if (isset($options['AUTO_INCREMENT']) === true &&
                $options['AUTO_INCREMENT'] == true) {
                $tableOptions[] = 'AUTO_INCREMENT='.$options['AUTO_INCREMENT'];
            }

            /*
             * Bir TABLE_COLLATION seçeneği olup olmadığını kontrol et
             */
            if (isset($options['TABLE_COLLATION']) === true &&
                $options['TABLE_COLLATION'] == true) {
                $collationParts = explode('_', $options['TABLE_COLLATION']);
                $tableOptions[] = 'DEFAULT CHARSET='.$collationParts[0];
                $tableOptions[] = 'COLLATE='.$options['TABLE_COLLATION'];
            }

            if (count($tableOptions) > 0) {
                return implode(' ', $tableOptions);
            }
        }

        return '';
    }

    /**
     * Bir tablonun primary key'i için SQL üretir.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function getPrimaryKey($table, $schema = null)
    {
        $sql = "exec sp_pkeys @table_name = '{$table}'";
        if ($schema) {
            $sql .= ", @table_owner = '{$schema}'";
        }

        return $sql;
    }
}
