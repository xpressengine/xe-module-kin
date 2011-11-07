<?php
    /**
     * @class  kinController
     * @author zero (skklove@gmail.com)
     * @brief  kin controller class
     **/

    class kinController  extends kin {

        function init() {
        }
		
		//insert question
        function procKinInsert() {
            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');
            $oPointModel = &getModel('point');
            $oPointController = &getController('point');
            $oModuleModel = &getModel('module');

            if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');

            $obj->module_srl = $this->module_srl;
            $obj->category_srl = Context::get('category_srl');
            $obj->document_srl = Context::get('document_srl');
            $obj->title = Context::get('title');
            $obj->content = Context::get('content');
            $obj->notify_message = Context::get('notify_message');
            $obj->allow_comment = 'Y';
            $obj->tags = Context::get('tags');
            settype($obj->title, "string");

            if(!$obj->title) return new Object(-1, 'msg_title_is_null');
            if(!$obj->content) return new Object(-1, 'msg_content_is_null');

            $oDocument = $oDocumentModel->getDocument($obj->document_srl);

            $logged_info = Context::get('logged_info');
            $point_config = $oModuleModel->getModuleConfig('point');
            $module_point_config = $oModuleModel->getModulePartConfig('point', $this->module_srl);
            $user_point = $oPointModel->getPoint($logged_info->member_srl, true);
            $min_point = 0;
            $max_point = $this->module_info->limit_give_point;
            if(!$max_point) $max_point = 100;

            if(!$oDocument->isExists()) {
                $give_point = abs((int)Context::get('give_point'));
                if($give_point > $user_point) return new Object(-1,sprintf(Context::getLang('msg_point_is_over'), $user_point));
                if($this->module_info->limit_write == 'Y' && $give_point > $max_point) return new Object(-1,sprintf(Context::getLang('msg_point_limited'), $max_point));
                if($this->module_info->limit_write == 'Y' && $give_point < $min_point) return new Object(-1,sprintf(Context::getLang('msg_point_shortage'), $min_point));

                $output = $oDocumentController->insertDocument($obj);
                if(!$output->toBool()) return $output;

                $msg_code = 'success_registed';
                $obj->document_srl = $output->get('document_srl');

                $give_args->document_srl = $obj->document_srl;
                $give_args->point = $give_point;
                $output = executeQuery('kin.insertKinPoint', $give_args);
                if(!$output->toBool()) return $output;

                $oPointController->setPoint($logged_info->member_srl, $user_point-$give_point);
            } else {
				if(!$oDocument->isGranted()) return new Object(-1,'msg_not_permitted');
                $output = $oDocumentController->updateDocument($oDocument, $obj);
                if(!$output->toBool()) return $output;
                $msg_code = 'success_updated';
            }

            $this->add('document_srl', $obj->document_srl);
            $this->setMessage($msg_code);

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispKinIndex');
				header('location:'.$returnUrl);
				return;
			}
        }

        function procKinDeleteDocument() {
            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');
            $oKinModel = &getModel('kin');
            $oPointController = &getController('point');
            $oPointModel = &getModel('point');

            $document_srl = Context::get('document_srl');
            if(!$document_srl) return new Object(-1,'msg_invalid_request');

            $oDocument = $oDocumentModel->getDocument($document_srl);
            if($oDocument->getCommentCount()>0) return new Object(-1,'msg_invalid_request');

            $give_point = $oKinModel->getKinPoint($document_srl);
            $user_point = $oPointModel->getPoint($oDocument->get('member_srl'), true);

            $output = $oDocumentController->deleteDocument($document_srl);
            if(!$output->toBool()) return $output;

            $args->document_srl = $document_srl;
            $output = executeQuery('kin.deleteKinPoint', $args);
            if(!$output->toBool()) return $output;

            if($give_point>0) $oPointController->setPoint($oDocument->get('member_srl'), $user_point+$give_point);
        }


		//��������
        function procKinSelectReply() {
            $oDocumentModel = &getModel('document');
            $oCommentModel = &getModel('comment');
            $oKinModel = &getModel('kin');
            $oPointController = &getController('point');
            $oPointModel = &getModel('point');
            $oModuleModel = &getModel('module');

            $comment_srl = Context::get('comment_srl');
            $oComment = $oCommentModel->getComment($comment_srl);
            if(!$oComment->isExists()) return new Object(-1,'msg_invalid_request');

            $oSourceDocument = $oDocumentModel->getDocument($oComment->get('document_srl'));
            if(!$oSourceDocument->isExists() || $oKinModel->getSelectedReply($oSourceDocument->document_srl))  return new ObjecT(-1,'msg_invalid_request');

            $logged_info = Context::get('logged_info');
            if($oSourceDocument->get('member_srl')!=$logged_info->member_srl && !$logged_info->is_admin)  return new ObjecT(-1,'msg_invalid_request');


            $args->document_srl = $oSourceDocument->document_srl;
            $args->selected = $comment_srl;
            //$args->in_time = time();
            $output = executeQuery('kin.insertKinThreadSelected', $args);
            if(!$output->toBool()) return $output;

            if($oSourceDocument->get('member_srl') == $oComment->get('member_srl')) return new Object();

            $module_point_config = $oModuleModel->getModulePartConfig('point', $this->module_srl);

            $min_point = $module_point_config['insert_document'];
            $user_point = $oPointModel->getPoint($oComment->get('member_srl'), true);
            $give_point = $oKinModel->getKinPoint($oSourceDocument->document_srl);
            $give_point = abs((int)($give_point/2));

            unset($args);
            $args->document_srl = $oSourceDocument->document_srl;
            $args->comment_srl = $oComment->comment_srl;
            $args->member_srl = $oComment->get('member_srl');
            $args->point = $give_point;
            $args->in_time = time();
            $output = executeQuery('kin.insertKinPointLog', $args);
            if(!$output->toBool()) return $output;

            $oPointController->setPoint($oComment->get('member_srl'), $user_point+$give_point);

            $owner_point = $oPointModel->getPoint($oSourceDocument->get('member_srl'), true);
            $oPointController->setPoint($oSourceDocument->get('member_srl'), $owner_point+$give_point);
        }


		//�ύ�ش�
        function procKinInsertReply() {
            $oKinModel = &getModel('kin');
            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');
            $oCommentController = &getController('comment');
            if(!$this->grant->write_reply) return new Object(-1, 'msg_not_permitted');

            $document_srl = Context::get('document_srl');
            $oSourceDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oSourceDocument->isExists()) return new Object(-1,'msg_invalid_request');

            //if($oKinModel->getSelectedReply($document_srl)) return new Object(-1,'msg_invalid_request');

            $obj->document_srl = $document_srl;
            $obj->comment_srl = Context::get('comment_srl');
            if(!$obj->comment_srl) $obj->comment_srl = getNextSequence();
            $obj->module_srl = $this->module_srl;
            $obj->content = Context::get('content');
            $obj->notify_message = 'Y';
            if(!$obj->content) return new Object(-1, 'msg_content_is_null');
	
            $output = $oCommentController->insertComment($obj);
            if(!$output->toBool()) return $output;

            $this->add('document_srl', $oSourceDocument->get('document_srl'));
            $this->setMessage('success_registed');
	
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'document_srl',$obj->document_srl, 'act', 'dispKinView');
				header('location:'.$returnUrl);
				return;
			}
        }

		//�޸Ļش�
        function procKinUpdateReply() {
            $oKinModel = &getModel('kin');
            $oDocumentModel = &getModel('document');
            $oCommentModel = &getModel('comment');
            $oCommentController = &getController('comment');
            if(!$this->grant->write_reply) return new Object(-1, 'msg_not_permitted');

			$document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');
            $oReply = $oCommentModel->getComment($comment_srl);
            if(!$oReply->isExists()) return new Object(-1,'msg_invalid_request');

            if($oKinModel->getSelectedReply($oReply->get('document_srl'))) return new Object(-1,'msg_invalid_request');

            $obj->module_srl = $this->module_srl;
            $obj->comment_srl = $comment_srl;
            $obj->content = Context::get('content');
            if(!$obj->content) return new Object(-1, 'msg_content_is_null');

            $output = $oCommentController->updateComment($obj);
            if(!$output->toBool()) return $output;

            $this->add('document_srl', $oReply->get('document_srl'));
            $this->setMessage('success_registed');

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'document_srl',$document_srl, 'act', 'dispKinView');
				header('location:'.$returnUrl);
				return;
			}
        }


		//ɾ��ش�
        function procKinDeleteReply() {
            $oKinModel = &getModel('kin');
            $oDocumentModel = &getModel('document');
            $oCommentModel = &getModel('comment');
            $oCommentController = &getController('comment');

            $comment_srl = Context::get('comment_srl');
            $oReply = $oCommentModel->getComment($comment_srl);
            if(!$oReply->isExists()) return new Object(-1,'msg_invalid_request');
            if(!$oReply->isGranted()) return new Object(-1,'msg_not_permitted');

            $oSourceDocument = $oDocumentModel->getDocument($oReply->get('document_srl'));
            if(!$oSourceDocument->isExists()) return new Object(-1,'msg_invalid_request');

            if($oKinModel->getSelectedReply($document_srl)==$document_srl) return new Object(-1,'msg_invalid_request');

            $oCommentController->deleteComment($oReply->comment_srl);
            $this->setMessage('success_deleted');
        }

        function procKinInsertComment() {
            $oKinModel = &getModel('kin');
			
            if(!$this->module_srl || !$this->grant->write_reply) return new Object(-1,'msg_invalid_request');
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object(-1,'msg_invalid_request');
			
            $args = Context::gets('parent_srl','content');
			$document_srl = Context::get('document_srl');

			if(!$args->content){
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'document_srl',$document_srl, 'act', 'dispKinView');
				header('location:'.$returnUrl);
				return;
			}

            $args->module_srl = $this->module_srl;
            $args->member_srl = $logged_info->member_srl;
            $args->nick_name = $logged_info->nick_name;
            $args->reply_srl = getNextSequence();
            $args->list_order = -1*$args->reply_srl;
            $output = executeQuery('kin.insertComment', $args);
            if(!$output->toBool()) return $output;

            $output = $oKinModel->getKinCommentList($this->module_info, $args->parent_srl, 1);
            $this->add('parent_srl', $output->get('parent_srl'));
            $this->add('html', $output->get('html'));

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'document_srl',$document_srl, 'act', 'dispKinView');
				header('location:'.$returnUrl);
				return;
			}
        }

        function procKinDeleteComment() {
            $oKinModel = &getModel('kin');

            if(!$this->module_srl || !$this->grant->write_reply) return new Object(-1,'msg_invalid_request');
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object(-1,'msg_invalid_request');

            $args = Context::gets('parent_srl','reply_srl', 'page');

            $output = executeQuery('kin.getReply', $args);
            if(!$output->toBool() || !$output->data) return new Object(-1,'msg_invalid_request');

            $reply = $output->data;
            if($reply->module_srl != $this->module_srl || (!$this->grant->manager && $reply->member_srl != $logged_info->member_srl)) return new Object(-1,'msg_not_permitted');

            $output = executeQuery('kin.deleteReply', $args);
            if(!$output->toBool()) return $output;

            $output = $oKinModel->getKinCommentList($this->module_info, $args->parent_srl, $page);
            $this->add('parent_srl', $output->get('parent_srl'));
            $this->add('html', $output->get('html'));
        }
    }
?>
