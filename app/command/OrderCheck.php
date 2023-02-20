<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class OrderCheck extends Command
{
    protected static $defaultName = 'order:check';
    protected static $defaultDescription = '拉取学杂费的账单,解析对账文件';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '学校id');
        $this->addArgument('name2', InputArgument::OPTIONAL, 'Name2 description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $name2 = $input->getArgument('name2');
        // var_dump($name);
        for ($i=0; $i < 1000000000; $i++) { 
            # code...
        }
        $output->writeln('Hello order:check-'. ($name ?? 'www') . '==' . $name2);
        return self::SUCCESS;
    }

}
