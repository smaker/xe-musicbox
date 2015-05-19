<?php
class musicboxModel extends musicbox
{
	/**
	 * @brief return module name in sitemap
	 **/
	function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'musicbox');
	}
	/**
	 * 선택한 음원의 가사를 return
	 */
	public function getMusicboxLyric()
	{
		// 음원 고유 번호
		$music_srl = Context::get('music_srl');

		// DB에서 가사를 가져온다
		$args = new stdClass;
		$args->music_srl = $music_srl;
		$output = executeQuery('musicbox.getMusicLyric', $args);

		// DB 에러가 발생하면
		if(!$output->toBool())
		{
			// 오류 출력
			return $output;
		}

		$lyric_info = $this->parseLyric($output->data->lyric);

		$this->add('lyric_info', $lyric_info);
	}

	/**
	 * 가사 정보를 읽어서 배열로 변환
	 */
	public function parseLyric($lyrics)
	{
		// 가사는 한 줄에 하나씩 있다
		$lyrics = explode(PHP_EOL, trim($lyrics));

		$lyrics_list = array();
		$time_list = array();

		if(count($lyrics) > 0)
		{
			foreach($lyrics as $line => $lyric)
			{
				// 시간과 가사를 분리한다
				$time = substr($lyric, 1, 8);

				$text = trim(substr($lyric, 10));

				// 시, 분, 초를 분리한다
				$minutes = substr($time, 0, 2);
				$seconds = substr($time, 3, 2);
				$mseconds = substr($time, 6, 2);

				$time_list[] = ($minutes * 60) + $seconds + $mseconds * 0.1;
				$lyric_list[] = $text;
			}
		}

		return array(
			'times' => $time_list,
			'lyrics' => $lyric_list
		);
	}
}