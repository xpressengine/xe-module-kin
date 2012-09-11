<?php
    /**
     * @class  kin
     * @author NHN (developers@xpressengine.com)
     * @brief  kin high class
     **/

    class kin extends ModuleObject {

        var $search_option = array('title','content','title_content','comment','user_name','nick_name','user_id','tag');
		var $list_count = 20;
		var $page_count = 10;

        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $oModuleController->insertTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after');
            $oModuleController->insertTrigger('member.getModuleListInSitemap', 'kin', 'model', 'triggerModuleListInSitemap', 'after');
            return new Object();
        }

        function checkUpdate() {
        	$oModuleModel = &getModel('module');

            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after')) {
            	return true;
            }
			// 2012. 09. 11 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'kin', 'model', 'triggerModuleListInSitemap', 'after')) return true;
            return false;
        }

        function moduleUpdate() {
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');
			if(!$oModuleModel->getTrigger('menu.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after'))
				$oModuleController->insertTrigger('member.getMemberMenu', 'kin', 'controller', 'triggerMemberMenu', 'after');
			// 2012. 09. 11 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'kin', 'model', 'triggerModuleListInSitemap', 'after'))
				$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'kin', 'model', 'triggerModuleListInSitemap', 'after');
            return new Object(0, 'success_updated');
        }

        function recompileCache() {
        }
    }
?>
