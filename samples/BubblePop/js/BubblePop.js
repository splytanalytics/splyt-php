/**
 * Bubble Pop
 * A simple "shell" game written in with the help of KineticJS (http://kineticjs.com/) and 
 * http://www.html5canvastutorials.com/kineticjs/html5-canvas-events-tutorials-introduction-with-kineticjs/
 *
 * This game is not intended to show case HTML5 skills (or lack thereof :P) or APIs but only to provide
 * a simple client to talk to a Splyt-instrumented PHP backend.  All Splyt integration in this sample
 * occurs within the included PHP index file and webservice files.
 * 
 * This file assume that Kinetic JS and jQuery have been sourced before it.
 * 
 * Enjoy!
 */
var BubblePop = {
    /**
     * BubblePop.init Initialize the game and draws the HUD.
     */
    init : function(container, serviceRoot, width, height) {
        // save our initialization vars...
        BubblePop.mContainer = container;
        BubblePop.mWidth = width;
        BubblePop.mHeight = height;
        BubblePop.mServiceRoot = serviceRoot;

        // create the main KineticJS stage...
        BubblePop.mStage = new Kinetic.Stage({
            container : container,
            width : width,
            height : height
        });

        // create a layer to put everything on...
        BubblePop.mLayer = new Kinetic.Layer();

        BubblePop.mLayer.add(new Kinetic.Rect({
            x : 0,
            y : 0,
            width : width,
            height : height,
            fill : 'ffffff'
        }));

        function getParameterByName(name) {
            name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
        }
        
        BubblePop.mUserId = getParameterByName('username');
        BubblePop.mGender = getParameterByName('gender');
        
        BubblePop.callService(
            'init', 
            {gender:BubblePop.mGender}, 
            function(data) {
                if (data) {
                    if (data.hasOwnProperty('goldBalance')) 
                        BubblePop.mGoldBalance = data.goldBalance;
                    if (data.hasOwnProperty('gameCost'))
                        BubblePop.mGameCost = data.gameCost;
                    if (data.hasOwnProperty('bubbleColor'))
                        BubblePop.mBubbleColor = data.bubbleColor;
                }
    
                // draw the HUD...
                BubblePop.drawHUD();
    
                // add the layer to the stage...
                BubblePop.mStage.add(BubblePop.mLayer);
    
                // put us in the ready state...
                BubblePop.goToReadyState();
            }
        );
    },

    /**
     * BubblePop.drawHUD Draw the HUD for the bubble pop game.
     */
    drawHUD : function() {
        // build the HUD...
        var x = BubblePop.mWidth - BubblePop.HUD_AREA.width + 30;
        var y = 5;
        var pad = 5;

        var hudPlusAreaDef = $.extend({
            x : x + pad,
            y : y + pad
        }, BubblePop.HUD_ADD_AREA);
        var hudPlusTextDef = $.extend({
            x : x + pad,
            y : y + pad
        }, BubblePop.HUD_ADD_PLUS);
        var hudTextDef = $.extend({
            x : x + pad + hudPlusAreaDef.width + pad,
            y : y + pad,
            text : BubblePop.makeHUDText()
        }, BubblePop.HUD_TEXT);
        var incAmount = 25;

        var hudImage = new Image();
        hudImage.src = 'img/badge.png';

        hudImage.onload = function() {
            var hudBG = new Kinetic.Image({
                x : 490,
                y : -100,
                image : hudImage,
                width : 180,
                height : 180
            });

            // add the image to the layer
            BubblePop.mLayer.add(hudBG);

            BubblePop.mLayer.add(new Kinetic.Rect(hudPlusAreaDef).on(
                    'touchend mouseup', function() {
                        BubblePop.purchaseGold(incAmount);
                    }));
            BubblePop.mLayer.add(new Kinetic.Text(hudPlusTextDef).on(
                    'touchend mouseup', function() {
                        BubblePop.purchaseGold(incAmount);
                    }));
            BubblePop.mLayer.add(new Kinetic.Text(hudTextDef));

            BubblePop.mLayer.draw();
        };
    },

    /**
     * BubblePop.goToReadyState Set up the stage for the ready state of the
     * game. This includes drawing the START button.
     */
    goToReadyState : function() {
        var startScreenWidth = 498;
        var startScreenHeight = 178;
        var x = (BubblePop.mWidth * .5) - (startScreenWidth * .5);
        var y = (BubblePop.mHeight * .5) - (startScreenHeight * .5);
        var cost = BubblePop.getGameCost();
        var buttonTextDef = $.extend({
            x : x,
            y : y + 130,
            text : "$" + cost + " Gold"
        }, BubblePop.START_BUTTON_TEXT);

        var cleanUpStateAndTransition = function() {
            // okay purchase the game...
            BubblePop.purchaseNewGame(function()
            {
                // clean up and shapes in the ready state...
                var stateShapes = BubblePop.mStage.get('.ready');
                stateShapes.each(function(node) {
                    node.remove();
                });

                // start a new game...
                BubblePop.startNewGame();
            });
        };

        var imageObj = new Image();
        imageObj.src = 'img/start_game.png';

        imageObj.onload = function() {
            var startGameScreen = new Kinetic.Image({
                x : x,
                y : y,
                image : imageObj,
                width : startScreenWidth,
                height : startScreenHeight,
                name : 'ready'
            }).on('mouseup touchend', cleanUpStateAndTransition);

            // add the shape to the layer
            BubblePop.mLayer.add(startGameScreen);

            BubblePop.mLayer.add(new Kinetic.Text(buttonTextDef).on(
                    'mouseup touchend', cleanUpStateAndTransition));

            BubblePop.mLayer.draw();
        };
    },

    /**
     * BubblePop.startNewGame Set up the stage for a new game. Draw all the
     * bubble that need popping.
     */
    startNewGame : function() {
        BubblePop.callService('gameEvent', {event:'gameStarted'});

        var cleanUpStateAndTransition = function(index) {
            var stateShapes = BubblePop.mStage.get('.gameplay');
            stateShapes.each(function(node) {
                node.remove();
            });
            BubblePop.goToWinState(index);
        };

        // Track how many pops were required to win.
        BubblePop.mNumPops = 0;

        // create new (or, quash old) bubbles!...
        BubblePop.mBubbleDefs = Array(BubblePop.NUM_BUBBLES);
        for ( var i = 0; i < BubblePop.NUM_BUBBLES; ++i) {
        var width = BubblePop.mWidth;
        var height = BubblePop.mHeight;

        var x = (width / (BubblePop.NUM_BUBBLES + 1)) * (i + 1);
        var y = height / 2;

        BubblePop.mBubbleDefs[i] = $.extend({
            x : x,
            y : y,
            id : "b" + i,
            fill : BubblePop.mBubbleColor
        }, BubblePop.BUBBLE_PROTO);

        // "put" the star "in" one of the bubbles...
        BubblePop.mStarBubble = BubblePop.rand(BubblePop.NUM_BUBBLES);

        // bubbles will just be circles...hooray programmer art!...also go
        // ahead and and a click handler to the bubbles to "pop" them...
        BubblePop.mLayer.add(new Kinetic.Circle(BubblePop.mBubbleDefs[i]).on(
                'mouseup touchend', function() {

                    BubblePop.mNumPops++;

                    var index = this.getId().charAt(1);
                    if (index == BubblePop.mStarBubble) {
                    // you win!
                    cleanUpStateAndTransition(index);
                    } else {
                    this.remove();
                    }
                }));
        }

        // now create a single animation for the scene that we use to make the
        // bubbles kind of ~waft~ there...
        BubblePop.mWaftAnim = new Kinetic.Animation(function(frame) {
            var stateShapes = BubblePop.mStage.get('.gameplay');
            stateShapes.each(function(node) {
                if (node.getId().charAt(0) == 'b') {
                    var origY = BubblePop.mBubbleDefs[node.getId().charAt(1)].y;
                    var updateY = 5 * Math.sin(frame.time * 2 * Math.PI / 2500)
                            + origY;
                    node.setY(updateY);
                }
            });
        }, BubblePop.mLayer);
        BubblePop.mWaftAnim.start();

        // update the layer...
        BubblePop.mLayer.draw();
    },

    getGameScore : function() {
        // score is on a 0 to 1 scale, based on the number of pops.
        //
        // starts at 1, and decreases with each pop. If all bubbles are
        // popped before player wins, final score is 0.
        return (BubblePop.NUM_BUBBLES - BubblePop.mNumPops)
                / parseFloat(BubblePop.NUM_BUBBLES - 1);
    },

    /**
     * BubblePop.goToWinState Sets up the stage for the YOU WON! state. Draws
     * and animates the star.
     */
    goToWinState : function(index) {

        BubblePop.callService('gameEvent', {event:'gameFinished', numberOfPops:BubblePop.mNumPops, winQuality:BubblePop.getGameScore().toFixed(3)});

        // create the star in the position of the bubble that was popped to
        // win...
        var starDef = $.extend({
            x : BubblePop.mBubbleDefs[index].x,
            y : BubblePop.mBubbleDefs[index].y
        }, BubblePop.STAR_PROTO);
        var starShape = new Kinetic.Star(starDef);
        BubblePop.mLayer.add(starShape);

        // wait a half second, then transition the star to scale up, then
        // scale down, then transition the game.
        setTimeout(function() {
            starShape.transitionTo({
                // scale up slowly...
                scale : {
                    x : 1.6,
                    y : 1.6
                },
                x : BubblePop.mWidth / 2,
                y : BubblePop.mHeight / 2,
                duration : 0.75,
                callback : function() {
                    setTimeout(function() {
                        starShape.transitionTo({
                            // then down more quickly...
                            scale : {
                                x : 0,
                                y : 0
                            },
                            duration : 0.2,
                            callback : function() {
                                // and restart the game...
                                starShape.remove();
                                BubblePop.goToReadyState();
                            }
                        });
                    }, 500);
                }
            });
        }, 500);

        // update the layer...
        BubblePop.mLayer.draw();
    },

    /**
     * BubblePop.purchaseGold Function to simulate purchasing gold. In this sample, the
     * heavy lifting for the purchase, and the Splyt instrumentation, is completed in purchase.php
     * on the application server.
     */
    purchaseGold : function(amount) {
        BubblePop.callService(
            'purchase',
            {offerid:'standard-gold', pointOfSale:'hud-plus'},
            function(data)
            {
                if(data && undefined !== data.goldBalance)
                {
                    // update balance and animate the HUD...
                    BubblePop.mGoldBalance = data.goldBalance;
                    BubblePop.updateHUD(false);
                }
            }
        );
    },

    /**
     * BubblePop.purchaseNewGame Wrapper for invoking the purchase of a game session, using
     * a call to purchase.php on the application server.
     */
    purchaseNewGame : function(callback) {
        BubblePop.callService(
            'purchase',
            {offerid:'standard-game', pointOfSale:'start-button'},
            function(data)
            {
                if(data)
                {
                    if(undefined !== data.goldBalance)
                    {
                        // update balance and animate the HUD...
                        BubblePop.mGoldBalance = data.goldBalance;
                        BubblePop.updateHUD(false);
                    }
                    
                    if('ok' === data.status)
                    {
                        callback();
                    }
                }
            }
        );
    },

    /**
     * BubblePop.getGameCost Returns the cost of each game of Bubble Pop in gold. Note
     * that this value is initially retrieved during the service call to init.php on 
     * startup which utilizes a Splyt Tuned Variable.
     */
    getGameCost : function() {
        return BubblePop.mGameCost;
    },

    /**
     * BubblePop.makeHUDText
     * Uses state information to create the text that should be rendered on the HUD
     */
    makeHUDText : function() {
        return "Gold: " + BubblePop.mGoldBalance;
    },

    /**
     * BubblePop.updateHUD
     * Update the HUD.  Can animate or not.
     */
    updateHUD : function(animate) {
        var text = BubblePop.mStage.get('#hud-text')[0];

        if (animate) {
        text.transitionTo({
            scale : {
                x : 1.1,
                y : 1.1
            },
            duration : 0.2,
            callback : function() {
                setTimeout(function() {
                    text.setText(BubblePop.makeHUDText());
                    BubblePop.mLayer.draw();
                    setTimeout(function() {
                        text.transitionTo({
                            scale : {
                                x : 1.0,
                                y : 1.0
                            },
                            duration : 0.2,
                        });
                    }, 500);
                }, 500);
            }
        });
        } else {
        text.setText(BubblePop.makeHUDText());
        BubblePop.mLayer.draw();
        }
    },

    /**
     * BubblePop.rand
     * Simple random number wrapper for use by Bubble Pop.
     * This function lifted from Jeff Friesen's Sea Battle HTML5 demo which can be found:
     * http://www.sitepoint.com/gaming-battle-on-the-high-seas-part-1/
     */
    rand : function(limit) {
        return (Math.random() * limit) | 0;
    },

    NUM_BUBBLES : 4,
    BUBBLE_PROTO : {
        radius : 20,
        stroke : 'grey',
        strokeWidth : 0.5,
        name : 'gameplay'
    },
    START_BUTTON : {
        width : 498,
        height : 178,
        fill : 'none',
        stroke : 'grey',
        strokeWidth : 0.5,
        name : 'ready',
        id : 'start-button'
    },
    START_BUTTON_TEXT : {
        fontSize : 30,
        fontFamily : 'Ostrich Sans Black',
        fill : 'bd6565',
        width : 498,
        height : 178,
        align : 'center',
        name : 'ready',
        id : 'start-text'
    },
    STAR_PROTO : {
        numPoints : 5,
        innerRadius : 20,
        outerRadius : 30,
        fill : 'ffec6e',
        stroke : 'grey',
        strokeWidth : 0.5,
        name : 'gameover',
        id : 'star'
    },
    HUD_AREA : {
        width : 150,
        height : 75,
        fill : 'black',
        stroke : 'cyan',
        strokeWidth : 2,
        name : 'hud',
        id : 'hud-area'
    },
    HUD_TEXT : {
        fontSize : 25,
        fontFamily : 'Ostrich Sans Black',
        fill : 'ebebeb',
        width : 100,
        height : 50,
        name : 'hud',
        id : 'hud-text'
    },
    HUD_ADD_AREA : {
        width : 20,
        height : 20,
        fill : 'ebebeb',
        name : 'hud',
        id : 'hud-add-area',
    },
    HUD_ADD_PLUS : {
        text : "+",
        fontSize : 25,
        fontFamily : 'Ostrich Sans Black',
        align : "center",
        fill : '3a69a4',
        width : 20,
        height : 20,
        name : 'hud',
        id : 'hud-add-plus'
    },

    /**
     * Wrapper for calls to the application server for the Bubble Pop game.
     * 
     * @param service
     * @param params
     * @param callback
     */
    callService : function(service, params, callback) {
        params = params || {};
        
        // let's send down the user id with everything
        params.userid = BubblePop.mUserId;
        
        $.ajax({
            url : BubblePop.mServiceRoot + 'php/' + service + '.php',
            dataType : 'json',
            data : params
        }).done(function(data) {
            callback && callback(data);
        }).fail(function() {
            callback && callback();
        });
    }
};