<?php
/**
 * Created by PhpStorm.
 * User: HePing
 * Date: 2019-03-05
 * Time: 11:05
 */
namespace INocturneSwoole\Db\Tests;

use INocturneSwoole\Db\Query;

require '../vendor/autoload.php';

class QueryTest
{
    public function run()
    {
        $server = new \Swoole\Http\Server("127.0.0.1", 9502, SWOOLE_BASE);
        $server->set([
            'worker_num' => 1,
            'daemonize'  => false
        ]);
        $server->on('Request', function ($request, $response)
        {
            \INocturneSwoole\Connection\MySQLPool::init([
                'test' => [
                    'serverInfo'    => ['host' => '120.79.187.246', 'user' => 'heping', 'password' => 'Pwd123@jayjay.cn', 'database' => 'wewechat_dev', 'charset' => 'utf8'],
                    'maxSpareConns' => 5,
                    'maxConns'      => 10
                ],
            ]);
            $swoole_mysql = \INocturneSwoole\Connection\MySQLPool::fetch('test');
            $data         = [
                'aaaaaaa',
                'aaaa'
            ];
            //            insert
            //            $query = (new Query($swoole_mysql))->insert('user', [
            //                'name',
            //                'password'
            //            ])->params($data)->execute();
            //            $query = (new Query($swoole_mysql))->from('user')->select('id')->fetchAll();
            //            var_dump($query);
            //            update
            //            $query = (new Query($swoole_mysql))->update('user', ['name', 'password'], 84)->params($data)->execute();
            //delete
            //            $query = (new Query($swoole_mysql))->delete('user', 3)->execute();
            //where
            // $query = (new Query($swoole_mysql))->from('user')->where('id = ? AND name = ? AND password = ?')->params(['690', 'aaaaaaa', 'aaaa'])->fetchAll();
            //count
            //            $query = (new  Query($swoole_mysql))->from('user')->where('id < ?')->params([5])->count();
            //orderBy
            //            $query = (new Query($swoole_mysql))->from('user','u')->orderBy('u.id','DESC')->execute();
            //groupBy
            //           $query = (new Query($swoole_mysql))->select('u.name')->from('user','u')->groupBy('u.name')->execute();
            //join
            //            $query = (new Query($swoole_mysql))->from('user','u')->select('info.info')->join('user_info as info','u.id = info.uid','inner')->execute();
            //limit
//            $query = (new Query($swoole_mysql))->from('user')->where('id<4')->limit(2)->execute();
//            var_dump($query);
            //$ret          = $swoole_mysql->query('select * from `user`');
            \INocturneSwoole\Connection\MySQLPool::recycle($swoole_mysql);
            $response->end('Test End');
        });
        $server->start();
    }
}
var_dump((new QueryTest())->run());

