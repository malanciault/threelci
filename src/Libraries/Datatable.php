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
        if (!empty($EXTRA_WHERE)) {
            $SQL .= " WHERE ( $EXTRA_WHERE )";
        } else {
            $SQL .= " WHERE (1)";
        }
        $query = $this->obj->db->query($SQL);
        $total = $query->num_rows();
        //------------------------------------------------
        if (!empty($_GET['search']['value'])) {
            $qry = array();
            foreach ($_GET['columns'] as $cl) {
                if ($cl['searchable'] == 'true')
                    $qry[] = " " . $cl['name'] . " like '%" . $_GET['search']['value'] . "%' ";
            }
            $SQL .= "AND ( ";
            $SQL .= implode("OR", $qry);
            $SQL .= " ) ";
        }
        //------------------------------------------------
        if (!empty($GROUP_BY)) {
            $SQL .= $GROUP_BY;
        }
        //------------------------------------------------
        $query = $this->obj->db->query($SQL);
        $filtered = $query->num_rows();

        $SQL .= " ORDER BY ";
        $SQL .= $_GET['columns'][$_GET['order'][0]['column']]['name'] . " ";
        $SQL .= $_GET['order'][0]['dir'];
        $SQL .= " LIMIT " . $_GET['length'] . " OFFSET " . $_GET['start'] . " ";

        $query = $this->obj->db->query($SQL);
        $data = $query->result_array();

        return array("recordsTotal" => $total, "recordsFiltered" => $filtered, 'data' => $data);
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