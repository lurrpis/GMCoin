<?php
/**
 * Create by lurrpis
 * Date 08/05/2017 5:29 PM
 * Blog lurrpis.com
 */

namespace GMCloud\GMCoin;

use GMCloud\GMCoin\Command\BuyCommand;
use GMCloud\GMCoin\Command\CancelCommand;
use GMCloud\GMCoin\Command\OrderCommand;
use GMCloud\GMCoin\Command\PullCommand;
use GMCloud\GMCoin\Command\PushCommand;
use GMCloud\GMCoin\Command\SellCommand;
use GMCloud\SDK\Yunbi;
use GMCloud\GMCoin\Command\NowCommand;
use GMCloud\GMCoin\Command\AccountCommand;
use GMCloud\GMCoin\Command\ConfigCommand;
use GMCloud\GMCoin\Command\DepthCommand;
use GMCloud\GMCoin\Command\MarketCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcher;
use RuntimeException;

class App
{
    const APP = 'GMCoin';
    const VERSION = '1.2.1';
    const ACCESS_KEY = 'ACCESS_KEY';
    const SECRET_KEY = 'SECRET_KEY';

    public static $command = [
        AccountCommand::class,
        NowCommand::class,
        OrderCommand::class,
        BuyCommand::class,
        SellCommand::class,
        CancelCommand::class,
        PullCommand::class,
        PushCommand::class,
        ConfigCommand::class
    ];

    // 是否需要密钥
    public static $keyCommand = [
        AccountCommand::class,
        NowCommand::class,
        OrderCommand::class,
        BuyCommand::class,
        SellCommand::class,
        CancelCommand::class,
        PullCommand::class,
        PushCommand::class
    ];

    public static $secretPath = __DIR__ . '/.secret';
    public static $accessKey;
    public static $secretKey;

    /**
     * @var Application $console
     */
    protected $console;

    public function addCommand()
    {
        foreach (static::$command as $item) {
            $this->console->add(new $item());
        }
    }

    public function addEvent()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            // get the output instance
            $output = $event->getOutput();

            // get the command to be executed
            $command = $event->getCommand();

            foreach (static::$keyCommand as $item) {
                if ($item::$name == $command->getName()) {
                    if (empty(static::$accessKey) || empty(static::$secretKey)) {
                        $config = $this->console->find('config');

                        $config->run(new ArrayInput([
                            '--force' => true
                        ]), $output);
                    }
                }
            }
        });

        $this->console->setDispatcher($dispatcher);
    }

    public static function initialize()
    {
        static::initSecretFile();

        static::$accessKey = static::getKeySecretFile(static::ACCESS_KEY);
        static::$secretKey = static::getKeySecretFile(static::SECRET_KEY);

        Yunbi::setAccessKey(static::$accessKey);
        Yunbi::setSecretKey(static::$secretKey);
    }

    public static function initSecretFile()
    {
        if (!file_exists(static::$secretPath)) {
            $secretFile = static::ACCESS_KEY . '=' . PHP_EOL . static::SECRET_KEY . '=';
            if (!file_put_contents(static::$secretPath, $secretFile)) {
                throw new RuntimeException('Can not create secret file:' . static::$secretPath);
            }
        }
    }

    public static function getKeySecretFile($key)
    {
        preg_match("/^{$key}=[a-z0-9A-Z_]*/m", file_get_contents(static::$secretPath), $match);

        $value = '';
        if (!empty($match[0])) {
            $value = explode('=', $match[0])[1];
        }

        return $value;
    }

    public static function setKeySecretFile($key, $value)
    {
        return file_put_contents(static::$secretPath,
            preg_replace(static::keyReplacementPattern($key), "{$key}={$value}",
                file_get_contents(static::$secretPath)));
    }

    public static function keyReplacementPattern($key)
    {
        $escaped = preg_quote(static::getKeySecretFile($key), '/');

        return "/^{$key}={$escaped}/m";
    }


    public function run()
    {
        $this->console = new Application(self::APP, self::VERSION);

        $this->addCommand();
        $this->addEvent();
        $this->initialize();

        $this->console->run();
    }
}