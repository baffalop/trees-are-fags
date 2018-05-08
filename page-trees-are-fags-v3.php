<?php
/*
Template Name: Trees Are Fags
*/
?>
<html>
<head>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<!--<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_uri(); ?>">-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link href="https://fonts.googleapis.com/css?family=EB+Garamond" rel="stylesheet">
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
            /*align-items: center;*/
        }

        .page {
            top: 50px;
            /*width: 100%;*/
            /*display: flex;*/
            /*flex-direction: column;*/
            /*justify-content: center;*/
        }

        html,body {
            overflow-x: hidden;
            height: 100%;
            margin: 0px;
            padding: 0px;
            background: url(imgs/bg.jpg);
            background-size: cover;
            background-position: 50% 0%;
        }

        body {
            position: relative;
            font-family: 'EB Garamond', serif;
            color: white;
            font-size: large;
            line-height: 1.4;
        }

        /* player controls */
        .title {
            position: relative;
            width: 100%;
            height: 220px;
            top: 40px;
            display: flex;
            justify-content: center;
        }

        .controls {
            position: relative;
            float: top;
            width: 600px;
            height: 250px;
            display: flex;
            align-items: center;
            flex-direction: row;
            justify-content: space-around;
        }

        .controlButton {
            cursor: pointer;
            height: 100px;
            width: 100px;
            background: transparent;
            background-size: auto 100%; /* image resized to div size */
            background-repeat: no-repeat;
            background-position: center;
        }

        .controlButton:active {
            background-size: auto 95%;
        }

        #ffw {
            background-image: url(imgs/ffw.png);
        }

        #rew {
            background-image: url(imgs/rew.png);
        }

        #playpause {
            background-image: url(imgs/play-pause-text.png);
            background-size: 100% auto;
            width: 350px;
        }

        #playpause:active {
            background-size: auto 95%;
        }

        .play {
            cursor: pointer;
            background-position: 50% 0px;
        }

        .play:active {
            background-position: 50% 2px;
        }

        /*.play: active {
            background-position: -5px 50%;
        }*/

        .loading {
            cursor: default;
            opacity: 0.5;
        }

        .pause {
            cursor: pointer;
            background-position: 50% -100px;
        }

        .pause:active {
            cursor: pointer;
            background-position: 50% -95px;
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
            float: bottom;
            max-width: 430;
            padding: 10px;
            margin: 10px;
        }

        a {
            color: #aeaeae;
            text-decoration: none;
        }

        a:hover {
            color: white;
        }

        a:active {
            color: white;
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
				speed: 700,
				continuous: false,
				disableScroll: true,
			});
			swipe.setup();

			// arrow keys to move between swipe pages
			window.addEventListener('keydown', function(e) {
				if (!e) e = window.event;
				// setup keys
				var keycode = e.keyCode || e.which;
				var Key = {
					LEFT: 37,
					RIGHT: 39
				};
				// test keys
				if (keycode == Key.LEFT) {
					swipe.prev();
				} else if (keycode == Key.RIGHT) {
					swipe.next();
				}
			});

			var cuePoolTotal = 15; // total number of phrases in the pool
			var cues = new Cues(cuePoolTotal);
			var playlist = ["intro", 3, "outro"];
			var skipTime = 5; // seconds to skip forward/back using buttons
			player = new Player(playlist, skipTime);
		}

		function Cue(start) {
			this.start = start; // start time in main narration timeline
			this.active = false;
			this.loaded = false;

			var file = cues.getNextCue();
			this.audio = new Audio(file);
			this.audio.preload = 'auto';

			this.end += this.audio.duration; // calculate end time in main timeline
			console.log("Cue start: " + this.start + " Cue end: " + this.end);

            var me = this; // binding
            this.audio.addEventListener('ended', function() { me.ended(); });
			this.audio.addEventListener('canplaythrough', function() { me.loaded(); });
		}

		Cue.prototype = {
			loaded: function() {
				this.loaded = true;
			},

            preload: function() {
			    this.audio.play();
			    this.audio.pause();
            },

			play: function() {
			    if (!this.loaded) {
			        player.audioWaiting();
                } else if (this.audio) {
					this.audio.play();
				}
			},

			pause: function() {
				this.audio.pause();
			},

			go: function() {
				// add handler for not loaded yet
				this.active = true;
				this.play();
			},

			ended: function() {
				this.active = false;
				player.nextCue();
			},

			deactivate: function() {
				if (this.active) {
					this.active = false;
					this.audio.currentTime = 0;
				}
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
		function Player(playlist, skipTime) {

			this.skipTime = skipTime;
			this.playing = false;
			this.waitForCue = false;
			this.waitLoad = false;

			// initialise main narration audio
			this.narration = new Audio(getFileName("main-narration"));
			this.narration.preload = "auto";
			var me = this; // retain binding
			this.narration.addEventListener('timeupdate', function() { me.seek(); });
			this.narration.addEventListener('ended', function() { me.ended(); });
			this.narration.addEventListener('canplaythrough', function() { me.loaded(); });
			this.narration.addEventListener('waiting', function() { me.audioWaiting(); });
			this.narration.addEventListener('playing', function() { me.audioUnwaiting(); });

			// initialise cues
			this.cues = [261, 356, 431.5, 529, 648, 888.8];
			this.curCue = -1; // index of next/current cue (will be incremented on first call of nextCue)
			var listlen = playlist.length;
			for (var i = 0; i < listlen; i++) {
				this.cues.append(new Cue(playlist[i]));
			}
			this.nextCue();
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
			    if (this.waitLoad)
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

            // play and pause each audio asset to trigger preloading on iOS
            // (needs to be in an on-click event)
            preload: function() {
			    this.narration.play();
                this.narration.pause();
                for (var i = 0; i < cueLen; i++) {
                    cues[i].preload();
                }
            },

            // if any element that needs to play now hasn't loaded (or onWaiting events)
            audioWaiting: function() {
			    if (!this.waitLoad) {
			        this.waitLoad = true;
                    this.pause();
                    playButton.removeClass('pause');
                    playButton.addClass('loading');
                }
            },

            // onPlaying - once an element that needs to play now has loaded
            audioUnwaiting: function() {
			    if (this.waitLoad /* and... */) {
			        this.waitLoad = false;
                    playButton.removeclass('loading');
                    playButton.addClass('pause');
                    this.play();
                }
            },

            // skip forwards/backwards [skipTime] secs
			skip: function(amount) {
				var newNarrationTime = this.narration.currentTime + amount;
				// skip in current active cue
				var cue = this.curCue();
				if (cue) {
					if (cue.active) {
						if (newNarrationTime < cue.start) {
							cue.deactivate();
						} else if (newNarrationTime > cue.end) {
							cue.ended();
						} else {
							cue.audio.currentTime = newNarrationTime - cue.start;
						}
					} else if (newNarrationTime > cue.start) { // if cue should be triggered now
						cue.go();
						cue.audio.currentTime = newNarrationTime - cue.start;
					}
				}
				// check previous cue
				if (this.curCue > 0 && newNarrationTime < this.cues[curCue-1].end) {
					this.curCue--;
					cue = this.curCue();
					cue.go();
					cue.audio.currentTime = newNarrationTime - cue.start;
				}
				// skip in narration
				if (newNarrationTime > this.narration.duration) {
					this.ended();
				} else {
					this.narration.currentTime = Math.max(newNarrationTime, 0);
				}
			},

			rew: function() {
				this.skip(-this.skipTime);
			},

			ffw: function() {
				this.skip(this.skipTime);
			},

			seek: function() {
				var cue = this.curCue();
				if (cue && !cue.active && this.narration.currentTime >= cue.start) { // test we haven't run out of cues yet
					cue.go();
				}
			},

			ended: function() {
				this.pause();
				var cue = this.activeCue();
				if (cue) cue.deactivate();
				this.waitForCue = false;
				this.curCue = 0;
				this.narration.currentTime = 0;
				swipe.slide(2, 600); // go to credits page
			}
		};

		// for 'continue' button - swipe to next page and preload
		function nextAndLoad() {
			player.preload(); // trigger preloading on iOS
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
			var dir = "<?php echo get_stylesheet_directory_uri().'/'; ?>audio/"; // directory/URI
			var prefix = "cue-"; // prefix if cue (ie. if passed a number)
			var postfix = ".mp3"; // format
			if (typeof affix == 'number') { // if passed a number, it's a cue
				return dir + prefix + affix + postfix;
			} else { // if passed text, that's the name of the main narration file
				return dir + affix + postfix;
			}
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
<!-- <div id="console"></div> -->
<div class="title">
    <img src="imgs/title.png" alt="Trees Are Fags" />
</div>
<div id="slider" class="swipe">
    <div class="swipe-wrap">
        <!-- ................page 1...........-->
        <div class="page">
            <div class="text-body">
                <p>
                    This is a guided encounter with a tree, so it is best that you listen in the company of trees. You will be led through a series of observations, reflections and movements in relation to trees. The encounter lasts just over 20 minutes. So find a spot among trees, turn off the ringer of your device, and when you are ready, swipe to the next screen to begin <em>Trees Are Fags</em>.
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
                <div class="controlButton play" id="playpause"></div>
                <div class="controlButton" id="ffw"></div>
            </div>
            <div class="text-body">
                <p>
                    <div class="nav">
                        <a onclick="swipe.prev()" href="#">&lt; Intro</a>
                    </div>
                    <div class="nav">
                        <a onclick="swipe.next()" href="#">Credits &gt;</a>
	                </div>
                </p>
            </div>
        </div>
        <!-- ................page 3...........-->
        <div class="page">
            <div class="text-body">
                <p>
                    <em>Trees are Fags</em> is an encounter created in 2018 by Benny Nemerofsky Ramsay, programmed and sound designed by Nikita Gaidakov. The piece features the voices of Matt Carter, Oskar Kirk Hansen, Bastien Pourtout, Edward Twaddle, Alberta Whittle and Virginia Woolf. Fragments from Sofia Gubaidulina’s <em>Sonata for Two Bassoons</em> (1977) was performed by Ronan Whittern. <em>Trees are Fags</em> was commissioned by Lux and co-produced by the <em>Cruising the Seventies</em> research team at the Edinburgh College of Art. For more information on Benny Nemerofsky Ramsay, visit <a href="http://www.nemerofsky.ca">www.nemerofsky.ca</a>.
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>