<?php
/*
Template Name: Trees Are Fags
*/
?>
<html>
<head>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<!--<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_uri(); ?>">-->
	<style>

		.swipe {
		  overflow: hidden;
		  visibility: hidden;
		  position: relative;
		}

		.swipe-wrap {
		  overflow: hidden;
		  position: relative;
		}

		.swipe-wrap > div {
		  position: relative;
		  float: left;
		  width: 100%;
		  min-height: 100%;
		  display: flex;
		  justify-content: center;
		  align-items: center;
		}

		.page {
			min-height: 100%;
		}

		html,body {
			overflow-x: hidden;
		    height: 100%;
		    margin: 0px;
		    padding: 0px;
		}

		body {
			position: relative;
		}

		/* player controls */
		.loadbar {
			position: relative;
			width: 100px;
			height: 50px;
			top: 60%;
			left: 50%;
			transform: translate(0%, -50%);
			text-align: center;
			font-size: 10px;
			font-family: sans-serif;
		}

		.controlButton {
			cursor: pointer;
			width: 60px;
			height: 60px;
			background: transparent;
			background-size: contain; /* image resized to div size */
			background-repeat: no-repeat;

			/*display: flex;
			flex-direction: column;*/
		}

		.controls {
			position: relative;
			width: 189px;
			/*top: 50%; /* centre (top left)
			transform: translateY(-50%); /* true centre: shift by half width/height */
			/* so inner divs are in a row */
			display: flex;
			align-items: center;
			flex-direction: row;
			justify-content: space-around;*/
		}

		#ffw {
			background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/ffw.png);
		}

		#ffw:active {
			opacity: 0.5;
		}

		#rew {
			background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/rew.png);
		}

		#rew:active {
			opacity: 0.5;
		}

		.play {
			background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/pause-play-button.jpg);
			cursor: pointer;
			opacity: 0.5;
		}

		.loading {
			cursor: default;
			background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/loading.png);
			opacity: 0.5;
		}

		.pause {
			opacity: 1;
		}

		#console {
			overflow: auto;
			position: absolute;
			left: 2px;
			top: 2px;
			background-color: #fffacd;
			width: 200px;
			height: 200px;
			padding: 2px;
			font-size: smaller;
		}

		/* text pages */
		.text-body {
			text-align: justify;
			max-width: 500px;
			padding: 10px;
		}

		a {
			color: #aeaeae;
			text-decoration: none;
		}

		a:hover {
			color: black;
		}

	</style>
	<script type="text/javascript"
	src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript"
	src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/swipe.min.js"></script>
	<script type="text/javascript">

		//
		var playButton;
		var loadBar;
		var swipe;

		var cues;
		var player;

		$(document).ready(init);

		function init() {
			playButton = $('#play');
			loadBar = $('.loadBar');

			swipe = new Swipe($('#slider')[0], {
				speed: 900,
				continuous: false,
				disableScroll: true,
			});
			swipe.setup();

			// arrow keys to move between swipe pages
			// window.addEventListener('keydown', function(e) {
			// 	if (!e) e = window.event;
			// 	var keycode = e.keyCode || e.which;
			// 	var Key = {
			// 		LEFT: 37,
			// 		RIGHT: 39
			// 	};

			// 	if (keycode == Key.LEFT) {
			// 		swipe.prev();
			// 	} else if (keycode == Key.RIGHT) {
			// 		swipe.next();
			// 	}
			// });

			var cuePoolTotal = 6; // total number of phrases in the pool
			var cues = new Cues(cuePoolTotal);

			var playlist = ["intro", 3, "outro"];
			var phraseGap = 1000; // pause between phrases (ms)
			var skipTime = 5; // seconds to skip forward/back using buttons
			player = new Player(playlist, phraseGap, skipTime);

			allAudioLoaded();

			// intro = new Audio(getFileName('intro'));
			// intro.addEventListener('canplaythrough', audioLoaded, false);
			// intro.addEventListener('timeupdate', audioSeek);
			/*
			try {
			    // Fix up for prefixing
			    window.AudioContext = window.AudioContext||window.webkitAudioContext;
			    context = new AudioContext();
			}
			catch(e) {
			    alert('Web Audio API is not supported in this browser');
			}
			*/

		}

		function Cue(start, end) {
			this.start = start;
			this.end = end;
			this.file = cues.getNextCue();
			this.active = false;
			this.loaded = false;
			this.timeNarrationPaused = 0; // logs time in this cue at which narration was paused to wait
		}

		Cue.prototype = {
			setup: function() {
				this.audio = 0; // get audio buffer !!!

				var me = this;
				this.audio.addEventListener('ended', function() { me.ended(); });
			},

			play: function() {
				if (this.audio) {
					this.audio.play();
				}
			}

			go: function() {
				// add handler for not loaded yet
				this.active = true;
				this.play();
			},

			ended: function() {
				this.active = false;
				player.nextCue();
			}

		};

		// object manages pool of shuffled cues (as filenames only)
		function Cues(total) {

			this.fileList = [];
			this.total = total;
			this.used = 0; // how many cues have been given away

			// initialise filenames array
			for (var i = 0; i < this.total; ++i) {
				this.fileList[i] = getFileName(i+1);
			}
			// shuffle
			this.fileList = shuffleArray(this.fileList);

			// get next filename from array
			this.getNextCue = function(n) {
				if (this.used < this.total) {
					return this.fileList[this.used++];
				}
			};
		}

		// main Player object
		function Player(playlist, gapTime, skipTime) {

			this.skipTime = skipTime;
			this.playing = false;
			this.waitForCue = false;

			// initialise main narration audio
			this.narration = new Audio(getFileName("main-narration"));
			this.narration.preload = "auto";
			var me = this; // retain binding
			this.narration.addEventListener('timeupdate', function() { me.seek(); });
			this.narration.addEventListener('canplaythrough', allAudioLoaded);
			this.narration.addEventListener('waiting', audioWaiting);
			this.narration.addEventListener('playing', audioUnwaiting);

			// initialise cues
			this.cues = [];
			this.curCue = 0; // index of next/current cue (will be incremented on first call of nextCue)
			var listlen = playlist.length;
			for (var i = 0; i < listlen; i++) {
				this.cues.append(new Cue(playlist[i][0], playlist[i][1]));
			}
		}

		Player.prototype = {
			// get currently queued cue (or null)
			curCue: function() {
				if (this.curCue >= this.cues.length) {
					return null;
				}
				return this.cues[this.curCue];
			},

			// get currently active cue (or null)
			activeCue: function() {
				var cue = this.curCue();
				if (cue && cue.active) {
					return cue;
				} else {
					return null;
				}
			},

			play: function() {
				this.playing = true;
				if (!this.waitForCue) this.narration.play();
				var cue = this.activeCue();
				if (cue) cue.play();
				playButton.addClass('pause');
			},

			pause: function() {
				this.playing = false;
				if (!this.waitForCue) this.narration.pause();
				var cue = this.activeCue();
				if (cue) cue.pause();
				playButton.removeClass('pause');
			},

			// method called directly by pressing the play/pause button
			playPause: function() {
				// if the sequence has ended, restart the sequence
				if (this.playing) {
					this.pause();
				} else {
					this.play();
				}
			},

			rew: function(skip) {
				// skip back in narration
				if (!this.waitForCue) {
					if (this.narration.currentTime >= this.skipTime) {
						this.narration.currentTime -= this.skipTime;
					} else {
						this.narration.currentTime = 0;
					}
				}
				// skip back in current active cue
				var cue = this.activeCue();
				var narrationPosition = this.narration.currentTime - this.skipTime;
				if (cue) {
					// need to skip back the right amount in narration if for the last few seconds
					// it was paused waiting for cue
					if (this.waitForCue) {
						narrationPosition += cue.audio.currentTime - cue.end;
						if (narrationPosition < cue.end) {
							this.waitForCue = false;
							if (this.playing) this.narration.play();
						} else {
							narrationPosition = cue.end;
						}
					}
					// skip cue
					if (cue.audio.currentTime >= this.skipTime) {
						cue.audio.currentTime -= this.skipTime;
					} else { // handle case for skip past beginning of cue
						cue.audio.pause();
						cue.audio.currentTime = 0;
						cue.active = false;
					}
				} else {
					if (this.curCue > 0 && --this.curCue)
				}
				this.narration.currentTime = Math.max(narrationPosition, 0);
			},

			ffw: function() {
				// skip forward narration
				if (!this.waitForCue) {
					if (this.narration.currentTime + this.skipTime < this.narration.duration) {
						this.narration.currentTime += this.skipTime;
					}
				}
				// skip forward in active cue
				var cue = this.activeCue();
				if (cue) {
				}
			},

			seek: function() {
				var time = this.narration.currentTime;
				var cue = this.curCue();
				if (cue) { // test we haven't run out of cues yet
					// if it's time for cue to begin, begin cue
					if (!cue.active && time >= cue.start) {
						// some cue points have no narration to play concurrently... pause for cue immediately
						if (cue.end == cue.start) {
							cue.timeNarrationPaused = 0;
							this.narration.pause();
							this.waitForCue = true;
						}
						cue.go();
					}
					// if we've come to the end of cue time in narration, pause to wait for cue to end
					else if (cue.active && !this.waitForCue && time >= cue.end) {
						cue.timeNarrationPaused = cue.audio.currentTime;
						this.narration.pause();
						this.waitForCue = true;
					}
				}
			},

			audioLoaded: function(i) {
				this.loadCount += i;
				consoleLog("Loaded audio item " + i.toString() + ". Load count: " + this.loadCount.toString());
				// when loadCount == arithmetic of playlist length, we know we've received all indices
				// hence, globally we can play through
				if (this.loadCount == this.loadTotal) {
					consoleLog("calling allAudioLoaded()");
					allAudioLoaded();
				}
			}
		};

		// play in a click event so audio begins loading (next page button)
		function nextAndLoad() {
			player.play();
			player.pause();
			swipe.next();
		}

		function allAudioLoaded() {
			consoleLog("allAudioLoaded called");
			playButton.removeClass('loading');
			playButton.addClass('play');
			$('.play').click(function() { player.playPause(); });
			$('#rew').click(function() { player.rew(); })
		}

		function getFileName(affix) {
			var dir = "<?php echo get_stylesheet_directory_uri().'/'; ?>audio/";
			var prefix = "Test-samp-";
			var postfix = ".mp3";
			if (typeof affix == 'number') {
				return dir + prefix + affix + postfix;
			} else {
				return dir + affix + postfix;
			}
		}

		function audioWaiting() {
			playButton.removeClass('play');
			playButton.removeClass('pause');
			playButton.addClass('loading');
		}

		function audioUnwaiting() {
			playButton.removeclass('loading');
			playButton.addClass('play');
			playButton.addClass('pause');
		}

		// based on Knuth Shuffle (https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array)
		function shuffleArray(arr) {
			var currentIndex = arr.length;
			var randomIndex, temp;

			// While there remain elements to shuffle...
			while (currentIndex != 0) {

				// Pick a remaining element...
				randomIndex = Math.floor(Math.random() * currentIndex);
				currentIndex--;
				// Swap with current
				temp = arr[randomIndex];
				arr[randomIndex] = arr[currentIndex];
				arr[currentIndex] = temp;
			}

			return arr;
		}

		function consoleLog(text) {
			$('#console').append('<p>' + text + '</p>');
			console.log(text);
		}

		// function loadLoop() {
		// 	var buffered = intro.buffered;
		// 	var dur = intro.duration;
		// 	var loaded;
		// 	if (buffered.length) {
		// 		loaded = 100 * buffered.end(0) / dur;
		// 		loadBar.html(loaded + '%');
		// 	}
		// 	if (buffered.length < dur) {
		// 		setTimeout(loadLoop, 200);
		// 	}
		// }

	</script>
