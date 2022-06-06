<?php
    /** @noinspection PhpUnhandledExceptionInspection */
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.org
     *
     * @description  Main framework loader
     * @category     Framework parts
     * @link         https://xbweb.org/doc/dist/loader
     * @core         Lyta
     * @subcore      5.3
     */

    /**** BACKWARD COMPATIBILITY ****/
    namespace {
        defined('JSON_PRETTY_PRINT')      or define('JSON_PRETTY_PRINT', 128);
        defined('JSON_UNESCAPED_SLASHES') or define('JSON_UNESCAPED_SLASHES', 64);

        if (!function_exists('http_response_code')) {
            function http_response_code($response_code = 0) {
                static $code = null;
                if (empty($code)) $code = 200;
                if (!empty($response_code)) $code = intval($response_code);
                return $code;
            }
        }
    }

    /**** FOLDERS NAME ****/
    namespace xbweb\Folders {
        /**** Core folder name ****/
        defined(__NAMESPACE__.'\\CORE') or define(__NAMESPACE__.'\\CORE', 'xbweb');

        /**** Modules folder name ****/
        defined(__NAMESPACE__.'\\MODULES') or define(__NAMESPACE__.'\\MODULES', 'modules');

        /**** Project content folder name ****/
        defined(__NAMESPACE__.'\\CONTENT') or define(__NAMESPACE__.'\\CONTENT', 'www');

        /**** Project admin panel content folder name ****/
        defined(__NAMESPACE__.'\\ADMIN') or define(__NAMESPACE__.'\\ADMIN', 'admin');
    }

    /**** PATHS ****/
    namespace xbweb\Paths {
        /**** Root path where core folder is located ****/
        if (!defined(__NAMESPACE__.'\\ROOT')) {
            $root = rtrim(strtr(realpath(rtrim(strtr(dirname(__FILE__), '\\', '/'), '/').'/..'), '\\', '/'), '/').'/';
            define(__NAMESPACE__.'\\ROOT', $root);
        }

        /**** Root path where public (content) files and folders are located ****/
        if (!defined(__NAMESPACE__.'\\WEBROOT')) {
            $root = rtrim(strtr($_SERVER['DOCUMENT_ROOT'], '\\', '/'), '/').'/';
            define(__NAMESPACE__.'\\WEBROOT', $root);
        }

        /**** Path where project content files and folders are located ****/
        define(__NAMESPACE__.'\\COREINWEB', ROOT == WEBROOT);

        /**** Path where runtime files and folders are located ****/
        defined(__NAMESPACE__.'\\RUNTIME') or define(__NAMESPACE__.'\\RUNTIME', WEBROOT.'var/');

        /**** Path where core files and folders are located ****/
        defined(__NAMESPACE__.'\\CORE') or define(__NAMESPACE__.'\\CORE', ROOT.(\xbweb\Folders\CORE).'/');

        /**** Path where core files and folders are located ****/
        defined(__NAMESPACE__.'\\CLASSES') or define(__NAMESPACE__.'\\CLASSES', ROOT.(\xbweb\Folders\CORE).'/classes/');

        /**** Path where core files and folders are located ****/
        defined(__NAMESPACE__.'\\LIB') or define(__NAMESPACE__.'\\LIB', CLASSES.'lib/');

        /**** Path where modules folders are located ****/
        defined(__NAMESPACE__.'\\MODULES') or define(__NAMESPACE__.'\\MODULES', ROOT.(\xbweb\Folders\MODULES).'/');

        /**** Path where project content files and folders are located ****/
        defined(__NAMESPACE__.'\\CONTENT') or define(__NAMESPACE__.'\\CONTENT', WEBROOT.(\xbweb\Folders\CONTENT).'/');

        /**** Path where project content files and folders are located ****/
        defined(__NAMESPACE__.'\\ADMIN') or define(__NAMESPACE__.'\\ADMIN', WEBROOT.(\xbweb\Folders\ADMIN).'/');
    }

    namespace xbweb\URLs {
        /**** Login URL ****/
        defined(__NAMESPACE__.'\\LOGIN') or define(__NAMESPACE__.'\\LOGIN', '/users/login');
    }

    namespace xbweb {
        defined(__NAMESPACE__.'\\CACHE_JSON_FLAGS') or define(__NAMESPACE__.'\\CACHE_JSON_FLAGS', (
            \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT
        ));

        require Paths\CLASSES.'debug.php';
    }

    /**** MAIN SECTION ****/
    namespace {
        require 'xbweb.php';
        require 'errors.php';
        // Load primary libraries
        require 'classes/lib/arrays.php';
        require 'classes/lib/flags.php';
        require 'classes/lib/access.php';
        require 'classes/lib/files.php';
        // Load primary classes
        require 'classes/basicobject.php';
        require 'classes/config.php';
        require 'classes/events.php';
        require 'classes/pipeline.php';
        require 'classes/request.php';
        require 'classes/db/provider.php';
        require 'classes/db/result.php';
        require 'classes/db.php';
        require 'classes/node.php';

        set_error_handler(function($en, $es, $ef, $el, $ec = null){
            throw new \xbweb\Error(array(
                'type'    => $en,
                'message' => $es,
                'file'    => $ef,
                'line'    => $el,
                'vars'    => $ec
            ));
        });

        spl_autoload_register(function($classname){
            $cn = explode('/', strtolower(strtr($classname, '\\', '/')));
            if (array_shift($cn) != 'xbweb') return true;
            if (empty($cn)) return true;
            switch ($cn[0]) {
                case 'modules':
                    array_shift($cn);
                    if (count($cn) < 2) {
                        http_response_code(500);
                        throw new Exception("Invalid module class '{$classname}'");
                    }
                    $mn = array_shift($cn);
                    $fn = xbweb\Paths\MODULES.$mn.'/classes/'.implode('/', $cn).'.php';
                    break;
                case 'www':
                case 'admin':
                    $mn = array_shift($cn);
                    if (empty($cn)) {
                        http_response_code(500);
                        throw new Exception("Invalid module class '{$classname}'");
                    }
                    $fn = xbweb\Paths\WEBROOT.$mn.'/classes/'.implode('/', $cn).'.php';
                    break;
                default:
                    $fn = xbweb\Paths\CORE.'classes/'.implode('/', $cn).'.php';
            }
            if (!file_exists($fn)) {
                http_response_code(500);
                throw new Exception("No class file for '{$classname}' in '{$fn}'");
            }
            require $fn;
            if (!class_exists($classname, false)) {
                http_response_code(500);
                throw new Exception("No class '{$classname}' in '{$fn}'");
            }
            return true;
        });
    }

    namespace xbweb {
        function password($v, $t = null) {
            if ($t === null) $t = Config::get('password_method', 'md5');
            switch ($t) {
                default: return md5($v);
            }
        }
    }

    namespace xbweb\Credits {
        define(__NAMESPACE__.'\\PRODUCT' , 'XBWeb CMF');
        define(__NAMESPACE__.'\\VERSION' , '0.1');
        define(__NAMESPACE__.'\\CORE'    , 'Lyta');
        define(__NAMESPACE__.'\\DBTYPE'  , 'MySQL');
    }