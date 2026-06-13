# Phalcon 4 — Microsoft SQL Server (PDO) Adaptörü

[English](README.md) · **Türkçe**

![PHP](https://img.shields.io/badge/PHP-%3E%3D7.2-777BB4?logo=php&logoColor=white)
![Phalcon](https://img.shields.io/badge/Phalcon-%3E%3D4.0-0C7CBA)
![SQL Server](https://img.shields.io/badge/Microsoft%20SQL%20Server-PDO-CC2927?logo=microsoftsqlserver&logoColor=white)
![License](https://img.shields.io/badge/lisans-MIT-green)

[Phalcon 4](https://phalcon.io) çatısı için PDO tabanlı **Microsoft SQL Server** veritabanı adaptörü. DI konteynerine `db` servisi olarak takılır; ORM, modeller ve Query Builder, MySQL veya PostgreSQL'de kullandığın aynı API ile SQL Server'a bağlanır.

## Özellikler

- MS SQL Server için **PDO adaptörü** (`Phalcon\Db\Adapter\Pdo\Sqlsrv`).
- **T-SQL lehçesi** (`Phalcon\Db\Dialect\Sqlsrv`): `OFFSET … ROWS FETCH NEXT … ROWS ONLY` ile sayfalama, köşeli parantezli tanımlayıcılar (`[kolon]`), `IDENTITY(1,1)` otomatik artış ve kilit ipuçları (`WITH (UPDLOCK)` / `WITH (NOLOCK)`).
- **Şema keşfi**: `describeColumns`, `listTables`, `listViews`, indeksler ve yabancı anahtarlar — `INFORMATION_SCHEMA`, `sys.*` ve `sp_columns` / `sp_pkeys` üzerinden.
- Phalcon **ORM modelleri** ve **Query Builder** ile sorunsuz çalışır.
- İsteğe bağlı ekler: veritabanı tabanlı **logger adaptörü** ve **sorgu hata ayıklama dinleyicisi**.

## Gereksinimler

| Gereksinim | Sürüm |
|---|---|
| PHP | `>= 7.2` |
| Phalcon C eklentisi (`ext-phalcon`) | `>= 4.0` |
| PHP SQL Server sürücüsü | `pdo_sqlsrv` |
| ODBC | Microsoft ODBC Driver 17/18 for SQL Server |

> `pdo_sqlsrv` eklentisi ve Microsoft'un ODBC sürücüsü sunucuda kurulu ve etkin olmalıdır. Bkz. [Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/sql/connect/php/) dokümantasyonu.

## Kurulum

Packagist'te yayımlanana kadar VCS bağımlılığı olarak kurulur. Projenin `composer.json` dosyasına depoyu ekle:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/yasin-kose/phalcon4-sqlsrv" }
    ]
}
```

Ardından bağımlılığı ekle:

```bash
composer require ankapix/phalcon4-sqlsrv:dev-master
```

## Kullanım

Adaptörü DI konteynerinde `db` servisi olarak kaydet:

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

Bundan sonra her şey Phalcon'un standart API'si üzerinden çalışır:

```php
// Ham sorgu (resultset döner)
$robots = $connection->query('SELECT * FROM robots WHERE type = ?', ['mechanical']);

// Yazma (başarı durumu döner, etkilenen satır sayısını sağlar)
$connection->execute('INSERT INTO robots (name) VALUES (?)', ['Astro Boy']);

// Sayfalama — lehçe bunu OFFSET / FETCH NEXT'e çevirir
$builder->limit(20, 40);

// Şema keşfi
print_r($connection->describeColumns('robots'));
```

### Tip eşlemesi

SQL Server kolon tipleri, Phalcon'un `Column` tiplerine şöyle eşlenir:

| SQL Server | Phalcon `Column` tipi |
|---|---|
| `int`, `smallint`, `tinyint` (`identity` dahil) | `TYPE_INTEGER` |
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

## İsteğe bağlı ekler

**Sorgu hata ayıklama dinleyicisi** — `$connection->debug` `true` olduğunda her SQL ifadesini ekrana basar:

```php
use Phalcon\Db\DbListener;

$eventsManager->attach('db', new DbListener());
$connection->setEventsManager($eventsManager);
```

**Veritabanı logger adaptörü** — log kayıtlarını `insertAsDict` ile bir tabloya yazar:

```php
use Phalcon\Logger\Adapter\Database;

$logger = new Database([
    'db'    => $connection,
    'table' => 'logs',
]);
```

## Notlar ve sınırlamalar

- SQL Server, `OFFSET`/`FETCH` için bir `ORDER BY` ister; sayfalanan sorguda yoksa lehçe otomatik olarak `ORDER BY 1` ekler.
- Canlı SQL Server'a karşı entegrasyon testleri henüz dahil değildir; lehçenin SQL üretimi, birim testlere uygun çekirdektir.

## Katkı

Issue ve pull request'ler memnuniyetle karşılanır. Lütfen genel sınıf/metot imzalarını Phalcon 4 temel sınıfları ve arayüzleriyle uyumlu tut.

## İletişim

Yasin Köse — [yasin@uludem.com.tr](mailto:yasin@uludem.com.tr)

## Lisans

[MIT Lisansı](LICENSE) ile yayımlanmıştır. © Yasin Köse / [ankapix](https://ankapix.com).
