<?php
/**
 * @class  musicboxAdminController
 * @author 퍼니엑스이 (contact@funnyxe.com)
 * @brief  musicbox 모듈의 admin controller class
 **/

class musicboxAdminController extends musicbox
{

	/**
	 * @brief 초기화
	 **/
	function init()
	{
	}
		/**
		 * @brief 게시판 추가
		 **/
		function procMusicboxAdminInsertPlayer($args = null)
		{
			// module 모듈의 model/controller 객체 생성
			$oModuleController = &getController('module');
			$oModuleModel = &getModel('module');

			// 게시판 모듈의 정보 설정
			$args = Context::getRequestVars();
			$args->module = 'musicbox';
			$args->mid = $args->player_name;
			unset($args->player_name);

			// 기본 값외의 것들을 정리
			if($args->use_category!='Y') $args->use_category = 'N';
			if(!in_array($args->order_target,$this->order_target)) $args->order_target = 'list_order';
			if(!in_array($args->order_type,array('asc','desc'))) $args->order_type = 'asc';

			// module_srl이 넘어오면 원 모듈이 있는지 확인
			if($args->module_srl) {
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
				if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
			}

			// module_srl의 값에 따라 insert/update
			if(!$args->module_srl) {
				$output = $oModuleController->insertModule($args);
				$msg_code = 'success_registed';
			} else {
				$output = $oModuleController->updateModule($args);
				$msg_code = 'success_updated';
			}

			if(!$output->toBool()) return $output;

			$this->add('page',Context::get('page'));
			$this->add('module_srl',$output->get('module_srl'));
			$this->setMessage($msg_code);
		}

		/**
		 * @brief 게시판 삭제
		 **/
		function procMusicboxAdminDeletePlayer() {
			$module_srl = Context::get('module_srl');

			// 원본을 구해온다
			$oModuleController = &getController('module');
			$output = $oModuleController->deleteModule($module_srl);
			if(!$output->toBool()) return $output;

			$this->add('module','splayer');
			$this->add('page',Context::get('page'));
			$this->setMessage('success_deleted');
		}

		/**
		 * @brief 음원 업로드
		 **/
		function procMusicboxAdminUploadMusic()
		{
			$music_info = Context::gets('music_title', 'music_artist', 'music_album', 'comment', 'module_srl', 'music_srl', 'lyric');

			$mp3File = $_FILES['mp3_file'];

			$errorCode = $mp3File['error'];
			$music_srl = Context::get('music_srl');

			// 정상적으로 업로드 되지 않았다면 에러
			if(!$music_srl && !is_uploaded_file($mp3File['tmp_name']))
			{
				switch($errorCode)
				{
					case UPLOAD_ERR_INI_SIZE:
						$message = '최대 ' . ini_get('upload_max_filesize') . '까지 업로드할 수 있습니다.';
						break;
					case UPLOAD_ERR_NO_FILE:
						$message = '음원 파일을 선택해주세요.';
						break;
				}
				return new Object(-1, $message);
			}

			$ext = strtolower(substr(strrchr($mp3File['name'],'.'),1));

			// 확장자 검사
			if(!$music_srl && !in_array($ext, array('mp3')))
			{
				return new Object(-1, $ext. 'msg_error_occured2');
			}

			$oDB = DB::getInstance();
			$oDB->begin();

			if($music_srl)
			{
				// 음원 수정
				$output = $this->updateMusic($music_info);
			}
			else
			{
				// 음원 추가
				$output = $this->insertMusic($music_info);
			}

			// 오류가 발생했다면
			if(!$output->toBool())
			{
				// 롤백한다
				$oDB->rollback();
				return $output;
			}

			// 오류가 없다면 커밋
			$oDB->commit();

			$returnUrl  = getNotEncodedUrl('','module','admin','act','dispMusicboxAdminMusicList', 'module_srl', $music_info->module_srl);

			$this->setRedirectUrl($returnUrl);
		}

		/**
		 * @brief 재생 목록 추가
		 **/
		function procSplayerAdminInsertPlaylist()
		{
			$playlist_info = Context::gets('playlist_title', 'music_srl', 'comment');

			// 필요한 값이 넘어오지 않았다면 에러
			if(!$playlist_info->playlist_title) return new Object(-1, 'msg_invalid_request');

			$music_list = explode(',', $playlist_info->music_srl);
		}

		/**
		 * @brief 음악 업로드
		 **/
		function insertMusic($obj)
		{
			$path = sprintf('files/attach/musicbox/%s', getNumberingPath($obj->module_srl,3));

			if(!is_dir($path))
			{
				mkdir(_XE_PATH_ . $path, 0707, TRUE);
				chmod(_XE_PATH_ . $path);
			}

			$upload_filename = md5(crypt($_FILES->mp3_file['name'].rand(1000000,900000), rand(0,100)));
			$upload_path =  $path. $upload_filename . '.mp3';

			// insert
			$args = new stdClass;
			$args->module_srl = $obj->module_srl;
			$args->title = trim($obj->music_title);
			$args->artist = trim($obj->music_artist);
			$args->album = trim($obj->music_album);
			$args->source_filename = $_FILES['mp3_file']['name'];
			$args->uploaded_filename = $upload_path;
			$args->comment = $obj->comment;
			$args->file_size = $_FILES['mp3_file']['size'];

			// upload
			if(!@move_uploaded_file($_FILES['mp3_file']['tmp_name'], _XE_PATH_ . $upload_path)) {
				return new Object(-1, 'File Upload failture');
			}

			return executeQuery('musicbox.insertMusic', $args);
		}
		/**
		 * @brief 음원 수정
		 **/
		function updateMusic($obj)
		{
			$fileUploaded = fALSE;
			if(is_uploaded_file($_FILES['mp3_file']['tmp_name']))
			{
				$fileUploaded = TRUE;
				$path = sprintf('files/attach/musicbox/%s', getNumberingPath($obj->module_srl,3));

				if(is_dir($path))
				{
					rmdir(_XE_PATH_ . $path, TRUE);
				}

				mkdir(_XE_PATH_ . $path, 0707, TRUE);
				chmod(_XE_PATH_ . $path);

				$upload_filename = md5(crypt($_FILES['mp3_file']['name'].rand(1000000,900000), rand(0,100)));
				$upload_path =  $path. $upload_filename . '.mp3';

				// upload
				if(!@move_uploaded_file($_FILES['mp3_file']['tmp_name'], _XE_PATH_ . $upload_path)) {
					return new Object(-1, 'File Upload failture');
				}
			}

			// insert
			$args = new stdClass;
			$args->music_srl = $obj->music_srl;
			$args->module_srl = $obj->module_srl;
			$args->title = trim($obj->music_title);
			$args->artist = trim($obj->music_artist);
			$args->album = trim($obj->music_album);
			if($fileUploaded)
			{
				$args->source_filename = $_FILES['mp3_file']['name'];
				$args->uploaded_filename = $path . $upload_path;
				$args->file_size = $_FILES['mp3_file']['size'];
			}
			$args->comment = $obj->comment;
			$args->lyric = $obj->lyric;
			$output =  executeQuery('musicbox.updateMusic', $args);
			debugPrint($output);
			return $output;
		}
	}
?>
