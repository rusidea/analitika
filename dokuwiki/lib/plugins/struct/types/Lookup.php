<?php
namespace dokuwiki\plugin\struct\types;

use dokuwiki\plugin\struct\meta\Column;
use dokuwiki\plugin\struct\meta\QueryBuilder;
use dokuwiki\plugin\struct\meta\Schema;
use dokuwiki\plugin\struct\meta\Search;
use dokuwiki\plugin\struct\meta\Value;

class Lookup extends Dropdown {

    protected $config = array(
        'schema' => '',
        'field' => ''
    );

    /** @var  Column caches the referenced column */
    protected $column = null;

    /**
     * Dropdown constructor.
     *
     * @param array|null $config
     * @param string $label
     * @param bool $ismulti
     * @param int $tid
     */
    public function __construct($config = null, $label = '', $ismulti = false, $tid = 0) {
        parent::__construct($config, $label, $ismulti, $tid);
        $this->config['schema'] = Schema::cleanTableName($this->config['schema']);
    }

    /**
     * Get the configured loojup column
     *
     * @return Column|false
     */
    protected function getLookupColumn() {
        if($this->column !== null) return $this->column;
        $this->column = $this->getColumn($this->config['schema'], $this->config['field']);
        return $this->column;
    }

    /**
     * Gets the given column, applies language place holder
     *
     * @param string $table
     * @param string $infield
     * @return Column|false
     */
    protected function getColumn($table, $infield) {
        global $conf;

        $table = new Schema($table);
        if(!$table->getId()) {
            // schema does not exist
            msg(sprintf('Schema %s does not exist', $table), -1);
            return false;
        }

        // apply language replacement
        $field = str_replace('$LANG', $conf['lang'], $infield);
        $column = $table->findColumn($field);
        if(!$column) {
            $field = str_replace('$LANG', 'en', $infield); // fallback to en
            $column = $table->findColumn($field);
        }
        if(!$column) {
            // field does not exist
            msg(sprintf('Field %s.%s does not exist', $table, $infield), -1);
            return false;
        }

        if($column->isMulti()) {
            // field is multi
            msg(sprintf('Field %s.%s is a multi field - not allowed for lookup', $table, $field), -1);
            return false;
        }

        return $column;
    }

    /**
     * Creates the options array
     *
     * @return array
     */
    protected function getOptions() {
        $schema = $this->config['schema'];
        $column = $this->getLookupColumn();
        if(!$column) return array();
        $field = $column->getLabel();

        $search = new Search();
        $search->addSchema($schema);
        $search->addColumn($field);
        $search->addSort($field);
        $result = $search->execute();
        $pids = $search->getPids();
        $len = count($result);

        /** @var Value[][] $result */
        $options = array('' => '');
        for($i = 0; $i < $len; $i++) {
            $options[$pids[$i]] = $result[$i][0]->getDisplayValue();
        }
        return $options;
    }

    /**
     * Render using linked field
     *
     * @param int|string $value
     * @param \Doku_Renderer $R
     * @param string $mode
     * @return bool
     */
    public function renderValue($value, \Doku_Renderer $R, $mode) {
        list(, $value) = json_decode($value);
        $column = $this->getLookupColumn();
        if(!$column) return false;
        return $column->getType()->renderValue($value, $R, $mode);
    }

    /**
     * Render using linked field
     *
     * @param \int[]|\string[] $values
     * @param \Doku_Renderer $R
     * @param string $mode
     * @return bool
     */
    public function renderMultiValue($values, \Doku_Renderer $R, $mode) {
        $values = array_map(
            function ($val) {
                list(, $val) = json_decode($val);
                return $val;
            }, $values
        );
        $column = $this->getLookupColumn();
        if(!$column) return false;
        return $column->getType()->renderMultiValue($values, $R, $mode);
    }

    /**
     * @param string $value
     * @return string
     */
    public function rawValue($value) {
        list($value) = json_decode($value);
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    public function displayValue($value) {
        list(, $value) = json_decode($value);
        $column = $this->getLookupColumn();
        if($column) {
            return $column->getType()->displayValue($value);
        } else {
            return '';
        }
    }

    /**
     * Merge with lookup table
     *
     * @param QueryBuilder $QB
     * @param string $tablealias
     * @param string $colname
     * @param string $alias
     */
    public function select(QueryBuilder $QB, $tablealias, $colname, $alias) {
        $schema = 'data_' . $this->config['schema'];
        $column = $this->getLookupColumn();
        if(!$column) {
            parent::select($QB, $tablealias, $colname, $alias);
            return;
        }

        $field = $column->getColName();
        $rightalias = $QB->generateTableAlias();
        $QB->addLeftJoin(
            $tablealias, $schema, $rightalias,
            "$tablealias.$colname = $rightalias.pid AND $rightalias.latest = 1"
        );
        $column->getType()->select($QB, $rightalias, $field, $alias);
        $sql = $QB->getSelectStatement($alias);
        $QB->addSelectStatement("STRUCT_JSON($tablealias.$colname, $sql)", $alias);
    }

    /**
     * Compare against lookup table
     *
     * @param QueryBuilder $QB
     * @param string $tablealias
     * @param string $colname
     * @param string $comp
     * @param string|\string[] $value
     * @param string $op
     */
    public function filter(QueryBuilder $QB, $tablealias, $colname, $comp, $value, $op) {
        $schema = 'data_' . $this->config['schema'];
        $column = $this->getLookupColumn();
        if(!$column) {
            parent::filter($QB, $tablealias, $colname, $comp, $value, $op);
            return;
        }
        $field = $column->getColName();

        // compare against lookup field
        $rightalias = $QB->generateTableAlias();
        $QB->addLeftJoin(
            $tablealias, $schema, $rightalias,
            "$tablealias.$colname = $rightalias.pid AND $rightalias.latest = 1"
        );
        $column->getType()->filter($QB, $rightalias, $field, $comp, $value, $op);
    }

    /**
     * Sort by lookup table
     *
     * @param QueryBuilder $QB
     * @param string $tablealias
     * @param string $colname
     * @param string $order
     */
    public function sort(QueryBuilder $QB, $tablealias, $colname, $order) {
        $schema = 'data_' . $this->config['schema'];
        $column = $this->getLookupColumn();
        if(!$column) {
            parent::sort($QB, $tablealias, $colname, $order);
            return;
        }
        $field = $column->getColName();

        $rightalias = $QB->generateTableAlias();
        $QB->addLeftJoin(
            $tablealias, $schema, $rightalias,
            "$tablealias.$colname = $rightalias.pid AND $rightalias.latest = 1"
        );
        $column->getType()->sort($QB, $rightalias, $field, $order);
    }

}
