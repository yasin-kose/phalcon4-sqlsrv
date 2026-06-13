<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Column;
use Phalcon\Db\Result\PdoSqlsrv as ResultPdo;

/**
 * Phalcon\Db\Adapter\Pdo\Sqlsrv
 * Microsoft SQL Server veritabanı sistemine özgü işlevler.
 * <code>
 * $config = array(
 *     "host"     => "192.168.0.11",
 *     "dbname"   => "blog",
 *     "username" => "sigma",
 *     "password" => "secret",
 * );
 * $connection = new \Phalcon\Db\Adapter\Pdo\Sqlsrv($config);
 * </code>.
 *
 * @property \Phalcon\Db\Dialect\Sqlsrv $_dialect
 */
class Sqlsrv extends \Phalcon\Db\Adapter\Pdo\AbstractPdo implements \Phalcon\Db\Adapter\AdapterInterface
{

    protected $_type = 'sqlsrv';
    protected $_dialectType = 'sqlsrv';

    /**
     * Bu metot, Phalcon\Db\Adapter\Pdo yapıcı metodunda otomatik olarak çağrılır.
     * Bir veritabanı bağlantısını yeniden kurmanız gerektiğinde bunu çağırın.
     *
     * @param array $descriptor
     *
     * @return bool
     */
    public function connect(?array $descriptor = null): bool 
    {
        if (is_null($descriptor) === true) {
            $descriptor = $this->descriptor;
        }

        /*
         * Geliştiricinin özel seçenekler tanımlayıp tanımlamadığını kontrol et, yoksa sıfırdan oluştur
         */
        if (isset($descriptor['options']) === true) {
            $options = $descriptor['options'];
            unset($descriptor['options']);
        } else {
            $options = array();
        }

        $dsn = "sqlsrv:server=" . $descriptor['host'] . ";database=" . $descriptor['dbname'] . ";Encrypt=No;LoginTimeout=10";
        $dbusername = $descriptor['username'];
        $dbpassword = $descriptor['password'];

        $this->_pdo = new \PDO($dsn, $dbusername, $dbpassword, $options);
        $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        /*
         * Dialect sınıfını ayarla
         */
        if (isset($descriptor['dialectClass']) === false) {
            $dialectClass = 'Phalcon\\Db\\Dialect\\' . ucfirst($this->_dialectType);
        } else {
            $dialectClass = $descriptor['dialectClass'];
        }
        /*
         * Örneği yalnızca dialect bir string ise oluştur
         */
        if (is_string($dialectClass) === true) {
            $dialectObject = new $dialectClass();
            $this->_dialect = $dialectObject;
        }
        return true;
    }

