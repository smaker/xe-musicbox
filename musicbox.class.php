<?php
	/**
	 * @class  musicbox
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  musicbox 모듈의 high class
	 **/

	class musicbox extends ModuleObject
	{
		public $triggers = array(
			// 사이트 맵에서 바로 모듈을 생성할 수 있도록 트리거 추가
			array('menu.getModuleListInSitemap', 'musicbox', 'model', 'triggerModuleListInSitemap', 'after')
		);

		public $search_option = array('title','content','title_content','comment','user_name','nick_name','user_id','tag'); ///< 검색 옵션

		var $order_target = array('list_order', 'update_order', 'regdate', 'voted_count', 'readed_count', 'comment_count', 'title'); // 정렬 옵션

		/**
		 * @brief 설치시 추가 작업이 필요할시 구현
		 **/
		function moduleInstall() {
			return new Object();
		}

		/**
		 * @brief 설치가 이상이 없는지 체크하는 method
		 **/
		function checkUpdate() {
			// moduleModel 객체 생성
			$oModuleModel = getModel('module');

			// 필요한 트리거가 DB에 있는지 확인
			foreach($this->triggers as $key => $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					return true;
				}
			}

			$oDB = DB::getInstance();
			if(!$oDB->isColumnExists('musicbox_music', 'lyric'))
			{
				return TRUE;
			}

			return false;
		}

		/**
		 * @brief 업데이트 실행
		 **/
		function moduleUpdate() {
			// moduleModel 객체 생성
			$oModuleModel = getModel('module');
			// moduleController 객체 생성
			$oModuleController = getController('module');

			// 필요한 트리거가 DB에 있는지 확인
			foreach($this->triggers as $key => $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}

			$oDB = DB::getInstance();
			if(!$oDB->isColumnExists('musicbox_music', 'lyric'))
			{
				$oDB->addColumn('musicbox_music', 'lyric', 'bigtext', '', '', TRUE);
			}

			return new Object(0,'success_updated');
		}

		/**
		 * @brief 캐시 파일 재생성
		 **/
		function recompileCache() {
		}
	}
?>