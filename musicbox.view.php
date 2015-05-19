<?php
/**
 * @class  musicboxView
 * @author 퍼니엑스이 (contact@funnyxe.com)
 * @brief  musicbox 모듈의 view class
 **/

class musicboxView extends musicbox
{
	/**
	 * @brief 초기화
	 **/
	function init()
	{
		/**
		 * 스킨 경로를 미리 template_path 라는 변수로 설정함
		 * 스킨이 존재하지 않는다면 default로 변경
		 **/
		$template_path = sprintf('%sskins/%s/',$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf('%sskins/%s/',$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);
	}

		/**
		 * @brief 뮤직박스
		 **/
		function dispMusicbox()
		{
			// 음악 듣기 권한이 없을 경우에는
			if(!$this->grant->listen)
			{
				return new Object(-1, 'msg_musicbox_not_permitted');
			}

			// 모듈에 등록된 모든 음원을 가져온다
			$args = new stdClass();
			$args->module_srl = $this->module_info->module_srl;
			$output = executeQueryArray('musicbox.getMusicList', $args);

			Context::set('music_list', $output->data);

			// 템플릿 파일 지정
			$this->setTemplateFile('player');
		}
	}
?>
