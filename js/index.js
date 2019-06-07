/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
var app = {
    // Application Constructor
    initialize: function() {
        document.addEventListener('deviceready', this.onDeviceReady.bind(this), false);
    },

    // deviceready Event Handler
    //
    // Bind any cordova events here. Common events are:
    // 'pause', 'resume', etc.
    onDeviceReady: function() {
        this.receivedEvent('deviceready');
    },

    // Update DOM on a Received Event
    receivedEvent: function(id) {
        const parentElement = document.getElementById(id);
        const listeningElement = parentElement.querySelector('.listening');
        const receivedElement = parentElement.querySelector('.received');

        listeningElement.setAttribute('style', 'display:none;');
        receivedElement.setAttribute('style', 'display:block;');

        console.log('Received Event: ' + id);

        // setup jQuery refs
        window.playButton = $('#playpause');

        const cuePoolTotal = 22 // total number of phrases in the pool
        window.cues = new Cues(cuePoolTotal)

        // DEFINE CUE POSITIONS HERE
        const playlist = [
            [263.8, 291.1], [359.4, 377.3], [432.0, 458.7], [533.9, 534.5], [639.3, 640], [717.5, 718], [845.9, 885.4]
        ]
        const startTime = 0
        const skipTime = 10 // seconds to skip forward/back using buttons
        window.player = new Player(startTime, playlist, skipTime);

        playButton.click( () => { player.playPause(); });
        $('#rew').click( () => { player.rew(); });
        $('#ffw').click( () => { player.ffw(); });

        $('.received').click(
          function nextAndLoad() {
              player.preload() // trigger preloading on iOS
              swipe.next()
          }
        )

        // setup swipe
        const swipeElement = $('#slider');
        window.swipe = new Swipe(swipeElement[0], {
            speed: 700, // of transition (ms)
            continuous: false, // ie. don't cycle back round
            disableScroll: false
        });
        swipe.setup();

        // report seek time
        window.setInterval( () => {
            console.log("Play position: " + player.narration.currentTime);
        }, 10000);
    }
};

app.initialize();