</head>
<body>
	<div id="console"></div>
	<div id="slider" class="swipe">
		<div class="swipe-wrap">
			<!-- ................page 1...........-->
			<div class="page">
				<div class="text-body">
					<h1>Trees Are Fags</h1>
					<p>
						The bassoon, or fagotto in Italian, is a very faggy instrument. Get it? Remember where the term faggot comes from in the first place - bundles of sticks, plain and simple. There emerges an obvious connection with the trees and shrubberies amongst which the more environmentally-inclined (green fingered?) gay cruisers of London execute their complex dances. I trust you are here now, in the thick of some thicket in Hyde Park. Listen to the bassoon. Listen to my voice. Execute my complex dance.
					</p>
					<p align="right">
						<a onclick="nextAndLoad()" href="#">Click here to continue &gt;</a>
					</p>
				</div>
			</div>
			<!-- ................page 2...........-->
			<div class="page">
				<div class="controls">
					<div class="controlButton" id="rew"></div>
					<div class="controlButton loading" id="play"></div>
					<div class="controlButton" id="ffw"></div>
				</div>
				<div class="text-body">
					<p>
						<span align="left">
							<a onclick="swipe.prev()"> href="#">&lt; Intro</a>
						</span>
						<span align="right">
							<a onclick="swipe.next()"> href="#">Credits &gt;</a>
						</span>
					</p>
				</div>
			</div>
			<!-- ................page 3...........-->
			<div class="page">
				<div class="text-body">
					<h1>Credits</h1>
					<p>Concept, text, graphic design: Benny Nemerofsky Ramsay.</p>
					<p>Bassoonist: Ronin.</p>
					<p>Sound design, sound engineering, web development: Nikita Gaidakov.</p>
				</div>
			</div>
		</div>
	</div>
</body>
</html>