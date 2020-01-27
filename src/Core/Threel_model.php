<?php

namespace Malanciault\Threelci\Core;

class Threel_model extends \CI_model {

    public $table_name;
    public $primary_key;
    public $identifier;
    public $decorate_array = false;
    public $decorate_array_tmp = false;
    public $join_array = false;
    public $i18n = false;


    public function __construct() {
        if (ENVIRONMENT == 'production') {
            $this->load->database('production');
        } else {
            $this->load->database('local');
        }
        parent::__construct();
        $class = get_class($this);

        if ($class != "Threel_model") {
            $this->table_name = strtolower(str_replace('_model', '', $class));
            $this->primary_key = $this->table_name . '_id';

            if ($this->i18n) {
                $this->identifier = in_array($this->table_name . '_i18n_name', $this->db->list_fields($this->table_name . '_i18n')) ? $this->table_name . '_i18n_name' : $this->primary_key;
                $this->join_array[$this->table_name . '_i18n'] = array(
                    'join' => $this->table_name . '_i18n_' . $this->primary_key . ' = ' . $this->primary_key
                );
            } else {
                $this->identifier = in_array($this->table_name . '_name', $this->db->list_fields($this->table_name)) ? $this->table_name . '_name' : $this->primary_key;
            }
        }
    }

    /**
     * Get the query
     *
     * Executing the common stuff for get_all(), get() and row_array() methods and returning $query
     *
     * @param mixed $where
     * @param bool $debug
     * @return mixed array of results or FALSE if no result
     */
    private function get_query($where = false, $debug = false, $lang = 'default') {
        $this->common_query_stuff($where, $debug, true, $lang);
        return $query = $this->db->get();
    }

    private function common_query_stuff($where = false, $debug = false, $add_from = true, $lang = 'default') {
        if ($add_from) {
            $this->db->from($this->table_name);
        }

        if ($where) {
            $this->db->where($where);
        }

        if ($this->i18n) {
            $this->db->where($this->table_name . '_i18n_language_id', $lang == 'default' ? $this->session->site_lang : $lang);
        }

        if ($this->join_array) {
            foreach($this->join_array as $k => $v) {
                if (isset($v['replace_user_id'])) {
                    $v['join'] = sprintf($v['join'], $this->session->user_id);
                }
                $this->db->join($k, $v['join'], isset($v['type']) ? $v['type'] : false);
                if (isset($v['i18n'])) {
                    $this->db->join($k . '_i18n', $k . '_id = ' . $k . '_i18n_' . $k . '_id AND ' . $k . "_i18n_language_id = '" . $this->session->site_lang . "'", 'left');
                }
            }
        }

        if ($debug) {
            x($this->db->get_compiled_select(NULL, false), 'query');
        }
    }

	public function get_all_jason($where = false, $order_by = false, $index_by = false, $debug = false) {
        $index_by = $this->primary_key;
        $query = $this->get_query($where, $debug);
        $total = $query->num_rows();

        if(!empty($_GET['search']['value'])) {
            foreach($_GET['columns'] as $cl) {
                if ($cl['searchable'] == 'true')
                    $this->db->or_like($cl['name'], $_GET['search']['value']);
            }
        }

        $query = $this->get_query($where, $debug);
        $filtered = $query->num_rows();

        if (isset($_GET['order'])) {
            $this->db->order_by($_GET['columns'][$_GET['order'][0]['column']]['name'], $_GET['order'][0]['dir'] );
        }
        if (isset($_GET['length']))
            $this->db->limit($_GET['length']);
        if (isset($_GET['start']))
            $this->db->offset($_GET['start']);

        if(!empty($_GET['search']['value'])) {
            foreach($_GET['columns'] as $cl) {
                if ($cl['searchable'] == 'true')
                    $this->db->or_like($cl['name'], $_GET['search']['value']);
            }
        }
        $query = $this->get_query($where, $debug);  
        $ret = $query->result_array();
        $this->decorate_data($ret);

        if ($index_by) {
            foreach($ret as $k => $v) {
                $indexed_ret[$v[$index_by]] = $v;
            }
            $ret = isset($indexed_ret) ? $indexed_ret : $ret;
        }
        
        return array("recordsTotal" => $total,"recordsFiltered" => $filtered,'data' => $ret);
    }

