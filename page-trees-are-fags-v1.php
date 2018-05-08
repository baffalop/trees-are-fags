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
	<script type="text/javascript" src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript" src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/swipe.min.js"></script>
	<script type="text/javascript">

		//var context;
		var playButton;
		var loadBar;
		var player;
		var swipe;

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
			window.addEventListener('keydown', function(e) {
				if (!e) e = window.event;
				var keycode = e.keyCode || e.which;
				var Key = {
					LEFT: 37,
					RIGHT: 39
				};

				if (keycode == Key.LEFT) {
					swipe.prev();
				} else if (keycode == Key.RIGHT) {
					swipe.next();
				}
			});

			var playlist = ["intro", 3, "outro"];
			var numPhrases = 6; // total number of phrases in the pool
			var phraseGap = 1000; // pause between phrases (ms)
			var skipTime = 5; // seconds to skip forward/back using buttons
			player = new Player(playlist, numPhrases, phraseGap, skipTime);

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

		// object representing 
		function Phrases(total) {

			this.fileList = [];
			this.total = total;
			this.used = 0;

			// initialise filenames array
			for (var i = 0; i < this.total; ++i) {
				this.fileList[i] = getFileName(i+1);
			}
			// shuffle
			this.fileList = shuffleArray(this.fileList);

			// get next n of fileList as an array of strings
			this.getPhrases = function(n) {
				var audioArray = [];
				while (n-- && this.used < this.total) {
					audioArray.push(this.fileList[this.used++]);
				}
				console.table(audioArray);
				return audioArray;
			};

		}

		function Gap(parent, gapTime) {
			this.parent = parent;
			this.remain = gapTime;
			this.go();
		}

		Gap.prototype = {
			go: function() {
				var me = this;
				this.start = Date.now();
				this.timeout = setTimeout(function() { me.parent.endGap(); }, this.remain);
			},

			pause: function() {
				this.remain -= Date.now() - this.start;
				clearTimeout(this.timeout);
			}
		};

		// main Player object
		function Player(playlist, phraseTotal, gapTime, skipTime) {

			this.gapTime = gapTime;
			this.skipTime = skipTime;
			this.current = 0; // index of currently-playing audio element
			this.playing = false;
			this.gap = null; // handles pauses between elements
			this.loadCount = 0;

			var choreography = new Phrases(phraseTotal);

			// build fileList (array of strings - filenames/paths) from playlist
			// fill in sequences of randomised phrases specified by numbers
			var fileList = [];
			var elem; // current element of playlist
			for (var i = 0; i < playlist.length; i++) {
				elem = playlist[i];
				// string elements refer to fixed narration
				if (typeof elem == "string") {
					fileList.push(getFileName(elem));
				}
				// numbers refer to how many phrases to take from the shuffled pool
				else if (typeof elem == "number") {
					fileList = fileList.concat(choreography.getPhrases(elem));
				}
			}
			// now we know how many items, generate total for loadCounting (see this.audioLoaded() )
			this.loadTotal = Math.floor(fileList.length*(fileList.length+1)/2); // total of arithmetic sum 1+...+fileList.length
			console.log("loadTotal: " + this.loadTotal.toString());

			var obj = this; // binding for callback function

			// initialise Audio elements from fileList
			this.sequence = fileList.map(function(x, i) {
				var content = new Audio(x);
				content.preload = "auto";
				content.addEventListener('ended', function() { obj.queueNext(); });
				content.addEventListener('timeupdate', function() { obj.seek(); });
				content.addEventListener('canplaythrough', function() {
					obj.audioLoaded(i+1); // pass index (offset 1) of loaded item to audioLoaded
				})
				content.addEventListener('waiting', audioWaiting);
				content.addEventListener('playing', audioUnwaiting);
				return content;
			});
		}

		Player.prototype = {
			// get currently queued audio element
			curr: function() {
				if (this.current >= this.sequence.length) {
					return null;
				}
				return this.sequence[this.current];
			},

			play: function() {
				if (this.gap) { // if we're in a gap between audio elements, resume gap
					this.gap.go();
				} else { // else play current element
					this.curr().play();
				}
				this.playing = true;
				playButton.addClass('pause');
			},

			pause: function() {
				if (this.gap) { // if we're waiting between audio elements, pause gap
					this.gap.pause();
				} else { // else pause current element
					this.curr().pause();

					// skip back a beat on pause?

					// if (this.curr().currentTime >= 1) {
					// 	this.curr().currentTime -= 1;
					// } else {
					// 	this.curr().currentTime = 0;
					// }
				}
				this.playing = false;
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

			rew: function() {
				if (this.gap) {
					this.gap.pause();
					var elapsed = (this.gapTime - this.gap.remain)/1000;
					if (elapsed < this.skipTime) {
						this.gap.remain = this.gapTime;
					} else {
						this.gap.remain += this.skipTime*1000;
					}
					this.gap.go();
				} else {
					if (this.curr().currentTime < this.skipTime) {
						this.curr().currentTime = 0;
					} else {
						this.curr().currentTime -= this.skipTime;
					}
				}
			},

			ffw: function() {
				var gap = this.gap;
				var cur = this.curr();
				if (gap) {
					gap.pause();
					if (gap.remain/1000 < this.skipTime) {
						this.endGap();
					} else {
						gap.remain -= this.skipTime*1000;
					}
					gap.go();
				} else {
					var left = cur.currentTime;
					if (cur.currentTime < this.skipTime) {
						cur.currentTime = 0;
					} else {
						cur.currentTime -= this.skipTime;
					}
				}
			},

			// on audio element ended - commence gap
			queueNext: function() {
				this.current++;
				if (this.current < this.sequence.length) {
					this.gap = new Gap(this, this.gapTime);
				} else {
					this.current = 0;
					this.pause();
					swipe.slide(2, 600); // go to credits page
				}
			},

			// on completion of gap
			endGap: function() {
				this.gap = null;
				this.curr().play();
			},

			seek: function() {
				var dots = "";
				var time = 10*this.curr().currentTime/this.curr().duration;
				for (var i = 0; i < time; i ++) {
					dots += '.';
				}
				loadBar.html(dots);
			},

			audioLoaded: function(i) {
				this.loadCount += i;
				consoleLog("Loaded audio item " + i.toString() + ". Load count: " + this.loadCount.toString());
				console.log("Loaded audio item " + i.toString() + ". Load count: " + this.loadCount.toString());
				// when loadCount == arithmetic of playlist length, we know we've received all indices
				// hence, globally we can play through
				if (this.loadCount == this.loadTotal) {
					consoleLog("calling allAudioLoaded()");
					console.log("calling allAudioLoaded()");
					allAudioLoaded();
				}
			}
		};

		function allAudioLoaded() {
			consoleLog("allAudioLoaded called");
console.log("allAudioLoaded called");
			playButton.removeClass('loading');
			playButton.addClass('play');
			$('.play').click(function() { player.playPause(); });
			$('#rew').click(function() { player.rew(); })
		}

		function getFileName(affix) {
			var dir = "<?php echo get_stylesheet_directory_uri().'/'; ?>audio/"
			var prefix = "Test-samp-";
			var postfix = ".mp3";
			return dir + prefix + affix + postfix;
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
						<a onclick="swipe.next()" href="#">Click here to continue &gt;</a>
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
				<div class="loadBar">.</div>
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