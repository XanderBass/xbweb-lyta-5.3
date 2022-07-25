<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Install controller
     * @category     Core controllers
     * @link         https://xbweb.ru/doc/dist/classes/controllers/install
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Controllers;

    use xbweb\Controller;
    use xbweb\DB;
    use xbweb\Request;
    use xbweb\Response;

    /**
     * Class Install
     */
    class Install extends Controller
    {
        /**
         * Constructor
         * @param string $path   Controller path
         * @param mixed  $model  Controller model
         * @throws \xbweb\Error
         */
        protected function __construct($path, $model = null)
        {
            parent::__construct($path, $model);
            if (true || !\xbweb\INSTALLED) {
                $this->_allowed[] = 'index';
            }
        }

        /**
         * Install CMF
         * @return array
         * @action /install
         */
        public function do_index()
        {
            if (Request::isPost()) {
                $errors = array();
                foreach (array('user', 'pass', 'name') as $k) {
                    if (empty($_POST['db'][$k])) $errors['db_'.$k] = 'empty';
                }
                foreach (array('password', 'email') as $k) {
                    if (empty($_POST['admin'][$k])) $errors['admin_'.$k] = 'empty';
                }
                if (empty($errors)) {
                    $db = array(
                        'host'   => empty($_POST['db']['host']) ? '127.0.0.1' : $_POST['db']['host'],
                        'user'   => $_POST['db']['user'],
                        'pass'   => $_POST['db']['pass'],
                        'name'   => $_POST['db']['name'],
                        'prefix' => empty($_POST['db']['prefix']) ? ''   : $_POST['db']['prefix'],
                        'port'   => empty($_POST['db']['port'])   ? 3306 : intval($_POST['db']['port']),
                    );
                    try {
                        $DBO = DB\Provider::create($db);
                    } catch (\Exception $e) {
                        $errors['db'] = $e->getMessage();
                        return Response::error($errors);
                    }
                    $admin = array(
                        'login'    => empty($_POST['admin']['login']) ? 'admin' : $DBO->escape($_POST['admin']['login']),
                        'password' => \xbweb\password($_POST['admin']['password']),
                        'email'    => $DBO->escape($_POST['admin']['email']),
                        'key'      => \xbweb::key()
                    );
                    // Create users table
                    $q = <<<sql
create table `[+prefix+]users` (
    `id`        bigint not null auto_increment,
    `login`     char(32)  not null,
    `email`     char(128) null,
    `phone`     bigint null,
    `password`  char(64)  not null,
    `key`       char(32)  not null,
    `created`   datetime null,
    `activated` datetime null,
    `deleted`   datetime null,
    `role`      tinyint not null default '0',
    `flags`     int not null default '0',
    primary key (`id`),
    unique index (`login`)
) engine = InnoDB comment = 'Users'
sql;
                    try {
                        $DBO->query($q);
                    } catch (\Exception $e) {
                        $errors['db'] = $e->getMessage();
                        return Response::error($errors);
                    }
                    // Create superuser
                    $q = <<<sql
insert into `[+prefix+]users`
    (`login`, `email`, `password`, `key`, `created`, `activated`, `role`)
values
    ('{$admin['login']}', '{$admin['email']}', '{$admin['password']}', '{$admin['key']}', now(), now(), -1)
sql;
                    try {
                        $DBO->query($q);
                    } catch (\Exception $e) {
                        $errors['db'] = $e->getMessage();
                        return Response::error($errors);
                    }
                    // Create config
                    $config = array(
                        'db' => $db,
                    );
                    $c = var_export($config, true);
                    $f = <<<php
<?php
    namespace xbweb {
        Config::set({$c});
    }
php;
                    if (file_put_contents(\xbweb\Paths\WEBROOT.'config.php', $f)) {
                        \xbweb::redirect('/admin/users/login');
                    } else {
                        return Response::error('Cannot write configuration file');
                    }
                } else {
                    return Response::error($errors);
                }
            }
            return Response::success();
        }
    }