    public function get_all($where = false, $order_by = false, $index_by = false, $debug = false, $json = false, $lang='default') {
        if ($order_by) {
            $this->db->order_by($order_by);
        } else {
            $this->db->order_by($this->identifier, 'ASC');
        }

        $query = $this->get_query($where, $debug, $lang);
        $ret = $query->result_array();
        $this->decorate_data($ret);

        if ($index_by) {
            foreach($ret as $k => $v) {
                $indexed_ret[$v[$index_by]] = $v;
            }
            $ret = isset($indexed_ret) ? $indexed_ret : $ret;
        }
        return count($ret) ? $ret : false;
    }

    public function get_all_for_lang($lang, $where = false, $order_by = false, $index_by = false, $debug = false) {
        return $this->get_all($where, $order_by, $index_by, $debug, $lang);
    }

    public function get_list_with_first_empty($where = false, $order_by = false, $index_by = false, $debug = false, $json = false) {
        return $this->get_list($where, $order_by, $index_by, $debug, $json, true);
    }

    public function get_list($where = false, $order_by = false, $index_by = false, $debug = false, $json = false, $first_empty = false) {
        $ret = false;
        $records = $this->get_all($where, $order_by, $index_by, $debug, $json);
        if ($first_empty) {
            $ret[0] = '---';
        }
        foreach($records as $record) {
            $ret[$record[$this->primary_key]] = $record[$this->identifier];
        }
        return $ret;
    }

    public function get_allD($where = false, $order_by = false, $index_by = false) {
        return $this->get_all($where, $order_by, $index_by, true);
    }

    public function get($id, $get_by_slug = false, $debug = false, $decorate = true, $lang = 'default') {
        if ($get_by_slug) {
            $key = $this->i18n ? $this->table_name . '_i18n_slug' : $this->table_name . '_slug';
        } else {
            $key = $this->primary_key;    
        }
        
        $query = $this->get_query(array($key => $id), $debug, $lang);
        $ret = $query->row_array();

        /** 
         * If we are looking for a translation and did not find it
         * let's fetch the record in the default language
         * and then update the language_id of the record for the $lang we needed
         */
        if (!$ret && $lang != 'default') {
            $query = $this->get_query(array($key => $id), $debug, 'default');
            $ret = $query->row_array();    
            if (isset($ret[$this->table_name . '_i18n_language_id'])) {
                $ret[$this->table_name . '_i18n_language_id'] = $lang;
            }
        }
        if ($ret && $decorate) {
            $this->decorate_data($ret);
        }
        return $ret;
    }

    public function get_for_edit($id, $lang = 'default', $get_by_slug = false, $debug = false) {
        return $this->get($id, $get_by_slug, $debug, false, $lang);
    }

    public function get_by_slug($id, $debug = false) {
        return $this->get($id, true, $debug);
    }

    public function get_by_slugD($id) {
        return $this->get($id, true, true);
    }

    public function getD($id) {
        return $this->get($id, false, true);
    }

    public function row_array($where = false, $lang='default', $debug = false) {
        $query = $this->get_query($where, $debug, $lang);
        $ret = $query->row_array();
        if ($ret) {
            $this->decorate_data($ret);
        }
        return $ret;
    }

    public function row_arrayD($where = false, $lang='default', $debug = false) {
        return $this->row_array($where, true);
    }

    /**
     * Count result in query
     * @param array $where clauses
     * @param bool $reset reset query after
     * @return int count results
     */
    public function count($where, $reset = false) {
        $this->common_query_stuff($where, false, false);
        return $this->db->count_all_results($this->table_name, $reset);
    }

