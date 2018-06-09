<?php
/*
Template Name: Trees Are Fags
*/
?>
<html>
<head>
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
            top: 20px;
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
            background: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/background-large.jpeg);
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

        .title {
            position: relative;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            height: 220px;
            width: auto;
            max-width: 100%;
        }

        .timeline {
            position: absolute;
            top: 400px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* player controls */
        .controls {
            position: relative;
            float: top;
            max-width: 100%;
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
            background-size: 100% auto; /* image resized to div size */
            background-repeat: no-repeat;
            background-position: center;
        }

        .controlButton:active {
            opacity: 0.5;
        }

        #ffw {
            background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/wind-fw.png);
            background-position: 100%;
        }

        #rew {
            background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/wind-back.png);
            background-position: 0%;
        }

        #playpause {
            background-image: url(<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/play-pause-wait.png);
            width: 180px;
            background-size: 100% auto;
        }

        .play {
            cursor: pointer;
            background-position: 50% 0px;
        }

        .pause {
            background-position: 50% -100px;
        }

        .loading {
            background-position: 50% -200px;
            cursor: default;
            animation: throb 2.3s infinite;
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
            max-width: 430px;
            padding: 10px;
            margin: 10px;
            margin-bottom: 30px;
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

        @keyframes throb {
            0% {opacity:  1.0;}
            /*5%{opacity:  1.0;}*/
            50%{opacity:  0.0;}
            /*95%{opacity:  1.0;}*/
            100%{opacity: 1.0;}
        }

    </style>
	<script type="text/javascript"
	src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript"
	src="<?php echo get_stylesheet_directory_uri().'/'; ?>js/swipe.min.js"></script>
	<script type="text/javascript">

		var playButton;
		var swipe;
		var player;

		$(document).ready(init);

		function init() {
		    // setup jQuery refs
			playButton = $('#playpause');

            // setup player
            var startTime = 0;
            var skipTime = 10; // seconds to skip forward/back using buttons
            player = new Player(startTime, skipTime);
            playButton.click( () => { player.playPause(); });
            $('#rew').click( () => { player.rew(); });
            $('#ffw').click( () => { player.ffw(); });

            // initialise timeline
            var tlSettings = {
                dimensions  : [601, 250],
                pad         : 10,
                offsetY     : 100,
                segInterval : 0.01,
                lineWeight  : 3
            };
            // timeline = new Timeline(tlSettings);

            // setup swipe
            var swipeElement = $('#slider');
			swipe = new Swipe(swipeElement[0], {
				speed: 700, // of transition (ms)
				continuous: false, // ie. don't cycle back round
				disableScroll: false
			});
			swipe.setup();

            // setup left and right keys (for closure)
            var Key = {
                LEFT: 37,
                RIGHT: 39
            };
            // arrow keys to move between swipe pages
			window.addEventListener('keydown', function(e) {
				if (!e) e = window.event;
				// setup keys
				var keycode = e.keyCode || e.which;
				// test keys
				if (keycode === Key.LEFT) {
					swipe.prev();
				} else if (keycode === Key.RIGHT) {
				    if (swipe.getPos() === 0) player.preload();
					swipe.next();
				}
			});
			// report seek time
            window.setInterval( () => {
                console.log("Play position: " + player.narration.currentTime);
            }, 10000);
		}

		function Player(startTime, skipTime) {

			this.playing = false;
			this.waitLoad = true;
            this.preloaded = false;
            this.skipTime = skipTime;
            this.startTime = startTime;

			// initialise main narration audio
			this.narration = new Audio(getFileName("main-narration-updated"));
			this.narration.preload = "auto";
            this.narration.addEventListener('canplaythrough',  () => { this.loaded(); });
			this.narration.addEventListener('timeupdate',  () => { this.seek(); });
			this.narration.addEventListener('ended',  () => { this.ended(); });
			this.narration.addEventListener('waiting',  () => { this.audioWaiting(); });
			this.narration.addEventListener('playing',  () => { this.audioUnwaiting(); });
		}

		Player.prototype =
        {
			play: function() {
                this.playing = true;
				this.narration.play();
				playButton.addClass('pause');
			},

			pause: function() {
				this.playing = false;
				this.narration.pause();
				playButton.removeClass('pause');
			},

			// method called directly by pressing the play/pause button
			playPause: function() {
			    if (!this.preloaded) {
			        this.preload();
			        this.play();
			        playButton.addClass('loading');
                }
                if (!this.waitLoad) {
                    if (this.playing) {
                        this.pause();
                    } else {
                        this.play();
                    }
                }
			},

            // skip forwards/backwards [amount] secs
			skip: function(amount) {
			    var newNarrationTime = this.narration.currentTime + amount;
				if (newNarrationTime > this.narration.duration) {
					this.ended();
				} else if (newNarrationTime < 0) {
				    this.narration.currentTime = 0;
                } else {
                    this.narration.currentTime = newNarrationTime;
                }
				console.log("Skipped to: " + this.narration.currentTime);
			},

			rew: function() {
			    if (!this.waitLoad) {
			        console.log('REwinding');
                    this.skip(-this.skipTime);
                }
			},

			ffw: function() {
			    if (!this.waitLoad) {
                    console.log('FFWing');
                    this.skip(this.skipTime);
                }
			},

			seek: function() {
				// timeline.draw(this.narration.currentTime / this.narration.duration);
			},

            // finished playing through
            ended: function() {
                this.pause();
                this.narration.currentTime = 0;
                swipe.slide(2, 600); // go to credits page
            },

            // play and pause each audio asset to trigger preloading on iOS
            // (needs to be in an on-click event)
            preload: function() {
			    if (!this.preloaded) {
			        this.preloaded = true;
                    this.narration.currentTime = this.startTime;
                    this.narration.play();
                    if (!this.playing) window.setTimeout( () => { this.narration.pause(); }, 50);
                }
            },

            // for canplaythrough event
            loaded: function() {
			    console.log('loaded');
			    if (this.waitLoad) {
			        this.audioUnwaiting();
                }
            },

            // if any element that needs to play now hasn't loaded (or onWaiting events)
            audioWaiting: function() {
                if (!this.waitLoad) {
                    this.waitLoad = true;
                    playButton.addClass('loading');
                }
            },

            // onPlaying - once an element that needs to play now has loaded
            audioUnwaiting: function() {
                if (this.waitLoad) {
                    this.waitLoad = false;
                    playButton.removeClass('loading');
                    if (this.playing) this.narration.play();
                }
            },
		};

		function Timeline(settings)
        {
            this.pad = settings.pad;
            this.offsetY = settings.offsetY;
            this.segInterval = settings.segInterval;
            this.lastValue = 0;
            this.dim = settings.dimensions;

            this.start = [this.pad, this.dim[1] - this.pad];
            this.end = [this.dim[0] - this.pad, this.start[1] - this.offsetY];
            this.range = this.end[1] - this.pad;

            // setup first line segment
            this.segs = [];
            var firstPoint = this.start;
            firstPoint.push(0); // 2th index is time value
            var secondPoint = this.pickPoint(this.segInterval);
            var firstSeg = [firstPoint, secondPoint];
            this.segs.push(firstSeg);

            // and finally, our canvas
            this.canvas = $('#timeline')[0].getContext('2d');
            this.canvas.lineCap = 'round';
            this.canvas.lineWidth = settings.lineWeight;
            this.canvas.strokeStyle = 'rgba(255,255,255,0.9)';
        }

        Timeline.prototype =
        {
            interp: function(pointFrom, pointTo, value) {
                var vector = [pointTo[0] - pointFrom[0], pointTo[1] - pointFrom[1]];
                var x = pointFrom[0] + value * vector[0];
                var y = pointFrom[1] + value * vector[1];
                var result = [x, y, value];
                return result;
            },

            // interpolate for whole timeline
            valueToPoint: function(value) {
                return this.interp(this.start, this.end, value);
            },

            // pick a new point at [value] along the line, with Y wiggle
            pickPoint: function(value) {
                var point = this.valueToPoint(value); // choose a point along the baseline
                point[1] -= Math.random()*this.range; // add a random Y offset
                return point;
            },

            // commence a new segment of the line
            newSeg: function() {
                var lastSeg = this.segs.length - 1;
                var joint = this.segs[lastSeg][1]; // make end of last seg start of new one
                var newValue = this.segs[lastSeg][1][2] + this.segInterval;
                var segEnd = this.pickPoint(newValue);
                this.segs.push([joint, segEnd]);
            },

            // commence a new segment, broken off from the previous one
            newBranch: function() {
                this.newSeg();
                var lastSeg = this.segs.length-1;
                this.segs[lastSeg][0] = this.pickPoint(this.segs[lastSeg][0][2]);
            },

            draw: function(value) {
                var lastSeg = this.segs.length - 1;
                // add new segments to reach value
                while (value > this.segs[lastSeg][1][2]) {
                    this.newSeg();
                    lastSeg ++;
                }
                // get first segment that needs redrawn
                for (var i = lastSeg - 1; i >= 0 && this.lastValue < this.segs[i][1][2]; i--)
                    ;
                i = i < 0 ? 0 : i;
                // clear canvas from segs[lastSeg][0][0] to segs[lastSeg][1][0]
                this.canvas.clearRect(this.segs[i][0][0], 0, this.dim[0], this.dim[1]);
                // fill in all previously unrendered whole segments
                for (; i < lastSeg && value < this.segs[i][1][2]; i ++) {
                    this.canvas.moveTo(this.segs[i][0][0], this.segs[i][0][1]);
                    this.canvas.lineTo(this.segs[i][1][0], this.segs[i][1][1]);
                    this.canvas.stroke();
                }
                // remove spare segments
                while (i < this.segs.length - 1) this.segs.pop();
                // draw current segment
                var relativeValue = (value-this.segs[i][0][2]) / (this.segs[i][1][2] - this.segs[i][0][2]);
                var endPoint = this.interp(this.segs[i][0], this.segs[i][1], relativeValue);
                this.canvas.moveTo(this.segs[i][0][0], this.segs[i][0][1]);
                this.canvas.lineTo(endPoint[0], endPoint[1]);
                this.canvas.stroke();
                this.lastValue = value;
            }
        };

		// for 'continue' button - swipe to next page and preload
		function nextAndLoad() {
			player.preload(); // trigger preloading on iOS
			swipe.next();
		}

		function getFileName(affix) {
			var dir = "<?php echo get_stylesheet_directory_uri().'/'; ?>audio/"; // directory/URI
			var prefix = "cue-"; // prefix if cue (ie. if passed a number)
			var postfix = ".mp3"; // format
			if (typeof affix === 'number') { // if passed a number, it's a cue
				if (affix < 10) affix = '0' + affix; // 0-padding
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
			while (currentIndex !== 0) {
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
<!-- <div id="console"></div>-->
<img src="<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/title-large-nounderline.png" class="title" alt="Trees
Are Fags" />
<!--<div class="timeline"><canvas id="timeline" width="600" height="250"></canvas></div>-->
<div id="slider" class="swipe">
    <div class="swipe-wrap">
        <!-- ................page 1...........-->
        <div class="page">
            <div class="text-body">
                <p>
                    This is a guided encounter with a tree, so it is best that you listen in the company of trees. You will be led through a series of observations, reflections and movements in relation to trees. The encounter lasts just over 20 minutes. So find a spot among trees, turn off the ringer of your device, and when you are ready, swipe to the next screen to begin <em>Trees Are Fags</em>.
                </p>
                <p align="right">
                    <a onclick="nextAndLoad()" href="#">Continue &gt;</a>
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
        </div>
        <!-- ................page 3...........-->
        <div class="page">
            <div class="text-body">
                <p>
                    <em>Trees are Fags</em> is an encounter created in 2018 by Benny Nemerofsky Ramsay, programmed and sound designed by Nikita Gaidakov. The piece features the voices of Matt Carter, Oskar Kirk Hansen, Bastien Pourtout, Edward Twaddle, Alberta Whittle and Virginia Woolf. Fragments from Sofia Gubaidulina’s <em>Sonata for Two Bassoons</em> (1977) was performed by Ronan Whittern. <em>Trees are Fags</em> was commissioned by Lux and co-produced by the <em>Cruising the Seventies</em> research team at the Edinburgh College of Art. For more information on Benny Nemerofsky Ramsay, visit <a href="http://www.nemerofsky.ca">www.nemerofsky.ca</a>.
                </p>
                <!-- TODO: increase space between <p>s via CSS -->
                <p align="center"><img src="<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/logos.png" width="150" /></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>