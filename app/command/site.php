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
use think\db\Connection;
use app\exception\PDOConnect;


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
        $config = [];
        $config['host'] = '127.0.0.1';
        $config['db'] = 'truedata';
        $config['username'] = 'root';
        $config['password'] = 'root';
        //$this->a1($config);
        $this->a2($config);
    }
    function a1($config){
        $conn = \mysqli_connect($config['hostname'], $config['username'], $config['password'], $config['database']);
        if (mysqli_connect_errno()) {
            echo "连接 MySQL 数据库失败：" . mysqli_connect_error();
            exit;
        }
        $sql = "SELECT * FROM fb_football_competition order by id desc limit 5";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                print_r($row);
            }
        } else {
            echo "没有查询到数据！";
        }
        mysqli_close($conn);
    }

    function a2($config){
        //$pdo = new PDOConnect($config['host'],$config['username'],$config['password'],$config['db']);
        $pdo = new PDOConnect($config);
        $res = $pdo->query("SELECT * FROM fb_football_competition order by id desc limit 5");
        print_r($res);
    }
}
