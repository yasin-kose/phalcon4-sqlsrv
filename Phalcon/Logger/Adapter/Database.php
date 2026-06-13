<?php

namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Exception;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger\Adapter as LoggerAdapter;
use Phalcon\Logger\AdapterInterface;

/**
 * Phalcon\Logger\Adapter\Database
 * Günlük kayıtlarını bir veritabanı tablosunda saklamak için adaptör.
 */
class Database extends LoggerAdapter implements AdapterInterface
{
    /**
     * Kullanıcı adı.
     *
     * @var string
     */
    protected $username = 'guest';

    /**
     * Adaptör seçenekleri.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Sınıf kurucusu.
     *
     * @param string $name
     * @param array  $options
     *
     * @throws \Phalcon\Logger\Exception
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['db'])) {
            throw new Exception("Parameter 'db' is required");
        }

        if (!isset($options['table'])) {
            throw new Exception("Parameter 'table' is required");
        }

        if (!empty($options['username'])) {
            $this->username = $options['username'];
        }

        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Phalcon\Logger\FormatterInterface
     */
    public function getFormatter()
    {
        if (!is_object($this->_formatter)) {
            $this->_formatter = new LineFormatter();
        }

        return $this->_formatter;
    }

    /**
     * Günlük kaydını yapılandırılan veritabanı tablosuna yazar.
     *
     * @param string $message
     * @param int    $type
     * @param int    $time
     * @param array  $context
     */
    public function logInternal($message, $type, $time, $context)
    {
        return $this->options['db']->insertAsDict(
                $this->options['table'], array(
                'LogType' => $type,
                'LogProcess' => $context['process'],
                'LogContent' => $message,
                'LogUser' => $this->username,
                'LogDate' => date('Y-m-d H:i:s', $time),
                'LogIP' => $this->getIP(),
                'LogBrowser' => $this->getBrowser(),
        ));
    }

    /**
     * Günlükçüyü kapatır.
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    public function getIP()
    {
        return (getenv('HTTP_X_FORWARDED_FOR')) ? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR');
    }

    public function getBrowser()
    {
        $info = array('name' => '', 'version' => '');

        // Aranacak bilinen tarayıcıları tanımla
        $browsers = array('chrome', 'firefox', 'safari', 'msie', 'opera',
            'mozilla', 'seamonkey', 'konqueror', 'netscape',
            'gecko', 'navigator', 'mosaic', 'lynx', 'amaya',
            'omniweb', 'avant', 'camino', 'flock', 'aol', );

        // Tüm ifadeleri bul (veya hiçbiri bulunamazsa boş dizi döndür)
        foreach ($browsers as $browser) {
            if (preg_match("#($browser)[/ ]?([0-9.]*)#", strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''), $match)) {
                $info['name'] = $match[1];
                $info['version'] = $match[2];
                break;
            }
        }

        return "{$info['name']} ({$info['version']})";
    }
}