    /**
     * Insert new record in table
     *
     * @param array $data to be inserted
     * @param bool $return_array wether to return an array of the inserted record or not
     * @return mixed array of inserted record if $return_array is TRUE, primary key of inserted record if not
     */
	public function insert($data, $return_array = false){
        if ($this->i18n) {
            $data_main = false;
            $data_i18n = false;

            foreach ($data as $key => $value) {
                if (strpos($key, 'i18n') === false) {
                    $data_main[$key] = $value;
                } else {
                    $data_i18n[$key] = $value;
                }
            }
            $this->db->insert($this->table_name, $data_main);
            $insert_id = $this->db->insert_id();
            $data_i18n[$this->table_name . '_i18n_' . $this->primary_key] = $insert_id;
            $this->db->reset_query();
            
            $this->db->insert($this->table_name . '_i18n', $data_i18n);
        } else {
            $this->db->insert($this->table_name, $data);
            $insert_id = $this->db->insert_id();
        }
        if ($return_array) {
            return $this->get($insert_id);
        } else {
            return $insert_id;
        }
    }

    public function replace($data) {
        return $this->db->replace($this->table_name, $data);
    }

    public function insert_batch($data, $escape = NULL, $batch_size = 100) {
        return $this->db->insert_batch($this->table_name, $data, $escape, $batch_size);
    }

    public function update ($id, $data, $return_array = false) {
        if ($this->i18n) {
            $data_main = false;
            $data_i18n = false;
            foreach ($data as $key => $value) {
                if (strpos($key, 'i18n') === false) {
                    $data_main[$key] = $value;
                } else {
                    $data_i18n[$key] = $value;
                }
            }
            if ($data_main) {
                $this->db->where($this->primary_key, $id);

                $this->db->update($this->table_name, $data_main);
                $this->db->reset_query();
            }
            if ($data_i18n) {
                $this->db->where($this->table_name . '_i18n_' . $this->primary_key, $id);
                $this->db->where($this->table_name . '_i18n_language_id', $data_i18n[$this->table_name . '_i18n_language_id']);
                $this->db->update($this->table_name . '_i18n', $data_i18n);    
            }
        } else {
            $this->db->where($this->primary_key, $id);
            $this->db->update($this->table_name, $data);    
        }
        
        if ($return_array) {
            return $this->get($id);
        }
    }

    public function update_lang($id, $lang, $data) {
        $data[$this->table_name . '_i18n_' . $this->primary_key] = $id;
        $data[$this->table_name . '_i18n_language_id'] = $lang;
        return $this->db->replace($this->table_name . '_i18n', $data);
    }

    public function delete($id) {
        if ($this->i18n) {
            $this->db->where($this->table_name . '_i18n_' . $this->primary_key, $id);
            $this->db->delete($this->table_name . '_i18n');
            $this->db->reset_query();
        }
        $this->db->where($this->primary_key, $id);
        return $this->db->delete($this->table_name);
    }

    public function delete_multiple($where) {
        if ($this->i18n) {
            $this->db->where($where);
            $records = $this->get_list();
            xd('deleting multiple items with i18n support');

            $this->db->where($this->table_name . '_i18n_' . $this->primary_key, $id);
            $this->db->delete($this->table_name . '_i18n');
            $this->db->reset_query();
        }
        $this->db->delete($this->table_name, $where);
    }

    public function decorate_data(&$data) {
        if (count($data)) {
            if ($this->decorate_array) {
                if (array_depth($data) == 2) {
                    foreach($data as $k => &$v) {
                        $this->decorate_data($v);
                    }
                } else {
                    foreach ($this->decorate_array as $v) {
                        $this->$v($data);
                    }
                }
            }
        }
    }

    public function disable_decorate() {
        $this->decorate_array_tmp = $this->decorate_array;
        $this->decorate_array = false;
    }

    public function restore_decorate() {
        $this->decorate_array = $this->decorate_array_tmp;
    }

    public function get_for_export($columns) {
        $this->db->select($columns);
        return $this->get_all();
    }
}