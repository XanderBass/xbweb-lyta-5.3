<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  User controller
     * @category     Core controllers
     * @link         https://xbweb.ru/doc/dist/classes/controllers/users
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\Controllers;

    use xbweb\ErrorDeleted;
    use xbweb\ErrorForbidden;
    use xbweb\ErrorNotFound;

    use xbweb\lib\Roles as LibRoles;
    use xbweb\lib\Users as LibUsers;

    use xbweb\Config;
    use xbweb\Mailer;
    use xbweb\PipeLine;
    use xbweb\Request;
    use xbweb\Response;
    use xbweb\Session;
    use xbweb\Model;
    use xbweb\User;

    use xbweb\DB;
    use xbweb\DB\Where;

    /**
     * User controller class
     */
    class Users extends Entity {
        const MODEL  = '/users';
        const ENTITY = 'user';

        /**
         * Constructor
         * @param string $path Controller path
         * @param string $model Model path
         * @throws \xbweb\Error
         */
        protected function __construct($path, $model) {
            parent::__construct($path, $model);
            $where = $this->_fused_where();
            $rmask = ~ LibRoles::toInt('moderator,admin,root');
            $r_val = "(`role` & {$rmask})";
            foreach (array('moderator', 'admin', 'root', 'user') as $k) {
                $value = "({$r_val} | ".(LibRoles::toInt($k)).')';
                $this->_queries['make'.$k] = array(
                    'title'   => 'Confirm make '.$k,
                    'success' => 'User is '.$k.' now',
                    'error'   => 'Error making user '.$k,
                    'confirm' => 'Change user role to '.$k.'?',
                    'query'   => "update `[+table+]` set `flags` = ({$value}) where " . $where
                );
            }
            $this->_queries['activate'] = array(
                'title'   => 'Confirm activate user',
                'success' => 'User is activated',
                'error'   => 'Error activate user',
                'confirm' => 'Activate user?',
                'query'   => "update `[+table+]` set `activated` = now() where " . $where
            );
        }

        /**
         * onConstruct
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         * @throws \xbweb\NodeError
         */
        protected function onConstruct() {
            $model = Model::create($this->_modelPath);
            $this->_fuse = Where::create($model);
            switch (User::current()->role) {
                case 'user'     : $this->_fuse->condition($model->primary, User::current()->id); break;
                case 'moderator': $this->_fuse->condition('role', 'moderator,admin,root', 'isnot'); break;
                case 'admin'    : $this->_fuse->condition('role', 'admin,root', 'isnot'); break;
                case 'root'     : $this->_fuse->condition('role', 'root', 'isnot'); break;
                default         : $this->_fuse->condition($model->primary, 0); break;
            }
        }

        /**
         * Login
         * @action /users/login
         * @return array
         * @throws \xbweb\Error
         */
        public function do_login() {
            if (User::current()->authorized) Response::redirectBack();
            if (Request::isPost()) {
                $errors = array();
                if (empty($_POST['login']))    $errors['login']    = 'empty';
                if (empty($_POST['password'])) $errors['password'] = 'empty';
                if (!empty($errors)) return Response::error($errors);
                try {
                    $user = LibUsers::getByLogin($_POST['login']);
                } catch (\Exception $e) {
                    return Response::error($e->getMessage());
                }
                if ($user['password'] != \xbweb\password($_POST['password'])) {
                    Session::instance()->failed();
                    return Response::error('Incorrect password');
                }
                User::current($user, !empty($_POST['safe']));
                if (Request::isAJAX()) return Response::success($user);
                Response::redirectBack();
            }
            $ret = Response::success();
            return $ret;
        }

        /**
         * Logout
         * @action /users/logout
         * @throws \xbweb\Error
         */
        public function do_logout() {
            if (!User::current()->authorized) \xbweb::redirect('/');
            User::current(false);
            if (Request::isAJAX()) return Response::success();
            return Response::redirectBack();
        }

        /**
         * Register user
         * @action /users/register
         * @return array
         * @throws \xbweb\Error
         */
        public function do_register() {
            if (User::current()->authorized) Response::redirectBack();
            $errors  = null;
            $request = null;
            if (Request::isPost()) {
                if ($user = LibUsers::register($request, $errors)) {
                    if (Config::get('users/activation', false)) {
                        return Response::message('Registration successfull! Check your email for activation');
                    } else {
                        $this->_logged($user);
                    }
                }
            }
            $model = Model::create($this->_modelPath);
            return Response::form($model->form('create', $request), $request, $errors);
        }

        /**
         * User activation
         * @action /users/activation
         * @return array
         * @throws ErrorForbidden
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        public function do_activation() {
            if (empty($_GET['user']) || empty($_GET['key'])) throw new ErrorForbidden('No key or user for activation');
            $user = LibUsers::getByID(intval($_GET['user']));
            if (empty($user)) throw new ErrorNotFound('User not found');
            $key = LibUsers::gkey($user, 'activation');
            if ($key != $_GET['key']) throw new ErrorForbidden('Invalid activation key');
            if ($this->_ukey("`activated` = now()", $user['id']))
                return $this->_logged($user, 'User activated successfully');
            return Response::error('Error user activation');
        }

        /**
         * Remain password
         * @action /users/remainpass
         * @return array
         * @throws \xbweb\Error
         */
        public function do_remainpass() {
            if (Request::isPost()) {
                if (empty($_POST['email'])) return Response::error('Email not sent');
                try {
                    $user = LibUsers::getByLogin($_POST['email']);
                    if (empty($user)) throw new ErrorNotFound('User not found');
                } catch (ErrorNotFound $e) {
                    return Response::error('User not found');
                }
                $key = LibUsers::gkey($user, 'password');
                $url = Request::canonical('/changepass?user=' . $user['id'] . '&key=' . $key);
                if (!Mailer::create()
                    ->from(Config::get('mailer/from', Request::mailbox('no-reply')))
                    ->to($user['email'])
                    ->send('/remainpass', 'Change password', array(
                        'user' => $user,
                        'url'  => $url
                    ))) return Response::error('Cannot sent registration mail');
                return Response::message('Check your email for reset password link');
            } else {
                return Response::form();
            }
        }

        /**
         * Change password
         * @action /users/changepass
         * @return array
         * @throws ErrorForbidden
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        public function do_changepass() {
            if (empty($_REQUEST['user']) || empty($_REQUEST['key'])) throw new ErrorForbidden('No key or user for change password');
            $user = intval($_REQUEST['user']);
            $user = LibUsers::getByID($user);
            if (empty($user)) throw new ErrorNotFound('User not found');
            $key = LibUsers::gkey($user, 'password');
            if ($key != $_REQUEST['key']) throw new ErrorForbidden('Invalid key');
            if (Request::isPost()) {
                if (empty($_REQUEST['password'])) return Response::error('Password not set');
                $p = \xbweb\password($_REQUEST['password']);
                if ($this->_ukey("`password` = '{$p}'", $user['id']))
                    return $this->_logged($user, 'Password changed successfully');
                return Response::error('Password change error');
            } else {
                return Response::form(null, array(
                    'user' => intval($_GET['user']),
                    'key'  => $_GET['key'],
                ));
            }
        }

        /**
         * Profile
         * @action /users/profile
         * @return array
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        public function do_profile() {
            User::checkAuthorized();
            $model  = Model::create($this->_modelPath);
            $values = $model->getOne(User::current()->id);
            if ($values === false) $this->_notfound();
            if (!empty($values['deleted'])) {
                if (in_array('deleted410', $model->options)) {
                    throw new ErrorDeleted(ucfirst($this->_entity).' deleted', Request::get('id'));
                } else {
                    $this->_notfound();
                }
            }
            $values = PipeLine::invoke($this->pipeName('data'), $values, 'get');
            $errors = null;
            if (Request::isPost()) {
                list($values, $errors) = $model->request('update', 'register', true);
                $values['id'] = User::current()->id;
                if (empty($errors)) {
                    if ($id = $model->save($values)) {} else {
                        $errors = 'Unable to save user profile';
                    }
                }
            }
            return Response::form($model->form('update', $values), $values, $errors);
        }

        /**
         * Request with update user key
         * @param $cq
         * @param $id
         * @return bool
         */
        protected function _ukey($cq, $id) {
            $k = \xbweb::key();
            $q = <<<sql
update `[+prefix+]users` set `key` = '{$k}', {$cq} where `id` = '{$id}'
sql;
            if ($result = DB::query($q, true)) return $result->success;
            return false;
        }

        /**
         * Autologin and redirect
         * @param array  $user  User
         * @param string $msg   Message
         * @return array
         * @throws \xbweb\Error
         */
        protected function _logged($user, $msg = null) {
            $url = '/';
            if (Config::get('users/autologin', false)) {
                User::current($user);
                $url = Config::get('users/autologin_url', '/');
            }
            if ($msg) return Response::message($msg, $url);
            \xbweb::redirect($url);
            return array(); // Fix for IDE
        }
    }