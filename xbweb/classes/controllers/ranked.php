<?php
    /**
     * Xander Bass Website Content Management Framework (XBWeb CMF)
     *
     * @author       Xander Bass
     * @copyright    Xander Bass
     * @license      https://opensource.org/licenses/mit-license.php MIT License
     * @link         https://xbweb.ru
     *
     * @description  Ranked entity controller prototype
     * @category     Controllers prototypes
     * @link         https://xbweb.ru/doc/dist/classes/controllers/ranked
     * @core         Lyta
     * @subcore      5.3
     */

    namespace xbweb\controllers;

    use xbweb\DB;
    use xbweb\ErrorNotFound;
    use xbweb\Language;
    use xbweb\Model;
    use xbweb\Request;
    use xbweb\Response;

    class Ranked extends Entity
    {
        /**
         * Constructor
         * @param string $path Controller path
         * @param string $model Model path
         * @param string $entity Entity
         * @throws \xbweb\Error
         */
        protected function __construct($path, $model, $entity = null)
        {
            parent::__construct($path, $model, $entity);
            $this->_queries['reset_rank'] = array(
                'title'   => Language::translate($this->_entity.'_reset_rank', 'Reset rank'),
                'success' => Language::translate($this->_entity.'_reset_rank_ok/description', 'Rank reset successfully'),
                'error'   => Language::translate($this->_entity.'_reset_rank_error/description', 'Rank reset error'),
                'confirm' => Language::translate($this->_entity.'_reset_rank/description', 'Confirm reset rank?')
            );
            $this->_queries['move_up'] = array(
                'title'   => Language::translate($this->_entity.'_move_up', 'Move '.$this->_entity),
                'success' => Language::translate($this->_entity.'_move_up_ok/description', ucfirst($this->_entity).' moved up successfully'),
                'error'   => Language::translate($this->_entity.'_move_up_error/description', 'Error moving '.$this->_entity),
                'confirm' => Language::translate($this->_entity.'_move_up/description', 'Move '.$this->_entity.' up')
            );
            $this->_queries['move_down'] = array(
                'title'   => Language::translate($this->_entity.'_move_down', 'Move '.$this->_entity),
                'success' => Language::translate($this->_entity.'_move_down_ok/description', ucfirst($this->_entity).' moved down successfully'),
                'error'   => Language::translate($this->_entity.'_move_down_error/description', 'Error moving '.$this->_entity),
                'confirm' => Language::translate($this->_entity.'_move_down/description', 'Move '.$this->_entity.' down')
            );
        }

        /**
         * Query reset rank
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public function query_reset_rank()
        {
            $sql = <<<sql
update `[+table+]` set `rank` = @r := (@r + 1) order by `rank` asc
sql;
            $sql = $this->_q($sql);
            if ($r1 = DB::query('set @r = 0')) if ($r2 = DB::query($sql)) $this->r();
            return Response::dialog('error', $this->_queries['reset_rank']);
        }

        /**
         * Query move up
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public function query_move_up()
        {
            return $this->_move_('up');
        }

        /**
         * Query move down
         * @return array
         * @throws \xbweb\Error
         * @throws \xbweb\ErrorNotFound
         */
        public function query_move_down()
        {
            return $this->_move_('down');
        }

        /**
         * Query move
         * @param string $dir  Direction
         * @return array
         * @throws ErrorNotFound
         * @throws \xbweb\Error
         */
        protected function _move_($dir)
        {
            $model = Model::create($this->_modelPath);
            $pk    = $model->primary;
            $id    = Request::get('id');
            $row   = $model->getOne($id, false);
            if (empty($row)) throw new ErrorNotFound('Item not found', $id);
            $rank_old = intval($row['rank']);
            $op  = $dir == 'down' ? '>'             : '<';
            $nv  = $dir == 'down' ? ($rank_old + 1) : 0;
            $dr  = $dir == 'down' ? 'asc'           : 'desc';
            $sql = <<<sql
select * from `[+table+]` where `rank` {$op} {$rank_old} order by `rank` {$dr} limit 1
sql;
            if ($row = DB::row($this->_q($sql))) {
                $rank_new = intval($row['rank']);
                $sql      = <<<sql
update `[+table+]` set `rank` = case `rank` 
when {$rank_new} then {$rank_old}
when {$rank_old} then {$rank_new}
else `rank` end where `rank` in ({$rank_old}, {$rank_new})
sql;
            } else {
                $sql = <<<sql
update `[+table+]` set `rank` = {$nv} where `{$pk}` = {$id}
sql;
            }
            if ($r2 = DB::query($this->_q($sql))) $this->r();
            return Response::dialog('error', $this->_queries['move_'.$dir]);
        }
    }