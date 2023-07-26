<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\model\siteModel;
use think\facade\Db;
use app\model\testModel;

class site extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('site')
            ->setDescription('连接数据库的站点');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
//        $output->writeln('site');
//        $site = (new siteModel())->where("status",1)->select()->toArray();
//        print_r($site);

        $config = [
            'type'     => 'mysql',
            'hostname' => '127.0.0.1',
            'database' => 'truedata',
            'username' => 'root',
            'password' => 'root',
            'hostport' => '3306',
            'charset'  => 'utf8',
            'prefix'   => '',
        ];
        $config = config("database.mysql");
        Db::setConfig('db2', $config);
        //$list = Db::connect('mysql')->query("select * from fb_football_competition where id=1");
        print_r($config);
    }
}
