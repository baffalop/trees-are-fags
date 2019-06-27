const undynamicFullNarration = 'main-narration'
const dynamicNarration = 'main-narration-no-cues'

// object manages pool of shuffled cues (as filenames only)
function Cues(total)
{
  this.fileList = []
  this.total = total
  this.used = 0 // how many cues have been given away

  // initialise filenames array
  for (let i = 0; i < this.total; ++i) {
    this.fileList[i] = getFileName(i)
  }
  // shuffle
  this.fileList = shuffleArray(this.fileList)

  // get next filename from array
  this.getNextCue = function ()
  {
    if (this.used < this.total) {
      return this.fileList[this.used++]
    }
  }
}

function Cue(start, end)
{
  this.start = start // start time in main narration timeline
  this.waitTime = end
  this.active = false
  this.hasLoaded = false
  this.virtualEndTime = 0
  this.mediaState = Media.MEDIA_NONE

  this.startingListener = null

  const file = cues.getNextCue()

  this.audio = new Media(
    file,
    () => console.log('Cue: media success!'),
    errorCode => console.log('Cue: media fail: ' + errorCode),
    statusCode => {
      console.log(`Cue: status ${statusCode}`)
      this.mediaState = statusCode
      switch (statusCode) {
        case Media.MEDIA_NONE:
          player.needToLoad(this)
          break
        case Media.MEDIA_STARTING:
          player.needToLoad(this)
          if (this.startingListener instanceof Function) {
            this.startingListener()
            this.startingListener = null
          }
          break
        case Media.MEDIA_RUNNING:
          this.loaded(this)
          break
        case Media.MEDIA_STOPPED:
          this.ended()
          break
      }
    }
  )
}

Cue.prototype =
  {
    getOverlap: function ()
    {
      return Math.max(0, this.waitTime - this.start)
    },

    getWaitInterval: function ()
    {
      return Math.max(0, this.audio.getDuration() - this.getOverlap())
    },

    getElapsedWait: function (callback)
    {
      this.audio.getCurrentPosition(position => {
        const doneTime = this.start + position
        const elapsedWait = Math.max(0, doneTime - this.waitTime)
        callback(elapsedWait)
      })
    },

    loaded: function ()
    {
      this.hasLoaded = true
      player.loaded(this)
    },

    preload: function ()
    {
      const calculateVirtualEndTime = () => this.virtualEndTime = this.start + this.audio.getDuration()

      if (this.mediaState >= Media.MEDIA_STARTING) {
        calculateVirtualEndTime()
      } else {
        this.startingListener = calculateVirtualEndTime
      }
    },

    play: function ()
    {
      if (!this.hasLoaded) {
        player.needToLoad(this)
      } else if (this.audio) {
        this.audio.play()
      }
    },

    pause: function ()
    {
      this.audio.pause()
    },

    go: function ()
    {
      this.active = true
      if (player.playing) this.play()
    },

    // played through (onEnded)
    ended: function ()
    {
      this.active = false
      this.audio.seekTo(0)
      player.resume()
      player.nextCue()
    },

    deactivate: function ()
    {
      if (this.active) {
        this.active = false
        this.audio.pause()
        this.audio.seekTo(0)
      }
    }

  }

// main Player object
function Player(startTime, playlist, skipTime)
{
  this.skipTime = skipTime
  this.playing = false
  this.preloaded = false
  this.waitForCue = false
  this.startTime = startTime
  this.loadPool = new Set()

  this.narration = new Media(
    dynamicNarration,
    () => console.log('Narration: media success!'),
    errorCode => console.log('Narration: media fail: ' + errorCode),
    statusCode => {
      console.log(`Narration: status ${statusCode}`)
      switch (statusCode) {
        case Media.MEDIA_NONE:
          this.needToLoad(this)
          break
        case Media.MEDIA_STARTING:
          this.needToLoad(this)
          break
        case Media.MEDIA_RUNNING:
          this.loaded(this)
          break
        case Media.MEDIA_STOPPED:
          this.ended()
          break
      }
    }
  )

  // initialise cues
  this.cues = []
  this.currentCueNumber = -1 // index of next/current cue (will be incremented on first call of nextCue)
  for (let item of playlist) {
    this.cues.push(new Cue(item[0], item[1]))
  }
  this.nextCue()
}

