<?php
class ControllerModuleQf extends Controller {
  private $error = array();
  private $html;
	public function index() {
    if(isset($this->request->get['fn']) && !empty($this->request->get['fn'])){
      $this->language->load('module/qf');
  		$this->load->model('module/qf');
      $this->data['form'] = $this->model_module_qf->getFormByName($this->request->get['fn']);
      $url = '';
      if(isset($this->request->get['pid']) && !empty($this->request->get['pid'])){
        $this->load->model('catalog/product');
        $url .= '&pid=' . $this->request->get['pid'];
        $this->data['product'] = $this->model_catalog_product->getProduct($this->request->get['pid']);
      }else{
        $this->data['product'] = false;
      }
      if($this->request->server['REQUEST_METHOD'] == 'POST'){
        if($this->validate($this->data['form'])){
          $data = $this->request->post;
          if(isset($this->request->get['pid']) && !empty($this->request->get['pid'])){
            $data['pid'] = $this->request->get['pid'];
          }

          $data['form_id'] = $this->data['form']['id'];
          //add to DB
          $this->model_module_qf->addContent($data);


          //send mail
          $mail = new Mail();
    			$mail->protocol = $this->config->get('config_mail_protocol');
    			$mail->parameter = $this->config->get('config_mail_parameter');
    			$mail->hostname = $this->config->get('config_smtp_host');
    			$mail->username = $this->config->get('config_smtp_username');
    			$mail->password = $this->config->get('config_smtp_password');
    			$mail->port = $this->config->get('config_smtp_port');
    			$mail->timeout = $this->config->get('config_smtp_timeout');
          if(!empty($this->data['form']['email'])){
            $mail->setTo($this->data['form']['email']);
          }else{
            $mail->setTo($this->config->get('config_email'));
          }
  				$mail->setFrom($this->config->get('config_email'));
  	  		$mail->setSender($this->language->get('title'));
  	  		$mail->setSubject($this->data['form']['name'], ENT_QUOTES, 'UTF-8');
          $mail->setHtml($this->html);
  	  		// $mail->setText(strip_tags(html_entity_decode($this->request->post['enquiry'], ENT_QUOTES, 'UTF-8')));
      		$mail->send();

          $this->data['success'] = $this->data['form']['success'];

        }else{
          foreach($this->data['form']['labels'] as $key => $label){
            if(isset($this->request->post[$key])){
              $this->data['form']['labels'][$key]['value'] = trim($this->request->post[$key]);
            }else{
              $this->data['form']['labels'][$key]['value'] = '';
            }

            if(isset($this->error[$key])){
              $this->data['error_' . $key] = $this->error[$key];
            }
          }
          if(isset($this->error['error_not_label'])){
            $this->data['error_not_label'] = $this->error['not_label'];
          }
        }
      }

      $this->data['button_submit'] = $this->language->get('button_submit');

      if(isset($this->request->get['popup']) && (int)$this->request->get['popup']==1){
        $this->data['popup'] = true;
        $url .='&popup=1';
      }else{
        $this->data['popup'] = false;
      }

      $this->data['action'] = $this->url->link('module/qf', 'fn=' . $this->request->get['fn'] . $url, 'SSL');

  		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/qf.tpl')) {
  			$this->template = $this->config->get('config_template') . '/template/module/qf.tpl';
  		} else {
  			$this->template = 'default/template/module/qf.tpl';
  		}

  		$this->response->setOutput($this->render());
    }else{
      $this->response->setOutput('');
    }
	}

  private function validate($form) {
    if(!empty($this->request->post)){
      $this->html = '
      <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd">
        <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo $title; ?></title>
        </head>
        <body style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000000;">
        <table style="border-collapse: collapse; width: 100%; border-top: 1px solid #DDDDDD; border-left: 1px solid #DDDDDD; margin-bottom: 20px;">
          <tbody>
      ';
      if(isset($this->request->get['pid'])){
        $p_link = $this->url->link('catalog/product', 'product_id=' . (int)$this->request->get['pid'], 'SSL');
        $this->html .= '<tr>
      <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;">' . $this->language->get('text_id') . ':</td><td style="font-size: 12px;  border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;"><a href="' . $p_link . '">' . (int)$this->request->get['pid'] . '</a></td></tr>';
      }
      foreach($form['labels'] as $key => $label){
        switch((int)$label['type']){
          //input, textarea
          case 1:
          case 3:
            if(isset($this->request->post[$key])){
              if($label['min'] != '-1' && mb_strlen(trim($this->request->post[$key]), 'UTF-8') < (int)$label['min'] ||
                 $label['max'] != '-1' && mb_strlen(trim($this->request->post[$key]), 'UTF-8') > (int)$label['max']){
                $this->error[$key] = $label['text_error'];
              }
            }else{
              if($label['min'] != '-1' || $label['max'] != '-1' || !empty($label['pattern'])){
                $this->error[$key] = $label['text_error'];
              }
            }
          break;
          //checkbox
          case 2:
            if(isset($this->request->post[$key])){
              if($label['min'] != '-1' && $this->request->post[$key] != 1){
                $this->error[$key] = $label['text_error'];
              }
            }else{
              if($label['min'] != '-1'){
                $this->error[$key] = $label['text_error'];
              }else{
                $this->request->post[$key] = 0;
              }
            }
          break;
        }
        $this->html .= '<tr><td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;">' . $label['text_admin'] . ':</td><td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;">' . $this->request->post[$key] . '</td></tr>';
      }
      $this->html .= '</tbody></table></body></html>';

      if(empty($this->error)){
        return true;
      }else{
        return false;
      }
    }else{
      $this->error['not_label'] = $this->language->get('error_not_label');
      return false;
    }
  }
}
?>
