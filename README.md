# Phalcon 4 — Microsoft SQL Server (PDO) Adapter

**English** · [Türkçe](README.tr.md)

![PHP](https://img.shields.io/badge/PHP-%3E%3D7.2-777BB4?logo=php&logoColor=white)
![Phalcon](https://img.shields.io/badge/Phalcon-%3E%3D4.0-0C7CBA)
![SQL Server](https://img.shields.io/badge/Microsoft%20SQL%20Server-PDO-CC2927?logo=microsoftsqlserver&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

A PDO-based **Microsoft SQL Server** database adapter for the [Phalcon 4](https://phalcon.io) framework. It plugs into Phalcon's DI as the `db` service and lets the ORM, models, and Query Builder talk to SQL Server using the same API you already use for MySQL or PostgreSQL.

## Features

- **PDO adapter** for MS SQL Server (`Phalcon\Db\Adapter\Pdo\Sqlsrv`).
- **T-SQL dialect** (`Phalcon\Db\Dialect\Sqlsrv`): pagination via `OFFSET … ROWS FETCH NEXT … ROWS ONLY`, bracket-quoted identifiers (`[col]`), `IDENTITY(1,1)` auto-increment, and lock hints (`WITH (UPDLOCK)` / `WITH (NOLOCK)`).
- **Schema introspection**: `describeColumns`, `listTables`, `listViews`, indexes and foreign keys via `INFORMATION_SCHEMA`, `sys.*`, and `sp_columns` / `sp_pkeys`.
- Works transparently with Phalcon **ORM models** and the **Query Builder**.
- Optional extras: a database-backed **logger adapter** and a **query-debug listener**.

## Requirements

| Requirement | Version |
|---|---|
| PHP | `>= 7.2` |
| Phalcon C extension (`ext-phalcon`) | `>= 4.0` |
| PHP SQL Server driver | `pdo_sqlsrv` |
| ODBC | Microsoft ODBC Driver 17/18 for SQL Server |

> The `pdo_sqlsrv` extension and Microsoft's ODBC driver must be installed and enabled on the server. See the [Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/sql/connect/php/) documentation.

## Installation

Until it is published on Packagist, install it as a VCS dependency. Add the repository to your project's `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/yasin-kose/phalcon4-sqlsrv" }
    ]
}
```

Then require it:

```bash
composer require ankapix/phalcon4-sqlsrv:dev-master
```

## Usage

Register the adapter as the `db` service in your DI container:

```php
use Phalcon\Db\Adapter\Pdo\Sqlsrv;

$di->setShared('db', function () use ($config) {
    return new Sqlsrv([
        'host'     => $config->database->host,
        'dbname'   => $config->database->name,
        'username' => $config->database->username,
        'password' => $config->database->password,
    ]);
});
```

From here, everything works through Phalcon's standard API:

```php
// Raw query (returns a resultset)
$robots = $connection->query('SELECT * FROM robots WHERE type = ?', ['mechanical']);

// Write (returns success state, exposes affected rows)
$connection->execute('INSERT INTO robots (name) VALUES (?)', ['Astro Boy']);

// Pagination — the dialect translates this to OFFSET / FETCH NEXT
$builder->limit(20, 40);

// Introspection
print_r($connection->describeColumns('robots'));
```

### Type mapping

SQL Server column types are mapped onto Phalcon's `Column` types as follows:

| SQL Server | Phalcon `Column` type |
|---|---|
| `int`, `smallint`, `tinyint` (incl. `identity`) | `TYPE_INTEGER` |
| `bigint`, `bigint identity` | `TYPE_BIGINTEGER` |
| `decimal`, `money`, `smallmoney` | `TYPE_DECIMAL` |
| `numeric` | `TYPE_DOUBLE` |
| `float`, `real` | `TYPE_FLOAT` |
| `bit` | `TYPE_BOOLEAN` |
| `date` | `TYPE_DATE` |
| `time` | `TYPE_TIME` |
| `datetime`, `datetime2`, `smalldatetime`, `datetimeoffset` | `TYPE_DATETIME` |
| `timestamp` | `TYPE_TIMESTAMP` |
| `char`, `nchar` | `TYPE_CHAR` |
| `varchar`, `nvarchar`, `uniqueidentifier` | `TYPE_VARCHAR` |
| `text`, `ntext`, `xml` | `TYPE_TEXT` |
| `varbinary`, `binary`, `image` | `TYPE_BLOB` |

## Optional extras

**Query-debug listener** — echoes each SQL statement when `$connection->debug` is `true`:

```php
use Phalcon\Db\DbListener;

$eventsManager->attach('db', new DbListener());
$connection->setEventsManager($eventsManager);
```

**Database logger adapter** — writes log entries to a table via `insertAsDict`:

```php
use Phalcon\Logger\Adapter\Database;

$logger = new Database([
    'db'    => $connection,
    'table' => 'logs',
]);
```

## Notes & limitations

- SQL Server requires an `ORDER BY` for `OFFSET`/`FETCH`; when a paginated query has none, the dialect appends `ORDER BY 1` automatically.
- Integration tests against a live SQL Server are not included yet; the dialect's SQL generation is the unit-testable core.

## Contributing

Issues and pull requests are welcome. Please keep the public class/method signatures compatible with the Phalcon 4 base classes and interfaces.

## Contact

Yasin Köse — [yasin@uludem.com.tr](mailto:yasin@uludem.com.tr)

## License

Released under the [MIT License](LICENSE). © Yasin Köse / [ankapix](https://ankapix.com).
