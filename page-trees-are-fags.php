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
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            height: 220px;
            width: auto;
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

		//
		var playButton;
		var swipe;

		var cues;
		var player;

		$(document).ready(init);

		function init() {
		    // setup jQuery refs
			playButton = $('#playpause');
			playButton.click(function() { player.playPause(); });

            // setup player
            var cuePoolTotal = 15; // total number of phrases in the pool
            cues = new Cues(cuePoolTotal);
            // DEFINE CUE POSITIONS HERE
            var playlist = [
                [261.26, 291.1], [356.18, 375.36], [430.94, 454.95], [529.53, 544.28], [647.82, 671.1], [746.13, 759.9],
                [888.74, 925.97], [1022.92, 1053.32]
            ];
            var startTime = 200;
            var skipTime = 5; // seconds to skip forward/back using buttons
            player = new Player(startTime, playlist, skipTime);
            // initialise timeline
            var tlDim = [600, 250];
            var tlPad = 10;
            var tlOffset = 100;
            var tlInt = 0.05;
            var tlWeight = 3;
            timeline = new Timeline(tlDim, tlPad, tlOffset, tlInt, tlWeight);
            // setup swipe
			swipe = new Swipe($('#slider')[0], {
				speed: 700, // of transition (ms)
				continuous: false, // ie. don't cycle back round
				disableScroll: true,
                draggable: true, // swipe on desktop
                callback: function(index, elem, dir) {
				    // ensure that preload starts on first swipe (for iOS)
				    if (index === 0 && dir === -1) {
				        player.preload();
                    }
                }
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
            window.setInterval(function() {
                console.log("Play position: " + player.narration.currentTime);
            }, 10000);
		}

		// object manages pool of shuffled cues (as filenames only)
		function Cues(total) {
			console.log("Cues created");

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
			this.getNextCue = function() {
				if (this.used < this.total) {
					return this.fileList[this.used++];
				}
			};
		}

		function Cue(triggers) {
			this.start = triggers[0]; // start time in main narration timeline
            this.waitTrigger = triggers[1]; // start time in main narration timeline
			this.active = false;
			this.hasLoaded = false;

			var file = cues.getNextCue();
			this.audio = new Audio(file);
			this.audio.preload = 'auto';

            var me = this; // binding
            this.audio.addEventListener('ended', function() { me.ended(); });
			this.audio.addEventListener('canplaythrough', function() { me.loaded(); });
		}

		Cue.prototype =
        {
            loaded: function() {
                console.log('Cue loaded');
                this.hasLoaded = true;
                if (player.waitLoad) {
                    player.audioUnwaiting();
                }
            },

            preload: function() {
                this.audio.play();
                var me = this;
                window.setTimeout(function() { me.audio.pause(); }, 5);
                // calculate end time in main timeline
                this.end = this.start + this.audio.duration;
                if (this.end > this.waitTrigger) {
                    player.waitTime += this.end - this.waitTrigger;
                    player.virtualDuration += this.end - this.waitTrigger;
                }
                console.log("Cue start: " + this.start + " Cue end: " + this.end + " Wait trigger: " + this
                    .waitTrigger);
            },

			play: function() {
			    if (!this.hasLoaded) {
			        player.audioWaiting();
                } else if (this.audio) {
					this.audio.play();
				}
			},

			pause: function() {
				this.audio.pause();
			},

			go: function() {
				this.active = true;
				this.play();
				var myCueNumber = player.curCue();
				console.log('Cue ' + myCueNumber + ' triggered');
			},

            // played through (onEnded)
			ended: function() {
                console.log('Cue ended');
                this.audio.pause();
				this.active = false;
				player.nextCue();
				timeline.newBranch();
			},

			deactivate: function() {
                console.log('Cue deactivated');
				if (this.active) {
					this.active = false;
                    this.audio.pause();
					this.audio.currentTime = 0;
				}
			}

		};

		// main Player object
		function Player(startTime, playlist, skipTime) {

			this.skipTime = skipTime;
			this.playing = false;
            this.preloaded = false;
			this.waitingForCue = false;
			this.waitLoad = false;
            this.startTime = startTime;
            // for keeping track of global time while waiting for cues
            this.virtualTime = 0;
            this.virtualDuration = 0;
            this.waitTime = 0;

			// initialise main narration audio
			this.narration = new Audio(getFileName("main-narration"));
			this.narration.preload = "auto";
			var me = this; // retain binding
            this.narration.addEventListener('canplaythrough', function() { me.loaded(); });
			this.narration.addEventListener('timeupdate', function() { me.seek(); });
			this.narration.addEventListener('ended', function() { me.ended(); });
			this.narration.addEventListener('waiting', function() { me.audioWaiting(); });
			this.narration.addEventListener('playing', function() { me.audioUnwaiting(); });

			// initialise cues
			this.cues = [];
			this.currentCueNumber = -1; // index of next/current cue (will be incremented on first call of nextCue)
			var listlen = playlist.length;
			for (var i = 0; i < listlen; i++) {
			    var newCue = new Cue(playlist[i]);
				this.cues.push(newCue);
				console.table(this.cues[i]);
			}
			this.nextCue();
		}

		Player.prototype =
        {
			// get currently queued cue (or null)
			curCue: function() {
				if (this.currentCueNumber >= this.cues.length) {
					return null;
				}
				return this.cues[this.currentCueNumber];
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
				if (!this.waitingForCue) this.narration.play();
				var cue = this.activeCue();
				if (cue) cue.play();
				playButton.addClass('pause');
			},

			pause: function() {
				this.playing = false;
				if (!this.waitingForCue) this.narration.pause();
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

            // skip forwards/backwards [amount] secs
			skip: function(amount) {
				var newNarrationTime = this.narration.currentTime + amount;
				// skip in current active cue
				var cue = this.curCue();
				if (cue) {
					if (cue.active) {
					    // are we stepping out of the time bounds of currently playing cue?
						if (newNarrationTime < cue.start) {
							cue.deactivate();
						} else if (newNarrationTime > cue.end) {
							cue.ended();
						} else {
							cue.audio.currentTime += newNarrationTime - this.narration.currentTime;
						}
					} else if (newNarrationTime > cue.start) {
					    // should a cue be triggered now?
						cue.go();
						cue.audio.currentTime += newNarrationTime - this.narration.currentTime;
					}
				}
				// are we skipping back into a previous cue?
				if (this.currentCueNumber > 0 && newNarrationTime < this.cues[this.currentCueNumber-1].end) {
					this.currentCueNumber--;
					cue = this.curCue();
					cue.go();
					cue.audio.currentTime += newNarrationTime - this.narration.currentTime;
				}
				// skip in narration
				if (newNarrationTime > this.narration.duration) {
					this.ended();
				} else if (newNarrationTime < 0) {
				    this.narration.currentTime -= this.narration.currentTime;
                } else {
                    this.narration.currentTime += newNarrationTime - this.narration.currentTime;
                }
				console.log("Skipped to: " + this.narration.currentTime);
			},

			rew: function() {
				this.skip(-this.skipTime);
			},

			ffw: function() {
				this.skip(this.skipTime);
			},

			seek: function() {
			    // TODO: virtualTime update
				var cue = this.curCue();
				timeline.draw(this.narration.currentTime / this.narration.duration);
				// launch next cue if we've come to its trigger
				if (cue && !cue.active && this.narration.currentTime >= cue.start) {
					cue.go();
				}
                // if we've come to the end of cue time in narration, pause to wait for cue to end
                else if (cue.active && !this.waitingForCue && this.narration.currentTime >= cue.waitTrigger) {
                    this.waitForCue();
                }
			},

            nextCue: function() {
			    if (this.waitingForCue) {
			        this.waitingForCue = false;
			        this.narration.play();
                }
			    this.currentCueNumber++;
                var cue = this.curCue();
                if (cue) {
                    // cue.setup();
                }
            },

            waitForCue: function() {
			    if (!this.waitingForCue) {
                    console.log('Waiting for cue');
			        this.waitingForCue = true;
			        this.narration.pause();
                }
            },

            // finished playing through
            ended: function() {
                this.pause();
                var cue = this.activeCue();
                if (cue) cue.deactivate();
                this.waitingForCue = false;
                this.currentCueNumber = 0;
                this.narration.currentTime -= this.narration.currentTime;
                swipe.slide(2, 600); // go to credits page
            },

            // play and pause each audio asset to trigger preloading on iOS
            // (needs to be in an on-click event)
            preload: function() {
			    if (!this.preloaded) {
			        this.preloaded = true;
                    // this.narration.currentTime += this.startTime;
                    var cueLen = this.cues.length;
                    for (var i = 0; i < cueLen; i++) {
                        this.cues[i].preload();
                    }
                    this.play();
                    playButton.click(function() { player.playPause(); });
                }
            },

            // for onLoaded event
            loaded: function() {
			    console.log('loaded');
                playButton.removeClass('loading');
                this.virtualDuration += this.narration.duration;
                $('#rew').click(function() { player.rew(); });
                $('#ffw').click(function() { player.ffw(); });
            },

            // if any element that needs to play now hasn't loaded (or onWaiting events)
            audioWaiting: function() {
                if (!this.waitLoad) {
                    this.waitLoad = true;
                    this.pause();
                    this.playing = false;
                    playButton.addClass('loading');
                }
            },

            // onPlaying - once an element that needs to play now has loaded
            audioUnwaiting: function() {
                if (this.waitLoad /* and... */) {
                    this.waitLoad = false;
                    playButton.removeClass('loading');
                    if (this.playing) this.play();
                }
            },
		};

		function Timeline(dimensions, pad, offsetY, segInterval, weight)
        {
            this.pad = pad;
            this.offsetY = offsetY;
            this.segInterval = segInterval;
            this.lastValue = 0;
            this.dim = dimensions;

            this.start = [this.pad, this.dim[1] - this.pad];
            this.end = [this.dim[0] - this.pad, this.dim[1] - this.offsetY];
            this.range = this.end[1] - this.pad;

            // line segments
            this.segs = [];
            var firstPoint = this.start;
            firstPoint.push(0);
            var firstSeg = [firstPoint, this.pickPoint(this.segInterval)];
            this.segs.push(firstSeg);

            // and finally, our canvas
            this.canvas = $('#timeline')[0].getContext('2d');
            this.canvas.lineCap = 'round';
            this.canvas.lineWidth = weight;
            this.canvas.strokeStyle = 'rgba(255,255,255,0.9)';
        }

        Timeline.prototype =
        {
            interp: function(pointFrom, pointTo, value) {
                var x = pointFrom[0] + (pointTo[0] - pointFrom[0])*value;
                var y = pointFrom[1] + (pointTo[1] - pointFrom[1])*value;
                return [x, y, value];
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
                var vtp = this.valueToPoint(value);
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
                while (i < this.segs.length) this.segs.pop();
                // draw current segment
                var relativeValue = (value-this.segs[i][0][2])*(this.segs[i][1][2] -
                                    this.segs[i][0][2]);
                canvas.moveTo(this.segs[i][0][0], this.segs[i][0][1]);
                var endPoint = this.interp(this.segs[i][0], this.segs[i][1], relativeValue);
                canvas.lineTo(endPoint[0], endPoint[1]);
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
<!-- <div id="console"></div> -->
<img src="<?php echo get_stylesheet_directory_uri().'/'; ?>imgs/title-large.png" class="title" alt="Trees Are Fags" />
<div class="timeline"><canvas id="timeline" width="600" height="250"></canvas></div>
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
                <div class="controlButton play loading" id="playpause"></div>
                <div class="controlButton" id="ffw"></div>
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