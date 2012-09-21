<?php
    /**
     * @class  kinAdminController
     * @author NHN (developer@nhn.com)
     * @brief  kin admin controller class
     **/

    class kinAdminController extends kin {

        function init() {
        }

        function procKinAdminInsert() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $args = Context::getRequestVars();
            $args->module = 'kin';
            $args->mid = $args->kin_name;
            unset($args->body);
            unset($args->kin_name);

            if($args->use_category!='Y') $args->use_category = 'N';
            if($args->limit_write !='Y') $args->limit_write = 'N';
            if(!$args->limit_give_point) $args->limit_give_point = 100;

            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
	
	        if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispKinAdminInsert');
				header('location:'.$returnUrl);
				return;
			}
        }

        function procKinAdminDelete() {
            $oModuleController = &getController('module');

            $module_srl = Context::get('module_srl');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $args->module_srl = $module_srl;
            executeQuery('kin.deleteReplies', $args);

            $this->setMessage('success_deleted');

	        if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'module_srl', $output->get('module_srl'), 'act', 'dispKinAdminList');
				header('location:'.$returnUrl);
				return;
			}
        }

    }
?>
