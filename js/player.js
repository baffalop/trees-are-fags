const undynamicFullNarration = 'main-narration'
const dynamicNarration = 'main-narration-no-cues'

// object manages pool of shuffled cues (as filenames only)
function Cues(total)
{
    this.fileList = [];
    this.total = total;
    this.used = 0; // how many cues have been given away

    // initialise filenames array
    for (let i = 0; i < this.total; ++i) {
        this.fileList[i] = getFileName(i);
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

function Cue(start, end)
{
    this.start = start; // start time in main narration timeline
    this.waitTime = end;
    this.active = false;
    this.hasLoaded = false;
    this.virtualEndTime = 0;

    const file = cues.getNextCue();
    this.audio = new Audio(file);
    this.audio.preload = 'auto';

    this.audio.addEventListener('canplaythrough', () => { this.loaded(); });
    this.audio.addEventListener('ended', () => { this.ended(); });
    this.audio.addEventListener('waiting', () => { player.needToLoad(this); });
}

Cue.prototype =
{
    getOverlap: function getOverlap()
    {
        return Math.max(0, this.waitTime - this.start);
    },

    getWaitInterval: function getWaitInterval()
    {
        return Math.max(0, this.audio.duration - this.getOverlap());
    },

    getElapsedWait: function getElapsedWait()
    {
        const doneTime = this.start + this.audio.currentTime;
        return Math.max(0, doneTime - this.waitTime);
    },

    loaded: function loaded()
    {
        this.hasLoaded = true;
        player.loaded(this);
    },

    prepareLoad: function prepareLoad()
    {
      this.audio.muted = true;
      this.audio.play();

      window.setTimeout(() => {
        this.audio.pause();
        this.audio.muted = false;
        this.audio.currentTime = 0;
      }, 100);

      const calculateVirtualEndTime = () => { this.virtualEndTime = this.start + this.audio.duration; };

      if (this.audio.readyState >= this.audio.HAVE_METADATA) {
        calculateVirtualEndTime();
      } else {
        this.audio.addEventListener('loadedmetadata', calculateVirtualEndTime);
      }
    },

    play: function play()
    {
        if (!this.hasLoaded) {
            player.needToLoad(this);
        } else if (this.audio) {
            this.audio.play();
        }
    },

    pause: function pause()
    {
        this.audio.pause();
    },

    go: function go()
    {
        this.active = true;
        if (player.playing) this.play();
    },

    // played through (onEnded)
    ended: function ended()
    {
        this.active = false;
        this.audio.currentTime = 0;
        player.resume();
        player.nextCue();
    },

    deactivate: function deactivate()
    {
        if (this.active) {
            this.active = false;
            this.audio.pause();
            this.audio.currentTime = 0;
        }
    }

};

// main Player object
function Player(startTime, playlist, skipTime)
{
    this.skipTime = skipTime;
    this.playing = false;
    this.preloaded = false;
    this.waitForCue = false;
    this.waitLoad = false;
    this.startTime = startTime;
    this.loadPool = new Set();

    // initialise main narration audio
    this.narration = new Audio(getFileName(dynamicNarration));
    this.narration.preload = "auto";
    this.narration.addEventListener('canplaythrough', () => { this.loaded(this);     });
    this.narration.addEventListener('timeupdate',     () => { this.seek();           });
    this.narration.addEventListener('ended',          () => { this.ended();          });
    this.narration.addEventListener('waiting',        () => { this.needToLoad(this); });
    this.narration.addEventListener('playing',        () => { this.loaded(this);     });

    // initialise cues
    this.cues = [];
    this.currentCueNumber = -1; // index of next/current cue (will be incremented on first call of nextCue)
    for (let item of playlist) {
        this.cues.push(new Cue(item[0], item[1]));
    }
    this.nextCue();
}

Player.prototype =
{
    // get currently queued cue (or null)
    curCue: function curCue()
    {
        if (this.currentCueNumber >= this.cues.length) {
            return null;
        }
        return this.cues[this.currentCueNumber];
    },

    // get currently active cue (or null)
    activeCue: function activeCue()
    {
        const cue = this.curCue();
        if (cue && cue.active) {
            return cue;
        } else {
            return null;
        }
    },

    play: function play()
    {
        this.playing = true;
        if (!this.waitForCue) this.narration.play();
        const cue = this.activeCue();
        if (cue) cue.play();
        playButton.addClass('pause');
    },

    pause: function pause()
    {
        this.playing = false;
        if (!this.waitForCue) this.narration.pause();
        const cue = this.activeCue();
        if (cue) cue.pause();
        playButton.removeClass('pause');
    },

    // method called directly by pressing the play/pause button
    playPause: function playPause()
    {
        if (!this.waitLoad) {
            if (this.playing) {
                this.pause();
            } else {
                this.play();
            }
        }
    },

    rew: function rew()
    {
        if (!this.waitLoad) {
            this.skip(-this.skipTime);
        }
    },

    ffw: function ffw()
    {
        if (!this.) {
            this.skip(this.skipTime);
        }
    },

    skip: function skip(amount)
    {
        const virtualTime = this.getVirtualTime();
        this.setVirtualTime(virtualTime + amount)
    },

    getVirtualTime: function getVirtualTime()
    {
        console.log(`currentTime is ${this.narration.currentTime}`)
        const buff = this.narration.buffered
        console.log(`buffered ${buff.start(0)} to ${buff.end(0)}`)

        let virtualTime = this.narration.currentTime;

        // add wait intervals of all previous cues
        for (let i = 0; i < this.currentCueNumber; i++) {
            const cue = this.cues[i];
            if (cue.getWaitInterval() > 0) {
                virtualTime += cue.getWaitInterval();
            }
        }

        // check if we're waiting on current cue
        if (this.waitForCue) {
            virtualTime += this.activeCue().getElapsedWait();
        }

        return virtualTime;
    },

    setVirtualTime: function setVirtualTime(virtualTime)
    {
        let cue = this.curCue();
        if (cue) cue.deactivate();
        this.currentCueNumber = -1;
        this.resume();

        let realTime = virtualTime;
        while (cue = this.nextCue()) {
            if (cue.start > realTime) {
                break;
            }

            if (cue.virtualEndTime > realTime) {
                cue.audio.currentTime = realTime - cue.start;
                cue.go();
                if (cue.getElapsedWait() > 0) {
                    realTime = cue.waitTime;
                    this.wait();
                }
                break;
            }

            realTime -= cue.getWaitInterval();
        }

        console.log(`Setting current time (${this.narration.currentTime}) to realTime ${realTime}`);
        this.narration.currentTime = realTime;
        console.log(`currentTime result is ${this.narration.currentTime}`);
    },

    seek: function seek()
    {
        const cue = this.curCue();
        if (cue) {
            if (!cue.active && this.narration.currentTime >= cue.start) {
                cue.go();
            } else if (cue.active && this.narration.currentTime >= cue.waitTime) {
                this.wait();
            }
        }
        // timeline.draw(this.narration.currentTime / this.narration.duration);
    },

    nextCue: function nextCue()
    {
        this.currentCueNumber++;
        return this.curCue();
    },

    wait: function wait()
    {
        if (!this.waitForCue) {
            this.waitForCue = true;
            this.narration.pause();
        }
    },

    resume: function resume()
    {
        if (this.waitForCue) {
            this.waitForCue = false;
            if (this.playing) this.narration.play();
        }
    },

    // finished playing through
    ended: function ended()
    {
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
    prepareLoad: function prepareLoad()
    {
        if (this.preloaded) {
          return;
        }

        this.preloaded = true;

        this.narration.muted = true;
        this.narration.play();

        window.setTimeout( () => {
          this.narration.pause();
          this.narration.muted = false;
          this.narration.currentTime = this.startTime;
        }, 100);

        for (let cue of this.cues) {
          cue.prepareLoad();
        }
    },

    loaded: function loaded(obj)
    {
        this.loadPool.delete(obj);
        if (!this.isWaiting()) {
            this.waitLoad = false;
            playButton.removeClass('loading');
        }
    },

    waitForLoad: function waitForLoad()
    {
        this.narration.pause();
        playButton.addClass('loading');
    },

    isWaiting: function isWaiting()
    {
        return this.loadPool.size > 0;
    },

    needToLoad: function needToLoad(obj)
    {
        const wasWaiting = this.isWaiting();
        this.loadPool.add(obj);
        if (!wasWaiting) {
          this.waitForLoad();
        }
    }
};

function getFileName(affix)
{
    const cdn = "https://cdn.getforge.com/trees-are-fags.eu/" + getCdnCode();
    const dir = "/audio/"; // directory/URI
    const prefix = "cue-"; // prefix if cue (ie. if passed a number)
    const postfix = ".mp3"; // format
    if (typeof affix === 'number') { // if passed a number, it's a cue
        if (affix < 10) affix = '0' + affix; // 0-padding
        return cdn + dir + prefix + affix + postfix;
    } else { // if passed text, that's the name of the main narration file
        return cdn + dir + affix + postfix;
    }
}

// based on Knuth Shuffle (https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array)
function shuffleArray(arr)
{
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
