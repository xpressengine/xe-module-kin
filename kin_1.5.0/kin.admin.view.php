<?php
    /**
     * @class  kinAdminView
     * @author zero (skklove@gmail.com)
     * @brief  kin admin view class
     **/

    class kinAdminView extends kin {

        function init() {
            $oModuleModel = &getModel('module');
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            $this->setTemplatePath(sprintf("%stpl/",$this->module_path));
            $this->setTemplateFile(strtolower(str_replace('dispKinAdmin','',$this->act)));

            $module_srl = Context::get('module_srl');
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'dispKinAdminList';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }
        }

        function dispKinAdminList() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 20;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQueryArray('kin.getKinList', $args);
            ModuleModel::syncModuleToSite($output->data);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('kin_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
        }

        function dispKinAdminInsert() {
            $oModuleModel = &getModel('module');
            $oLayoutModel = &getModel('layout');

			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
			Context::set('mlayout_list', $mobile_layout_list);

			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
			Context::set('mskin_list', $mskin_list);

            Context::set('skin_list', $oModuleModel->getSkins($this->module_path));
            Context::set('layout_list', $oLayoutModel->getLayoutList());
        }

        function dispKinAdminCategory() {
            $oDocumentModel = &getModel('document');
            Context::set('category_content', $oDocumentModel->getCategoryHTML($this->module_info->module_srl));
        }

	
		//show advance config page
        function dispKinAdminAdditions() {
            $content = '';

            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('addition_content', $content);
        }

        function dispKinAdminDelete() {
            $oDocumentModel = &getModel('document');

            if(!$this->module_info) return new Object(-1,'msg_invalid_request');

            Context::set('document_count', $oDocumentModel->getDocumentCount($this->module_info->module_srl));
        }
        
    }

