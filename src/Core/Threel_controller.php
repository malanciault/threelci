<?php

namespace Malanciault\Threelci\Core;

class Threel_controller extends \CI_Controller {

	public $post_methods = array();
	public $ajax_methods = array();
	public $profiler_disabled = false;

	/**
	 * @var array ressources to be loaded on demand. Accepts:
	 *		['header'][]
	 *	 			['js']
	 * 				['css']
	 *		['footer'][]
	 * 				['js']
	 * 				['css']
	 */
	private $on_demand_ressources = array();

	public function __construct() {
        parent::__construct();
		if ($this->uri->segment(1) == 'admin') {
			if (!$this->session->has_userdata('is_admin_login'))
				redirect('auth/login');
		}
        if ((in_array($this->router->method, $this->post_methods) || in_array($this->router->method, $this->ajax_methods)) && $this->input->method() != 'post') {
        	show_404();
        }
        if (in_array($this->router->method, $this->ajax_methods)) {
        	$this->profiler_disabled = true;
        	$this->output->enable_profiler(FALSE);
        }
    }

	public function load_full_template($template, $data=false, $admin=false) {
		$this->decorate_with_common_data($data);
		$extra = $admin ? 'admin/' : '';
		$this->load->view('templates/header', $data);
		$this->load->view($extra . $template, $data);
		$this->load->view('templates/footer', $data);
	}

	public function load_admin_full_template($template, $data=false) {
		$this->load_full_template($template, $data, true);
	}

	private function decorate_with_common_data(&$data = false) {
		if (!$data) {
			$data = array();
		}
		if (!isset($data['fluid']))
			$data['fluid'] = '';
		$data['body_class'] = "user";
		$data['upload_url'] = $this->config->item('upload_url');

		$data['on_demand_ressources'] = $this->on_demand_ressources;

        $data['site_name'] = $this->config->item('app_title_' . $this->session->site_lang);
		if (!isset($data['page_title']) || !$data['page_title']) {
            $data['page_title'] = $this->config->item('app_title_' . $this->session->site_lang);
        } else {
            if (!isset($data['page_title_no_app_name'])) {
                $data['page_title'] .= ' - ' . $this->config->item('app_title_' . $this->session->site_lang);
            }
        }


		if (!isset($data['page_description']) || !$data['page_description'])
            $data['page_description'] = __("Planetair est une initiative du Centre international UNISFÉRA, une organisation sans but lucratif fondée au Canada en 2002.");

        if (!isset($data['page_image']) || !$data['page_image']) {
            $data['page_image'] = site_url("assets/img/planetair-compenser.png");
        }
	}

	public function get_upload_folder() {
		return hash('sha256', $this->session->user_id . BETTERSELF_HASH);
	}

	public function get_full_upload_folder() {
		return base_url() . 'files/' . $this->get_upload_folder() . '/';
	}

	public function check_access($data) {
		if (!$data) {
			$this->session->set_flashdata('error_msg', __("Désolé, la page que vous tentiez d'accéder est malheureusement introuvable."));
			redirect($this->agent->referrer() ? $this->agent->referrer() : base_url());
		}
	}

	public function get_component_url($item_id, $item_type) {
		$this->db->select('program_slug, module_slug, capsule_slug');
		$this->db->from('capsule');
		$this->db->join('module', 'ON capsule_module_id = module_id');
		$this->db->join('program', 'ON module_program_id = program_id');
		$this->db->group_by('program_id');
		$this->db->where($item_type . '_id', $item_id);
		$query = $this->db->get();
		$row_array = $query->row_array();
        $ret = site_url('programme/') . $row_array['program_slug'] . '/';

        switch ($item_type) {
        	case 'capsule':
        			$ret .= $row_array['module_slug'] . '/' . $row_array['capsule_slug'] . '/';
        		break;
        	case 'module':
					$ret .= $row_array['module_slug'] . '/';
        		break;
        }
        return $ret;
	}

	public function get_cols_to_use($count) {
		switch($count) {
			case 1: return "col-12 col-md-4";
			break;
			case 2: return "col-12 col-md-4";
			break;
			case 3: return "col-12 col-md-4";
			break;
			case 4: return "col-12 col-md-4";
			break;
			case 5: return "col-12 col-md-4";
			break;
			case 6: return "col-12 col-md-4";
			break;
			default: return "col-12 col-sm-4";
			break;
		}
	}

	public function build_breadcrumb(&$data, $type) {
		switch($type) {
			case 'capsule' :
				$data['breadcrumb'][] = array(
					'url' => '/',
					'caption' => 'Programmes',
				);
				$data['breadcrumb'][] = array(
					'url' => '/p/' . $data['product']['product_i18n_slug'] . '/',
					'caption' => $data['product']['product_i18n_name'],
				);
				$data['breadcrumb'][] = array(
					'caption' => $data['capsule']['capsule_i18n_name'],
					'active' => true,
				);
				break;
			case 'product' :
				$data['breadcrumb'][] = array(
					'url' => '/',
					'caption' => 'Programmes',
				);
				$data['breadcrumb'][] = array(
					'caption' => $data['product']['product_i18n_name'],
					'active' => true,
				);
				break;
		}
	}
	/**
	 * Add ressource to be loaded
	 * @param str $ressource URL of ressource to be loaded
	 * @param str $where to load it, either header or footer
	 */
	public function add_on_demand_ressource($ressource, $where=false) {
		$array = explode('.', $ressource);
		if (is_array($array)) {
			$ext = $array[count($array) - 1];
			if ($ext == 'css') {
				$this->on_demand_ressources['header']['css'][] = $ressource;
			} elseif($ext = 'js') {
				$this->on_demand_ressources[$where]['js'][] = $ressource;
			}
		}
	}

	public function get_admin_view_link(&$record, $params) {
        return '<a href="' . site_url('admin/' . $params['class'] . '/view/' . $record[$params['class'] . '_id']) . '">' . $record[$params['field']] . '</a>';
    }
}