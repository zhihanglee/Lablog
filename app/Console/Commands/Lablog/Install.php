<?php

namespace App\Console\Commands\Lablog;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lablog:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '设置基础配置';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * 获取并替换 .env 中的数据库账号密码
         */
        $app_name = $this->ask('请输入应用名', 'LABLOG');
        $app_url = $this->ask('请输入应用域名', 'https://imwnk.cn');
        $username = $this->ask('请输入数据库账号', 'root');
        $password = $this->ask('请输入数据库密码', false);
        $database = $this->ask('请输入数据库名', 'lablog');
        // ask 不允许为空  此处是为了兼容一些数据库密码为空的情况
        $password = $password ? $password : '';
        $envExample = file_get_contents(base_path('.env.example'));
        $search_db = [
            'APP_NAME=Lablog',
            'APP_URL=http://localhost',
            'DB_DATABASE=homestead',
            'DB_USERNAME=homestead',
            'DB_PASSWORD=secret'
        ];
        $replace_db = [
            'APP_NAME='.$app_name,
            'APP_URL='.$app_url,
            'DB_DATABASE='.$database,
            'DB_USERNAME='.$username,
            'DB_PASSWORD='.$password,
        ];
        $env = str_replace($search_db, $replace_db, $envExample);
        file_put_contents(base_path('.env'), $env);
    }
}
