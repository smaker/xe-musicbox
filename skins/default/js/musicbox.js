(function($){
	$(function(){
		xe.musicbox = $('#musicboxBase');

		jpLyric = $('.jp-lyric');

		$('#musicboxContainer .jp-lyric').on('click', '.jp-lyric-line', function() {
			xe.musicbox.jPlayer('play', $(this).data('time'));
		})

		function onTimeUpdate(event)
		{
			for (var i = 0, l = lyricList.length; i < l; i++) {
				if (event.jPlayer.status.currentTime > lyricList[i][0]) {
					var line = $('.jp-lyric>.jp-lyric-line:eq(' + i + ')');
					var prevLine = $('.jp-lyric>.jp-lyric-line');
					prevLine.attr('class', 'jp-lyric-line');

					line.attr('class', 'jp-lyric-line  jp-lyric-current-line');
					jpLyric.parent().css('top', 88 - line.offsetTop);
				}
			}
		}

		function onPlay(event)
		{
		}

		function onSetMedia(event)
		{
			// 현재 재생중인 음원
			var current		= myPlaylist.current;
			// 전체 재생 목록
	        var playlist	= myPlaylist.playlist;


			// 재생 목록에서 현재 재생중인 음원 정보를 찾는다
			$.each(playlist, function (index, obj){
				// 찾았다면
				if (index == current)
				{
					var musicSrl = obj.music_srl;
					// 서버에 등록된 가사를 가져온다
					$.exec_json('musicbox.getMusicboxLyric', { music_srl : musicSrl }, function(data)
					{
						var times = data.lyric_info.times;
						var timesCount = data.lyric_info.times.length;
						var lyrics = data.lyric_info.lyrics;
						var lyricsCount = data.lyric_info.lyrics.length;
						// 가사 초기화
						jpLyric.empty();
						// 
						lyricList = [];
						for(var i=0;i<timesCount;i++)
						{
							lyricList[i] = [];
							lyricList[i][0] = times[i];
							lyricList[i][1] = lyrics[i];
							$('<li class="jp-lyric-line">').data('time', times[i]).text(lyrics[i]).appendTo(jpLyric);
						}
					});
				}
			});
		}

		var myPlaylist = new jPlayerPlaylist({
			jPlayer: "#musicboxBase",
			cssSelectorAncestor: "#musicboxContainer",
		}, playableMusics, {
			playlistOptions: {
				loopOnPrevious: true
			},
			loop: false,
			swfPath: request_uri + 'modules/musicbox/skins/default/js',
			solution : 'html, flash',
			supplied: 'mp3',
			wmode : 'window',
			preload: 'auto',
			autoPlay: true,
			autoBlur: false,
			smoothPlayBar: true,
			keyEnabled: true,
			useStateClassSkin: true,
		});

		$('#musicboxBase').bind($.jPlayer.event.setmedia, onSetMedia);
		// 재생중일 때 실행되는 이벤트
		$('#musicboxBase').bind($.jPlayer.event.timeupdate, onTimeUpdate);
		// 재생 버튼을 눌렀을 때 실행되는 이벤트
		$('#musicboxBase').bind($.jPlayer.event.play, onPlay);
	});
})(jQuery);