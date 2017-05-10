<?php
/**
 * Create by lurrpis
 * Date 04/05/2017 6:37 PM
 * Blog lurrpis.com
 */

namespace GMCloud\GMCoin\Command;

use GMCloud\GMCoin\Client\Yunbi;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccountCommand extends BaseCommand
{
    public static $name = 'account';
    public static $status = [
        0   => '冻结',
        1   => '正常'
    ];

    protected function configure()
    {
        $this->setName(static::$name)->setDescription('My account');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $account = Yunbi::account();

        static::header($output);
        $output->writeln('<info>账号: </info>' . $account['email']);
        $output->writeln('<info>状态: </info>' . static::$status[$account['activated']]);
        $output->writeln('<info>ID: </info>' . $account['memo']);

        $table = new Table($output);

        $header = [];
        foreach($account['accounts'] as $item) {
            $header = array_keys($item);
            $table->addRow(array_values($item));
        }

        $table->setHeaders($header);

        $table->render();
    }
}