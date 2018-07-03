const undynamicFullNarration = 'main-narration'
const dynamicNarration = 'trees-are-fags-v2'

// object manages pool of shuffled cues (as filenames only)
function Cues(total) {
    console.log("Cues created");

    this.fileList = [];
    this.total = total;
    this.used = 0; // how many cues have been given away

    // initialise filenames array
    for (let i = 0; i < this.total; ++i) {
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

function Cue(start) {
    this.start = start; // start time in main narration timeline
    this.active = false;
    this.hasLoaded = false;

    const file = cues.getNextCue();
    console.log("Creating Cue on file = " + file);
    this.audio = new Audio(file);
    this.audio.preload = 'auto';

    this.audio.addEventListener('ended', () => { this.ended(); });
    this.audio.addEventListener('canplaythrough', () => { this.loaded(); });
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
        this.audio.pause();
        // calculate end time in main timeline
        this.end = this.start + this.audio.duration;
        console.log("Cue start: " + this.start + " Cue end: " + this.end);
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
        const myCueNumber = player.curCue();
        console.log('Cue ' + myCueNumber + ' triggered');
    },

    // played through (onEnded)
    ended: function() {
        console.log('Cue ended');
        this.audio.pause();
        this.active = false;
        player.nextCue();
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
    this.waitForCue = false;
    this.waitLoad = false;
    this.skipTime = skipTime;
    this.startTime = startTime;

    // initialise main narration audio
    this.narration = new Audio(getFileName(dynamicNarration));
    this.narration.preload = "auto";
    this.narration.addEventListener('canplaythrough', () => { this.loaded();         });
    this.narration.addEventListener('timeupdate',     () => { this.seek();           });
    this.narration.addEventListener('ended',          () => { this.ended();          });
    this.narration.addEventListener('waiting',        () => { this.audioWaiting();   });
    this.narration.addEventListener('playing',        () => { this.audioUnwaiting(); });
    this.narration.addEventListener('canplaythrough', () => { this.loaded();         });
    this.narration.addEventListener('timeupdate',     () => { this.seek();           });
    this.narration.addEventListener('ended',          () => { this.ended();          });
    this.narration.addEventListener('waiting',        () => { this.audioWaiting();   });
    this.narration.addEventListener('playing',        () => { this.audioUnwaiting(); });

    // initialise cues
    this.cues = [];
    this.currentCueNumber = -1; // index of next/current cue (will be incremented on first call of nextCue)
    const listlen = playlist.length;
    for (let i = 0; i < listlen; i++) {
        const newCue = new Cue(playlist[i]);
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
        const cue = this.curCue();
        if (cue && cue.active) {
            return cue;
        } else {
            return null;
        }
    },

    play: function() {
        this.playing = true;
        if (!this.waitForCue) this.narration.play();
        const cue = this.activeCue();
        if (cue) cue.play();
        playButton.addClass('pause');
    },

    pause: function() {
        this.playing = false;
        if (!this.waitForCue) this.narration.pause();
        const cue = this.activeCue();
        if (cue) cue.pause();
        playButton.removeClass('pause');
    },

    // method called directly by pressing the play/pause button
    playPause: function() {
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
        let newNarrationTime = this.narration.currentTime + amount;
        // skip in current active cue
        let cue = this.curCue();
        if (cue) {
            if (cue.active) {
                // are we stepping out of the time bounds of currently playing cue?
                if (newNarrationTime < cue.start) {
                    cue.deactivate();
                } else if (newNarrationTime > cue.end) {
                    cue.ended();
                } else {
                    cue.audio.currentTime = newNarrationTime - cue.start;
                }
            } else if (newNarrationTime > cue.start) {
                // should a cue be triggered now?
                cue.go();
                cue.audio.currentTime = newNarrationTime - cue.start;
            }
        }
        // are we skipping back into a previous cue?
        if (this.currentCueNumber > 0 && newNarrationTime < this.cues[this.currentCueNumber-1].end) {
            this.currentCueNumber--;
            cue = this.curCue();
            cue.go();
            cue.audio.currentTime = newNarrationTime - cue.start;
        }
        // skip in narration
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
        const cue = this.curCue();
        if (cue && !cue.active && this.narration.currentTime >= cue.start) { // test we haven't run out of cues yet
            cue.go();
        }
        timeline.draw(this.narration.currentTime / this.narration.duration);
    },

    nextCue: function() {
        this.currentCueNumber++;
        const cue = this.curCue();
        if (cue) {
            // cue.setup();
        }
    },

    // finished playing through
    ended: function() {
        this.pause();
        const cue = this.activeCue();
        if (cue) cue.deactivate();
        this.waitForCue = false;
        this.currentCueNumber = 0;
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
            window.setTimeout( () => { this.narration.pause(); }, 5);
            const cueLen = this.cues.length;
            for (let i = 0; i < cueLen; i++) {
                this.cues[i].preload();
            }
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
        }
    },
};

function getFileName(affix) {
    const dir = "audio/"; // directory/URI
    const prefix = "cue-"; // prefix if cue (ie. if passed a number)
    const postfix = ".mp3"; // format
    if (typeof affix === 'number') { // if passed a number, it's a cue
        if (affix < 10) affix = '0' + affix; // 0-padding
        return dir + prefix + affix + postfix;
    } else { // if passed text, that's the name of the main narration file
        return dir + affix + postfix;
    }
}

// based on Knuth Shuffle (https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array)
function shuffleArray(arr) {
    let currentIndex = arr.length;
    let randomIndex, temp;

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
