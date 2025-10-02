<?php

namespace YOOtheme\Framework\Wordpress;

use YOOtheme\Framework\Database\Database as BaseDatabase;

class Database extends BaseDatabase
{
    protected $db;

    /**
     * Constructor.
     *
     * @param \wpdb $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->prefix = $db->get_blog_prefix();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($statement, array $params = array())
    {
        return $this->db->get_results($this->prepareQuery($statement, $params), ARRAY_A);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssoc($statement, array $params = array())
    {
        return $this->db->get_row($this->prepareQuery($statement, $params), ARRAY_A);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchArray($statement, array $params = array())
    {
        return $this->db->get_row($this->prepareQuery($statement, $params), ARRAY_N);
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = array())
    {
        return $this->db->query($this->prepareQuery($query, $params));
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data)
    {
        return $this->db->insert($this->replacePrefix($table), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, array $identifier)
    {
        return $this->db->update($this->replacePrefix($table), $data, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, array $identifier)
    {
        return $this->db->delete($this->replacePrefix($table), $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function escape($text)
    {
        return $this->db->_escape($text);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId()
    {
        return $this->db->insert_id;
    }
}
