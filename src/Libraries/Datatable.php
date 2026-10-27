<?php

namespace Malanciault\Threelci\Libraries;

class Datatable
{
    function __construct()
    {
        $this->obj =& get_instance();
    }

    //--------------------------------------------
    function LoadJson($SQL, $EXTRA_WHERE = '', $GROUP_BY = '')
    {
        $db = $this->obj->db;

        if (!empty($EXTRA_WHERE)) {
            $SQL .= " WHERE ( $EXTRA_WHERE )";
        } else {
            $SQL .= " WHERE (1)";
        }
        $query = $db->query($SQL);
        $total = $query->num_rows();
        //------------------------------------------------
        $columns = (isset($_GET['columns']) && is_array($_GET['columns'])) ? $_GET['columns'] : array();

        $search = isset($_GET['search']['value']) ? (string) $_GET['search']['value'] : '';
        if ($search !== '') {
            $like = $db->escape_like_str($search);
            $qry = array();
            foreach ($columns as $cl) {
                if (isset($cl['searchable']) && $cl['searchable'] == 'true' && $this->is_valid_column($cl))
                    $qry[] = " " . $cl['name'] . " LIKE '%" . $like . "%' ESCAPE '!' ";
            }
            if (!empty($qry)) {
                $SQL .= "AND ( ";
                $SQL .= implode("OR", $qry);
                $SQL .= " ) ";
            }
        }
        //------------------------------------------------
        if (!empty($GROUP_BY)) {
            $SQL .= $GROUP_BY;
        }
        //------------------------------------------------
        $query = $db->query($SQL);
        $filtered = $query->num_rows();

        $order_index = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : -1;
        if (isset($columns[$order_index]) && $this->is_valid_column($columns[$order_index])) {
            $dir = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
            $SQL .= " ORDER BY " . $columns[$order_index]['name'] . " " . $dir;
        }

        $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
        $start = isset($_GET['start']) ? max(0, (int) $_GET['start']) : 0;
        if ($length > 0) {
            $SQL .= " LIMIT " . $length . " OFFSET " . $start . " ";
        }

        $query = $db->query($SQL);
        $data = $query->result_array();

        return array("recordsTotal" => $total, "recordsFiltered" => $filtered, 'data' => $data);
    }

    // Column names come from the client: allow plain or table-qualified identifiers only
    private function is_valid_column($cl)
    {
        return isset($cl['name']) && is_string($cl['name'])
            && preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $cl['name']);
    }

    public function transformThreelDataset($class, $records, $fields, $actions = array('edit'), $ml = false, $view_link = true)
    {
        $data = array();
        foreach ($records['data'] as $record) {
            $id = $record[$class . '_id'];
            $row = array();
            foreach ($fields as $field) {
                if (is_array($field)) {
                    $method = $field['method'];
                    $row[] = $field['object']->$method($record, array('field' => $class . '_id', 'class' => $class));
                } else {
                    if ($view_link && $field == $class . '_id') {
                        $row[] = '<a href="' . site_url('admin/' . $class . '/view/' . $id) . '">' . $record[$field] . '</a>';
                    } else {
                        $row[] = $record[$field];
                    }
                }
            }
            if ($ml) {
                $row[] = '<a href="' . site_url('admin/' . $class . '/edit_ml/' . $id) . '/' . $this->obj->i18n->get_inversed_language() . '">' . $this->obj->i18n->get_inversed_short() . '</a>';
            }
            $row[] = $this->get_actions($class, $id, $actions);
            $data[] = $row;
        }
        return $data;
    }

    public function get_actions($class, $id, $actions, $custom_actions = false)
    {
        $ret = '';
        if (in_array('view', $actions))
            $ret .= '<a href="' . site_url('admin/' . $class . '/view/' . $id) . '"><i class="fal fa-browser fa-lg"></i></a> ';

        if (in_array('edit', $actions))
            $ret .= '<a href="' . site_url('admin/' . $class . '/edit/' . $id) . '"><i class="fal fa-edit fa-lg"></i></a> ';

        if (in_array('copy', $actions))
            $ret .= '<a href="#" data-item-id="' . $id . '"  data-toggle="modal" data-target="#confirm-copy-' . $class . '"><i class="fal fa-copy fa-lg"></i></a> ';

        if (in_array('delete', $actions))
            $ret .= '<a href="#" data-href="' . site_url('admin/' . $class . '/delete/' . $id) . '"  data-toggle="modal" data-target="#confirm-delete-' . $class . '"><i class="fal fa-trash fa-lg"></i></a> ';
        if ($custom_actions) {
            foreach ($custom_actions as $action) {
                $ret .= '<a href="' . site_url('admin/' . $class . '/' . $action['action'] . '/' . $id) . '"><i class="fal fa-' . $action['icon'] . ' fa-lg"></i></a> ';
            }
        }
        return $ret;
    }
}