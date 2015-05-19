<?php
	/**
	 * @class  splayerAdminView
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  splayer 모듈의 admin view class
	 **/

	class musicboxAdminView extends musicbox {

		/**
		 * @brief 초기화
		 **/
		function init() {
			// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
			$module_srl = Context::get('module_srl');
			if(!$module_srl && $this->module_srl) {
				$module_srl = $this->module_srl;
				Context::set('module_srl', $module_srl);
			}

			// module model 객체 생성 
			$oModuleModel = &getModel('module');

			// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
			if($module_srl) {
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
				if(!$module_info) {
					Context::set('module_srl','');
					$this->act = 'list';
				} else {
					ModuleModel::syncModuleToSite($module_info);
					$this->module_info = $module_info;
					Context::set('module_info',$module_info);
				}
			}

			// 모듈 카테고리 목록을 구함
			$module_category = $oModuleModel->getModuleCategories();
			Context::set('module_category', $module_category);

			// 템플릿 경로 지정
			$this->setTemplatePath($this->module_path.'tpl');
		}

		/**
		 * @brief 뮤직박스 관리 페이지
		 **/
		function dispMusicboxAdminContent() {
			// 등록된 모듈을 불러와 세팅
			$args->sort_index = 'module_srl';
			$args->page = Context::get('page');
			$args->list_count = 20;
			$args->page_count = 10;
			$args->s_module_category_srl = Context::get('module_category_srl');

			$s_mid = Context::get('s_mid');
			if($s_mid) $args->s_mid = $s_mid;

			$s_browser_title = Context::get('s_browser_title');
			if($s_browser_title) $args->s_browser_title = $s_browser_title;

			$output = executeQueryArray('musicbox.getPlayerList', $args);
			ModuleModel::syncModuleToSite($output->data);

			// 템플릿에 쓰기 위해서 context::set
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('player_list', $output->data);
			Context::set('page_navigation', $output->page_navigation);

			// 템플릿 파일 지정
			$this->setTemplateFile('player_list');
		}

		/**
		 * @brief 선택된 플레이어의 정보 출력 (바로 정보 입력으로 변경)
		 **/
		function dispMusicboxAdminPlayerInfo() {
			$this->dispMusicboxAdminInsertPlayer();
		}

		/**
		 * @brief 게시판 추가 폼 출력
		 **/
		function dispMusicboxAdminInsertPlayer()
		{
			// 스킨 목록을 구해옴
			$oModuleModel = &getModel('module');
			$skin_list = $oModuleModel->getSkins($this->module_path);
			Context::set('skin_list',$skin_list);

			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
			Context::set('mskin_list', $mskin_list);

			// 레이아웃 목록을 구해옴
			$oLayoutModel = &getModel('layout');
			$layout_list = $oLayoutModel->getLayoutList();
			Context::set('layout_list', $layout_list);

			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
			Context::set('mlayout_list', $mobile_layout_list);

			// 템플릿 파일 지정
			$this->setTemplateFile('player_insert');
		}

		/**
		 * @brief 재생 목록 관리
		 **/
		function dispMusicboxAdminManagePlaylist()
		{
			// 재생 목록을 구해옴
			$output = executeQuery('splayer.getAdminPlaylists');

			// 템플릿에 쓰기 위해서 context::set
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('playlist_list', $output->data);
			Context::set('page_navigation', $output->page_navigation);

			// 템플릿 파일 지정
			$this->setTemplateFile('manage_playlist');
		}

		/**
		 * @brief 재생 목록 관리
		 **/
		function dispMusicboxAdminInsertPlaylist()
		{
			// 재생 목록을 구해옴
			/*$output = executeQuery('splayer.getAdminPlaylists');

			// 템플릿에 쓰기 위해서 context::set
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('playlist_list', $output->data);
			Context::set('page_navigation', $output->page_navigation);*/

			// 템플릿 파일 지정
			$this->setTemplateFile('playlist_insert');
		}

		/**
		 * @brief 게시판 추가 설정 보여줌
		 * 추가설정은 서비스형 모듈들에서 다른 모듈과의 연계를 위해서 설정하는 페이지임
		 **/
		function dispMusicboxAdminBoardAdditionSetup() {
			// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
			$content = '';

			// 추가 설정을 위한 트리거 호출 
			// 게시판 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
			$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
			$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
			Context::set('setup_content', $content);

			// 템플릿 파일 지정
			$this->setTemplateFile('addition_setup');
		}

		/**
		 * @brief 권한 목록 출력
		 **/
		function dispMusicboxAdminGrantInfo() {
			// 공통 모듈 권한 설정 페이지 호출
			$oModuleAdminModel = &getAdminModel('module');
			$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
			Context::set('grant_content', $grant_content);

			$this->setTemplateFile('grant_list');
		}

		public function dispMusicboxAdminMusicList()
		{
			$args = new stdClass();
			$args->module_srl = $this->module_info->module_srl;
			$output = executeQueryArray('musicbox.getMusicList', $args);
			Context::set('music_list', $output->data);

			$this->setTemplateFile('music_list');
		}

		/**
		 * 음원 업로드
		 */
		public function dispMusicboxAdminUploadMusic()
		{
			$music_srl = Context::get('music_srl');
			$module_srl = Context::get('module_srl');

			// music_srl이 넘어왔다면
			if($music_srl)
			{
				$args = new stdClass;
				$args->music_srl = $music_srl;
				$output = executeQuery('musicbox.getMusic', $args);
				Context::set('oMusic', $output->data);
			}
			else
			{
				$oMusic = new stdClass;
				Context::set('oMusic', $oMusic);
			}

			
			$this->setTemplateFile('upload_music');
		}

		/**
		 * @brief splayer module용 메시지 출력
		 **/
		function alertMessage($message) {
			$script =  sprintf('<script type="text/javascript"> jQuery(document).ready(function($) { alert("%s"); });</script>', Context::getLang($message));
			Context::addHtmlHeader($script);
		}
	}
?>