    /**
     * Bir tabloyu tanımlayan Phalcon\Db\Column nesnelerinden oluşan bir dizi döndürür
     * <code>
     * print_r($connection->describeColumns("posts"));
     * </code>.
     *
     * @param string $table
     * @param string $schema
     *
     * @return \Phalcon\Db\Column[]
     */
    public function describeColumns(string $table, ?string $schema = null): array
    {
        $oldColumn = null;

        /*
         * Birincil anahtarları al
         */
        $primaryKeys = array();
        foreach ($this->fetchAll($this->_dialect->getPrimaryKey($table, $schema)) as $field) {
            $primaryKeys[$field['COLUMN_NAME']] = true;
        }

        /*
         * Bir tabloyu tanımlamak için SQL'i al
         * Sütunları çekmek için FETCH_NUM kullanıyoruz
         * Tanımlamayı al
         * Alan İndeksleri: 0:name, 1:type, 2:not null, 3:key, 4:default, 5:extra
         */
        foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema)) as $field) {
            /*
             * Varsayılan olarak bind tipi ikidir
             */
            $definition = array('bindType' => Column::BIND_PARAM_STR);

            /*
             * Her sütun tipini kontrol ederek onu bir Phalcon\Db\Column'a dönüştürüyoruz
             */
            $autoIncrement = false;
            $columnType = $field['TYPE_NAME'];
            switch ($columnType) {
                /*
                 * Smallint/Bigint/Integers/Int int türündedir
                 */
                case 'int identity':
                case 'tinyint identity':
                case 'smallint identity':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    $autoIncrement = true;
                    break;
                case 'bigint identity':
                    $definition['type'] = Column::TYPE_BIGINTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    $autoIncrement = true;
                    break;
                case 'bigint':
                    $definition['type'] = Column::TYPE_BIGINTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;
                case 'decimal':
                case 'money':
                case 'smallmoney':
                    $definition['type'] = Column::TYPE_DECIMAL;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;
                case 'int':
                case 'tinyint':
                case 'smallint':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;
                case 'numeric':
                    $definition['type'] = Column::TYPE_DOUBLE;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;
                case 'float':
                case 'real':
                    $definition['type'] = Column::TYPE_FLOAT;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;

                /*
                 * Boolean
                 */
                case 'bit':
                    $definition['type'] = Column::TYPE_BOOLEAN;
                    $definition['bindType'] = Column::BIND_PARAM_BOOL;
                    break;

                /*
                 * Date türleri tarihtir
                 */
                case 'date':
                    $definition['type'] = Column::TYPE_DATE;
                    break;

                /*
                 * Time
                 */
                case 'time':
                    $definition['type'] = Column::TYPE_TIME;
                    break;

                /*
                 * datetime için özel tip
                 */
                case 'datetime':
                case 'datetime2':
                case 'smalldatetime':
                case 'datetimeoffset':
                    $definition['type'] = Column::TYPE_DATETIME;
                    break;

                /*
                 * Timestamp türleri tarihtir
                 */
                case 'timestamp':
                    $definition['type'] = Column::TYPE_TIMESTAMP;
                    break;

                /*
                 * Char türleri char'dır
                 */
                case 'char':
                case 'nchar':
                    $definition['type'] = Column::TYPE_CHAR;
                    break;

                case 'varchar':
                case 'nvarchar':
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;

                /*
                 * Text türleri varchar'dır
                 */
                case 'text':
                case 'ntext':
                case 'xml':
                    $definition['type'] = Column::TYPE_TEXT;
                    break;

                /*
                 * blob tipi
                 */
                case 'varbinary':
                case 'binary':
                case 'image':
                    $definition['type'] = Column::TYPE_BLOB;
                    break;

                /*
                 * GUID bir string olarak saklanır
                 */
                case 'uniqueidentifier':
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;

                /*
                 * Varsayılan olarak string'dir
                 */
                default:
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;
            }

            /*
             * Sütun tipinde parantez varsa, sütun boyutunu oradan almaya çalışıyoruz
             */
            $definition['size'] = (int) $field['LENGTH'];
            $definition['precision'] = (int) $field['PRECISION'];

            if ($field['SCALE'] || $field['SCALE'] == '0') {
                //                $definition["scale"] = (int) $field['SCALE'];
                $definition['size'] = $definition['precision'];
            }

            /*
             * Konumlar
             */
            if (!$oldColumn) {
                $definition['first'] = true;
            } else {
                $definition['after'] = $oldColumn;
            }

            /*
             * Alanın birincil anahtar olup olmadığını kontrol et
             */
            if (isset($primaryKeys[$field['COLUMN_NAME']])) {
                $definition['primary'] = true;
            }

            /*
             * Sütunun null değerlere izin verip vermediğini kontrol et
             */
            if ($field['NULLABLE'] == 0) {
                $definition['notNull'] = true;
            }

            /*
             * Sütunun otomatik artan olup olmadığını kontrol et
             */
            if ($autoIncrement) {
                $definition['autoIncrement'] = true;
            }

            /*
             * Sütunun varsayılan değere sahip olup olmadığını kontrol et
             */
            if ($field['COLUMN_DEF'] != null) {
                $definition['default'] = $field['COLUMN_DEF'];
            }

            $columnName = $field['COLUMN_NAME'];
            $columns[] = new Column($columnName, $definition);
            $oldColumn = $columnName;
        }
        return $columns;
    }
    
    public function getDsnDefaults(): array
    {
        return [];
    }
    /**
     * SQL ifadelerini veritabanı sunucusuna gönderir ve başarı durumunu döndürür.
     * Bu metodu yalnızca sunucuya gönderilen SQL ifadesi satır döndürdüğünde kullanın
     * <code>
     * //Querying data
     * $resultset = $connection->query("SELECT * FROM robots WHERE type = 'mechanical'");
     * $resultset = $connection->query("SELECT * FROM robots WHERE type = ?", array("mechanical"));
     * </code>.
     *
     * @param string $sqlStatement
     * @param mixed  $bindParams
     * @param mixed  $bindTypes
     *
     * @return bool|\Phalcon\Db\ResultInterface
     */
    public function query($sqlStatement, $bindParams = null, $bindTypes = null)
    {
        $eventsManager = $this->eventsManager;

        /*
         * Bir EventsManager mevcutsa beforeQuery olayını çalıştır
         */
        if (is_object($eventsManager)) {
            $this->_sqlStatement = $sqlStatement;
            $this->_sqlVariables = $bindParams;
            $this->_sqlBindTypes = $bindTypes;

            if ($eventsManager->fire('db:beforeQuery', $this, $bindParams) === false) {
                return false;
            }
        }

        $pdo = $this->_pdo;

        $cursor = \PDO::CURSOR_SCROLL;
        if (strpos($sqlStatement, 'exec') !== false) {
            $cursor = \PDO::CURSOR_FWDONLY;
        }

        if (is_array($bindParams)) {
            $statement = $pdo->prepare($sqlStatement, array(\PDO::ATTR_CURSOR => $cursor));
            if (is_object($statement)) {
                $statement = $this->executePrepared($statement, $bindParams, $bindTypes);
            }
        } else {
            $statement = $pdo->prepare($sqlStatement, array(\PDO::ATTR_CURSOR => $cursor));
            $statement->execute();
        }

        /*
         * Bir EventsManager mevcutsa afterQuery olayını çalıştır
         */
        if (is_object($statement)) {
            if (is_object($eventsManager)) {
                $eventsManager->fire('db:afterQuery', $this, $bindParams);
            }
            return new ResultPdo($this, $statement, $sqlStatement, $bindParams, $bindTypes);
        }
        return $statement;
    }

    /**
     * SQL ifadelerini veritabanı sunucusuna gönderir ve başarı durumunu döndürür.
     * Bu metodu yalnızca sunucuya gönderilen SQL ifadesi herhangi bir satır döndürmediğinde kullanın
     * <code>
     * //Inserting data
     * $success = $connection->execute("INSERT INTO robots VALUES (1, 'Astro Boy')");
     * $success = $connection->execute("INSERT INTO robots VALUES (?, ?)", array(1, 'Astro Boy'));
     * </code>.
     *
     * @param string $sqlStatement
     * @param mixed  $bindParams
     * @param mixed  $bindTypes
     *
     * @return bool
     */
   public function execute(string $sqlStatement, $bindParams = NULL, $bindTypes = NULL): bool
        {
            $eventsManager = $this->eventsManager;
    
            /*
             * Bir EventsManager mevcutsa beforeQuery olayını çalıştır
             */
            if (is_object($eventsManager)) {
                $this->_sqlStatement = $sqlStatement;
                $this->_sqlVariables = $bindParams;
                $this->_sqlBindTypes = $bindTypes;
    
                if ($eventsManager->fire('db:beforeQuery', $this, $bindParams) === false) {
                    return false;
                }
            }
    
            /*
             * affectedRows değerini 0 olarak başlat
             */
            $affectedRows = 0;
    
            $pdo = $this->_pdo;
    
            $cursor = \PDO::CURSOR_SCROLL;
            if (strpos($sqlStatement, 'exec') !== false) {
                $cursor = \PDO::CURSOR_FWDONLY;
            }
    
            if (is_array($bindParams)) {
                $statement = $pdo->prepare($sqlStatement, array(\PDO::ATTR_CURSOR => $cursor));
                if (is_object($statement)) {
                   $newStatement = $this->executePrepared($statement, $bindParams, $bindTypes);
                    $affectedRows = $newStatement->rowCount();
                }
            } else {
                $statement = $pdo->prepare($sqlStatement, array(\PDO::ATTR_CURSOR => $cursor));
                $statement->execute();
                $affectedRows = $statement->rowCount();
            }
    
            /*
             * Bir EventsManager mevcutsa afterQuery olayını çalıştır
             */
            if (is_int($affectedRows)) {
                $this->_affectedRows = $affectedRows;
                if (is_object($eventsManager)) {
                    $eventsManager->fire('db:afterQuery', $this, $bindParams);
                }
            }
    
            return true;
        }
}
