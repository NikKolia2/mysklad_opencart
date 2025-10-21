<?php

class ControllerExtensionModuleNikSkladOrder extends Controller {
    public function index(){
        $this->load->library('nikskladorder');
        $this->load->language('extension/module/nikskladorder');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
       
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting($this->nikskladorder::$configPrefix, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/html', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
		);
		
		
		$data['action'] = $this->url->link('extension/module/nikskladorder', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		
		$data['stores'] = $this->nikskladorder->getStores();
		$data['organizations'] = $this->nikskladorder->getOrganizations();
		$data['saleschannel'] = $this->nikskladorder->getSalesChannel();
		$data['module_currencies'] = $this->nikskladorder->getCurrencies();
		$data['order_statuses'] = $this->nikskladorder->getStatuses();

		
		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_status'])) {
			$data[$this->nikskladorder::$configPrefix.'_status'] = $this->request->post[$this->nikskladorder::$configPrefix.'_status'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_status'] = $this->config->get($this->nikskladorder::$configPrefix.'_status');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_sklad_login'])) {
			$data[$this->nikskladorder::$configPrefix.'_sklad_login'] = $this->request->post[$this->nikskladorder::$configPrefix.'_sklad_login'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_sklad_login'] = $this->config->get($this->nikskladorder::$configPrefix.'_sklad_login');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_sklad_password'])) {
			$data[$this->nikskladorder::$configPrefix.'_sklad_password'] = $this->request->post[$this->nikskladorder::$configPrefix.'_sklad_password'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_sklad_password'] = $this->config->get($this->nikskladorder::$configPrefix.'_sklad_password');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_organization'])) {
			$data[$this->nikskladorder::$configPrefix.'_organization'] = $this->request->post[$this->nikskladorder::$configPrefix.'_organization'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_organization'] = $this->config->get($this->nikskladorder::$configPrefix.'_organization');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_store'])) {
			$data[$this->nikskladorder::$configPrefix.'_store'] = $this->request->post[$this->nikskladorder::$configPrefix.'_store'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_store'] = $this->config->get($this->nikskladorder::$configPrefix.'_store');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_salechannel'])) {
			$data[$this->nikskladorder::$configPrefix.'_salechannel'] = $this->request->post[$this->nikskladorder::$configPrefix.'_salechannel'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_salechannel'] = $this->config->get($this->nikskladorder::$configPrefix.'_salechannel');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_order_prefix'])) {
			$data[$this->nikskladorder::$configPrefix.'_order_prefix'] = $this->request->post[$this->nikskladorder::$configPrefix.'_order_prefix'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_order_prefix'] = $this->config->get($this->nikskladorder::$configPrefix.'_order_prefix');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_order_status'])) {
			$data[$this->nikskladorder::$configPrefix.'__order_status'] = $this->request->post[$this->nikskladorder::$configPrefix.'_order_prefix'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_order_status'] = $this->config->get($this->nikskladorder::$configPrefix.'_order_status');
		}

		if (isset($this->request->post[$this->nikskladorder::$configPrefix.'_currency'])) {
			$data[$this->nikskladorder::$configPrefix.'_currency'] = $this->request->post[$this->nikskladorder::$configPrefix.'_currency'];
		} else {
			$data[$this->nikskladorder::$configPrefix.'_currency'] = $this->config->get($this->nikskladorder::$configPrefix.'_currency');
		}


        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/nikskladorder', $data));
    }

	public function install() {
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent('nikskladorder-send-order-mysklad', 'catalog/model/checkout/order/addOrder/after', 'event/nikskladorder/send');
	}
		
	public function uninstall() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEvent('nikskladorder-send-order-mysklad');
	}
}