Player.prototype =
  {
    // get currently queued cue (or null)
    curCue: function ()
    {
      if (this.currentCueNumber >= this.cues.length) {
        return null
      }
      return this.cues[this.currentCueNumber]
    },

    // get currently active cue (or null)
    activeCue: function ()
    {
      const cue = this.curCue()
      if (cue && cue.active) {
        return cue
      } else {
        return null
      }
    },

    play: function ()
    {
      this.playing = true
      if (!this.waitForCue) this.narration.play()
      const cue = this.activeCue()
      if (cue) cue.play()
      playButton.addClass('pause')
    },

    pause: function ()
    {
      this.playing = false
      if (!this.waitForCue) this.narration.pause()
      const cue = this.activeCue()
      if (cue) cue.pause()
      playButton.removeClass('pause')
    },

    // method called directly by pressing the play/pause button
    playPause: function ()
    {
      this.preload()
      if (!this.isWaiting()) {
        if (this.playing) {
          this.pause()
        } else {
          this.play()
        }
      }
    },

    rew: function ()
    {
      if (!this.isWaiting()) {
        this.skip(-this.skipTime)
      }
    },

    ffw: function ()
    {
      if (!this.isWaiting()) {
        this.skip(this.skipTime)
      }
    },

    skip: function (amount)
    {
      this.getVirtualTime(result => this.setVirtualTime(result + amount))
    },

    getVirtualTime: function (callback)
    {
      this.narration.getCurrentPosition(position => {
        let virtualTime = position

        // add wait intervals of all previous cues
        for (let i = 0; i < this.currentCueNumber; i++) {
          const cue = this.cues[i]
          if (cue.getWaitInterval() > 0) {
            virtualTime += cue.getWaitInterval()
          }
        }

        // check if we're waiting on current cue
        if (this.waitForCue) {
          this.activeCue().getElapsedWait(elapsedWait => callback(virtualTime + elapsedWait))
        } else {
          callback(virtualTime)
        }
      })
    },

    setVirtualTime: function (virtualTime)
    {
      let cue = this.curCue()
      if (cue) cue.deactivate()
      this.currentCueNumber = -1
      this.resume()

      let realTime = virtualTime
      let unWait = false
      while (cue = this.nextCue()) {
        if (cue.start > realTime) {
          break
        }

        if (cue.virtualEndTime > realTime) {
          cue.audio.seekTo(realTime - cue.start)
          cue.go()
          unWait = true
          break
        }

        realTime -= cue.getWaitInterval()
      }

      const andFinally = finalRealtime => {
        // check boundary conditions on duration of audio
        if (finalRealtime < 0) {
          this.narration.seekTo(0)
        } else if (realTime > this.narration.getDuration()) {
          this.ended()
        } else {
          this.narration.seekTo(realTime)
        }
      }

      if (unWait) {
        cue.getElapsedWait(elapsedWait => {
          if (elapsedWait > 0) {
            realTime = cue.waitTime
            this.wait()
          }
          andFinally(realTime)
        })
      } else {
        andFinally(realTime)
      }
    },

    seek: function ()
    {
      const cue = this.curCue()
      if (cue) {
        if (cue.active) {
          this.narration.getCurrentPosition(position => {
            if (position >= cue.waitTime) this.wait()
          })
        } else {
          this.narration.getCurrentPosition(position => {
            if (position >= cue.start) cue.go()
          })
        }
      }
    },

    nextCue: function ()
    {
      this.currentCueNumber++
      return this.curCue()
    },

    wait: function ()
    {
      if (!this.waitForCue) {
        this.waitForCue = true
        this.narration.pause()
      }
    },

    resume: function ()
    {
      if (this.waitForCue) {
        this.waitForCue = false
        if (this.playing) this.narration.play()
      }
    },

    // finished playing through
    ended: function ()
    {
      this.pause()
      const cue = this.activeCue()
      if (cue) cue.deactivate()
      this.waitForCue = false
      this.currentCueNumber = 0
      this.narration.seekTo(0)
      swipe.slide(2, 600) // go to credits page
    },

    // play and pause each audio asset to trigger preloading on iOS
    // (needs to be in an on-click event)
    preload: function ()
    {
      if (this.preloaded) {
        return
      }

      this.preloaded = true

      for (let cue of this.cues) {
        cue.preload()
      }
    },

    loaded: function (obj)
    {
      this.loadPool.delete(obj)
      if (!this.isWaiting()) {
        playButton.removeClass('loading')
        if (this.playing) {
          this.narration.play()
        }
      }
    },

    waitForLoad: function ()
    {
      this.narration.pause()
      playButton.addClass('loading')
    },

    isWaiting: function ()
    {
      return this.loadPool.size > 0
    },

    needToLoad: function (obj)
    {
      const wasWaiting = this.isWaiting()
      this.loadPool.add(obj)
      if (!wasWaiting) {
        this.waitForLoad()
      }
    }
  }

// based on Knuth Shuffle (https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array)
function shuffleArray(arr)
{
  let currentIndex = arr.length
  let randomIndex, temp

  // While there remain elements to shuffle...
  while (currentIndex !== 0) {
    // Pick a remaining element...
    randomIndex = Math.floor(Math.random() * currentIndex)
    currentIndex--
    // Swap with current
    temp = arr[randomIndex]
    arr[randomIndex] = arr[currentIndex]
    arr[currentIndex] = temp
  }
  return arr
}

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
